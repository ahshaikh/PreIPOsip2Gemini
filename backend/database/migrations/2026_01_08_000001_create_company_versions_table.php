<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Company Version History (FIX 33, 34, 35)
     *
     * Purpose:
     * - Track all changes to company data with complete version history
     * - Create immutable snapshots at deal approval points
     * - Enable data protection and change auditing
     * - Support field-level change tracking and version comparison
     */
    public function up(): void
    {
        Schema::create('company_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->integer('version_number')->comment('Sequential version number per company');

            // Version snapshot data (complete company state at this version)
            $table->json('snapshot_data')->comment('Complete company data snapshot');

            // Change tracking
            $table->json('changed_fields')->nullable()->comment('Array of field names that changed in this version');
            $table->text('change_summary')->nullable()->comment('Human-readable summary of changes');
            $table->json('field_diffs')->nullable()->comment('Detailed field-level diffs (old vs new values)');

            // Approval snapshot marker (FIX 35 - Immutability)
            $table->boolean('is_approval_snapshot')->default(false)->comment('True if created at deal approval');
            $table->foreignId('deal_id')->nullable()->constrained('deals')->onDelete('set null')->comment('Deal this approval snapshot relates to');

            // Data protection status
            $table->boolean('is_protected')->default(false)->comment('If true, data cannot be modified');
            $table->timestamp('protected_at')->nullable();
            $table->text('protection_reason')->nullable();

            // Creator tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('created_by_type')->default('user')->comment('user, system, admin, api');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index(['company_id', 'version_number']);
            $table->index('is_approval_snapshot');
            $table->index('is_protected');
            $table->index('created_at');
            $table->index('created_by');

            // Unique constraint: one version number per company
            $table->unique(['company_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_versions');
    }
};
