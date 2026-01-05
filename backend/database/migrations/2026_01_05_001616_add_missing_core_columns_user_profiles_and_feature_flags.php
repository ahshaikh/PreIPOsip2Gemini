<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CORE SCHEMA FIX — POST AUDIT
     *
     * Adds missing boot-critical columns detected by schema:assert --core
     *
     * RULES:
     * - ADDITIVE ONLY
     * - IDEMPOTENT
     * - NO ordering assumptions
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | USER PROFILES
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('user_profiles', 'address')) {
                    $table->text('address')
                        ->nullable()
                        ->comment('User residential / correspondence address');
                }

                if (!Schema::hasColumn('user_profiles', 'preferences')) {
                    $table->json('preferences')
                        ->nullable()
                        ->comment('User preference metadata (UI, comms, etc)');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FEATURE FLAGS
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('feature_flags')) {
            Schema::table('feature_flags', function (Blueprint $table) {
                if (!Schema::hasColumn('feature_flags', 'is_active')) {
                    $table->boolean('is_active')
                        ->default(true)
                        ->comment('Master enable/disable switch');
                }

                if (!Schema::hasColumn('feature_flags', 'percentage')) {
                    $table->unsignedTinyInteger('percentage')
                        ->default(100)
                        ->comment('Rollout percentage (0–100)');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * NOTE:
     * Core schema fixes are NOT reversible in production.
     * This method is intentionally left empty.
     */
    public function down(): void
    {
        // NOOP — additive core schema fix
    }
};
