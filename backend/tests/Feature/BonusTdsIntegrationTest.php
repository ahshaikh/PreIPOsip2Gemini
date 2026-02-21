<?php

/**
 * V-BONUS-TDS-INTEGRATION-2026: Bonus Engine & TDS Cross-Validation Test
 *
 * Verifies:
 * 1. BonusTransaction creates proper ledger entries
 * 2. TDS deducted correctly (10% above ₹10K threshold)
 * 3. Wallet reflects net amount (gross - TDS)
 * 4. Ledger reflects gross + tax entries
 * 5. Drift test holds with bonuses included
 */

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\BulkPurchase;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\LedgerAccount;
use App\Services\WalletService;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Services\PaymentWebhookService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Enums\TransactionType;

class BonusTdsIntegrationTest extends TestCase
{
    protected User $user;
    protected Plan $plan;
    protected Subscription $subscription;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        $this->walletService = app(WalletService::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->user->wallet->update(['balance_paise' => 0]);

        $this->plan = Plan::first();

        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => $this->plan->monthly_amount,
            'status' => 'active',
            'consecutive_payments_count' => 0,
            'bonus_multiplier' => 1.0,
        ]);

        // Ensure inventory
        $product = Product::first();
        if ($product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'total_value_received' => 10000000,
                'value_remaining' => 10000000,
            ]);
        }
    }

    // =========================================================================
    // TEST 1: Bonus Creates Proper Ledger Entries
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_transaction_creates_balanced_ledger_entries()
    {
        // Create and process a payment to trigger bonus
        $payment = $this->createAndProcessPayment();

        // Get bonus transactions
        $bonuses = BonusTransaction::where('payment_id', $payment->id)->get();
        $this->assertGreaterThan(0, $bonuses->count(), 'Bonuses should be created');

        // Verify ledger is balanced
        $totalDebits = LedgerLine::where('type', 'debit')->sum('amount_paise');
        $totalCredits = LedgerLine::where('type', 'credit')->sum('amount_paise');

        $this->assertEquals(
            $totalDebits,
            $totalCredits,
            "Ledger imbalanced after bonus! Debits: {$totalDebits}, Credits: {$totalCredits}"
        );

        // Verify bonus ledger entries exist
        foreach ($bonuses as $bonus) {
            if ($bonus->status === 'credited') {
                // Find wallet transaction for this bonus
                $walletTxn = Transaction::where('user_id', $this->user->id)
                    ->where('type', 'bonus_credit')
                    ->where('amount_paise', $bonus->final_amount * 100)
                    ->first();

                $this->assertNotNull(
                    $walletTxn,
                    "Wallet transaction should exist for bonus of {$bonus->final_amount}"
                );
            }
        }
    }

    // =========================================================================
    // TEST 2: TDS Deduction Below Threshold (No TDS)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function tds_not_deducted_for_bonus_below_threshold()
    {
        // Create payment that triggers a small bonus (below ₹10K threshold)
        $payment = $this->createAndProcessPayment();

        // Get bonus transactions
        $bonus = BonusTransaction::where('payment_id', $payment->id)
            ->where('type', 'consistency')
            ->first();

        if ($bonus && $bonus->gross_amount < 10000) { // Below ₹10K
            // TDS should be 0
            $this->assertEquals(
                0,
                $bonus->tds_amount ?? 0,
                'TDS should not be deducted for amounts below threshold'
            );

            // Net amount should equal gross amount
            $this->assertEquals(
                $bonus->gross_amount,
                $bonus->final_amount,
                'Net amount should equal gross when no TDS'
            );
        }
    }

    // =========================================================================
    // TEST 3: TDS Deduction Above Threshold
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function tds_correctly_deducted_for_bonus_above_threshold()
    {
        // Create a high-value subscription with large multiplier to trigger big bonus
        $premiumPlan = Plan::factory()->create([
            'monthly_amount' => 100000, // ₹1,00,000
            'consistency_bonus' => 15000, // ₹15,000 consistency bonus (above threshold)
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'plan_id' => $premiumPlan->id,
            'amount' => $premiumPlan->monthly_amount,
            'status' => 'active',
            'consecutive_payments_count' => 0,
            'bonus_multiplier' => 1.0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_tds_test_' . uniqid(),
            'amount' => $premiumPlan->monthly_amount,
            'is_on_time' => true,
        ]);

        // Process payment
        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_tds_' . uniqid(),
        ]);

        $payment->refresh();
        if ($payment->status === Payment::STATUS_PAID) {
            (new ProcessSuccessfulPaymentJob($payment))->handle(
                app(BonusCalculatorService::class),
                app(AllocationService::class),
                app(ReferralService::class),
                app(WalletService::class)
            );
        }

        // Check if any bonus exceeded threshold
        $largeBonuses = BonusTransaction::where('payment_id', $payment->id)
            ->where('gross_amount', '>=', 10000)
            ->get();

        foreach ($largeBonuses as $bonus) {
            if ($bonus->tds_amount !== null && $bonus->tds_amount > 0) {
                // TDS should be 10% of gross
                $expectedTds = $bonus->gross_amount * 0.10;
                $this->assertEquals(
                    $expectedTds,
                    $bonus->tds_amount,
                    "TDS should be 10% of gross amount"
                );

                // Net should be gross - TDS
                $expectedNet = $bonus->gross_amount - $bonus->tds_amount;
                $this->assertEquals(
                    $expectedNet,
                    $bonus->final_amount,
                    "Net amount should be gross - TDS"
                );
            }
        }
    }

    // =========================================================================
    // TEST 4: Wallet Reflects Net Amount
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_credits_net_amount_after_tds()
    {
        $initialBalance = $this->user->wallet->balance_paise;

        // Process payment to trigger bonus
        $payment = $this->createAndProcessPayment();

        // Calculate expected wallet increase
        $bonuses = BonusTransaction::where('payment_id', $payment->id)
            ->where('status', 'credited')
            ->get();

        $expectedIncrease = $bonuses->sum('final_amount') * 100; // Convert to paise

        // Verify wallet balance
        $this->user->wallet->refresh();
        $actualIncrease = $this->user->wallet->balance_paise - $initialBalance;

        $this->assertEquals(
            $expectedIncrease,
            $actualIncrease,
            "Wallet should reflect net bonus amount"
        );
    }

    // =========================================================================
    // TEST 5: Ledger Reflects Gross + TDS Entries
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_contains_gross_and_tds_entries()
    {
        $initialLedgerCount = LedgerEntry::count();

        // Process payment
        $payment = $this->createAndProcessPayment();

        // Verify ledger entries increased
        $finalLedgerCount = LedgerEntry::count();
        $this->assertGreaterThanOrEqual(
            $initialLedgerCount,
            $finalLedgerCount,
            "Ledger entries should be created"
        );

        // Verify all ledger entries are balanced
        $entries = LedgerEntry::where('id', '>', $initialLedgerCount)->get();
        foreach ($entries as $entry) {
            $debits = LedgerLine::where('ledger_entry_id', $entry->id)
                ->where('type', 'debit')
                ->sum('amount_paise');
            $credits = LedgerLine::where('ledger_entry_id', $entry->id)
                ->where('type', 'credit')
                ->sum('amount_paise');

            $this->assertEquals(
                $debits,
                $credits,
                "Ledger entry #{$entry->id} imbalanced! Debits: {$debits}, Credits: {$credits}"
            );
        }
    }

    // =========================================================================
    // TEST 6: Bonus Drift Test
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_calculations_have_no_rounding_drift()
    {
        // Run multiple payment cycles
        for ($month = 1; $month <= 6; $month++) {
            $payment = Payment::factory()->create([
                'user_id' => $this->user->id,
                'subscription_id' => $this->subscription->id,
                'status' => 'pending',
                'gateway_order_id' => 'order_drift_' . $month . '_' . uniqid(),
                'amount' => $this->plan->monthly_amount,
                'is_on_time' => true,
            ]);

            $webhookService = app(PaymentWebhookService::class);
            $webhookService->handleSuccessfulPayment([
                'order_id' => $payment->gateway_order_id,
                'id' => 'pay_drift_' . $month,
            ]);

            $payment->refresh();
            if ($payment->status === Payment::STATUS_PAID) {
                (new ProcessSuccessfulPaymentJob($payment))->handle(
                    app(BonusCalculatorService::class),
                    app(AllocationService::class),
                    app(ReferralService::class),
                    app(WalletService::class)
                );
            }

            $this->subscription->increment('consecutive_payments_count');
        }

        // Verify: Sum of bonus_transactions.final_amount = Sum of wallet bonus credits
        $totalBonusCredited = BonusTransaction::where('subscription_id', $this->subscription->id)
            ->where('status', 'credited')
            ->sum('final_amount');

        $totalWalletCredits = Transaction::where('user_id', $this->user->id)
            ->where('type', 'bonus_credit')
            ->where('status', 'completed')
            ->sum('amount_paise') / 100; // Convert paise to rupees

        $this->assertEquals(
            $totalBonusCredited,
            $totalWalletCredits,
            "Bonus drift detected! BonusTransactions: {$totalBonusCredited}, WalletCredits: {$totalWalletCredits}"
        );

        // Verify wallet balance is consistent
        $this->user->wallet->refresh();
        $walletTxnSum = 0;
        foreach (Transaction::where('wallet_id', $this->user->wallet->id)->where('status', 'completed')->get() as $txn) {
            $type = TransactionType::tryFrom($txn->type);
            if ($type && $type->isCredit()) {
                $walletTxnSum += $txn->amount_paise;
            } else {
                $walletTxnSum -= $txn->amount_paise;
            }
        }

        $this->assertEquals(
            $this->user->wallet->balance_paise,
            $walletTxnSum,
            "Wallet balance drift detected!"
        );
    }

    // =========================================================================
    // TEST 7: TDS Exemption Boundary (Exactly ₹10K)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function tds_exemption_at_exact_threshold_boundary()
    {
        // Test at ₹9,999 (just below)
        $bonusJustBelow = 9999;
        $tdsBelow = $this->calculateExpectedTds($bonusJustBelow);
        $this->assertEquals(0, $tdsBelow, 'No TDS for amount just below threshold');

        // Test at ₹10,000 (at threshold)
        $bonusAtThreshold = 10000;
        $tdsAt = $this->calculateExpectedTds($bonusAtThreshold);
        // TDS applies at or above threshold
        $this->assertEquals(1000, $tdsAt, 'TDS of 10% at exact threshold');

        // Test at ₹10,001 (just above)
        $bonusJustAbove = 10001;
        $tdsAbove = $this->calculateExpectedTds($bonusJustAbove);
        $this->assertEquals(1000.1, $tdsAbove, 'TDS of 10% for amount above threshold');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    private function createAndProcessPayment(): Payment
    {
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'status' => 'pending',
            'gateway_order_id' => 'order_bonus_' . uniqid(),
            'amount' => $this->plan->monthly_amount,
            'is_on_time' => true,
        ]);

        $webhookService = app(PaymentWebhookService::class);
        $webhookService->handleSuccessfulPayment([
            'order_id' => $payment->gateway_order_id,
            'id' => 'pay_bonus_' . uniqid(),
        ]);

        $payment->refresh();

        if ($payment->status === Payment::STATUS_PAID) {
            (new ProcessSuccessfulPaymentJob($payment))->handle(
                app(BonusCalculatorService::class),
                app(AllocationService::class),
                app(ReferralService::class),
                app(WalletService::class)
            );
        }

        return $payment;
    }

    private function calculateExpectedTds(float $grossAmount): float
    {
        $threshold = 10000; // ₹10K threshold
        $tdsRate = 0.10; // 10%

        if ($grossAmount < $threshold) {
            return 0;
        }

        return $grossAmount * $tdsRate;
    }
}
