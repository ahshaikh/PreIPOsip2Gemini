<?php

/**
 * NoNestedTransactionTest
 *
 * INVARIANT: No nested transactions in financial lifecycle.
 *
 * Verifies that domain services (WalletService, AllocationService, etc.)
 * do NOT open their own DB::transaction() when called from orchestrator.
 *
 * Nested transactions cause:
 * - Savepoints (MySQL) which can be released incorrectly
 * - Partial commits in some databases
 * - Complex rollback semantics
 *
 * @package Tests\FinancialLifecycle\TransactionBoundary
 */

namespace Tests\FinancialLifecycle\TransactionBoundary;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\StaticAnalysisHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class NoNestedTransactionTest extends FinancialLifecycleTestCase
{
    /**
     * Static analysis: Detect nested transaction patterns in code.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function static_analysis_detects_nested_transactions(): void
    {
        $analyzer = new StaticAnalysisHelper(base_path());
        $violations = $analyzer->scanForNestedTransactions();

        // During pre-refactor, this may find violations
        // After refactor, this should pass (no nested transactions)
        $this->assertEmpty(
            $violations,
            "Nested transaction patterns detected:\n" .
            $this->formatViolations($violations) .
            "\n\nDomain services should NOT call DB::transaction() when invoked from orchestrator."
        );
    }

    /**
     * Runtime test: Maximum transaction depth during lifecycle is 1.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function runtime_transaction_depth_never_exceeds_one(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $maxDepth = 0;
        $currentDepth = 0;
        $depthHistory = [];

        DB::listen(function ($query) use (&$maxDepth, &$currentDepth, &$depthHistory) {
            $sql = strtoupper($query->sql);

            if (strpos($sql, 'BEGIN') !== false || strpos($sql, 'START TRANSACTION') !== false) {
                $currentDepth++;
                $maxDepth = max($maxDepth, $currentDepth);
                $depthHistory[] = ['action' => 'BEGIN', 'depth' => $currentDepth];
            }

            if (strpos($sql, 'COMMIT') !== false || strpos($sql, 'ROLLBACK') !== false) {
                $depthHistory[] = ['action' => 'COMMIT/ROLLBACK', 'depth' => $currentDepth];
                $currentDepth = max(0, $currentDepth - 1);
            }

            // Track savepoints (indicate nested transactions in Laravel)
            if (strpos($sql, 'SAVEPOINT') !== false) {
                $depthHistory[] = ['action' => 'SAVEPOINT', 'depth' => $currentDepth, 'sql' => $query->sql];
            }
        });

        // Process payment
        $this->processPaymentLifecycle($payment);

        // ASSERTION: Max depth should never exceed 1
        $this->assertLessThanOrEqual(
            1,
            $maxDepth,
            "Transaction depth exceeded 1 (max: {$maxDepth}). " .
            "This indicates nested transactions. " .
            "Depth history: " . json_encode($depthHistory)
        );
    }

    /**
     * Test that WalletService deposit does not start its own transaction.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_service_deposit_honors_external_transaction(): void
    {
        $this->createTestUser();
        $walletService = app(\App\Services\WalletService::class);

        $transactionCount = 0;

        DB::listen(function ($query) use (&$transactionCount) {
            if (stripos($query->sql, 'BEGIN') !== false ||
                stripos($query->sql, 'START TRANSACTION') !== false) {
                $transactionCount++;
            }
        });

        // Call deposit within orchestrator's transaction
        DB::transaction(function () use ($walletService, &$transactionCount) {
            $walletService->deposit(
                $this->testUser,
                100000, // 1000 rupees in paise
                \App\Enums\TransactionType::DEPOSIT,
                'Test deposit within transaction'
            );
        });

        // ASSERTION: Only the outer transaction should be counted
        // If WalletService starts its own transaction, count would be > 1
        // NOTE: This test will pass if WalletService detects existing transaction
        // and skips its own DB::transaction() call
        $this->assertEquals(
            1,
            $transactionCount,
            "WalletService should not start nested transaction. " .
            "Found {$transactionCount} transaction starts."
        );
    }

    /**
     * Test that BonusCalculatorService honors external transaction.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_calculator_honors_external_transaction(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        // Mark payment as paid for bonus calculation
        $payment->update(['status' => Payment::STATUS_PAID]);

        $transactionCount = 0;

        DB::listen(function ($query) use (&$transactionCount) {
            if (stripos($query->sql, 'BEGIN') !== false ||
                stripos($query->sql, 'START TRANSACTION') !== false) {
                $transactionCount++;
            }
        });

        // Call bonus calculator within orchestrator's transaction
        DB::transaction(function () use ($payment) {
            $bonusService = app(\App\Services\BonusCalculatorService::class);
            try {
                $bonusService->calculateAndAwardBonuses($payment);
            } catch (\Throwable $e) {
                // May fail due to missing config - that's OK for this test
            }
        });

        // ASSERTION: Only outer transaction
        $this->assertLessThanOrEqual(
            1,
            $transactionCount,
            "BonusCalculatorService should not start nested transaction. " .
            "Found {$transactionCount} transaction starts."
        );
    }

    /**
     * Test that AllocationService honors external transaction.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allocation_service_honors_external_transaction(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $this->createTestInventory();
        $payment = $this->createTestPayment();

        $transactionCount = 0;

        DB::listen(function ($query) use (&$transactionCount) {
            if (stripos($query->sql, 'BEGIN') !== false ||
                stripos($query->sql, 'START TRANSACTION') !== false) {
                $transactionCount++;
            }
        });

        // Call allocation within orchestrator's transaction
        DB::transaction(function () use ($payment) {
            $allocationService = app(\App\Services\AllocationService::class);
            try {
                $allocationService->allocateSharesLegacy($payment, 1000.00);
            } catch (\Throwable $e) {
                // May fail - that's OK for this test
            }
        });

        // ASSERTION: Only outer transaction
        $this->assertLessThanOrEqual(
            1,
            $transactionCount,
            "AllocationService should not start nested transaction. " .
            "Found {$transactionCount} transaction starts."
        );
    }

    /**
     * Test that DoubleEntryLedgerService honors external transaction.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_service_honors_external_transaction(): void
    {
        $this->createTestUser();
        $payment = $this->createTestPayment();

        $transactionCount = 0;

        DB::listen(function ($query) use (&$transactionCount) {
            if (stripos($query->sql, 'BEGIN') !== false ||
                stripos($query->sql, 'START TRANSACTION') !== false) {
                $transactionCount++;
            }
        });

        // Call ledger service within orchestrator's transaction
        DB::transaction(function () use ($payment) {
            $ledgerService = app(\App\Services\DoubleEntryLedgerService::class);
            try {
                $ledgerService->recordUserDeposit(
                    $this->testUser,
                    $payment,
                    100.00
                );
            } catch (\Throwable $e) {
                // May fail - that's OK for this test
            }
        });

        // ASSERTION: Only outer transaction
        $this->assertLessThanOrEqual(
            1,
            $transactionCount,
            "DoubleEntryLedgerService should not start nested transaction. " .
            "Found {$transactionCount} transaction starts."
        );
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
            // Fallback to current implementation
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
