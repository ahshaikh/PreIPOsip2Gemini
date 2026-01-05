<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing clicked_at column to push_logs table
     * The original push_logs table was created without this field
     */
    public function up(): void
    {
        Schema::table('push_logs', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('push_logs', 'clicked_at')) {
                $table->timestamp('clicked_at')->nullable()->after('opened_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('push_logs', function (Blueprint $table) {
            if (Schema::hasColumn('push_logs', 'clicked_at')) {
                $table->dropColumn('clicked_at');
            }
        });
    }
};
