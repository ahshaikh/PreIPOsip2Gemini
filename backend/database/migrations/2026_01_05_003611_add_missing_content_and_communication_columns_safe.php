<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * CHANNEL MESSAGE TEMPLATES
         */
        if (Schema::hasTable('channel_message_templates')) {
            Schema::table('channel_message_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('channel_message_templates', 'subject')) {
                    $table->string('subject')->nullable()
                        ->comment('Email / notification subject');
                }
            });
        }

        /**
         * OUTBOUND MESSAGE QUEUE
         */
        if (Schema::hasTable('outbound_message_queue')) {
            Schema::table('outbound_message_queue', function (Blueprint $table) {
                if (!Schema::hasColumn('outbound_message_queue', 'subject')) {
                    $table->string('subject')->nullable()
                        ->comment('Optional subject (email / notification)');
                }

                if (!Schema::hasColumn('outbound_message_queue', 'message_metadata')) {
                    $table->json('message_metadata')->nullable()
                        ->comment('Provider-specific metadata payload');
                }
            });
        }

        /**
         * SMS TEMPLATES
         */
        if (Schema::hasTable('sms_templates')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('sms_templates', 'body')) {
                    $table->text('body')->nullable()
                        ->comment('SMS message body');
                }

                if (!Schema::hasColumn('sms_templates', 'dlt_template_id')) {
                    $table->string('dlt_template_id')->nullable()
                        ->comment('DLT template ID (India compliance)');
                }
            });
        }

        /**
         * EMAIL TEMPLATES
         */
        if (Schema::hasTable('email_templates')) {
            Schema::table('email_templates', function (Blueprint $table) {
                if (!Schema::hasColumn('email_templates', 'subject')) {
                    $table->string('subject')->nullable()
                        ->comment('Email subject');
                }
            });
        }

        /**
         * UNIFIED INBOX MESSAGES
         */
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
    }

    public function down(): void
    {
        // INTENTIONALLY LEFT EMPTY
        // Post-audit migrations are additive-only by policy.
    }
};
