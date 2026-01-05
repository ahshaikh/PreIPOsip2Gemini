<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | AUDIT & SYSTEM LOGGING
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_logs', 'old_values')) {
                    $table->json('old_values')->nullable();
                }
                if (!Schema::hasColumn('activity_logs', 'new_values')) {
                    $table->json('new_values')->nullable();
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'admin_id')) {
                    $table->unsignedBigInteger('admin_id')->nullable();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | CMS / CONTENT
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('blog_categories')) {
            Schema::table('blog_categories', function (Blueprint $table) {
                if (!Schema::hasColumn('blog_categories', 'name')) {
                    $table->string('name')->nullable();
                }
                if (!Schema::hasColumn('blog_categories', 'icon')) {
                    $table->string('icon')->nullable();
                }
            });
        }

        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                if (!Schema::hasColumn('faqs', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('page_versions')) {
            Schema::table('page_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('page_versions', 'version_number')) {
                    $table->integer('version_number')->nullable();
                }
                if (!Schema::hasColumn('page_versions', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::hasColumn('page_versions', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | COMMUNICATION & MESSAGING
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('channel_message_templates')) {
            Schema::table('channel_message_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('channel_message_templates', 'subject')) {
                    $table->string('subject')->nullable();
                }
            });
        }

        if (Schema::hasTable('outbound_message_queue')) {
            Schema::table('outbound_message_queue', function (Blueprint $table) {
                if (!Schema::hasColumn('outbound_message_queue', 'subject')) {
                    $table->string('subject')->nullable();
                }
                if (!Schema::hasColumn('outbound_message_queue', 'message_metadata')) {
                    $table->json('message_metadata')->nullable();
                }
            });
        }

        if (Schema::hasTable('unified_inbox_messages')) {
            Schema::table('unified_inbox_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('unified_inbox_messages', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable();
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable();
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'error_message')) {
                    $table->text('error_message')->nullable();
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'message_metadata')) {
                    $table->json('message_metadata')->nullable();
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'external_message_id')) {
                    $table->string('external_message_id')->nullable();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | REFERRALS & PROMOTIONS
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('referral_campaigns')) {
            Schema::table('referral_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('referral_campaigns', 'slug')) {
                    $table->string('slug')->nullable();
                }
                if (!Schema::hasColumn('referral_campaigns', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('referral_campaigns', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable();
                }
                if (!Schema::hasColumn('referral_campaigns', 'ends_at')) {
                    $table->timestamp('ends_at')->nullable();
                }
                if (!Schema::hasColumn('referral_campaigns', 'max_referrals')) {
                    $table->integer('max_referrals')->nullable();
                }
            });
        }

        if (Schema::hasTable('referral_transactions')) {
            Schema::table('referral_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('referral_transactions', 'amount_paise')) {
                    $table->bigInteger('amount_paise')->nullable();
                }
                if (!Schema::hasColumn('referral_transactions', 'level')) {
                    $table->integer('level')->nullable();
                }
                if (!Schema::hasColumn('referral_transactions', 'status')) {
                    $table->string('status')->nullable();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | USER & AGREEMENTS
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('user_agreement_signatures')) {
            Schema::table('user_agreement_signatures', function (Blueprint $table) {
                if (!Schema::hasColumn('user_agreement_signatures', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable();
                }
                if (!Schema::hasColumn('user_agreement_signatures', 'legal_agreement_id')) {
                    $table->unsignedBigInteger('legal_agreement_id')->nullable();
                }
                if (!Schema::hasColumn('user_agreement_signatures', 'legal_agreement_version_id')) {
                    $table->unsignedBigInteger('legal_agreement_version_id')->nullable();
                }
                if (!Schema::hasColumn('user_agreement_signatures', 'version_signed')) {
                    $table->string('version_signed')->nullable();
                }
                if (!Schema::hasColumn('user_agreement_signatures', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }
                if (!Schema::hasColumn('user_agreement_signatures', 'user_agent')) {
                    $table->text('user_agent')->nullable();
                }
                if (!Schema::hasColumn('user_agreement_signatures', 'signed_at')) {
                    $table->timestamp('signed_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty.
        // This migration is additive-only and production-safe.
    }
};
