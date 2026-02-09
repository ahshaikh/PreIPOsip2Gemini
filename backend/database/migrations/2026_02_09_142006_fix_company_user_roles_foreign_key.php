<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix CompanyUserRole foreign key to reference company_users instead of users
 *
 * ROOT CAUSE FIX:
 * The company_user_roles table was incorrectly referencing users.id,
 * but the company portal authentication uses company_users table.
 * This caused "unauthorized" errors because CompanyUser records couldn't
 * have roles assigned.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_user_roles', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['user_id']);
            $table->dropForeign(['assigned_by']);
        });

        Schema::table('company_user_roles', function (Blueprint $table) {
            // Add correct foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('company_users')
                ->onDelete('cascade');

            $table->foreign('assigned_by')
                ->references('id')
                ->on('company_users')
                ->onDelete('set null');
        });

        DB::statement('ALTER TABLE company_user_roles COMMENT = "Role assignments for CompanyUser (company portal)"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_user_roles', function (Blueprint $table) {
            // Drop company_users foreign keys
            $table->dropForeign(['user_id']);
            $table->dropForeign(['assigned_by']);
        });

        Schema::table('company_user_roles', function (Blueprint $table) {
            // Restore original foreign keys to users table
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('assigned_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};
