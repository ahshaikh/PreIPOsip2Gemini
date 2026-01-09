<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: Make audit_logs.module nullable
 *
 * PROBLEM: LogsStateChanges trait creates audit logs without module field
 * causing SQL error: "Field 'module' doesn't have a default value"
 *
 * SOLUTION: Make module column nullable with default value
 * Module is a categorization field, not critical for audit functionality
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Make module nullable with default value
            $table->string('module', 100)->nullable()->default('system')->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Revert to NOT NULL (may fail if NULL values exist)
            $table->string('module', 100)->nullable(false)->change();
        });
    }
};
