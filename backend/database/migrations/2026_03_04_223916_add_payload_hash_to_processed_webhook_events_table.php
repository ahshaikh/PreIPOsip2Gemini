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
        Schema::table('processed_webhook_events', function (Blueprint $table) {
            $table->string('payload_hash')->after('event_type')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('processed_webhook_events', function (Blueprint $table) {
            $table->dropIndex(['payload_hash']);
            $table->dropColumn('payload_hash');
        });
    }
};
