<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Investment;
use App\Models\UserInvestment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\BonusTransaction;
use App\Models\Referral;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * User Investments Seeder - Phase 7 (OPTIONAL TEST DATA)
 *
 * Seeds test investment data for end-to-end testing:
 * - Subscriptions (User SIP subscriptions)
 * - Payments (Test payment records)
 * - Investments (Top-level investment records)
 * - User Investments (Share allocations)
 * - Transactions (Wallet ledger)
 * - Bonus Transactions
 * - Referrals
 *
 * CRITICAL:
 * - ONLY runs if APP_ENV=local or APP_ENV=testing
 * - Creates realistic test data for QA/testing
 * - Maintains financial integrity (wallet conservation)
 * - Respects inventory constraints
 *
 * WARNING: DO NOT RUN IN PRODUCTION
 */
class UserInvestmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Safety check - only run in local/testing environment
        if (!app()->environment(['local', 'testing', 'development'])) {
            $this->command->warn('⚠️  Skipping UserInvestmentsSeeder - Not in local/testing environment');
            return;
        }

        $this->command->warn('⚠️  This seeder creates TEST DATA ONLY. Do not run in production!');

        DB::transaction(function () {
            $this->seedSubscriptions();
            $this->seedReferrals();
            $this->command->info('✅ User Investments test data seeded successfully');
        });
    }

    /**
     * Seed user subscriptions and related data
     */
    private function seedSubscriptions(): void
    {
        // Get test users (KYC verified)
        $user1 = User::where('email', 'user1@test.com')->first();
        $user2 = User::where('email', 'user2@test.com')->first();
        $user3 = User::where('email', 'user3@test.com')->first();
        $user4 = User::where('email', 'user4@test.com')->first();
        $user5 = User::where('email', 'user5@test.com')->first();

        if (!$user1 || !$user2 || !$user3) {
            $this->command->error('❌ Test users not found. Run IdentityAccessSeeder first.');
            return;
        }

        // Get plans
        $planA = Plan::where('code', 'PLAN_A')->first();
        $planB = Plan::where('code', 'PLAN_B')->first();
        $planC = Plan::where('code', 'PLAN_C')->first();

        // Get products
        $techCorp = Product::where('slug', 'techcorp-india-shares')->first();
        $healthPlus = Product::where('slug', 'healthplus-solutions-shares')->first();
        $financeHub = Product::where('slug', 'financehub-technologies-shares')->first();
        $eduTech = Product::where('slug', 'edutech-academy-shares')->first();
        $greenEnergy = Product::where('slug', 'greenenergy-innovations-shares')->first();

        // User 1: Plan A - 2 payments
        $this->createSubscriptionFlow($user1, $planA, $techCorp, 2, 5000);

        // User 2: Plan B - 2 payments
        $this->createSubscriptionFlow($user2, $planB, $financeHub, 2, 10000);

        // User 3: Plan C - 2 payments
        $this->createSubscriptionFlow($user3, $planC, $greenEnergy, 2, 25000);

        // User 4: Plan A - 1 payment (referred by User 1)
        if ($user4) {
            $this->createSubscriptionFlow($user4, $planA, $healthPlus, 1, 5000);
        }

        // User 5: Plan B - 1 payment (referred by User 2)
        if ($user5) {
            $this->createSubscriptionFlow($user5, $planB, $eduTech, 1, 10000);
        }

        $this->command->info('  ✓ Test subscriptions and investments seeded');
    }

    /**
     * Create complete subscription flow for a user
     */
    private function createSubscriptionFlow(User $user, Plan $plan, Product $product, int $paymentCount, int $amountPerPayment): void
    {
        // Create subscription
        $subscription = Subscription::firstOrCreate(
            ['user_id' => $user->id, 'plan_id' => $plan->id],
            [
                'subscription_code' => 'SUB' . strtoupper(Str::random(8)),
                'status' => 'active',
                'start_date' => now()->subMonths($paymentCount),
                'end_date' => now()->addMonths(12 - $paymentCount),
                'payment_frequency' => 'monthly',
                'amount' => $amountPerPayment,
            ]
        );

        // Create top-level investment
        $investment = Investment::firstOrCreate(
            ['subscription_id' => $subscription->id],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'total_amount' => $amountPerPayment * $paymentCount,
                'invested_amount' => $amountPerPayment * $paymentCount,
                'status' => 'active',
                'start_date' => $subscription->start_date,
            ]
        );

        // Get user wallet
        $wallet = Wallet::where('user_id', $user->id)->first();
        $runningBalance = $wallet ? $wallet->balance_paise : 0;

        // Create payments, transactions, and allocations
        for ($i = 1; $i <= $paymentCount; $i++) {
            $paymentDate = now()->subMonths($paymentCount - $i + 1);

            // Create payment
            $payment = Payment::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'gateway_payment_id' => 'pay_test_' . Str::random(16),
                ],
                [
                    'gateway' => 'razorpay',
                    'amount' => $amountPerPayment,
                    'status' => 'completed',
                    'payment_method' => 'upi',
                    'paid_at' => $paymentDate,
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ]
            );

            // Create credit transaction (payment received)
            $runningBalance += ($amountPerPayment * 100); // Convert to paise
            Transaction::firstOrCreate(
                ['transaction_id' => 'txn_credit_' . $payment->id],
                [
                    'user_id' => $user->id,
                    'type' => 'credit',
                    'amount_paise' => $amountPerPayment * 100,
                    'balance_before_paise' => $runningBalance - ($amountPerPayment * 100),
                    'balance_after_paise' => $runningBalance,
                    'description' => 'Payment received for ' . $plan->name,
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'created_at' => $paymentDate,
                ]
            );

            // Allocate shares (debit transaction)
            $sharesToAllocate = floor($amountPerPayment / $product->price_per_share);
            $allocationValue = $sharesToAllocate * $product->price_per_share;

            // Get bulk purchase for inventory tracking
            $bulkPurchase = BulkPurchase::where('product_id', $product->id)->first();

            if ($bulkPurchase && $bulkPurchase->quantity_reserved >= $sharesToAllocate) {
                // Create user investment (share allocation)
                UserInvestment::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'payment_id' => $payment->id,
                    ],
                    [
                        'investment_id' => $investment->id,
                        'bulk_purchase_id' => $bulkPurchase->id,
                        'quantity' => $sharesToAllocate,
                        'price_per_unit' => $product->price_per_share,
                        'value_allocated' => $allocationValue,
                        'status' => 'active',
                        'invested_at' => $paymentDate,
                        'allocation_status' => 'allocated',
                    ]
                );

                // Update bulk purchase inventory
                $bulkPurchase->increment('quantity_allocated', $sharesToAllocate);
                $bulkPurchase->decrement('quantity_reserved', $sharesToAllocate);

                // Create debit transaction (investment)
                $runningBalance -= ($allocationValue * 100);
                Transaction::firstOrCreate(
                    ['transaction_id' => 'txn_invest_' . $payment->id],
                    [
                        'user_id' => $user->id,
                        'type' => 'debit',
                        'amount_paise' => $allocationValue * 100,
                        'balance_before_paise' => $runningBalance + ($allocationValue * 100),
                        'balance_after_paise' => $runningBalance,
                        'description' => 'Investment in ' . $product->name,
                        'reference_type' => UserInvestment::class,
                        'reference_id' => $payment->id,
                        'created_at' => $paymentDate,
                    ]
                );
            }
        }

        // Calculate and credit progressive bonus (after all payments)
        $progressiveBonusRate = $plan->configs()->where('config_key', 'progressive_bonus_rate')->first();
        if ($progressiveBonusRate) {
            $bonusAmount = ($amountPerPayment * $paymentCount) * (floatval($progressiveBonusRate->value) / 100);
            $bonusAmount = round($bonusAmount);

            if ($bonusAmount > 0) {
                // Create bonus transaction
                BonusTransaction::firstOrCreate(
                    ['user_id' => $user->id, 'subscription_id' => $subscription->id],
                    [
                        'amount' => $bonusAmount,
                        'bonus_type' => 'progressive',
                        'description' => 'Progressive bonus for ' . $plan->name . ' (' . $paymentCount . ' months)',
                        'credited_at' => now()->subDays(rand(1, 5)),
                    ]
                );

                // Credit to wallet transaction
                $runningBalance += ($bonusAmount * 100);
                Transaction::firstOrCreate(
                    ['transaction_id' => 'txn_bonus_' . $subscription->id . '_' . Str::random(8)],
                    [
                        'user_id' => $user->id,
                        'type' => 'credit',
                        'amount_paise' => $bonusAmount * 100,
                        'balance_before_paise' => $runningBalance - ($bonusAmount * 100),
                        'balance_after_paise' => $runningBalance,
                        'description' => 'Progressive bonus credited',
                        'reference_type' => BonusTransaction::class,
                        'reference_id' => $user->id,
                        'created_at' => now()->subDays(rand(1, 5)),
                    ]
                );
            }
        }

        // Update wallet balance
        if ($wallet) {
            $wallet->update(['balance_paise' => $runningBalance]);
        }
    }

    /**
     * Seed referral relationships
     */
    private function seedReferrals(): void
    {
        $user1 = User::where('email', 'user1@test.com')->first();
        $user2 = User::where('email', 'user2@test.com')->first();
        $user4 = User::where('email', 'user4@test.com')->first();
        $user5 = User::where('email', 'user5@test.com')->first();

        if (!$user1 || !$user2 || !$user4 || !$user5) {
            return;
        }

        // User 4 referred by User 1
        Referral::firstOrCreate(
            ['referrer_id' => $user1->id, 'referred_id' => $user4->id],
            [
                'bonus_earned' => 500,
                'status' => 'completed',
                'completed_at' => now()->subDays(10),
            ]
        );

        // User 5 referred by User 2
        Referral::firstOrCreate(
            ['referrer_id' => $user2->id, 'referred_id' => $user5->id],
            [
                'bonus_earned' => 500,
                'status' => 'completed',
                'completed_at' => now()->subDays(8),
            ]
        );

        $this->command->info('  ✓ Referrals seeded');
    }
}
