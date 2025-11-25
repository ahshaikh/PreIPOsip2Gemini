<?php
// V-PERFORMANCE-INDEXES - Add missing database indexes for performance optimization
// V-FIX: Added column existence checks to prevent migration failures

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Only create composite index if both columns exist
                if ($this->hasColumn('users', 'status') && $this->hasColumn('users', 'kyc_status')) {
                    if (!$this->hasIndex('users', 'users_status_kyc_status_index')) {
                        $table->index(['status', 'kyc_status'], 'users_status_kyc_status_index');
                    }
                }
                if ($this->hasColumn('users', 'referral_code') && !$this->hasIndex('users', 'users_referral_code_index')) {
                    $table->index('referral_code', 'users_referral_code_index');
                }
                if ($this->hasColumn('users', 'referred_by') && !$this->hasIndex('users', 'users_referred_by_index')) {
                    $table->index('referred_by', 'users_referred_by_index');
                }
                if ($this->hasColumn('users', 'status') && !$this->hasIndex('users', 'users_status_index')) {
                    $table->index('status', 'users_status_index');
                }
            });
        }

        // Payments table indexes
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if ($this->hasColumn('payments', 'user_id') && $this->hasColumn('payments', 'status')) {
                    if (!$this->hasIndex('payments', 'payments_user_status_index')) {
                        $table->index(['user_id', 'status'], 'payments_user_status_index');
                    }
                }
                if ($this->hasColumn('payments', 'status') && $this->hasColumn('payments', 'created_at')) {
                    if (!$this->hasIndex('payments', 'payments_status_created_index')) {
                        $table->index(['status', 'created_at'], 'payments_status_created_index');
                    }
                }
                if ($this->hasColumn('payments', 'gateway_payment_id') && !$this->hasIndex('payments', 'payments_gateway_payment_index')) {
                    $table->index('gateway_payment_id', 'payments_gateway_payment_index');
                }
            });
        }

        // Subscriptions table indexes
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if ($this->hasColumn('subscriptions', 'user_id') && $this->hasColumn('subscriptions', 'status')) {
                    if (!$this->hasIndex('subscriptions', 'subscriptions_user_status_index')) {
                        $table->index(['user_id', 'status'], 'subscriptions_user_status_index');
                    }
                }
                if ($this->hasColumn('subscriptions', 'next_payment_date') && !$this->hasIndex('subscriptions', 'subscriptions_next_payment_index')) {
                    $table->index('next_payment_date', 'subscriptions_next_payment_index');
                }
            });
        }

        // User investments table indexes
        if (Schema::hasTable('user_investments')) {
            Schema::table('user_investments', function (Blueprint $table) {
                if ($this->hasColumn('user_investments', 'user_id') && $this->hasColumn('user_investments', 'product_id')) {
                    if (!$this->hasIndex('user_investments', 'investments_user_product_index')) {
                        $table->index(['user_id', 'product_id'], 'investments_user_product_index');
                    }
                }
                if ($this->hasColumn('user_investments', 'status') && !$this->hasIndex('user_investments', 'investments_status_index')) {
                    $table->index('status', 'investments_status_index');
                }
            });
        }

        // Wallets table indexes
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if ($this->hasColumn('wallets', 'user_id')) {
                    if (!$this->hasIndex('wallets', 'wallets_user_index')) {
                        $table->index('user_id', 'wallets_user_index');
                    }
                }
            });
        }

        // Withdrawals table indexes
        if (Schema::hasTable('withdrawals')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                if ($this->hasColumn('withdrawals', 'user_id') && $this->hasColumn('withdrawals', 'status')) {
                    if (!$this->hasIndex('withdrawals', 'withdrawals_user_status_index')) {
                        $table->index(['user_id', 'status'], 'withdrawals_user_status_index');
                    }
                }
                if ($this->hasColumn('withdrawals', 'status') && $this->hasColumn('withdrawals', 'created_at')) {
                    if (!$this->hasIndex('withdrawals', 'withdrawals_status_created_index')) {
                        $table->index(['status', 'created_at'], 'withdrawals_status_created_index');
                    }
                }
            });
        }

        // KYC documents table indexes
        if (Schema::hasTable('kyc_documents')) {
            Schema::table('kyc_documents', function (Blueprint $table) {
                if ($this->hasColumn('kyc_documents', 'user_id') && $this->hasColumn('kyc_documents', 'status')) {
                    if (!$this->hasIndex('kyc_documents', 'kyc_user_status_index')) {
                        $table->index(['user_id', 'status'], 'kyc_user_status_index');
                    }
                }
            });
        }

        // Activity logs table indexes
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if ($this->hasColumn('activity_logs', 'user_id') && $this->hasColumn('activity_logs', 'created_at')) {
                    if (!$this->hasIndex('activity_logs', 'activity_user_created_index')) {
                        $table->index(['user_id', 'created_at'], 'activity_user_created_index');
                    }
                }
            });
        }

        // Notifications table indexes
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if ($this->hasColumn('notifications', 'user_id') && $this->hasColumn('notifications', 'is_read')) {
                    if (!$this->hasIndex('notifications', 'notifications_user_read_index')) {
                        $table->index(['user_id', 'is_read'], 'notifications_user_read_index');
                    }
                }
            });
        }

        // Bonus transactions table indexes
        if (Schema::hasTable('bonus_transactions')) {
            Schema::table('bonus_transactions', function (Blueprint $table) {
                if ($this->hasColumn('bonus_transactions', 'user_id') && $this->hasColumn('bonus_transactions', 'type')) {
                    if (!$this->hasIndex('bonus_transactions', 'bonus_tx_user_type_index')) {
                        $table->index(['user_id', 'type'], 'bonus_tx_user_type_index');
                    }
                }
            });
        }

        // Products table indexes
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if ($this->hasColumn('products', 'status') && !$this->hasIndex('products', 'products_status_index')) {
                    $table->index('status', 'products_status_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexSafely('users', 'users_status_kyc_status_index');
        $this->dropIndexSafely('users', 'users_referral_code_index');
        $this->dropIndexSafely('users', 'users_referred_by_index');
        $this->dropIndexSafely('users', 'users_status_index');

        $this->dropIndexSafely('payments', 'payments_user_status_index');
        $this->dropIndexSafely('payments', 'payments_status_created_index');
        $this->dropIndexSafely('payments', 'payments_gateway_payment_index');

        $this->dropIndexSafely('subscriptions', 'subscriptions_user_status_index');
        $this->dropIndexSafely('subscriptions', 'subscriptions_next_payment_index');

        $this->dropIndexSafely('user_investments', 'investments_user_product_index');
        $this->dropIndexSafely('user_investments', 'investments_status_index');

        $this->dropIndexSafely('wallets', 'wallets_user_index');

        $this->dropIndexSafely('withdrawals', 'withdrawals_user_status_index');
        $this->dropIndexSafely('withdrawals', 'withdrawals_status_created_index');

        $this->dropIndexSafely('kyc_documents', 'kyc_user_status_index');

        $this->dropIndexSafely('activity_logs', 'activity_user_created_index');

        $this->dropIndexSafely('notifications', 'notifications_user_read_index');

        $this->dropIndexSafely('bonus_transactions', 'bonus_tx_user_type_index');

        $this->dropIndexSafely('products', 'products_status_index');
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Table might not exist or other error
        }
        return false;
    }

    /**
     * Check if a column exists on a table
     */
    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Safely drop an index if it exists
     */
    private function dropIndexSafely(string $table, string $indexName): void
    {
        if (Schema::hasTable($table) && $this->hasIndex($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
