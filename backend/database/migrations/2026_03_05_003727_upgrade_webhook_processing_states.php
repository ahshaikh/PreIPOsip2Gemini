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
        // 1. Upgrade Enum values using temporary unique placeholders to avoid case-insensitive collisions
        DB::statement("ALTER TABLE webhook_event_ledger MODIFY COLUMN processing_status ENUM('pending', 'processing', 'success', 'failed', 'dead_letter', 'RECEIVED', 'VALIDATED', 'ENQUEUED', '_PROCESSING', '_PROCESSED', '_FAILED', '_DEAD_LETTER') DEFAULT 'pending'");

        // 2. Map existing states to new uppercase states
        DB::table('webhook_event_ledger')->where('processing_status', 'pending')->update(['processing_status' => 'RECEIVED']);
        DB::table('webhook_event_ledger')->where('processing_status', 'processing')->update(['processing_status' => '_PROCESSING']);
        DB::table('webhook_event_ledger')->where('processing_status', 'success')->update(['processing_status' => '_PROCESSED']);
        DB::table('webhook_event_ledger')->where('processing_status', 'failed')->update(['processing_status' => '_FAILED']);
        DB::table('webhook_event_ledger')->where('processing_status', 'dead_letter')->update(['processing_status' => '_DEAD_LETTER']);

        // 3. Final cleanup to the requested uppercase states
        DB::statement("ALTER TABLE webhook_event_ledger MODIFY COLUMN processing_status ENUM('RECEIVED', 'VALIDATED', 'ENQUEUED', 'PROCESSING', 'PROCESSED', 'FAILED', 'DEAD_LETTER') DEFAULT 'RECEIVED'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE webhook_event_ledger MODIFY COLUMN processing_status ENUM('pending', 'processing', 'success', 'failed', 'dead_letter', 'RECEIVED', 'VALIDATED', 'ENQUEUED', '_PROCESSING', '_PROCESSED', '_FAILED', '_DEAD_LETTER') DEFAULT 'pending'");
        
        DB::table('webhook_event_ledger')->where('processing_status', 'RECEIVED')->update(['processing_status' => 'pending']);
        DB::table('webhook_event_ledger')->where('processing_status', 'VALIDATED')->update(['processing_status' => 'pending']);
        DB::table('webhook_event_ledger')->where('processing_status', 'ENQUEUED')->update(['processing_status' => 'pending']);
        DB::table('webhook_event_ledger')->where('processing_status', 'PROCESSING')->update(['processing_status' => 'processing']);
        DB::table('webhook_event_ledger')->where('processing_status', 'PROCESSED')->update(['processing_status' => 'success']);
        DB::table('webhook_event_ledger')->where('processing_status', 'FAILED')->update(['processing_status' => 'failed']);
        DB::table('webhook_event_ledger')->where('processing_status', 'DEAD_LETTER')->update(['processing_status' => 'dead_letter']);

        DB::statement("ALTER TABLE webhook_event_ledger MODIFY COLUMN processing_status ENUM('pending', 'processing', 'success', 'failed', 'dead_letter') DEFAULT 'pending'");
    }
};
