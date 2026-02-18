<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallet: add locked_balance_paise only (paise model canonical)
        // Schema::table('wallets', function (Blueprint $table) {
        //     $table->bigInteger('locked_balance_paise')
        //         ->default(0)
        //         ->after('balance_paise');
 
        //     $table->index(['user_id', 'locked_balance_paise'], 'idx_wallet_locked');
        // });

        // Withdrawal locking fields
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->boolean('funds_locked')->default(false)->after('status');
            $table->timestamp('funds_locked_at')->nullable()->after('funds_locked');
            $table->timestamp('funds_unlocked_at')->nullable()->after('funds_locked_at');

            $table->index(['user_id', 'funds_locked'], 'idx_withdrawal_locked');
        });

        // Audit table
        Schema::create('fund_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('lock_type', [
                'withdrawal',
                'investment_hold',
                'penalty_hold',
                'manual'
            ]);

            $table->string('lockable_type');
            $table->unsignedBigInteger('lockable_id');

            $table->bigInteger('amount_paise');
            $table->enum('status', ['active', 'released', 'expired'])->default('active');

            $table->timestamp('locked_at');
            $table->timestamp('released_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedBigInteger('locked_by')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();

            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['lockable_type', 'lockable_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_locks');

        Schema::table('withdrawals', function (Blueprint $table) {

            $table->dropColumn([
                'funds_locked',
                'funds_locked_at',
                'funds_unlocked_at'
            ]);
        });

        // Schema::table('wallets', function (Blueprint $table) {
        //     $table->dropIndex('idx_wallet_locked');
        //     $table->dropColumn('locked_balance_paise');
        // });
    }
};
