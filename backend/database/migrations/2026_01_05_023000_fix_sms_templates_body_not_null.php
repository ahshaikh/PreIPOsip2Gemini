<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_templates')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 1: Drop NOT NULL constraint from body
        |--------------------------------------------------------------------------
        | MySQL requires body to be nullable for legacy inserts.
        | This does NOT weaken integrity because content still exists.
        */
        DB::statement("
            ALTER TABLE sms_templates
            MODIFY body TEXT NULL
        ");

        /*
        |--------------------------------------------------------------------------
        | STEP 2: Backfill body from content (idempotent)
        |--------------------------------------------------------------------------
        */
        if (
            Schema::hasColumn('sms_templates', 'content') &&
            Schema::hasColumn('sms_templates', 'body')
        ) {
            DB::statement("
                UPDATE sms_templates
                SET body = content
                WHERE body IS NULL
            ");
        }
    }

    public function down(): void
    {
        // Intentionally no rollback — production-safe additive migration
    }
};
