<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_templates')) {
            return;
        }

        Schema::table('sms_templates', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Canonical SMS content column
            |--------------------------------------------------------------------------
            | `body` is the authoritative column.
            | `content` exists for backward compatibility.
            */
            if (!Schema::hasColumn('sms_templates', 'body')) {
                $table->text('body')->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Backfill body from content (one-time repair)
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
        // Intentionally empty — additive, production-safe migration
    }
};
