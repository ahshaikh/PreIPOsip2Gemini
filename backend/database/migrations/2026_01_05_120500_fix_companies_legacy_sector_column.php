<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 1: Make legacy `sector` column nullable
        |--------------------------------------------------------------------------
        | The system has migrated to sector_id.
        | The legacy string column must not block inserts.
        */
        if (Schema::hasColumn('companies', 'sector')) {
            DB::statement("
                ALTER TABLE companies
                MODIFY sector VARCHAR(255) NULL
            ");
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 2: Backfill sector from related sectors table (best-effort)
        |--------------------------------------------------------------------------
        | Keeps admin UI / legacy code readable.
        */
        if (
            Schema::hasColumn('companies', 'sector') &&
            Schema::hasColumn('companies', 'sector_id') &&
            Schema::hasTable('sectors')
        ) {
            DB::statement("
                UPDATE companies c
                JOIN sectors s ON s.id = c.sector_id
                SET c.sector = s.name
                WHERE c.sector IS NULL
            ");
        }
    }

    public function down(): void
    {
        // Intentionally irreversible — additive, production-safe migration
    }
};
