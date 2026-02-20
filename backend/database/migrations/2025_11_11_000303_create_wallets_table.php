<?php
// V-PHASE3-1730-064 (Created) | V-FINAL-1730-615 (Consolidated)
// V-CANONICAL-PAISE-2026: Paise-only schema - no decimal columns

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // CANONICAL: Paise-only wallet schema (no decimal columns)
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->bigInteger('balance_paise')->default(0);
            $table->bigInteger('locked_balance_paise')->default(0);
            $table->timestamps();
        });

        // V-PHASE3-1730-065: CANONICAL paise-only transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');

            $table->string('type'); // deposit, withdrawal, bonus_credit, etc.
            $table->string('status')->default('completed'); // pending, completed

            // CANONICAL: Integer paise columns only
            $table->bigInteger('amount_paise')->default(0);
            $table->bigInteger('balance_before_paise')->default(0);
            $table->bigInteger('balance_after_paise')->default(0);
            $table->bigInteger('tds_deducted_paise')->default(0);

            $table->text('description');

            $table->nullableMorphs('reference'); // Links to Payment, Withdrawal, Bonus

            // Reversal tracking (immutability enforcement)
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversed_by_transaction_id')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('paired_transaction_id')->nullable();

            $table->timestamps();
        });

        // V-PHASE3-1730-066: Withdrawals (paise for monetary, decimal for fees/rates)
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');

            // CANONICAL: Paise for monetary values
            $table->bigInteger('amount_paise')->default(0);
            $table->bigInteger('fee_paise')->default(0);
            $table->bigInteger('tds_deducted_paise')->default(0);
            $table->bigInteger('net_amount_paise')->default(0); // amount - fee - tds

            $table->string('status')->default('pending');
            $table->json('bank_details');

            $table->foreignId('admin_id')->nullable()->constrained('users');
            $table->string('utr_number')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });

        // CANONICAL: Paise-based constraints
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT balance_paise_must_be_positive CHECK (balance_paise >= 0)');
            DB::statement('ALTER TABLE wallets ADD CONSTRAINT locked_balance_paise_must_be_positive CHECK (locked_balance_paise >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
    }
};