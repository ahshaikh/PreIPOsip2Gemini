<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 4.1: Update Ledger Accounts for Expense-Based Inventory Model
 *
 * KEY ACCOUNTING CHANGE:
 * - Inventory is NO LONGER treated as a balance-sheet asset
 * - Inventory purchase cost is EXPENSED IMMEDIATELY on acquisition
 * - Inventory quantity is tracked OPERATIONALLY (not financially) via BulkPurchase
 *
 * RATIONALE:
 * - Simpler accounting (no COGS calculation at sale time)
 * - More conservative (expenses recognized upfront)
 * - Inventory value is already tracked in bulk_purchases.value_remaining
 * - Financial truth: we spent the money, it's an expense
 *
 * REVENUE RECOGNITION:
 * - When shares are sold, the margin (user price - cost basis) is income
 * - This is recognized as SHARE_SALE_INCOME (discount margin)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update INVENTORY account to mark it as deprecated
        DB::table('ledger_accounts')
            ->where('code', 'INVENTORY')
            ->update([
                'description' => 'DEPRECATED: Inventory is now expensed immediately on purchase. ' .
                    'This account exists for historical compatibility only. ' .
                    'New purchases should use COST_OF_SHARES. ' .
                    'Inventory quantity is tracked operationally via bulk_purchases table.',
                'updated_at' => now(),
            ]);

        // Update COST_OF_SHARES to clarify its expanded role
        DB::table('ledger_accounts')
            ->where('code', 'COST_OF_SHARES')
            ->update([
                'description' => 'Immediate expense recognition for inventory purchases. ' .
                    'On bulk purchase: DEBIT this account, CREDIT BANK. ' .
                    'Represents the total cost of acquiring shares, expensed at time of purchase.',
                'updated_at' => now(),
            ]);

        // Update SHARE_SALE_INCOME to clarify margin recognition
        DB::table('ledger_accounts')
            ->where('code', 'SHARE_SALE_INCOME')
            ->update([
                'description' => 'Platform margin income from share sales. ' .
                    'Calculated as: User Payment - Proportional Cost Basis. ' .
                    'Shares are acquired at 12-15% discount; this margin is recognized as income when allocated.',
                'updated_at' => now(),
            ]);

        // Add INVENTORY_PURCHASE_EXPENSE if it doesn't exist (clearer naming)
        $exists = DB::table('ledger_accounts')
            ->where('code', 'INVENTORY_PURCHASE_EXPENSE')
            ->exists();

        if (!$exists) {
            DB::table('ledger_accounts')->insert([
                'code' => 'INVENTORY_PURCHASE_EXPENSE',
                'name' => 'Inventory Purchase Expense',
                'type' => 'EXPENSE',
                'description' => 'Direct expense for share inventory purchases. ' .
                    'Used for immediate expensing model where inventory cost is recognized upfront.',
                'is_system' => true,
                'normal_balance' => 'DEBIT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original descriptions
        DB::table('ledger_accounts')
            ->where('code', 'INVENTORY')
            ->update([
                'description' => 'Value of shares purchased in bulk and held for retail distribution.',
                'updated_at' => now(),
            ]);

        DB::table('ledger_accounts')
            ->where('code', 'COST_OF_SHARES')
            ->update([
                'description' => 'Cost basis of shares allocated to users (reduces inventory).',
                'updated_at' => now(),
            ]);

        DB::table('ledger_accounts')
            ->where('code', 'SHARE_SALE_INCOME')
            ->update([
                'description' => 'Revenue from selling shares to users at retail price.',
                'updated_at' => now(),
            ]);

        // Remove the new account
        DB::table('ledger_accounts')
            ->where('code', 'INVENTORY_PURCHASE_EXPENSE')
            ->delete();
    }
};
