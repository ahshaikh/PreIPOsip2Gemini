<?php

namespace Database\Seeders;

use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

class LedgerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ====================================================
            // ASSETS (1000 - 1999)
            // ====================================================
            [
                'code' => '1000',
                'name' => 'Cash at Bank (Operations)',
                'type' => 'asset',
                'description' => 'Main operational bank account',
                'is_system' => true,
            ],
            [
                'code' => '1200',
                'name' => 'User Chargeback Receivables',
                'type' => 'asset',
                'description' => 'Amounts owed by users after chargeback shortfall',
                'is_system' => true,
            ],
            [
                'code' => '1100',
                'name' => 'Payment Gateway Escrow',
                'type' => 'asset',
                'description' => 'Funds held by gateway (Stripe/Razorpay) before payout',
                'is_system' => true,
            ],

            // ====================================================
            // LIABILITIES (2000 - 2999)
            // ====================================================
            [
                'code' => '2000',
                'name' => 'User Wallet Balances',
                'type' => 'liability',
                'description' => 'Total funds owed to users in their virtual wallets',
                'is_system' => true,
            ],
            [
                'code' => '2100',
                'name' => 'Dispute Reserve Account',
                'type' => 'liability',
                'description' => 'Funds held back to cover potential chargebacks',
                'is_system' => true,
            ],
            [
                'code' => '2200',
                'name' => 'Unearned Subscription Revenue',
                'type' => 'liability',
                'description' => 'Prepaid subscriptions not yet recognized as revenue',
                'is_system' => true,
            ],

            // ====================================================
            // REVENUE (3000 - 3999)
            // ====================================================
            [
                'code' => '3000',
                'name' => 'Subscription Revenue',
                'type' => 'income',
                'description' => 'Income from active plan payments',
                'is_system' => true,
            ],
            [
                'code' => '3100',
                'name' => 'Platform Fees',
                'type' => 'income',
                'description' => 'Fees collected from transactions or penalties',
                'is_system' => true,
            ],

            // ====================================================
            // EXPENSES (4000 - 4999)
            // ====================================================
            [
                'code' => '4000',
                'name' => 'Milestone Bonus Expense',
                'type' => 'expense',
                'description' => 'Bonuses paid out to users for loyalty milestones',
                'is_system' => true,
            ],
            [
                'code' => '4100',
                'name' => 'Referral Bonus Expense',
                'type' => 'expense',
                'description' => 'Bonuses paid for user referrals',
                'is_system' => true,
            ],
            [
                'code' => '4200',
                'name' => 'Payment Gateway Fees',
                'type' => 'expense',
                'description' => 'Transaction fees kept by the payment provider',
                'is_system' => true,
            ],
            [
                'code' => '4300',
                'name' => 'Chargeback Losses',
                'type' => 'expense',
                'description' => 'Losses incurred from lost disputes and penalties',
                'is_system' => true,
            ],
        ];

        foreach ($accounts as $account) {
            LedgerAccount::updateOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}