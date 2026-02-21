<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE bulk_purchases
                MODIFY value_remaining BIGINT UNSIGNED NOT NULL
            ");
        }

        if ($driver === 'sqlite') {
            // SQLite does not support altering column type safely.
            // Enforcement remains at application layer.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE bulk_purchases
                MODIFY value_remaining BIGINT NOT NULL
            ");
        }
    }
};