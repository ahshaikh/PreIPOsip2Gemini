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
            $table->string('resource_type')->nullable()->after('event_id');
            $table->string('resource_id')->nullable()->after('resource_type');
            $table->unsignedBigInteger('event_timestamp')->nullable()->after('headers_hash');
            
            // Shortened index name for MariaDB/MySQL limits
            $table->index(['resource_type', 'resource_id', 'event_timestamp'], 'idx_webhook_res_ts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_event_ledger', function (Blueprint $table) {
            $table->dropIndex('idx_webhook_res_ts');
            $table->dropColumn(['resource_type', 'resource_id', 'event_timestamp']);
        });
    }
};
