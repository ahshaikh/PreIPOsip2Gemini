<?php
// V-PERFORMANCE-INDEXES-PHASE4 - Additional performance indexes for frequently queried columns

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
        // Support tickets table indexes
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                // Index for user's tickets filtered by status
                if ($this->hasColumn('support_tickets', 'user_id') && $this->hasColumn('support_tickets', 'status')) {
                    if (!$this->hasIndex('support_tickets', 'support_tickets_user_status_index')) {
                        $table->index(['user_id', 'status'], 'support_tickets_user_status_index');
                    }
                }

                // Index for admin views sorted by status and created_at
                if ($this->hasColumn('support_tickets', 'status') && $this->hasColumn('support_tickets', 'created_at')) {
                    if (!$this->hasIndex('support_tickets', 'support_tickets_status_created_index')) {
                        $table->index(['status', 'created_at'], 'support_tickets_status_created_index');
                    }
                }

                // Index for assigned tickets
                if ($this->hasColumn('support_tickets', 'assigned_to')) {
                    if (!$this->hasIndex('support_tickets', 'support_tickets_assigned_index')) {
                        $table->index('assigned_to', 'support_tickets_assigned_index');
                    }
                }

                // Index for category filtering
                if ($this->hasColumn('support_tickets', 'category')) {
                    if (!$this->hasIndex('support_tickets', 'support_tickets_category_index')) {
                        $table->index('category', 'support_tickets_category_index');
                    }
                }
            });
        }

        // Support messages table indexes
        if (Schema::hasTable('support_messages')) {
            Schema::table('support_messages', function (Blueprint $table) {
                // Index for ticket's messages
                if ($this->hasColumn('support_messages', 'ticket_id') && $this->hasColumn('support_messages', 'created_at')) {
                    if (!$this->hasIndex('support_messages', 'support_messages_ticket_created_index')) {
                        $table->index(['ticket_id', 'created_at'], 'support_messages_ticket_created_index');
                    }
                }
            });
        }

        // Payments table - paid_at is frequently used for revenue reporting
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Index for date-based revenue queries
                if ($this->hasColumn('payments', 'paid_at')) {
                    if (!$this->hasIndex('payments', 'payments_paid_at_index')) {
                        $table->index('paid_at', 'payments_paid_at_index');
                    }
                }

                // Composite index for revenue reporting (status + paid_at)
                if ($this->hasColumn('payments', 'status') && $this->hasColumn('payments', 'paid_at')) {
                    if (!$this->hasIndex('payments', 'payments_status_paid_at_index')) {
                        $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
                    }
                }
            });
        }

        // Email templates table - frequently queried by type
        if (Schema::hasTable('email_templates')) {
            Schema::table('email_templates', function (Blueprint $table) {
                if ($this->hasColumn('email_templates', 'type')) {
                    if (!$this->hasIndex('email_templates', 'email_templates_type_index')) {
                        $table->index('type', 'email_templates_type_index');
                    }
                }
            });
        }

        // Products table - slug is used for lookups
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if ($this->hasColumn('products', 'slug')) {
                    if (!$this->hasIndex('products', 'products_slug_index')) {
                        $table->index('slug', 'products_slug_index');
                    }
                }
            });
        }

        // Plans table - status for active plans lookup
        if (Schema::hasTable('plans')) {
            Schema::table('plans', function (Blueprint $table) {
                if ($this->hasColumn('plans', 'status')) {
                    if (!$this->hasIndex('plans', 'plans_status_index')) {
                        $table->index('status', 'plans_status_index');
                    }
                }
            });
        }

        // Referrals table indexes
        if (Schema::hasTable('referrals')) {
            Schema::table('referrals', function (Blueprint $table) {
                // Index for referrer's referrals
                if ($this->hasColumn('referrals', 'referrer_id') && $this->hasColumn('referrals', 'status')) {
                    if (!$this->hasIndex('referrals', 'referrals_referrer_status_index')) {
                        $table->index(['referrer_id', 'status'], 'referrals_referrer_status_index');
                    }
                }

                // Index for referred user
                if ($this->hasColumn('referrals', 'referred_id')) {
                    if (!$this->hasIndex('referrals', 'referrals_referred_index')) {
                        $table->index('referred_id', 'referrals_referred_index');
                    }
                }
            });
        }

        // Blog posts table - status and published_at for public queries
        if (Schema::hasTable('blog_posts')) {
            Schema::table('blog_posts', function (Blueprint $table) {
                if ($this->hasColumn('blog_posts', 'status') && $this->hasColumn('blog_posts', 'published_at')) {
                    if (!$this->hasIndex('blog_posts', 'blog_posts_status_published_index')) {
                        $table->index(['status', 'published_at'], 'blog_posts_status_published_index');
                    }
                }

                if ($this->hasColumn('blog_posts', 'slug')) {
                    if (!$this->hasIndex('blog_posts', 'blog_posts_slug_index')) {
                        $table->index('slug', 'blog_posts_slug_index');
                    }
                }
            });
        }

        // Personal access tokens - frequently queried by tokenable
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                if ($this->hasColumn('personal_access_tokens', 'tokenable_id') && $this->hasColumn('personal_access_tokens', 'tokenable_type')) {
                    if (!$this->hasIndex('personal_access_tokens', 'pat_tokenable_index')) {
                        $table->index(['tokenable_type', 'tokenable_id'], 'pat_tokenable_index');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexSafely('support_tickets', 'support_tickets_user_status_index');
        $this->dropIndexSafely('support_tickets', 'support_tickets_status_created_index');
        $this->dropIndexSafely('support_tickets', 'support_tickets_assigned_index');
        $this->dropIndexSafely('support_tickets', 'support_tickets_category_index');

        $this->dropIndexSafely('support_messages', 'support_messages_ticket_created_index');

        $this->dropIndexSafely('payments', 'payments_paid_at_index');
        $this->dropIndexSafely('payments', 'payments_status_paid_at_index');

        $this->dropIndexSafely('email_templates', 'email_templates_type_index');

        $this->dropIndexSafely('products', 'products_slug_index');

        $this->dropIndexSafely('plans', 'plans_status_index');

        $this->dropIndexSafely('referrals', 'referrals_referrer_status_index');
        $this->dropIndexSafely('referrals', 'referrals_referred_index');

        $this->dropIndexSafely('blog_posts', 'blog_posts_status_published_index');
        $this->dropIndexSafely('blog_posts', 'blog_posts_slug_index');

        $this->dropIndexSafely('personal_access_tokens', 'pat_tokenable_index');
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
