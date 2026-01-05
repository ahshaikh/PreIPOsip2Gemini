<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Content, CMS & Communication Infrastructure
     * -------------------------------------------
     * NON-CORE tables only.
     * Additive, production-safe.
     */
    public function up(): void
    {
        /**
         * ==========================================================
         * PAGE VERSIONS
         * ==========================================================
         */
        if (Schema::hasTable('page_versions')) {
            Schema::table('page_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('page_versions', 'version_number')) {
                    $table->integer('version_number')->default(1)->after('id');
                }
                if (!Schema::hasColumn('page_versions', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->after('version_number');
                }
                if (!Schema::hasColumn('page_versions', 'notes')) {
                    $table->text('notes')->nullable()->after('created_by');
                }
            });
        }

        /**
         * ==========================================================
         * FAQS
         * ==========================================================
         */
        if (Schema::hasTable('faqs')) {
            Schema::table('faqs', function (Blueprint $table) {
                if (!Schema::hasColumn('faqs', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->after('id');
                }
            });
        }

        /**
         * ==========================================================
         * FEATURE FLAGS
         * ==========================================================
         */
        if (Schema::hasTable('feature_flags')) {
            Schema::table('feature_flags', function (Blueprint $table) {
                if (!Schema::hasColumn('feature_flags', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('name');
                }
                if (!Schema::hasColumn('feature_flags', 'percentage')) {
                    $table->unsignedTinyInteger('percentage')
                        ->default(100)
                        ->comment('Rollout percentage (0–100)')
                        ->after('is_active');
                }
            });
        }

        /**
         * ==========================================================
         * CHANNEL MESSAGE TEMPLATES
         * ==========================================================
         */
	if (Schema::hasTable('channel_message_templates')) {
	    Schema::table('channel_message_templates', function (Blueprint $table) {
	        if (!Schema::hasColumn('channel_message_templates', 'subject')) {
	            $table->string('subject')->nullable()
	                ->comment('Optional subject for email / push channels');
	        }
	    });
	}

        /**
         * ==========================================================
         * SMS TEMPLATES
         * ==========================================================
         */
        if (Schema::hasTable('sms_templates')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('sms_templates', 'body')) {
                    $table->text('body')->after('name');
                }
                if (!Schema::hasColumn('sms_templates', 'dlt_template_id')) {
                    $table->string('dlt_template_id')->nullable()->after('body');
                }
            });
        }

        /**
         * ==========================================================
         * OUTBOUND MESSAGE QUEUE
         * ==========================================================
         */
	if (Schema::hasTable('outbound_message_queue')) {
	    Schema::table('outbound_message_queue', function (Blueprint $table) {
	        if (!Schema::hasColumn('outbound_message_queue', 'subject')) {
	            $table->string('subject')->nullable()
	                ->comment('Optional subject for email / notification');
	        }

	        if (!Schema::hasColumn('outbound_message_queue', 'message_metadata')) {
        	    $table->json('message_metadata')->nullable()
        	        ->comment('Provider-specific payload metadata');
	        }
	    });
	}


        /**
         * ==========================================================
         * UNIFIED INBOX MESSAGES
         * ==========================================================
         */
        if (Schema::hasTable('unified_inbox_messages')) {
            Schema::table('unified_inbox_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('unified_inbox_messages', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable()->after('created_at');
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable()->after('processed_at');
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'error_message')) {
                    $table->text('error_message')->nullable()->after('failed_at');
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'message_metadata')) {
                    $table->json('message_metadata')->nullable()->after('error_message');
                }
                if (!Schema::hasColumn('unified_inbox_messages', 'external_message_id')) {
                    $table->string('external_message_id')->nullable()->after('message_metadata');
                }
            });
        }

        /**
         * ==========================================================
         * USER NOTIFICATION PREFERENCES
         * ==========================================================
         */
        if (Schema::hasTable('user_notification_preferences')) {
            Schema::table('user_notification_preferences', function (Blueprint $table) {
                if (!Schema::hasColumn('user_notification_preferences', 'notification_type')) {
                    $table->string('notification_type')->after('id');
                }
                if (!Schema::hasColumn('user_notification_preferences', 'email_enabled')) {
                    $table->boolean('email_enabled')->default(true)->after('notification_type');
                }
                if (!Schema::hasColumn('user_notification_preferences', 'sms_enabled')) {
                    $table->boolean('sms_enabled')->default(false)->after('email_enabled');
                }
                if (!Schema::hasColumn('user_notification_preferences', 'push_enabled')) {
                    $table->boolean('push_enabled')->default(true)->after('sms_enabled');
                }
                if (!Schema::hasColumn('user_notification_preferences', 'in_app_enabled')) {
                    $table->boolean('in_app_enabled')->default(true)->after('push_enabled');
                }
            });
        }

        /**
         * ==========================================================
         * TUTORIALS
         * ==========================================================
         */
        if (Schema::hasTable('tutorials')) {
            Schema::table('tutorials', function (Blueprint $table) {
                if (!Schema::hasColumn('tutorials', 'thumbnail_url')) {
                    $table->string('thumbnail_url')->nullable()->after('title');
                }
                if (!Schema::hasColumn('tutorials', 'user_role')) {
                    $table->string('user_role')->nullable()->after('thumbnail_url');
                }
                if (!Schema::hasColumn('tutorials', 'estimated_minutes')) {
                    $table->unsignedSmallInteger('estimated_minutes')->nullable()->after('user_role');
                }
                if (!Schema::hasColumn('tutorials', 'auto_launch')) {
                    $table->boolean('auto_launch')->default(false)->after('estimated_minutes');
                }
                if (!Schema::hasColumn('tutorials', 'trigger_page')) {
                    $table->string('trigger_page')->nullable()->after('auto_launch');
                }
                if (!Schema::hasColumn('tutorials', 'trigger_conditions')) {
                    $table->json('trigger_conditions')->nullable()->after('trigger_page');
                }
                if (!Schema::hasColumn('tutorials', 'completions_count')) {
                    $table->unsignedInteger('completions_count')->default(0)->after('trigger_conditions');
                }
                if (!Schema::hasColumn('tutorials', 'avg_completion_rate')) {
                    $table->decimal('avg_completion_rate', 5, 2)->default(0)->after('completions_count');
                }
                if (!Schema::hasColumn('tutorials', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)->after('avg_completion_rate');
                }
                if (!Schema::hasColumn('tutorials', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('is_featured');
                }
            });
        }
    }

    /**
     * No rollback – additive migration
     */
    public function down(): void
    {
        // Intentionally left blank
    }
};
