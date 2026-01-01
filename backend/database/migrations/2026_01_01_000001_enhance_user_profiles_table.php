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
            // Name fields
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('mother_name')->nullable()->after('last_name');
            $table->string('wife_name')->nullable()->after('mother_name');

            // Professional/Personal details
            $table->string('occupation')->nullable()->after('gender');
            $table->string('education')->nullable()->after('occupation');

            // Social media links stored as JSON
            // Example: {"facebook": "...", "linkedin": "...", "twitter": "..."}
            $table->json('social_links')->nullable()->after('education');

            // Note: mobile already exists in users table (not user_profiles)
            // Note: dob already exists in user_profiles table
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name',
                'mother_name',
                'wife_name',
                'occupation',
                'education',
                'social_links',
            ]);
        });
    }
};
