<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adding 'dead_letter' to the processing_status enum
        // Note: For MySQL/MariaDB, we use change() but require doctrine/dbal or native DB::statement
        try {
            DB::statement("ALTER TABLE webhook_event_ledger MODIFY COLUMN processing_status ENUM('pending', 'processing', 'success', 'failed', 'dead_letter') DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Fallback for other drivers or if DB::statement fails
            Schema::table('webhook_event_ledger', function (Blueprint $table) {
                $table->string('processing_status')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE webhook_event_ledger MODIFY COLUMN processing_status ENUM('pending', 'processing', 'success', 'failed') DEFAULT 'pending'");
        } catch (\Exception $e) {
             Schema::table('webhook_event_ledger', function (Blueprint $table) {
                $table->string('processing_status')->change();
            });
        }
    }
};
