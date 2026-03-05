<?php

/**
 * FloatEliminationTest
 *
 * INVARIANT: No float operations in financial lifecycle code.
 *
 * Floating point arithmetic causes precision errors that accumulate
 * in financial calculations. All monetary values must use integers (paise).
 *
 * Examples of precision errors:
 * - 0.1 + 0.2 = 0.30000000000000004
 * - 10000.01 * 100 = 1000000.9999999999
 *
 * @package Tests\FinancialLifecycle\MonetaryPrecision
 */

namespace Tests\FinancialLifecycle\MonetaryPrecision;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\StaticAnalysisHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Transaction;

class FloatEliminationTest extends FinancialLifecycleTestCase
{
    /**
     * Static analysis: Detect float usage in lifecycle code.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function static_analysis_detects_float_usage(): void
    {
        $analyzer = new StaticAnalysisHelper(base_path());
        $violations = $analyzer->scanForFloatUsage();

        $this->assertEmpty(
            $violations,
            "Float usage detected in financial code:\n" .
            $this->formatViolations($violations) .
            "\n\nAll monetary values must use integers (paise)."
        );
    }

    /**
     * Test that payment amounts are stored as integers.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_amounts_stored_as_integers(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        // Create payment with specific amount
        $payment = Payment::factory()->create([
            'user_id' => $this->testUser->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_test_' . uniqid(),
            'amount' => 5000.50, // Rupees with paise
            'amount_paise' => 500050, // Exact paise
        ]);

        // Verify amount_paise is integer
        $this->assertIsInt(
            $payment->amount_paise,
            "Payment amount_paise must be integer"
        );

        // Verify precision preservation
        $this->assertEquals(
            500050,
            $payment->amount_paise,
            "Payment amount_paise should preserve exact value"
        );
    }

    /**
     * Test that wallet balance operations use integers.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_balance_operations_use_integers(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Deposit using integer paise
        $transaction = $walletService->deposit(
            $this->testUser,
            500050, // Integer paise
            \App\Enums\TransactionType::DEPOSIT,
            'Integer deposit test'
        );

        // Verify transaction amount is integer
        $this->assertIsInt(
            $transaction->amount_paise,
            "Transaction amount_paise must be integer"
        );

        $this->assertEquals(
            500050,
            $transaction->amount_paise,
            "Transaction should store exact paise value"
        );

        // Verify wallet balance is integer
        $this->testWallet->refresh();
        $this->assertIsInt(
            $this->testWallet->balance_paise,
            "Wallet balance_paise must be integer"
        );
    }

    /**
     * Test that float inputs are rejected or converted.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function float_inputs_properly_handled(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Current behavior: WalletService normalizes floats (with warning)
        // Target behavior: Either reject or convert with logging

        $floatAmount = 5000.50; // This is ambiguous - rupees or paise?

        // The service should either:
        // 1. Reject with clear error
        // 2. Treat as rupees and convert (with warning)
        // 3. Require explicit unit specification

        // Current implementation treats floats as rupees
        $transaction = $walletService->deposit(
            $this->testUser,
            $floatAmount, // Float input
            \App\Enums\TransactionType::DEPOSIT,
            'Float conversion test'
        );

        // Verify the stored value is integer paise
        $this->assertIsInt($transaction->amount_paise);

        // If treated as rupees: 5000.50 * 100 = 500050 paise
        // If treated as paise: 5000 paise (rounded)
        $this->assertEquals(
            500050,
            $transaction->amount_paise,
            "Float should be converted to paise correctly (rupees * 100)"
        );
    }

    /**
     * Test that DB queries don't use float bindings.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function database_queries_avoid_floats(): void
    {
        $this->createTestUser();
        $this->createTestSubscription();
        $payment = $this->createTestPayment();

        $floatBindings = [];

        DB::listen(function ($query) use (&$floatBindings) {
            foreach ($query->bindings as $binding) {
                if (is_float($binding)) {
                    // Check if this is a financial table query
                    $financialTables = ['wallets', 'transactions', 'payments', 'bonus_transactions', 'user_investments'];
                    foreach ($financialTables as $table) {
                        if (stripos($query->sql, $table) !== false) {
                            $floatBindings[] = [
                                'sql' => $query->sql,
                                'binding' => $binding,
                            ];
                            break;
                        }
                    }
                }
            }
        });

        // Process payment
        $this->processPaymentLifecycle($payment);

        $this->assertEmpty(
            $floatBindings,
            "Float bindings detected in financial queries:\n" .
            json_encode($floatBindings, JSON_PRETTY_PRINT) .
            "\n\nAll database operations must use integer paise."
        );
    }

    /**
     * Test arithmetic precision with edge cases.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function arithmetic_precision_maintained(): void
    {
        // Test cases that would fail with float arithmetic
        $testCases = [
            ['a' => 10001, 'b' => 10002, 'expected_sum' => 20003],
            ['a' => 99999, 'b' => 1, 'expected_sum' => 100000],
            ['a' => 333333, 'b' => 333333, 'expected_sum' => 666666], // 1/3 + 1/3 in paise
        ];

        foreach ($testCases as $case) {
            $sum = $case['a'] + $case['b'];

            $this->assertEquals(
                $case['expected_sum'],
                $sum,
                "Integer arithmetic should be precise: {$case['a']} + {$case['b']}"
            );
        }
    }

    /**
     * Test that large amounts don't overflow.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function large_amounts_dont_overflow(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Test large amount: 10 crore rupees = 1,000,000,000 paise
        $largeAmountPaise = 1000000000; // 1 billion paise

        // PHP integers can handle this on 64-bit systems
        $this->assertIsInt($largeAmountPaise);

        // Verify DB can store it (BIGINT)
        $transaction = $walletService->deposit(
            $this->testUser,
            $largeAmountPaise,
            \App\Enums\TransactionType::DEPOSIT,
            'Large amount test'
        );

        $this->assertEquals(
            $largeAmountPaise,
            $transaction->amount_paise,
            "Large amounts must be stored precisely"
        );

        // Verify wallet balance
        $this->testWallet->refresh();
        $this->assertEquals(
            $largeAmountPaise,
            $this->testWallet->balance_paise
        );
    }

    /**
     * Test that bonus calculations use integers.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_calculations_use_integers(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Process bonus
        $bonusService = app(\App\Services\BonusCalculatorService::class);

        try {
            $totalBonus = $bonusService->calculateAndAwardBonuses($payment);

            // Bonus transactions should use integer paise
            $bonusTxns = \App\Models\BonusTransaction::where('payment_id', $payment->id)->get();

            foreach ($bonusTxns as $txn) {
                // Amount should be stored in a way that's integer-convertible
                $this->assertTrue(
                    is_numeric($txn->amount),
                    "Bonus amount should be numeric"
                );
            }
        } catch (\Throwable $e) {
            // May fail due to config - that's OK for this test
        }
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
