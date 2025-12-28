<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PROTOCOL:
     * - Double-entry accounting for admin financial operations
     * - Every entry is immutable (no updates/deletes allowed)
     * - Balance is CALCULATED from entries, not stored separately
     * - Accounts: cash, inventory, liabilities, revenue, expenses
     * - Equation: Assets (cash + inventory) = Liabilities + Equity (revenue - expenses)
     */
    public function up(): void
    {
        Schema::create('admin_ledger_entries', function (Blueprint $table) {
            $table->id();

            // Account classification
            $table->enum('account', [
                'cash',         // Liquid money admin has
                'inventory',    // Money spent on bulk purchases
                'liabilities',  // Money owed (bonuses, campaign discounts, withdrawals)
                'revenue',      // Money earned from payments
                'expenses'      // Money paid out (bonuses, withdrawals)
            ]);

            // Entry type
            $table->enum('type', ['debit', 'credit']);

            // Amount in paise (integer for precision)
            $table->bigInteger('amount_paise');

            // Balance snapshots (in paise)
            $table->bigInteger('balance_before_paise');
            $table->bigInteger('balance_after_paise');

            // Provenance (why did this entry occur?)
            $table->string('reference_type'); // 'payment', 'bulk_purchase', 'campaign_usage', 'referral', 'withdrawal'
            $table->unsignedBigInteger('reference_id');

            // Description for audit
            $table->text('description');

            // Link to paired entry (debit has corresponding credit)
            $table->foreignId('entry_pair_id')->nullable()->constrained('admin_ledger_entries');

            // Timestamps (entries are immutable)
            $table->timestamps();

            // Indexes
            $table->index('account');
            $table->index(['account', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_pair_id');
        });

        // Add database constraint to prevent balance manipulation
        DB::statement('
            ALTER TABLE admin_ledger_entries
            ADD CONSTRAINT check_balance_consistency
            CHECK (
                (type = "debit" AND balance_after_paise = balance_before_paise + amount_paise)
                OR
                (type = "credit" AND balance_after_paise = balance_before_paise - amount_paise)
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_ledger_entries');
    }
};
