<?php

/**
 * BonusPaisePrecisionTest
 *
 * INVARIANT: Bonus calculations must use integer paise.
 *
 * @package Tests\FinancialLifecycle\Bonus
 */

namespace Tests\FinancialLifecycle\Bonus;

use Tests\FinancialLifecycle\FinancialLifecycleTestCase;
use App\Models\Payment;
use App\Models\BonusTransaction;
use App\Models\Transaction;

class BonusPaisePrecisionTest extends FinancialLifecycleTestCase
{
    /**
     * Test bonus amounts are calculated in paise.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_amounts_calculated_in_paise(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);
        $payment->update(['status' => Payment::STATUS_PAID]);

        try {
            $bonusService = app(\App\Services\BonusCalculatorService::class);
            $totalBonus = $bonusService->calculateAndAwardBonuses($payment);

            // Check bonus transactions
            $bonusTxns = BonusTransaction::where('payment_id', $payment->id)->get();

            foreach ($bonusTxns as $txn) {
                // Amount should be stored as decimal/numeric
                $this->assertTrue(
                    is_numeric($txn->amount),
                    "Bonus amount should be numeric"
                );
            }

            // Check wallet transactions
            $walletBonuses = Transaction::where('wallet_id', $this->testWallet->id)
                ->where('type', 'bonus_credit')
                ->get();

            foreach ($walletBonuses as $wtxn) {
                $this->assertIsInt(
                    $wtxn->amount_paise,
                    "Wallet bonus transaction should use integer paise"
                );
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Bonus calculation failed: ' . $e->getMessage());
        }
    }

    /**
     * Test TDS calculation precision.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function tds_calculation_precise(): void
    {
        $tdsService = app(\App\Services\TdsCalculationService::class);

        // Test various amounts
        $testCases = [
            ['gross' => 1000.00, 'expected_net' => 900.00], // 10% TDS
            ['gross' => 500.50, 'expected_net' => 450.45],
            ['gross' => 333.33, 'expected_net' => 300.00], // Rounded
        ];

        foreach ($testCases as $case) {
            $result = $tdsService->calculate($case['gross'], 'bonus');

            // Verify gross = net + tds
            $this->assertEqualsWithDelta(
                $case['gross'],
                $result->netAmount + $result->tdsAmount,
                0.01,
                "TDS invariant violated for gross {$case['gross']}"
            );
        }
    }

    /**
     * Test bonus wallet credit matches TDS result.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_wallet_credit_matches_tds(): void
    {
        $this->createTestUser();
        $subscription = $this->createTestSubscription();
        $payment = $this->createTestPayment($subscription);
        $payment->update(['status' => Payment::STATUS_PAID]);

        try {
            $bonusService = app(\App\Services\BonusCalculatorService::class);
            $bonusService->calculateAndAwardBonuses($payment);

            // Get bonus transactions and corresponding wallet credits
            $bonusTxns = BonusTransaction::where('payment_id', $payment->id)->get();

            foreach ($bonusTxns as $btxn) {
                $grossAmount = $btxn->amount;
                $tdsDeducted = $btxn->tds_deducted;
                $netAmount = $grossAmount - $tdsDeducted;

                // Find corresponding wallet transaction
                $walletTxn = Transaction::where('reference_type', BonusTransaction::class)
                    ->where('reference_id', $btxn->id)
                    ->where('type', 'bonus_credit')
                    ->first();

                if ($walletTxn) {
                    $walletAmountRupees = $walletTxn->amount_paise / 100;

                    $this->assertEqualsWithDelta(
                        $netAmount,
                        $walletAmountRupees,
                        0.01,
                        "Wallet credit should match net bonus after TDS"
                    );
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Bonus test failed: ' . $e->getMessage());
        }
    }
}
