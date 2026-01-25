<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 FIX (GAP 25-27): Security Audit Log Table
 *
 * Stores security-relevant events for audit and investigation:
 * - Visibility violation attempts
 * - Unauthorized access attempts
 * - Suspicious activity patterns
 *
 * This table is append-only (no updates/deletes) for audit integrity.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('security_audit_log')) {
            Schema::create('security_audit_log', function (Blueprint $table) {
                $table->id();

                // Event classification
                $table->string('event_type', 50); // visibility_violation_attempt, unauthorized_access, etc.
                $table->string('severity', 20)->default('warning'); // info, warning, critical

                // Actor (who triggered the event)
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('actor_type', 30)->default('user'); // user, system, anonymous

                // Resource being accessed
                $table->string('resource_type', 50)->nullable(); // disclosure, company, user, etc.
                $table->unsignedBigInteger('resource_id')->nullable();

                // Event details
                $table->json('details')->nullable();
                $table->text('message')->nullable();

                // Request context
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('request_method', 10)->nullable();
                $table->text('request_path')->nullable();
                $table->string('session_id', 100)->nullable();

                // Timestamp
                $table->timestamp('created_at');

                // INDEXES for efficient querying
                $table->index(['event_type', 'created_at']);
                $table->index(['user_id', 'created_at']);
                $table->index(['resource_type', 'resource_id']);
                $table->index(['severity', 'created_at']);
                $table->index(['ip_address']);
            });
        }

        // Add visibility fields to company_disclosures if not present
        if (Schema::hasTable('company_disclosures')) {
            Schema::table('company_disclosures', function (Blueprint $table) {
                if (!Schema::hasColumn('company_disclosures', 'visibility')) {
                    $table->string('visibility', 20)->default('public')->after('status');
                }
                if (!Schema::hasColumn('company_disclosures', 'is_visible')) {
                    $table->boolean('is_visible')->default(true)->after('visibility');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_log');

        if (Schema::hasTable('company_disclosures')) {
            Schema::table('company_disclosures', function (Blueprint $table) {
                if (Schema::hasColumn('company_disclosures', 'visibility')) {
                    $table->dropColumn('visibility');
                }
                if (Schema::hasColumn('company_disclosures', 'is_visible')) {
                    $table->dropColumn('is_visible');
                }
            });
        }
    }
};
