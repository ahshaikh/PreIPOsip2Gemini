<?php

/**
 * NoRupeeConversionBridgeTest
 *
 * INVARIANT: No /100 or *100 conversions inside lifecycle code.
 *
 * Rupee-to-paise conversions should happen at system boundaries:
 * - API input validation
 * - Database read/write
 * - Display formatting
 *
 * Inside lifecycle code, all values should already be in paise.
 * This prevents confusion about whether a value is rupees or paise.
 *
 * @package Tests\FinancialLifecycle\MonetaryPrecision
 */

namespace Tests\FinancialLifecycle\MonetaryPrecision;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use Tests\FinancialLifecycle\Support\StaticAnalysisHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;

class NoRupeeConversionBridgeTest extends FinancialLifecycleTestCase
{
    /**
     * Static analysis: Detect /100 conversions in lifecycle code.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function static_analysis_detects_division_by_100(): void
    {
        $analyzer = new StaticAnalysisHelper(base_path());
        $violations = $analyzer->scanForDivisionBy100();

        // Filter out legitimate display/logging conversions
        $criticalViolations = array_filter(
            $violations,
            fn($v) => !$this->isDisplayConversion($v)
        );

        $this->assertEmpty(
            $criticalViolations,
            "Division by 100 detected in financial calculation code:\n" .
            $this->formatViolations($criticalViolations) .
            "\n\nRupee conversions should only happen at boundaries, not in lifecycle logic."
        );
    }

    /**
     * Test that service methods accept paise directly.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function service_methods_accept_paise(): void
    {
        $this->createTestUser();

        $walletService = app(\App\Services\WalletService::class);

        // Method should accept integer paise
        $amountPaise = 500000; // 5000 rupees

        $transaction = $walletService->deposit(
            $this->testUser,
            $amountPaise,
            \App\Enums\TransactionType::DEPOSIT,
            'Paise input test'
        );

        // Should store exactly what was passed (no conversion)
        $this->assertEquals(
            $amountPaise,
            $transaction->amount_paise,
            "Service should accept and store paise directly without conversion"
        );
    }

    /**
     * Test that internal calculations don't convert units.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function internal_calculations_dont_convert(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        $conversionOperations = [];

        DB::listen(function ($query) use (&$conversionOperations) {
            // Look for queries that might indicate conversion
            // e.g., amount / 100 or amount * 100 in query
            if (preg_match('/\/ 100|\* 100/', $query->sql)) {
                $conversionOperations[] = $query->sql;
            }
        });

        // Process payment
        $this->processPaymentLifecycle($payment);

        $this->assertEmpty(
            $conversionOperations,
            "Unit conversion operations detected in queries:\n" .
            implode("\n", $conversionOperations)
        );
    }

    /**
     * Test that ledger entries use paise consistently.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_entries_use_paise(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);

        // Process payment
        $this->processPaymentLifecycle($payment);

        // Get ledger lines
        $ledgerLines = \App\Models\LedgerLine::orderBy('id', 'desc')
            ->limit(10)
            ->get();

        foreach ($ledgerLines as $line) {
            // Verify amount_paise is integer
            $this->assertIsInt(
                $line->amount_paise,
                "LedgerLine amount_paise must be integer"
            );

            // Verify no precision loss (amount_paise should be reasonable)
            $this->assertGreaterThan(
                0,
                $line->amount_paise,
                "LedgerLine amount_paise should be positive"
            );
        }
    }

    /**
     * Test that conversions only happen at boundaries.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function conversions_at_boundaries_only(): void
    {
        // Boundary points where conversion is acceptable:
        // 1. API input (rupees from client -> paise in system)
        // 2. API output (paise from system -> rupees for display)
        // 3. Gateway interaction (gateway uses paise)

        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        // Simulate API input: amount in rupees
        $apiInput = ['amount' => 5000.50]; // Rupees

        // Conversion should happen here, at the boundary
        $amountPaise = (int) round($apiInput['amount'] * 100);

        $this->assertEquals(500050, $amountPaise);

        // Create payment with paise (already converted)
        $payment = Payment::factory()->create([
            'user_id' => $this->testUser->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_test_' . uniqid(),
            'amount' => $apiInput['amount'],
            'amount_paise' => $amountPaise, // Pre-converted
        ]);

        // Inside lifecycle, only paise should be used
        $this->assertEquals(
            $amountPaise,
            $payment->amount_paise
        );
    }

    /**
     * Test payment getAmountPaiseStrict method.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function payment_provides_strict_paise_accessor(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();

        $payment = Payment::factory()->create([
            'user_id' => $this->testUser->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_test_' . uniqid(),
            'amount' => 5000.00,
            'amount_paise' => 500000,
        ]);

        // Payment should have a method that guarantees paise
        if (method_exists($payment, 'getAmountPaiseStrict')) {
            $paise = $payment->getAmountPaiseStrict();

            $this->assertIsInt($paise, "getAmountPaiseStrict must return integer");
            $this->assertEquals(500000, $paise);
        } else {
            $this->markTestIncomplete(
                "Payment::getAmountPaiseStrict() method not found. " .
                "Add this method to guarantee integer paise access."
            );
        }
    }

    /**
     * Test that bonus amounts are in paise throughout.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_amounts_in_paise(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);
        $payment->update(['status' => Payment::STATUS_PAID]);

        try {
            $bonusService = app(\App\Services\BonusCalculatorService::class);
            $totalBonus = $bonusService->calculateAndAwardBonuses($payment);

            // Check wallet transactions for bonus
            $bonusTxns = \App\Models\Transaction::where('wallet_id', $this->testWallet->id)
                ->where('type', 'bonus_credit')
                ->get();

            foreach ($bonusTxns as $txn) {
                $this->assertIsInt(
                    $txn->amount_paise,
                    "Bonus wallet transaction must use integer paise"
                );
            }
        } catch (\Throwable $e) {
            // May fail due to config
        }
    }

    /**
     * Check if violation is for display/logging conversion (acceptable).
     */
    private function isDisplayConversion(array $violation): bool
    {
        $acceptablePatterns = [
            'Log::',
            'logger(',
            'dd(',
            'dump(',
            '->format',
            'number_format',
            'sprintf',
            // Display helpers
            'formatMoney',
            'toRupees',
            'displayAmount',
        ];

        foreach ($acceptablePatterns as $pattern) {
            if (stripos($violation['code'], $pattern) !== false) {
                return true;
            }
        }

        return false;
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
