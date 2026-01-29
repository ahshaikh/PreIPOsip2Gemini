<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX 18: Wallet Locking/Reservation System
 *
 * CRITICAL FIX: Prevents users from spending funds that are reserved for pending withdrawals
 *
 * Problem: Users could:
 * 1. Request withdrawal of $1000
 * 2. While admin reviewing, invest that same $1000
 * 3. Withdrawal approved -> insufficient funds
 *
 * Solution: Lock funds when withdrawal requested, unlock on rejection/completion
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Add locked balance tracking only if columns don't exist
            if (!Schema::hasColumn('wallets', 'locked_balance_paise')) {
            if (Schema::hasColumn('wallets', 'balance_paise')) {
                $table->bigInteger('locked_balance_paise')->default(0)->after('balance_paise');
            } else {
                $table->bigInteger('locked_balance_paise')->default(0);
            }
                }
            if (!Schema::hasColumn('wallets', 'locked_balance')) {
                if (Schema::hasColumn('wallets', 'balance')) {
                    $table->decimal('locked_balance', 15, 2)->default(0)->after('balance');
                }
            }
        });

        // Add index if it doesn't exist
        if (!$this->indexExists('wallets', 'idx_wallet_locked')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->index(['user_id', 'locked_balance_paise'], 'idx_wallet_locked');
            });
        }

        // Add lock tracking fields to withdrawals
        Schema::table('withdrawals', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawals', 'funds_locked')) {
                $table->boolean('funds_locked')->default(false)->after('status');
            }
            if (!Schema::hasColumn('withdrawals', 'funds_locked_at')) {
                $table->timestamp('funds_locked_at')->nullable()->after('funds_locked');
            }
            if (!Schema::hasColumn('withdrawals', 'funds_unlocked_at')) {
                $table->timestamp('funds_unlocked_at')->nullable()->after('funds_locked_at');
            }
        });

        // Add index if it doesn't exist
        if (!$this->indexExists('withdrawals', 'idx_withdrawal_locked')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                $table->index(['user_id', 'funds_locked'], 'idx_withdrawal_locked');
            });
        }

        // Create fund_locks table for audit trail
        if (!Schema::hasTable('fund_locks')) {
            Schema::create('fund_locks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');

                // What type of lock (withdrawal, investment_hold, etc.)
                $table->enum('lock_type', ['withdrawal', 'investment_hold', 'penalty_hold', 'manual']);

                // Reference to the entity that caused the lock
                $table->string('lockable_type'); // Withdrawal, Subscription, etc.
                $table->unsignedBigInteger('lockable_id');

                // Amount locked (in paise for precision)
                $table->bigInteger('amount_paise');
                $table->decimal('amount', 15, 2);

                // Lock status
                $table->enum('status', ['active', 'released', 'expired'])->default('active');

                // Timestamps
                $table->timestamp('locked_at');
                $table->timestamp('released_at')->nullable();
                $table->timestamp('expires_at')->nullable();

                // Who performed the action
                $table->unsignedBigInteger('locked_by')->nullable();
                $table->unsignedBigInteger('released_by')->nullable();

                // Metadata
                $table->text('reason')->nullable();
                $table->json('metadata')->nullable();

                $table->timestamps();

                // Indexes
                $table->index(['user_id', 'status']);
                $table->index(['lockable_type', 'lockable_id']);
                $table->index(['status', 'expires_at']);
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $query = "SELECT COUNT(*) as count FROM information_schema.statistics
                  WHERE table_schema = ? AND table_name = ? AND index_name = ?";

        $result = $connection->selectOne($query, [$databaseName, $table, $index]);

        return $result->count > 0;
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_locks');

        Schema::table('withdrawals', function (Blueprint $table) {
            if ($this->indexExists('withdrawals', 'idx_withdrawal_locked')) {
                $table->dropIndex('idx_withdrawal_locked');
            }
            $table->dropColumn(['funds_locked', 'funds_locked_at', 'funds_unlocked_at']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            if ($this->indexExists('wallets', 'idx_wallet_locked')) {
                $table->dropIndex('idx_wallet_locked');
            }
            $table->dropColumn(['locked_balance_paise', 'locked_balance']);
        });
    }
};