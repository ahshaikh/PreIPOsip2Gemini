<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ARCHITECTURAL FIX: Convert Actor Fields to Polymorphic Relationships
 *
 * PROBLEM:
 * - disclosure tables use submitted_by/answered_by/created_by with FK → users.id
 * - Comments say "CompanyUser who..." but FK points to wrong table
 * - Semantic reality: CompanyUsers submit, Users approve
 *
 * SOLUTION:
 * - Convert to polymorphic relationships (actor_type + actor_id)
 * - Matches existing AuditLog pattern (actor_type + actor_id)
 * - Semantically correct: CompanyUser can submit, User can approve
 *
 * AFFECTED TABLES:
 * 1. company_disclosures (submitted_by, last_modified_by)
 * 2. disclosure_versions (created_by)
 * 3. disclosure_clarifications (answered_by)
 *
 * NOTE: approved_by remains FK → users (only admins approve)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================================
        // 1. COMPANY_DISCLOSURES TABLE
        // =====================================================================
        Schema::table('company_disclosures', function (Blueprint $table) {
            // Drop old FK constraints first
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['last_modified_by']);

            // Drop old columns
            $table->dropColumn(['submitted_by', 'last_modified_by']);
        });

        Schema::table('company_disclosures', function (Blueprint $table) {
            // Add polymorphic submitted_by (CompanyUser or User)
            $table->string('submitted_by_type')->nullable()
                ->after('submitted_at')
                ->comment('Morph type: App\Models\User or App\Models\CompanyUser');
            $table->unsignedBigInteger('submitted_by_id')->nullable()
                ->after('submitted_by_type')
                ->comment('Morph ID: User or CompanyUser who submitted');

            // Add polymorphic last_modified_by (CompanyUser or User)
            $table->string('last_modified_by_type')->nullable()
                ->after('last_modified_at')
                ->comment('Morph type: App\Models\User or App\Models\CompanyUser');
            $table->unsignedBigInteger('last_modified_by_id')->nullable()
                ->after('last_modified_by_type')
                ->comment('Morph ID: User or CompanyUser who last modified');

            // Add indexes for polymorphic relationships
            $table->index(['submitted_by_type', 'submitted_by_id'], 'company_disclosures_submitted_by_index');
            $table->index(['last_modified_by_type', 'last_modified_by_id'], 'company_disclosures_last_modified_by_index');
        });

        // =====================================================================
        // 2. DISCLOSURE_VERSIONS TABLE
        // =====================================================================
        Schema::table('disclosure_versions', function (Blueprint $table) {
            // Drop old FK constraint
            $table->dropForeign(['created_by']);

            // Drop old column
            $table->dropColumn('created_by');
        });

        Schema::table('disclosure_versions', function (Blueprint $table) {
            // Add polymorphic created_by (CompanyUser or User)
            $table->string('created_by_type')->nullable()
                ->after('created_by_user_agent')
                ->comment('Morph type: App\Models\User or App\Models\CompanyUser');
            $table->unsignedBigInteger('created_by_id')->nullable()
                ->after('created_by_type')
                ->comment('Morph ID: User or CompanyUser who created this version');

            // Add index for polymorphic relationship
            $table->index(['created_by_type', 'created_by_id'], 'disclosure_versions_created_by_index');
        });

        // =====================================================================
        // 3. DISCLOSURE_CLARIFICATIONS TABLE
        // =====================================================================
        Schema::table('disclosure_clarifications', function (Blueprint $table) {
            // Drop old FK constraint
            $table->dropForeign(['answered_by']);

            // Drop old column
            $table->dropColumn('answered_by');
        });

        Schema::table('disclosure_clarifications', function (Blueprint $table) {
            // Add polymorphic answered_by (CompanyUser or User)
            $table->string('answered_by_type')->nullable()
                ->after('answer_body')
                ->comment('Morph type: App\Models\User or App\Models\CompanyUser');
            $table->unsignedBigInteger('answered_by_id')->nullable()
                ->after('answered_by_type')
                ->comment('Morph ID: User or CompanyUser who answered');

            // Add index for polymorphic relationship
            $table->index(['answered_by_type', 'answered_by_id'], 'disclosure_clarifications_answered_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse in opposite order

        // =====================================================================
        // 3. DISCLOSURE_CLARIFICATIONS TABLE
        // =====================================================================
        Schema::table('disclosure_clarifications', function (Blueprint $table) {
            $table->dropIndex('disclosure_clarifications_answered_by_index');
            $table->dropColumn(['answered_by_type', 'answered_by_id']);
        });

        Schema::table('disclosure_clarifications', function (Blueprint $table) {
            $table->foreignId('answered_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who answered (was incorrectly constrained to users table)');
        });

        // =====================================================================
        // 2. DISCLOSURE_VERSIONS TABLE
        // =====================================================================
        Schema::table('disclosure_versions', function (Blueprint $table) {
            $table->dropIndex('disclosure_versions_created_by_index');
            $table->dropColumn(['created_by_type', 'created_by_id']);
        });

        Schema::table('disclosure_versions', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('CompanyUser who triggered this version creation (was incorrectly constrained)');
        });

        // =====================================================================
        // 1. COMPANY_DISCLOSURES TABLE
        // =====================================================================
        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->dropIndex('company_disclosures_submitted_by_index');
            $table->dropIndex('company_disclosures_last_modified_by_index');
            $table->dropColumn(['submitted_by_type', 'submitted_by_id', 'last_modified_by_type', 'last_modified_by_id']);
        });

        Schema::table('company_disclosures', function (Blueprint $table) {
            $table->foreignId('submitted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who submitted (was incorrectly constrained to users table)');

            $table->foreignId('last_modified_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who last modified (was incorrectly constrained to users table)');
        });
    }
};
