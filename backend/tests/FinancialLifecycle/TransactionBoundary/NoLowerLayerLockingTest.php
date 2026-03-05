<?php

/**
 * NoLowerLayerLockingTest
 *
 * INVARIANT: Only FinancialOrchestrator may acquire row locks.
 *
 * Verifies that domain services (WalletService, AllocationService, etc.)
 * do NOT call lockForUpdate() or sharedLock(). All lock acquisition
 * must be centralized in the orchestration layer.
 *
 * Centralized locking ensures:
 * - Consistent lock order (prevents deadlocks)
 * - Clear transaction boundaries
 * - Predictable behavior under concurrency
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\StaticAnalysisHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Wallet;

class NoLowerLayerLockingTest extends FinancialLifecycleTestCase
{
    /**
     * Static analysis: Detect lockForUpdate outside orchestrator.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function static_analysis_detects_locking_outside_orchestrator(): void
    {
        $analyzer = new StaticAnalysisHelper(base_path());
        $violations = $analyzer->scanForLockingOutsideOrchestrator();

        // This test is expected to FAIL until refactor moves all locks to orchestrator
        $this->assertEmpty(
            $violations,
            "Row locking detected outside FinancialOrchestrator:\n" .
            $this->formatViolations($violations) .
            "\n\nOnly FinancialOrchestrator should acquire row locks."
        );
    }

    /**
     * Runtime test: Track which services acquire locks.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function runtime_only_orchestrator_acquires_locks(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $lockingQueries = [];

        DB::listen(function ($query) use (&$lockingQueries) {
            if (stripos($query->sql, 'FOR UPDATE') !== false ||
                stripos($query->sql, 'LOCK IN SHARE MODE') !== false) {

                // Capture backtrace to identify caller
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
                $caller = $this->identifyLockCaller($backtrace);

                $lockingQueries[] = [
                    'sql' => $query->sql,
                    'caller' => $caller,
                    'table' => $this->extractTableFromLockQuery($query->sql),
                ];
            }
        });

        // Process payment
        $this->processPaymentLifecycle($payment);

        // Analyze lock callers
        $nonOrchestratorLocks = array_filter(
            $lockingQueries,
            fn($lock) => $lock['caller'] !== 'FinancialOrchestrator' &&
                         $lock['caller'] !== 'Unknown'
        );

        // ASSERTION: All locks should come from FinancialOrchestrator
        $this->assertEmpty(
            $nonOrchestratorLocks,
            "Locks acquired outside orchestrator:\n" .
            json_encode($nonOrchestratorLocks, JSON_PRETTY_PRINT) .
            "\n\nDomain services must NOT acquire their own locks."
        );
    }

    /**
     * Test that WalletService does not lock directly.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_service_does_not_lock_directly(): void
    {
        $this->createTestUser();

        $lockCount = 0;

        DB::listen(function ($query) use (&$lockCount) {
            if (stripos($query->sql, 'FOR UPDATE') !== false &&
                stripos($query->sql, 'wallets') !== false) {
                $lockCount++;
            }
        });

        // Call wallet service method that currently locks
        // After refactor, this should NOT produce locks
        $walletService = app(\App\Services\WalletService::class);

        // When called standalone (not from orchestrator), service may lock
        // When called from orchestrator, it should NOT lock
        DB::transaction(function () use ($walletService, &$lockCount) {
            // Simulate orchestrator already holding lock
            Wallet::where('id', $this->testWallet->id)->lockForUpdate()->first();

            // Now wallet service should detect existing lock and not re-lock
            // This is the expected behavior after refactor
            $walletService->deposit(
                $this->testUser,
                100000,
                \App\Enums\TransactionType::DEPOSIT,
                'Test deposit'
            );
        });

        // After refactor: WalletService should trust orchestrator's lock
        // During transition: This may still show multiple locks
        // The test documents expected behavior for refactor target
        $this->markTestIncomplete(
            "This test validates refactor target: WalletService should not lock when " .
            "called from orchestrator context. Current lock count: {$lockCount}"
        );
    }

    /**
     * Test that AllocationService does not lock directly.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allocation_service_does_not_lock_directly(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $lockQueries = [];

        DB::listen(function ($query) use (&$lockQueries) {
            if (stripos($query->sql, 'FOR UPDATE') !== false) {
                $lockQueries[] = [
                    'sql' => $query->sql,
                    'table' => $this->extractTableFromLockQuery($query->sql),
                ];
            }
        });

        // Call allocation service within orchestrator context
        DB::transaction(function () use ($payment) {
            $allocationService = app(\App\Services\AllocationService::class);
            try {
                $allocationService->allocateSharesLegacy($payment, 1000.00);
            } catch (\Throwable $e) {
                // May fail - that's OK
            }
        });

        // After refactor: AllocationService should not acquire any locks
        // Orchestrator passes pre-locked inventory
        $this->markTestIncomplete(
            "This test validates refactor target: AllocationService should receive " .
            "pre-locked records from orchestrator. Current locks: " .
            json_encode(array_column($lockQueries, 'table'))
        );
    }

    /**
     * Test that lock acquisition follows strict order.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function lock_acquisition_follows_strict_order(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $this->enableQueryLogging();

        // Process payment
        $this->processPaymentLifecycle($payment);

        $this->disableQueryLogging();

        // Verify lock order
        $this->assertLockOrderCorrect();
    }

    /**
     * Identify the service/class that acquired a lock from backtrace.
     */
    private function identifyLockCaller(array $backtrace): string
    {
        foreach ($backtrace as $frame) {
            if (!isset($frame['class'])) {
                continue;
            }

            $class = $frame['class'];

            if (strpos($class, 'FinancialOrchestrator') !== false) {
                return 'FinancialOrchestrator';
            }

            if (strpos($class, 'WalletService') !== false) {
                return 'WalletService';
            }

            if (strpos($class, 'AllocationService') !== false) {
                return 'AllocationService';
            }

            if (strpos($class, 'BonusCalculatorService') !== false) {
                return 'BonusCalculatorService';
            }

            if (strpos($class, 'PaymentWebhookService') !== false) {
                return 'PaymentWebhookService';
            }

            if (strpos($class, 'DoubleEntryLedgerService') !== false) {
                return 'DoubleEntryLedgerService';
            }
        }

        return 'Unknown';
    }

    /**
     * Extract table name from lock query.
     */
    private function extractTableFromLockQuery(string $sql): string
    {
        if (preg_match('/FROM\s+[`"]?(\w+)[`"]?.*FOR UPDATE/i', $sql, $matches)) {
            return $matches[1];
        }
        return 'unknown';
    }

    /**
     * Process payment through lifecycle.
     */
    private function processPaymentLifecycle(Payment $payment): void
    {
        try {
            $orchestrator = app(\App\Services\FinancialOrchestrator::class);
            $orchestrator->processSuccessfulPayment($payment);
        } catch (\Throwable $e) {
            $webhookService = app(\App\Services\PaymentWebhookService::class);
            $webhookService->handleSuccessfulPayment([
                'order_id' => $payment->gateway_order_id,
                'id' => 'pay_' . $payment->gateway_order_id,
            ]);

            $payment->refresh();
            if ($payment->status === Payment::STATUS_PAID) {
                \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);
            }
        }
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
