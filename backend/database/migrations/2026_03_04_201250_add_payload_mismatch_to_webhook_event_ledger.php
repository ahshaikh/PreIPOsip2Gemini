<?php

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
        Schema::table('webhook_event_ledger', function (Blueprint $table) {
            $table->boolean('payload_mismatch_detected')->default(false)->after('replay_detected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_event_ledger', function (Blueprint $table) {
            $table->dropColumn('payload_mismatch_detected');
        });
    }
};
