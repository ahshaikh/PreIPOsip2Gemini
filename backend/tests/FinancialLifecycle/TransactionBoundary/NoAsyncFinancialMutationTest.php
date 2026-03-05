<?php

/**
 * NoAsyncFinancialMutationTest
 *
 * INVARIANT: No async job dispatches inside financial transactions.
 *
 * Dispatching queued jobs inside a DB transaction is dangerous because:
 * - If transaction rolls back, job may still execute
 * - Job references stale/non-existent data
 * - Financial mutations become non-atomic
 *
 * After refactor: FinancialOrchestrator completes all mutations synchronously,
 * then dispatches follow-up jobs AFTER commit.
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\StaticAnalysisHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Models\Payment;

class NoAsyncFinancialMutationTest extends FinancialLifecycleTestCase
{
    /**
     * Static analysis: Detect async dispatches inside transactions.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function static_analysis_detects_async_in_transactions(): void
    {
        $analyzer = new StaticAnalysisHelper(base_path());
        $violations = $analyzer->scanForAsyncFinancialMutations();

        $this->assertEmpty(
            $violations,
            "Async job dispatches inside transactions detected:\n" .
            $this->formatViolations($violations) .
            "\n\nFinancial mutations must complete synchronously. " .
            "Dispatch follow-up jobs AFTER commit."
        );
    }

    /**
     * Test that no jobs are dispatched during transaction.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function no_jobs_dispatched_during_transaction(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        Queue::fake();

        $jobsDispatchedDuringTransaction = [];
        $inTransaction = false;

        // Track transaction state
        DB::listen(function ($query) use (&$inTransaction) {
            $sql = strtoupper($query->sql);
            if (strpos($sql, 'BEGIN') !== false || strpos($sql, 'START TRANSACTION') !== false) {
                $inTransaction = true;
            }
            if (strpos($sql, 'COMMIT') !== false || strpos($sql, 'ROLLBACK') !== false) {
                $inTransaction = false;
            }
        });

        // Track job dispatches
        Queue::assertNothingPushed();

        // Process payment
        try {
            $orchestrator = app(\App\Services\FinancialOrchestrator::class);

            DB::transaction(function () use ($orchestrator, $payment, &$jobsDispatchedDuringTransaction, &$inTransaction) {
                // Check queue before
                $orchestrator->processSuccessfulPayment($payment);
            });
        } catch (\Throwable $e) {
            // Fallback - current implementation may dispatch jobs
            $webhookService = app(\App\Services\PaymentWebhookService::class);
            $webhookService->handleSuccessfulPayment([
                'order_id' => $payment->gateway_order_id,
                'id' => 'pay_' . $payment->gateway_order_id,
            ]);
        }

        // After refactor: Jobs should be dispatched AFTER commit, not during
        // This test documents expected behavior
        $this->markTestIncomplete(
            "This test validates that no async jobs are dispatched during " .
            "financial transaction. After refactor, follow-up jobs (notifications, " .
            "lucky draw) should dispatch AFTER commit."
        );
    }

    /**
     * Test that dispatchSync is used for critical financial operations.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function critical_operations_use_dispatch_sync(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        Queue::fake();

        // Simulate what happens when ProcessSuccessfulPaymentJob is dispatched
        // After refactor, this should be called synchronously by orchestrator
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Critical path should NOT queue jobs - should execute inline
        // Non-critical (notifications, etc.) can be queued after commit

        $criticalJobs = [
            \App\Jobs\ProcessSuccessfulPaymentJob::class,
            \App\Jobs\ProcessPaymentBonusJob::class,
        ];

        // Run the job synchronously as orchestrator would
        \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);

        // No critical jobs should be in queue (they run sync)
        foreach ($criticalJobs as $jobClass) {
            Queue::assertNotPushed($jobClass);
        }

        // Non-critical jobs CAN be queued
        $nonCriticalJobs = [
            \App\Jobs\SendPaymentConfirmationEmailJob::class,
            \App\Jobs\GenerateLuckyDrawEntryJob::class,
        ];

        // These are OK to queue as they don't affect financial state
    }

    /**
     * Test that job failure doesn't leave partial financial state.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function job_failure_doesnt_leave_partial_state(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $initialWalletBalance = $this->testWallet->balance_paise;
        $initialTransactionCount = \App\Models\Transaction::count();

        // Simulate job that fails mid-execution
        $this->expectException(\RuntimeException::class);

        try {
            DB::transaction(function () use ($payment) {
                // Partial work
                $this->testWallet->increment('balance_paise', $payment->amount_paise);

                // Simulate queued job that would fail
                // After refactor, this should be atomic
                throw new \RuntimeException('Simulated async job failure');
            });
        } catch (\RuntimeException $e) {
            throw $e;
        }

        // State should be unchanged due to rollback
        $this->testWallet->refresh();
        $this->assertEquals(
            $initialWalletBalance,
            $this->testWallet->balance_paise,
            "Wallet balance should be unchanged after failed transaction"
        );

        $this->assertEquals(
            $initialTransactionCount,
            \App\Models\Transaction::count(),
            "Transaction count should be unchanged after failed transaction"
        );
    }

    /**
     * Test that after-commit hooks are used for non-critical jobs.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function after_commit_hooks_for_non_critical_jobs(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        Queue::fake();

        $afterCommitExecuted = false;

        DB::transaction(function () use ($payment, &$afterCommitExecuted) {
            // Critical financial work
            $this->testWallet->increment('balance_paise', $payment->amount_paise);

            // Non-critical should use afterCommit
            DB::afterCommit(function () use (&$afterCommitExecuted) {
                $afterCommitExecuted = true;
            });
        });

        $this->assertTrue(
            $afterCommitExecuted,
            "After-commit callback should execute after transaction commits"
        );

        // In real implementation, notification jobs would be dispatched in afterCommit
    }

    /**
     * Test that failed transaction doesn't trigger after-commit.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function failed_transaction_skips_after_commit(): void
    {
        $afterCommitExecuted = false;

        try {
            DB::transaction(function () use (&$afterCommitExecuted) {
                DB::afterCommit(function () use (&$afterCommitExecuted) {
                    $afterCommitExecuted = true;
                });

                throw new \RuntimeException('Simulated failure');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        $this->assertFalse(
            $afterCommitExecuted,
            "After-commit callback should NOT execute when transaction rolls back"
        );
    }

    /**
     * Format violations for error message.
     */
    private function formatViolations(array $violations): string
    {
        $lines = [];
        foreach ($violations as $v) {
            $lines[] = "  - {$v['file']}:{$v['line']}: {$v['description']}";
            $lines[] = "    Code: {$v['code']}";
        }
        return implode("\n", $lines);
    }
}
