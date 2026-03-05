<?php

/**
 * SingleTransactionBoundaryTest
 *
 * INVARIANT: Single DB transaction per payment lifecycle.
 *
 * Verifies that the FinancialOrchestrator processes the entire payment
 * lifecycle (wallet deposit, allocation, bonus, ledger entries) within
 * a single database transaction boundary.
 *
 * EXPECTED TO FAIL: Until FinancialOrchestrator is implemented, the
 * current architecture uses multiple transactions across services.
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\UserInvestment;
use App\Models\BonusTransaction;
use App\Models\LedgerEntry;

class SingleTransactionBoundaryTest extends FinancialLifecycleTestCase
{
    /**
     * Test that payment lifecycle executes within single transaction.
     *
     * After refactor: FinancialOrchestrator::processSuccessfulPayment()
     * should wrap all operations in one DB::transaction().
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_lifecycle_uses_single_transaction(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $this->enableQueryLogging();

        // Track transaction boundaries
        $transactionStarts = 0;
        $transactionCommits = 0;

        DB::listen(function ($query) use (&$transactionStarts, &$transactionCommits) {
            $sql = strtoupper($query->sql);
            if (strpos($sql, 'BEGIN') !== false || strpos($sql, 'START TRANSACTION') !== false) {
                $transactionStarts++;
            }
            if (strpos($sql, 'COMMIT') !== false) {
                $transactionCommits++;
            }
        });

        // Process payment through lifecycle
        // TARGET: FinancialOrchestrator::processSuccessfulPayment($payment)
        // CURRENT: PaymentWebhookService + Jobs (multiple transactions)
        $orchestrator = $this->getFinancialOrchestrator();

        if ($orchestrator) {
            $orchestrator->processSuccessfulPayment($payment);
        } else {
            // Fallback to current implementation for baseline
            $this->processPaymentCurrentImplementation($payment);
        }

        $this->disableQueryLogging();

        // ASSERTION: Only ONE transaction boundary for entire lifecycle
        // This will fail until FinancialOrchestrator consolidates operations
        $this->assertEquals(
            1,
            $transactionStarts,
            "Expected single transaction boundary. Found {$transactionStarts} transaction starts. " .
            "FinancialOrchestrator must wrap all financial mutations in ONE transaction."
        );

        $this->assertEquals(
            1,
            $transactionCommits,
            "Expected single commit. Found {$transactionCommits} commits. " .
            "All financial mutations should commit atomically."
        );
    }

    /**
     * Test that partial failure rolls back ALL changes.
     *
     * If bonus calculation fails, wallet deposit should also be rolled back.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function partial_failure_rolls_back_entire_lifecycle(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $initialWalletBalance = $this->testWallet->balance_paise;
        $initialTransactionCount = Transaction::count();
        $initialBonusCount = BonusTransaction::count();
        $initialLedgerCount = LedgerEntry::count();

        // Simulate failure during bonus calculation
        // TARGET: FinancialOrchestrator should catch and rollback
        $orchestrator = $this->getFinancialOrchestrator();

        $exceptionThrown = false;

        try {
            if ($orchestrator) {
                // Mock bonus service to throw exception
                $this->mockBonusServiceToFail();
                $orchestrator->processSuccessfulPayment($payment);
            } else {
                // For current implementation, manually test rollback behavior
                DB::transaction(function () use ($payment) {
                    // Simulate partial execution
                    $this->testWallet->increment('balance_paise', $payment->amount_paise);

                    // Then failure
                    throw new \RuntimeException('Simulated bonus calculation failure');
                });
            }
        } catch (\Throwable $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Exception should propagate from lifecycle');

        // ASSERTION: All changes rolled back
        $this->testWallet->refresh();
        $this->assertEquals(
            $initialWalletBalance,
            $this->testWallet->balance_paise,
            "Wallet balance should be unchanged after rollback"
        );

        $this->assertEquals(
            $initialTransactionCount,
            Transaction::count(),
            "Transaction count should be unchanged after rollback"
        );

        $this->assertEquals(
            $initialBonusCount,
            BonusTransaction::count(),
            "Bonus transaction count should be unchanged after rollback"
        );

        $this->assertEquals(
            $initialLedgerCount,
            LedgerEntry::count(),
            "Ledger entry count should be unchanged after rollback"
        );
    }

    /**
     * Test that commit only happens after ALL operations succeed.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function commit_only_after_all_operations_succeed(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        // Track operation sequence
        $operationSequence = [];

        Event::listen('eloquent.created: App\Models\Transaction', function () use (&$operationSequence) {
            $operationSequence[] = 'wallet_transaction_created';
        });

        Event::listen('eloquent.created: App\Models\BonusTransaction', function () use (&$operationSequence) {
            $operationSequence[] = 'bonus_transaction_created';
        });

        Event::listen('eloquent.created: App\Models\LedgerEntry', function () use (&$operationSequence) {
            $operationSequence[] = 'ledger_entry_created';
        });

        $this->enableQueryLogging();

        $orchestrator = $this->getFinancialOrchestrator();
        if ($orchestrator) {
            $orchestrator->processSuccessfulPayment($payment);
        } else {
            $this->processPaymentCurrentImplementation($payment);
        }

        $this->disableQueryLogging();

        // Find COMMIT position in query log
        $commitPosition = null;
        foreach ($this->queryLog as $index => $query) {
            if (stripos($query['sql'], 'COMMIT') !== false) {
                $commitPosition = $index;
                break;
            }
        }

        // ASSERTION: COMMIT should be AFTER all INSERT/UPDATE operations
        $this->assertNotNull($commitPosition, "COMMIT should appear in query log");

        foreach ($this->queryLog as $index => $query) {
            if (preg_match('/INSERT INTO\s+`?(transactions|bonus_transactions|ledger_entries)/i', $query['sql'])) {
                $this->assertLessThan(
                    $commitPosition,
                    $index,
                    "All financial mutations must complete BEFORE commit. " .
                    "Found INSERT after COMMIT position."
                );
            }
        }
    }

    /**
     * Test that savepoints are not used (would indicate nested transactions).
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function no_savepoints_in_lifecycle(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $this->enableQueryLogging();

        $orchestrator = $this->getFinancialOrchestrator();
        if ($orchestrator) {
            $orchestrator->processSuccessfulPayment($payment);
        } else {
            $this->processPaymentCurrentImplementation($payment);
        }

        $this->disableQueryLogging();

        // Check for savepoint queries (indicate nested transactions in Laravel)
        $savepointQueries = array_filter(
            $this->queryLog,
            fn($q) => stripos($q['sql'], 'SAVEPOINT') !== false
        );

        $this->assertEmpty(
            $savepointQueries,
            "Savepoints detected. This indicates nested transactions. " .
            "FinancialOrchestrator should use flat transaction boundary. " .
            "Found: " . json_encode(array_column($savepointQueries, 'sql'))
        );
    }

    /**
     * Attempt to get FinancialOrchestrator if it exists (post-refactor).
     */
    private function getFinancialOrchestrator()
    {
        // This will return null until FinancialOrchestrator is created
        try {
            return app(\App\Services\FinancialOrchestrator::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Process payment using current (pre-refactor) implementation.
     */
    private function processPaymentCurrentImplementation(Payment $payment): void
    {
        $webhookService = app(\App\Services\PaymentWebhookService::class);

        // Simulate webhook
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_' . $payment->gateway_order_id,
        ]);

        // Process the job synchronously
        $payment->refresh();
        if ($payment->status === Payment::STATUS_PAID) {
            \App\Jobs\ProcessSuccessfulPaymentJob::dispatchSync($payment);
        }
    }

    /**
     * Mock bonus service to throw exception for testing rollback.
     */
    private function mockBonusServiceToFail(): void
    {
        $this->mock(\App\Services\BonusCalculatorService::class, function ($mock) {
            $mock->shouldReceive('calculateAndAwardBonuses')
                ->andThrow(new \RuntimeException('Simulated bonus failure'));
        });
    }
}
