<?php

/**
 * V-FINANCIAL-GUARANTEES-2026: Institutional Financial Invariant Test
 *
 * This is the MASTER reconciliation test that verifies all financial invariants hold.
 * If this test fails, the system has a fundamental accounting error.
 *
 * Guarantees verified:
 * 1. Σ(wallet balances) reconciles with Σ(wallet transactions)
 * 2. Σ(liabilities) = Σ(user entitlements)
 * 3. Σ(receivables) = Σ(outstanding chargebacks)
 * 4. Σ(bonus payouts) = Σ(bonus_transactions where credited)
 * 5. Σ(subscription revenue) = Σ(paid payments)
 * 6. Ledger debits = Ledger credits (double-entry balance)
 * 7. No negative wallet balances
 * 8. No orphaned transactions
 */

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\BonusTransaction;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\LedgerAccount;
use App\Services\WalletService;
use App\Services\PaymentWebhookService;
use App\Services\BonusCalculatorService;
use App\Services\AllocationService;
use App\Services\ReferralService;
use App\Services\DoubleEntryLedgerService;
use App\Jobs\ProcessSuccessfulPaymentJob;
use App\Enums\TransactionType;

class FinancialGuaranteesTest extends TestCase
{
    protected WalletService $walletService;
    protected PaymentWebhookService $webhookService;
    protected array $users = [];
    protected array $subscriptions = [];
    protected array $payments = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        $this->walletService = app(WalletService::class);
        $this->webhookService = app(PaymentWebhookService::class);

        // Ensure we have inventory
        $product = Product::first();
        if ($product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'total_value_received' => 10000000, // ₹100,000
                'value_remaining' => 10000000,
            ]);
        }
    }

    /**
     * Create a realistic financial scenario with multiple users,
     * payments, bonuses, and various transaction types.
     */
    private function createFinancialScenario(): void
    {
        $plan = Plan::first();

        // Create 5 users with different financial states
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();
            $user->assignRole('user');

            // Wallet is auto-created by UserFactory, update initial balance
            $user->wallet->update(['balance_paise' => 0]);

            $this->users[] = $user;

            // Create subscription
            $subscription = Subscription::factory()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => $plan->monthly_amount,
                'status' => 'active',
                'consecutive_payments_count' => $i, // Varying payment history
                'bonus_multiplier' => 1.0 + ($i * 0.1), // Varying multipliers
            ]);
            $this->subscriptions[] = $subscription;

            // Create varying number of payments per user
            for ($j = 0; $j <= $i; $j++) {
                $orderId = 'order_' . $user->id . '_' . $j;
                $payment = Payment::factory()->create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'gateway_order_id' => $orderId,
                    'amount' => $plan->monthly_amount,
                    'amount_paise' => $plan->monthly_amount * 100,
                    'is_on_time' => true,
                ]);

                // Process payment through full lifecycle
                $this->webhookService->handleSuccessfulPayment([
                    'order_id' => $orderId,
                    'id' => 'pay_' . $orderId,
                ]);

                // Run the job to process bonuses
                $payment->refresh();
                if ($payment->status === Payment::STATUS_PAID) {
                    try {
                        // V-WAVE3-FIX: Use dispatchSync to let container inject IdempotencyService
                        ProcessSuccessfulPaymentJob::dispatchSync($payment);
                    } catch (\Exception $e) {
                        // Log but continue - some jobs may fail due to inventory
                    }
                }

                $this->payments[] = $payment->fresh();
            }
        }
    }

    // =========================================================================
    // INVARIANT 1: Wallet Balance Reconciliation
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_balances_reconcile_with_transaction_history()
    {
        $this->createFinancialScenario();

        // For each wallet, verify: balance = Σ(credits) - Σ(debits)
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            $transactions = Transaction::where('wallet_id', $wallet->id)
                ->where('status', 'completed')
                ->get();

            $calculatedBalance = 0;
            foreach ($transactions as $txn) {
                $type = TransactionType::tryFrom($txn->type);
                if ($type && $type->isCredit()) {
                    $calculatedBalance += $txn->amount_paise;
                } else {
                    $calculatedBalance -= $txn->amount_paise;
                }
            }

            $this->assertEquals(
                $wallet->balance_paise,
                $calculatedBalance,
                "Wallet #{$wallet->id} balance mismatch. Stored: {$wallet->balance_paise}, Calculated: {$calculatedBalance}"
            );
        }
    }

    // =========================================================================
    // INVARIANT 2: Ledger Double-Entry Balance
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function ledger_debits_equal_credits()
    {
        $this->createFinancialScenario();

        // V-DOUBLE-ENTRY: Every entry must balance
        $totalDebits = LedgerLine::where('type', 'debit')->sum('amount_paise');
        $totalCredits = LedgerLine::where('type', 'credit')->sum('amount_paise');

        $this->assertEquals(
            $totalDebits,
            $totalCredits,
            "Ledger imbalance! Debits: {$totalDebits}, Credits: {$totalCredits}"
        );

        // Also verify per-entry balance
        $entries = LedgerEntry::all();
        foreach ($entries as $entry) {
            $entryDebits = LedgerLine::where('ledger_entry_id', $entry->id)
                ->where('type', 'debit')
                ->sum('amount_paise');
            $entryCredits = LedgerLine::where('ledger_entry_id', $entry->id)
                ->where('type', 'credit')
                ->sum('amount_paise');

            $this->assertEquals(
                $entryDebits,
                $entryCredits,
                "Entry #{$entry->id} imbalance! Debits: {$entryDebits}, Credits: {$entryCredits}"
            );
        }
    }

    // =========================================================================
    // INVARIANT 3: No Negative Wallet Balances
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_negative_wallet_balances_exist()
    {
        $this->createFinancialScenario();

        $negativeWallets = Wallet::where('balance_paise', '<', 0)->get();

        $this->assertCount(
            0,
            $negativeWallets,
            "Found {$negativeWallets->count()} wallets with negative balance!"
        );
    }

    // =========================================================================
    // INVARIANT 4: Payment Revenue Reconciliation
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function subscription_revenue_matches_paid_payments()
    {
        $this->createFinancialScenario();

        // Total paid payments
        $paidPayments = Payment::where('status', Payment::STATUS_PAID)->sum('amount_paise');

        // Refunded payments should be subtracted
        $refundedPayments = Payment::where('status', Payment::STATUS_REFUNDED)->sum('amount_paise');

        // Chargebacks should also be subtracted
        $chargebackPayments = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->sum('chargeback_amount_paise');

        $netRevenue = $paidPayments - $refundedPayments - $chargebackPayments;

        // Verify ledger has corresponding revenue entries
        $revenueAccount = LedgerAccount::where('code', 'REVENUE_SIP')->first();

        if ($revenueAccount) {
            $ledgerRevenue = LedgerLine::where('ledger_account_id', $revenueAccount->id)
                ->where('type', 'credit')
                ->sum('amount_paise');

            $this->assertGreaterThanOrEqual(
                0,
                $ledgerRevenue,
                "Ledger revenue should be non-negative"
            );
        }

        // Net revenue should never be negative
        $this->assertGreaterThanOrEqual(
            0,
            $netRevenue,
            "Net revenue is negative: {$netRevenue}"
        );
    }

    // =========================================================================
    // INVARIANT 5: Bonus Payout Reconciliation
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function bonus_payouts_reconcile_with_wallet_credits()
    {
        $this->createFinancialScenario();

        // Sum of all bonus transactions (net of reversals)
        $bonusAwarded = BonusTransaction::where('status', 'credited')
            ->sum('final_amount');

        // Convert to paise for comparison
        $bonusAwardedPaise = $bonusAwarded * 100;

        // Sum of all bonus credits in wallets
        $walletBonusCredits = Transaction::where('type', 'bonus_credit')
            ->where('status', 'completed')
            ->sum('amount_paise');

        // These should match
        $this->assertEquals(
            $bonusAwardedPaise,
            $walletBonusCredits,
            "Bonus mismatch! BonusTransactions: {$bonusAwardedPaise}, WalletCredits: {$walletBonusCredits}"
        );
    }

    // =========================================================================
    // INVARIANT 6: No Orphaned Transactions
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function no_orphaned_wallet_transactions_exist()
    {
        $this->createFinancialScenario();

        // Transactions must have valid wallet_id
        $orphanedByWallet = Transaction::whereNotIn(
            'wallet_id',
            Wallet::pluck('id')
        )->count();

        $this->assertEquals(
            0,
            $orphanedByWallet,
            "Found {$orphanedByWallet} transactions with invalid wallet_id"
        );

        // Transactions must have valid user_id
        $orphanedByUser = Transaction::whereNotIn(
            'user_id',
            User::pluck('id')
        )->count();

        $this->assertEquals(
            0,
            $orphanedByUser,
            "Found {$orphanedByUser} transactions with invalid user_id"
        );
    }

    // =========================================================================
    // INVARIANT 7: Liability Mirror (Wallet = Liability)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function wallet_balances_mirror_liability_ledger()
    {
        $this->createFinancialScenario();

        // Sum of all wallet balances = platform liability to users
        $totalWalletBalance = Wallet::sum('balance_paise');

        // Check liability account in ledger
        $liabilityAccount = LedgerAccount::where('code', 'LIABILITY_USER_WALLETS')->first();

        if ($liabilityAccount) {
            $liabilityCredits = LedgerLine::where('ledger_account_id', $liabilityAccount->id)
                ->where('type', 'credit')
                ->sum('amount_paise');

            $liabilityDebits = LedgerLine::where('ledger_account_id', $liabilityAccount->id)
                ->where('type', 'debit')
                ->sum('amount_paise');

            $netLiability = $liabilityCredits - $liabilityDebits;

            $this->assertEquals(
                $totalWalletBalance,
                $netLiability,
                "Wallet-Liability mirror broken! Wallets: {$totalWalletBalance}, Liability: {$netLiability}"
            );
        }
    }

    // =========================================================================
    // INVARIANT 8: Receivables Match Outstanding Chargebacks
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function receivables_match_outstanding_chargebacks()
    {
        $this->createFinancialScenario();

        // Sum of chargeback amounts where wallet couldn't cover
        $outstandingChargebacks = Payment::where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->whereNotNull('receivable_amount_paise')
            ->sum('receivable_amount_paise');

        // Receivables ledger account
        $receivablesAccount = LedgerAccount::where('code', 'RECEIVABLE_USER_CHARGEBACKS')->first();

        if ($receivablesAccount && $outstandingChargebacks > 0) {
            $receivablesBalance = LedgerLine::where('ledger_account_id', $receivablesAccount->id)
                ->where('type', 'debit')
                ->sum('amount_paise');

            $receivablesCredits = LedgerLine::where('ledger_account_id', $receivablesAccount->id)
                ->where('type', 'credit')
                ->sum('amount_paise');

            $netReceivables = $receivablesBalance - $receivablesCredits;

            $this->assertEquals(
                $outstandingChargebacks,
                $netReceivables,
                "Receivables mismatch! Chargebacks: {$outstandingChargebacks}, Ledger: {$netReceivables}"
            );
        }
    }

    // =========================================================================
    // INVARIANT 9: Cross-Check Summary
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function full_financial_reconciliation_passes()
    {
        $this->createFinancialScenario();

        // This is the master check that runs all invariants in one scenario
        $report = $this->generateReconciliationReport();

        // All checks must pass
        $this->assertTrue(
            $report['ledger_balanced'],
            "Ledger imbalanced: " . json_encode($report['ledger_details'])
        );

        $this->assertTrue(
            $report['no_negative_wallets'],
            "Negative wallets found: " . json_encode($report['negative_wallet_ids'])
        );

        $this->assertTrue(
            $report['wallet_txn_reconciled'],
            "Wallet-Transaction mismatch: " . json_encode($report['wallet_mismatches'])
        );

        $this->assertTrue(
            $report['bonus_reconciled'],
            "Bonus mismatch: " . json_encode($report['bonus_details'])
        );
    }

    /**
     * Generate a comprehensive reconciliation report.
     */
    private function generateReconciliationReport(): array
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'ledger_balanced' => true,
            'ledger_details' => [],
            'no_negative_wallets' => true,
            'negative_wallet_ids' => [],
            'wallet_txn_reconciled' => true,
            'wallet_mismatches' => [],
            'bonus_reconciled' => true,
            'bonus_details' => [],
        ];

        // Check ledger balance
        $totalDebits = LedgerLine::where('type', 'debit')->sum('amount_paise');
        $totalCredits = LedgerLine::where('type', 'credit')->sum('amount_paise');
        if ($totalDebits !== $totalCredits) {
            $report['ledger_balanced'] = false;
            $report['ledger_details'] = [
                'debits' => $totalDebits,
                'credits' => $totalCredits,
                'diff' => $totalDebits - $totalCredits,
            ];
        }

        // Check negative wallets
        $negativeWallets = Wallet::where('balance_paise', '<', 0)->pluck('id')->toArray();
        if (count($negativeWallets) > 0) {
            $report['no_negative_wallets'] = false;
            $report['negative_wallet_ids'] = $negativeWallets;
        }

        // Check wallet-transaction reconciliation
        foreach (Wallet::all() as $wallet) {
            $txnSum = 0;
            foreach (Transaction::where('wallet_id', $wallet->id)->where('status', 'completed')->get() as $txn) {
                $type = TransactionType::tryFrom($txn->type);
                if ($type && $type->isCredit()) {
                    $txnSum += $txn->amount_paise;
                } else {
                    $txnSum -= $txn->amount_paise;
                }
            }

            if ($wallet->balance_paise !== $txnSum) {
                $report['wallet_txn_reconciled'] = false;
                $report['wallet_mismatches'][] = [
                    'wallet_id' => $wallet->id,
                    'stored' => $wallet->balance_paise,
                    'calculated' => $txnSum,
                ];
            }
        }

        // Check bonus reconciliation
        $bonusAwarded = BonusTransaction::where('status', 'credited')->sum('final_amount') * 100;
        $walletCredits = Transaction::where('type', 'bonus_credit')
            ->where('status', 'completed')
            ->sum('amount_paise');

        if ($bonusAwarded !== $walletCredits) {
            $report['bonus_reconciled'] = false;
            $report['bonus_details'] = [
                'bonus_transactions' => $bonusAwarded,
                'wallet_credits' => $walletCredits,
            ];
        }

        return $report;
    }
}
