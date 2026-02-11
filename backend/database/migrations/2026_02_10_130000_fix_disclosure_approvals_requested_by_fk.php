<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: disclosure_approvals.requested_by Foreign Key
 *
 * PROBLEM:
 * The original migration created requested_by with FK to `users` table,
 * but company disclosures are submitted by `company_users`, not `users`.
 *
 * ERROR:
 * "Integrity constraint violation: 1452 Cannot add or update a child row:
 * a foreign key constraint fails (`preipo`.`disclosure_approvals`,
 * CONSTRAINT `disclosure_approvals_requested_by_foreign` FOREIGN KEY
 * (`requested_by`) REFERENCES `users` (`id`))"
 *
 * FIX:
 * Drop the incorrect FK to `users` and create correct FK to `company_users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disclosure_approvals', function (Blueprint $table) {
            // Drop the incorrect foreign key constraint
            $table->dropForeign(['requested_by']);
        });

        Schema::table('disclosure_approvals', function (Blueprint $table) {
            // Add correct foreign key to company_users
            $table->foreign('requested_by')
                ->references('id')
                ->on('company_users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disclosure_approvals', function (Blueprint $table) {
            // Drop the company_users foreign key
            $table->dropForeign(['requested_by']);
        });

        Schema::table('disclosure_approvals', function (Blueprint $table) {
            // Restore original (incorrect) foreign key to users
            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};
