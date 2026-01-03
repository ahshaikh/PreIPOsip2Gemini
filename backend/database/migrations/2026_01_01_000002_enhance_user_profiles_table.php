<?php
// V-FIX-PROFILE-ENHANCEMENT (2026-01-01)
// Add comprehensive profile fields requested by user
// Fields: middle_name, mother_name, wife_name, occupation, education, social_links

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {

            // =========================
            // Name fields
            // =========================
            if (!Schema::hasColumn('user_profiles', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (!Schema::hasColumn('user_profiles', 'mother_name')) {
                $table->string('mother_name')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('user_profiles', 'wife_name')) {
                $table->string('wife_name')->nullable()->after('mother_name');
            }

            // =========================
            // Professional / Personal
            // =========================
            if (!Schema::hasColumn('user_profiles', 'occupation')) {
                $table->string('occupation')->nullable()->after('gender');
            }

            if (!Schema::hasColumn('user_profiles', 'education')) {
                $table->string('education')->nullable()->after('occupation');
            }

            // =========================
            // Social links (JSON)
            // =========================
            if (!Schema::hasColumn('user_profiles', 'social_links')) {
                $table->json('social_links')->nullable()->after('education');
            }

            // Notes:
            // - mobile exists in users table
            // - dob already exists in user_profiles
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {

            if (Schema::hasColumn('user_profiles', 'social_links')) {
                $table->dropColumn('social_links');
            }

            if (Schema::hasColumn('user_profiles', 'education')) {
                $table->dropColumn('education');
            }

            if (Schema::hasColumn('user_profiles', 'occupation')) {
                $table->dropColumn('occupation');
            }

            if (Schema::hasColumn('user_profiles', 'wife_name')) {
                $table->dropColumn('wife_name');
            }

            if (Schema::hasColumn('user_profiles', 'mother_name')) {
                $table->dropColumn('mother_name');
            }

            if (Schema::hasColumn('user_profiles', 'middle_name')) {
                $table->dropColumn('middle_name');
            }
        });
    }
};
