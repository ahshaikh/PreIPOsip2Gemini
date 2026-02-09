<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Ensure category column exists on disclosure_modules with proper enum values
 *
 * This migration is idempotent - safe to run even if column already exists
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column exists
        if (!Schema::hasColumn('disclosure_modules', 'category')) {
            Schema::table('disclosure_modules', function (Blueprint $table) {
                $table->enum('category', [
                    'governance',
                    'financial',
                    'legal',
                    'operational'
                ])->default('operational')
                    ->after('description')
                    ->comment('Disclosure requirement category for UI grouping');
            });
        }

        // Set default category for any NULL values
        DB::table('disclosure_modules')
            ->whereNull('category')
            ->update(['category' => 'operational']);

        // Add index if it doesn't exist
        try {
            Schema::table('disclosure_modules', function (Blueprint $table) {
                $table->index(['category', 'tier', 'is_active'], 'idx_disclosure_modules_category_tier');
            });
        } catch (\Exception $e) {
            // Index might already exist, that's okay
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only drop if we created it
        if (Schema::hasColumn('disclosure_modules', 'category')) {
            try {
                Schema::table('disclosure_modules', function (Blueprint $table) {
                    $table->dropIndex('idx_disclosure_modules_category_tier');
                });
            } catch (\Exception $e) {
                // Index might not exist, that's okay
            }

            Schema::table('disclosure_modules', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
