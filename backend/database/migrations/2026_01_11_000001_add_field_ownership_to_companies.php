<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 1 STABILIZATION - Issue 1: Overloaded companies Table
 *
 * PROBLEM:
 * Companies table mixes issuer truth, governance state, and platform assertions
 * without explicit ownership boundaries.
 *
 * SURGICAL FIX:
 * Add field_ownership_map to explicitly label which domain owns each field.
 * No fields removed. No table split. Pure containment layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Metadata column tracking field ownership
            $table->json('field_ownership_map')->nullable()->after('updated_at')
                ->comment('Maps each field to owning domain: issuer_truth, governance_state, or platform_assertions');
        });

        // Initialize ownership map for existing companies
        DB::statement("
            UPDATE companies
            SET field_ownership_map = JSON_OBJECT(
                'issuer_truth', JSON_ARRAY(
                    'name', 'legal_name', 'cin', 'pan', 'registration_number',
                    'registration_date', 'registered_office_address', 'corporate_office_address',
                    'website', 'email', 'phone', 'industry', 'sector', 'description',
                    'incorporation_country', 'business_model', 'target_market'
                ),
                'governance_state', JSON_ARRAY(
                    'lifecycle_state', 'buying_enabled', 'is_suspended', 'suspension_reason',
                    'suspended_at', 'suspended_by', 'tier_1_approved_at', 'tier_2_approved_at',
                    'tier_3_approved_at', 'last_tier_progression_at'
                ),
                'platform_assertions', JSON_ARRAY(
                    'platform_generated_note'
                )
            )
            WHERE field_ownership_map IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('field_ownership_map');
        });
    }
};
