<?php
// V-PERFORMANCE-INDEXES - Add missing database indexes for performance optimization

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
                // Composite index for common queries
                if (!$this->hasIndex('users', 'users_status_kyc_status_index')) {
                    $table->index(['status', 'kyc_status'], 'users_status_kyc_status_index');
                }
                if (!$this->hasIndex('users', 'users_referral_code_index')) {
                    $table->index('referral_code', 'users_referral_code_index');
                }
                if (!$this->hasIndex('users', 'users_referred_by_index')) {
                    $table->index('referred_by', 'users_referred_by_index');
                }
                if (!$this->hasIndex('users', 'users_created_at_index')) {
                    $table->index('created_at', 'users_created_at_index');
                }
            });
        }

        // Payments table indexes
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->hasIndex('payments', 'payments_user_status_index')) {
                    $table->index(['user_id', 'status'], 'payments_user_status_index');
                }
                if (!$this->hasIndex('payments', 'payments_status_created_index')) {
                    $table->index(['status', 'created_at'], 'payments_status_created_index');
                }
                if (!$this->hasIndex('payments', 'payments_gateway_ref_index')) {
                    $table->index('gateway_reference', 'payments_gateway_ref_index');
                }
            });
        }

        // Subscriptions table indexes
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!$this->hasIndex('subscriptions', 'subscriptions_user_status_index')) {
                    $table->index(['user_id', 'status'], 'subscriptions_user_status_index');
                }
                if (!$this->hasIndex('subscriptions', 'subscriptions_next_payment_index')) {
                    $table->index('next_payment_date', 'subscriptions_next_payment_index');
                }
            });
        }

        // User investments table indexes
        if (Schema::hasTable('user_investments')) {
            Schema::table('user_investments', function (Blueprint $table) {
                if (!$this->hasIndex('user_investments', 'investments_user_product_index')) {
                    $table->index(['user_id', 'product_id'], 'investments_user_product_index');
                }
                if (!$this->hasIndex('user_investments', 'investments_status_index')) {
                    $table->index('status', 'investments_status_index');
                }
            });
        }

        // Wallets table indexes
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if (!$this->hasIndex('wallets', 'wallets_user_type_index')) {
                    $table->index(['user_id', 'wallet_type'], 'wallets_user_type_index');
                }
            });
        }

        // Wallet transactions table indexes
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                if (!$this->hasIndex('wallet_transactions', 'wallet_tx_wallet_type_index')) {
                    $table->index(['wallet_id', 'type'], 'wallet_tx_wallet_type_index');
                }
                if (!$this->hasIndex('wallet_transactions', 'wallet_tx_created_index')) {
                    $table->index('created_at', 'wallet_tx_created_index');
                }
            });
        }

        // Withdrawals table indexes
        if (Schema::hasTable('withdrawals')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                if (!$this->hasIndex('withdrawals', 'withdrawals_user_status_index')) {
                    $table->index(['user_id', 'status'], 'withdrawals_user_status_index');
                }
                if (!$this->hasIndex('withdrawals', 'withdrawals_status_created_index')) {
                    $table->index(['status', 'created_at'], 'withdrawals_status_created_index');
                }
            });
        }

        // KYC documents table indexes
        if (Schema::hasTable('kyc_documents')) {
            Schema::table('kyc_documents', function (Blueprint $table) {
                if (!$this->hasIndex('kyc_documents', 'kyc_user_status_index')) {
                    $table->index(['user_id', 'status'], 'kyc_user_status_index');
                }
                if (!$this->hasIndex('kyc_documents', 'kyc_type_status_index')) {
                    $table->index(['document_type', 'status'], 'kyc_type_status_index');
                }
            });
        }

        // Support tickets table indexes
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                if (!$this->hasIndex('support_tickets', 'tickets_user_status_index')) {
                    $table->index(['user_id', 'status'], 'tickets_user_status_index');
                }
                if (!$this->hasIndex('support_tickets', 'tickets_status_priority_index')) {
                    $table->index(['status', 'priority'], 'tickets_status_priority_index');
                }
            });
        }

        // Activity logs table indexes
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (!$this->hasIndex('activity_logs', 'activity_user_created_index')) {
                    $table->index(['user_id', 'created_at'], 'activity_user_created_index');
                }
                if (!$this->hasIndex('activity_logs', 'activity_type_created_index')) {
                    $table->index(['activity_type', 'created_at'], 'activity_type_created_index');
                }
            });
        }

        // Notifications table indexes
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!$this->hasIndex('notifications', 'notifications_user_read_index')) {
                    $table->index(['user_id', 'is_read'], 'notifications_user_read_index');
                }
            });
        }

        // Bonuses table indexes
        if (Schema::hasTable('bonuses')) {
            Schema::table('bonuses', function (Blueprint $table) {
                if (!$this->hasIndex('bonuses', 'bonuses_user_type_index')) {
                    $table->index(['user_id', 'type'], 'bonuses_user_type_index');
                }
                if (!$this->hasIndex('bonuses', 'bonuses_status_index')) {
                    $table->index('status', 'bonuses_status_index');
                }
            });
        }

        // Products table indexes
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!$this->hasIndex('products', 'products_status_index')) {
                    $table->index('status', 'products_status_index');
                }
                if (!$this->hasIndex('products', 'products_category_status_index')) {
                    $table->index(['category', 'status'], 'products_category_status_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndexIfExists('users_status_kyc_status_index');
                $table->dropIndexIfExists('users_referral_code_index');
                $table->dropIndexIfExists('users_referred_by_index');
                $table->dropIndexIfExists('users_created_at_index');
            });
        }

        // Payments table
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndexIfExists('payments_user_status_index');
                $table->dropIndexIfExists('payments_status_created_index');
                $table->dropIndexIfExists('payments_gateway_ref_index');
            });
        }

        // Subscriptions table
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndexIfExists('subscriptions_user_status_index');
                $table->dropIndexIfExists('subscriptions_next_payment_index');
            });
        }

        // User investments table
        if (Schema::hasTable('user_investments')) {
            Schema::table('user_investments', function (Blueprint $table) {
                $table->dropIndexIfExists('investments_user_product_index');
                $table->dropIndexIfExists('investments_status_index');
            });
        }

        // Wallets table
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->dropIndexIfExists('wallets_user_type_index');
            });
        }

        // Wallet transactions table
        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropIndexIfExists('wallet_tx_wallet_type_index');
                $table->dropIndexIfExists('wallet_tx_created_index');
            });
        }

        // Withdrawals table
        if (Schema::hasTable('withdrawals')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                $table->dropIndexIfExists('withdrawals_user_status_index');
                $table->dropIndexIfExists('withdrawals_status_created_index');
            });
        }

        // KYC documents table
        if (Schema::hasTable('kyc_documents')) {
            Schema::table('kyc_documents', function (Blueprint $table) {
                $table->dropIndexIfExists('kyc_user_status_index');
                $table->dropIndexIfExists('kyc_type_status_index');
            });
        }

        // Support tickets table
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropIndexIfExists('tickets_user_status_index');
                $table->dropIndexIfExists('tickets_status_priority_index');
            });
        }

        // Activity logs table
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndexIfExists('activity_user_created_index');
                $table->dropIndexIfExists('activity_type_created_index');
            });
        }

        // Notifications table
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndexIfExists('notifications_user_read_index');
            });
        }

        // Bonuses table
        if (Schema::hasTable('bonuses')) {
            Schema::table('bonuses', function (Blueprint $table) {
                $table->dropIndexIfExists('bonuses_user_type_index');
                $table->dropIndexIfExists('bonuses_status_index');
            });
        }

        // Products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndexIfExists('products_status_index');
                $table->dropIndexIfExists('products_category_status_index');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
