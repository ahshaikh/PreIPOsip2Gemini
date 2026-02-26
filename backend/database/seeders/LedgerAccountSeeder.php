<?php

namespace Database\Seeders;

use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

class LedgerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [

            // ============================
            // ASSETS
            // ============================
            [
                'code' => 'BANK',
                'name' => 'Cash at Bank (Operations)',
                'type' => 'asset',
                'description' => 'Main operational bank account',
                'is_system' => true,
            ],
            [
                'code' => 'ACCOUNTS_RECEIVABLE',
                'name' => 'User Chargeback Receivables',
                'type' => 'asset',
                'description' => 'Amounts owed by users after chargeback shortfall',
                'is_system' => true,
            ],
            [
                'code' => 'INVENTORY',
                'name' => 'Pre-IPO Share Inventory',
                'type' => 'asset',
                'description' => 'Inventory of shares available for allocation',
                'is_system' => true,
            ],

            // ============================
            // LIABILITIES
            // ============================
            [
                'code' => 'USER_WALLET_LIABILITY',
                'name' => 'User Wallet Balances',
                'type' => 'liability',
                'description' => 'Total funds owed to users in their virtual wallets',
                'is_system' => true,
            ],
            [
                'code' => 'BONUS_LIABILITY',
                'name' => 'Bonus Payable to Users',
                'type' => 'liability',
                'description' => 'Accrued but unpaid bonuses',
                'is_system' => true,
            ],
            [
                'code' => 'TDS_PAYABLE',
                'name' => 'TDS Payable',
                'type' => 'liability',
                'description' => 'Tax deducted at source payable to authorities',
                'is_system' => true,
            ],
            [
                'code' => 'REFUNDS_PAYABLE',
                'name' => 'Refunds Payable',
                'type' => 'liability',
                'description' => 'Refunds owed to users',
                'is_system' => true,
            ],

            // ============================
            // EQUITY
            // ============================
            [
                'code' => 'OWNER_CAPITAL',
                'name' => 'Owner Capital',
                'type' => 'equity',
                'description' => 'Initial capital invested by founders',
                'is_system' => true,
            ],
            [
                'code' => 'RETAINED_EARNINGS',
                'name' => 'Retained Earnings',
                'type' => 'equity',
                'description' => 'Accumulated profits retained in business',
                'is_system' => true,
            ],

            // ============================
            // REVENUE
            // ============================
            [
                'code' => 'SUBSCRIPTION_INCOME',
                'name' => 'Subscription Revenue',
                'type' => 'income',
                'description' => 'Income from active plan payments',
                'is_system' => true,
            ],
            [
                'code' => 'PLATFORM_FEES',
                'name' => 'Platform Fees',
                'type' => 'income',
                'description' => 'Fees collected from transactions or penalties',
                'is_system' => true,
            ],
            [
                'code' => 'SHARE_SALE_INCOME',
                'name' => 'Share Sale Income',
                'type' => 'income',
                'description' => 'Income from share sales',
                'is_system' => true,
            ],
            [
                'code' => 'INTEREST_INCOME',
                'name' => 'Interest Income',
                'type' => 'income',
                'description' => 'Interest earned on deposits',
                'is_system' => true,
            ],

            // ============================
            // EXPENSES
            // ============================
            [
                'code' => 'MARKETING_EXPENSE',
                'name' => 'Marketing Expense',
                'type' => 'expense',
                'description' => 'Marketing and promotional costs',
                'is_system' => true,
            ],
            [
                'code' => 'OPERATING_EXPENSES',
                'name' => 'Operating Expenses',
                'type' => 'expense',
                'description' => 'General operating expenses',
                'is_system' => true,
            ],
            [
                'code' => 'COST_OF_SHARES',
                'name' => 'Cost of Shares Sold',
                'type' => 'expense',
                'description' => 'Cost basis of shares allocated to users',
                'is_system' => true,
            ],
            [
                'code' => 'PAYMENT_GATEWAY_FEES',
                'name' => 'Payment Gateway Fees',
                'type' => 'expense',
                'description' => 'Transaction fees kept by payment provider',
                'is_system' => true,
            ],
            [
                'code' => 'INVENTORY_PURCHASE_EXPENSE',
                'name' => 'Inventory Purchase Expense',
                'type' => 'expense',
                'description' => 'Cost of acquiring share inventory',
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