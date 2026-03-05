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
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('webhook_id')->index();
            $table->string('resource_type')->nullable()->after('provider')->index();
            $table->string('resource_id')->nullable()->after('resource_type')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->dropColumn(['provider', 'resource_type', 'resource_id']);
        });
    }
};
