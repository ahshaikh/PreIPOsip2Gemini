<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

/**
 * FIX 12 (P3): Campaign Approval Database Constraint
 *
 * Enforces that campaigns can only be active if they have been approved
 * Prevents bypass of approval workflow
 */
return new class extends Migration
{
    public function up(): void
    {
        // First, fix any existing violations
        // Deactivate any active campaigns that haven't been approved
        DB::statement("
            UPDATE campaigns
            SET is_active = false
            WHERE is_active = true
            AND approved_at IS NULL
        ");

        // Add CHECK constraint (MySQL 8.0.16+)
        // Note: For older MySQL versions, this will be enforced at application level only
        try {
            DB::statement("
                ALTER TABLE campaigns
                ADD CONSTRAINT check_campaign_approval
                CHECK (
                    is_active = false
                    OR (is_active = true AND approved_at IS NOT NULL)
                )
            ");
        } catch (\Exception $e) {
            // If CHECK constraint not supported (older MySQL), log warning
            \Log::warning('Campaign approval CHECK constraint not supported on this MySQL version. Relying on application-level validation.');
        }

        // Add composite index for performance
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'is_active') || !Schema::hasColumn('campaigns', 'approved_at')) {
                return; // Skip if columns don't exist
            }

            $table->index(['is_active', 'approved_at'], 'idx_campaign_active_approved');
        });
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE campaigns DROP CONSTRAINT IF EXISTS check_campaign_approval");
        } catch (\Exception $e) {
            // Constraint may not exist
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('idx_campaign_active_approved');
        });
    }
};
