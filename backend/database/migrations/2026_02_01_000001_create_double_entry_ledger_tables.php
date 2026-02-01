<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * DOUBLE-ENTRY LEDGER SYSTEM
 *
 * This migration creates a true double-entry accounting ledger that:
 * - Mirrors the platform's real bank account
 * - Tracks all assets, liabilities, equity, income, and expenses
 * - Enforces the fundamental accounting equation: Assets = Liabilities + Equity
 * - Ensures SUM(debits) == SUM(credits) for every journal entry
 *
 * INVARIANTS:
 * - Ledger entries are IMMUTABLE after creation (append-only)
 * - No balance columns exist - balances are always computed from lines
 * - Every business event creates exactly one ledger entry with balanced lines
 *
 * @see App\Models\LedgerAccount
 * @see App\Models\LedgerEntry
 * @see App\Models\LedgerLine
 * @see App\Services\DoubleEntryLedgerService
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =========================================================================
        // LEDGER_ACCOUNTS - Chart of Accounts
        // =========================================================================
        // Defines all accounts in the system. System accounts cannot be deleted.
        // Account types follow standard accounting:
        // - ASSET: Things the platform owns (cash, inventory)
        // - LIABILITY: Things the platform owes (user wallets, bonuses)
        // - EQUITY: Owner's stake (capital contributions)
        // - INCOME: Money earned (subscriptions, fees)
        // - EXPENSE: Money spent (marketing, operations)
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Unique account code (e.g., BANK, INVENTORY)');
            $table->string('name', 255)->comment('Human-readable account name');
            $table->enum('type', ['ASSET', 'LIABILITY', 'EQUITY', 'INCOME', 'EXPENSE'])
                  ->comment('Account type per standard accounting');
            $table->boolean('is_system')->default(true)
                  ->comment('System accounts cannot be deleted');
            $table->text('description')->nullable()
                  ->comment('Detailed description of account purpose');
            $table->string('normal_balance', 10)->default('DEBIT')
                  ->comment('Normal balance direction: DEBIT or CREDIT');
            $table->timestamps();

            $table->index('type');
            $table->index('is_system');
        });

        // =========================================================================
        // LEDGER_ENTRIES - Journal Entry Headers
        // =========================================================================
        // One ledger entry = one business event (e.g., inventory purchase, user deposit)
        // Entries are IMMUTABLE - corrections require reversal entries
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type', 100)
                  ->comment('Type of business event (e.g., bulk_purchase, user_deposit)');
            $table->unsignedBigInteger('reference_id')
                  ->comment('ID of the related business entity');
            $table->text('description')->nullable()
                  ->comment('Human-readable description of the entry');
            $table->date('entry_date')->comment('Date of the business event');
            $table->unsignedBigInteger('created_by')->nullable()
                  ->comment('Admin/user who created this entry');
            $table->boolean('is_reversal')->default(false)
                  ->comment('True if this entry reverses another entry');
            $table->unsignedBigInteger('reverses_entry_id')->nullable()
                  ->comment('ID of the entry being reversed (if is_reversal=true)');
            $table->timestamp('created_at')->useCurrent();

            // Entries are immutable - no updated_at column
            // Corrections are made via reversal entries

            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_date');
            $table->index('is_reversal');
            $table->foreign('reverses_entry_id')
                  ->references('id')
                  ->on('ledger_entries')
                  ->onDelete('restrict');
        });

        // =========================================================================
        // LEDGER_LINES - Double-Entry Rows
        // =========================================================================
        // Each ledger entry has multiple lines (minimum 2) that must balance:
        // SUM(debits) == SUM(credits)
        //
        // This constraint is enforced at the application level because MySQL
        // doesn't support CHECK constraints that span multiple rows.
        Schema::create('ledger_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ledger_entry_id')
                  ->comment('Parent journal entry');
            $table->unsignedBigInteger('ledger_account_id')
                  ->comment('Account being debited or credited');
            $table->enum('direction', ['DEBIT', 'CREDIT'])
                  ->comment('Whether this line debits or credits the account');
            $table->decimal('amount', 18, 2)->unsigned()
                  ->comment('Amount in rupees (always positive)');
            $table->timestamp('created_at')->useCurrent();

            // No updates allowed - immutable ledger
            // CHECK constraint for positive amounts
            // Note: MySQL CHECK constraints are parsed but not enforced in some versions
            // Application-level validation is required

            $table->foreign('ledger_entry_id')
                  ->references('id')
                  ->on('ledger_entries')
                  ->onDelete('restrict'); // Cannot delete entries with lines

            $table->foreign('ledger_account_id')
                  ->references('id')
                  ->on('ledger_accounts')
                  ->onDelete('restrict'); // Cannot delete accounts with lines

            $table->index('ledger_entry_id');
            $table->index('ledger_account_id');
            $table->index('direction');
        });

        // =========================================================================
        // SEED REQUIRED SYSTEM ACCOUNTS
        // =========================================================================
        // These accounts are required for platform operation and cannot be deleted
        $this->seedSystemAccounts();
    }

    /**
     * Seed the required system accounts (Chart of Accounts).
     */
    private function seedSystemAccounts(): void
    {
        $now = now();

        $accounts = [
            // ASSETS - Things the platform owns
            [
                'code' => 'BANK',
                'name' => 'Platform Bank Account',
                'type' => 'ASSET',
                'description' => 'Primary bank account holding platform capital. Mirrors real bank balance.',
                'normal_balance' => 'DEBIT',
            ],
            [
                'code' => 'INVENTORY',
                'name' => 'Pre-IPO Share Inventory',
                'type' => 'ASSET',
                'description' => 'Value of shares purchased in bulk and held for retail distribution.',
                'normal_balance' => 'DEBIT',
            ],
            [
                'code' => 'ACCOUNTS_RECEIVABLE',
                'name' => 'Accounts Receivable',
                'type' => 'ASSET',
                'description' => 'Amounts owed to the platform (pending payments, etc.)',
                'normal_balance' => 'DEBIT',
            ],

            // LIABILITIES - Things the platform owes
            [
                'code' => 'USER_WALLET_LIABILITY',
                'name' => 'User Wallet Balances',
                'type' => 'LIABILITY',
                'description' => 'Total funds held in user wallets. Platform owes this to users.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'BONUS_LIABILITY',
                'name' => 'Bonus Balances',
                'type' => 'LIABILITY',
                'description' => 'Accrued bonus obligations owed to users.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'TDS_PAYABLE',
                'name' => 'TDS Payable',
                'type' => 'LIABILITY',
                'description' => 'Tax Deducted at Source to be remitted to government.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'REFUNDS_PAYABLE',
                'name' => 'Refunds Payable',
                'type' => 'LIABILITY',
                'description' => 'Pending refunds owed to users.',
                'normal_balance' => 'CREDIT',
            ],

            // EQUITY - Owner's stake
            [
                'code' => 'OWNER_CAPITAL',
                'name' => 'Owner Capital',
                'type' => 'EQUITY',
                'description' => 'Capital contributed by platform owners.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'RETAINED_EARNINGS',
                'name' => 'Retained Earnings',
                'type' => 'EQUITY',
                'description' => 'Accumulated profits retained in the business.',
                'normal_balance' => 'CREDIT',
            ],

            // INCOME - Money earned
            [
                'code' => 'SUBSCRIPTION_INCOME',
                'name' => 'Subscription Revenue',
                'type' => 'INCOME',
                'description' => 'Revenue from user subscriptions and investments.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'PLATFORM_FEES',
                'name' => 'Platform Fees',
                'type' => 'INCOME',
                'description' => 'Transaction fees, service charges, and other fee income.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'SHARE_SALE_INCOME',
                'name' => 'Share Sale Income',
                'type' => 'INCOME',
                'description' => 'Revenue from selling shares to users at retail price.',
                'normal_balance' => 'CREDIT',
            ],
            [
                'code' => 'INTEREST_INCOME',
                'name' => 'Interest Income',
                'type' => 'INCOME',
                'description' => 'Interest earned on platform funds.',
                'normal_balance' => 'CREDIT',
            ],

            // EXPENSES - Money spent
            [
                'code' => 'MARKETING_EXPENSE',
                'name' => 'Marketing & Bonus Cost',
                'type' => 'EXPENSE',
                'description' => 'Costs for bonuses, referral payouts, and marketing campaigns.',
                'normal_balance' => 'DEBIT',
            ],
            [
                'code' => 'OPERATING_EXPENSES',
                'name' => 'Operating Expenses',
                'type' => 'EXPENSE',
                'description' => 'Salaries, rent, utilities, and other operational costs.',
                'normal_balance' => 'DEBIT',
            ],
            [
                'code' => 'COST_OF_SHARES',
                'name' => 'Cost of Shares Sold',
                'type' => 'EXPENSE',
                'description' => 'Cost basis of shares allocated to users (reduces inventory).',
                'normal_balance' => 'DEBIT',
            ],
            [
                'code' => 'PAYMENT_GATEWAY_FEES',
                'name' => 'Payment Gateway Fees',
                'type' => 'EXPENSE',
                'description' => 'Fees charged by Razorpay, PayU, and other gateways.',
                'normal_balance' => 'DEBIT',
            ],
        ];

        foreach ($accounts as $account) {
            DB::table('ledger_accounts')->insert(array_merge($account, [
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_lines');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
