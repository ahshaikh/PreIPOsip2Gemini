<?php
// V-SEEDER (Created for development environment setup)

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            [
                'name' => 'New Dashboard',
                'key' => 'new_dashboard',
                'description' => 'Enable the redesigned user dashboard with enhanced analytics and insights',
                'is_enabled' => false,
                'rollout_percentage' => 0,
            ],
            [
                'name' => 'Auto-Debit (UPI Mandate)',
                'key' => 'auto_debit',
                'description' => 'Allow users to set up automatic SIP payments via UPI mandate',
                'is_enabled' => true,
                'rollout_percentage' => 100,
            ],
            [
                'name' => 'Referral Program',
                'key' => 'referral_program',
                'description' => 'Enable the referral program for users to invite friends and earn bonuses',
                'is_enabled' => true,
                'rollout_percentage' => 100,
            ],
            [
                'name' => 'Lucky Draw',
                'key' => 'lucky_draw',
                'description' => 'Enable lucky draw participation for qualifying payments',
                'is_enabled' => true,
                'rollout_percentage' => 100,
            ],
            [
                'name' => 'Celebration Bonuses',
                'key' => 'celebration_bonuses',
                'description' => 'Enable special festival and celebration bonuses',
                'is_enabled' => true,
                'rollout_percentage' => 100,
            ],
            [
                'name' => 'Advanced Reports',
                'key' => 'advanced_reports',
                'description' => 'Enable advanced reporting features for admin dashboard',
                'is_enabled' => true,
                'rollout_percentage' => 100,
            ],
            [
                'name' => 'Two-Factor Authentication',
                'key' => '2fa',
                'description' => 'Enable optional 2FA for users via TOTP authenticator apps',
                'is_enabled' => true,
                'rollout_percentage' => 100,
            ],
            [
                'name' => 'Dark Mode',
                'key' => 'dark_mode',
                'description' => 'Enable dark mode theme option in the frontend',
                'is_enabled' => false,
                'rollout_percentage' => 0,
            ],
            [
                'name' => 'Mobile App Integration',
                'key' => 'mobile_app',
                'description' => 'Enable features specific to mobile app users',
                'is_enabled' => false,
                'rollout_percentage' => 0,
            ],
            [
                'name' => 'Beta Features',
                'key' => 'beta_features',
                'description' => 'Enable experimental beta features for testing',
                'is_enabled' => false,
                'rollout_percentage' => 5,
            ],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::updateOrCreate(
                ['key' => $flag['key']],
                $flag
            );
        }
    }
}
