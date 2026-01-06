<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Promotional Campaign Seeder (Post-Audit - Phase 2)
 *
 * Seeds promotional campaigns for user engagement and conversions.
 *
 * Creates:
 * - 3 promotional campaigns (New Year, First Investment, Festival)
 *
 * IMPORTANT:
 * - All campaigns follow the "Zero Hardcoded Values" principle
 * - Campaign rules are database-driven and admin-editable
 * - Idempotent: Safe to run multiple times
 * - Production-safe: Uses updateOrCreate
 * - Campaigns require admin approval workflow
 *
 * NOTE: This seeder works alongside CampaignBootstrapSeeder
 */
class PromotionalCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to set as creator/approver
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin')->orWhere('name', 'super-admin');
        })->first();

        if (!$adminUser) {
            $this->command->warn('⚠️  No admin user found. Skipping promotional campaign seeder.');
            $this->command->warn('   Run UserSeeder first to create admin users.');
            return;
        }

        DB::transaction(function () use ($adminUser) {
            $this->seedPromotionalCampaigns($adminUser);
        });

        $this->command->info('✓ Promotional campaigns seeded successfully');
    }

    /**
     * Seed promotional campaigns
     */
    private function seedPromotionalCampaigns(User $adminUser): void
    {
        $campaigns = [
            [
                'title' => 'New Year Investment Bonus 2026',
                'subtitle' => 'Start the year with 10% extra on your investment',
                'code' => 'NEWYEAR2026',
                'description' => 'Kickstart your investment journey with our New Year special offer. Get 10% instant discount on your first investment.',
                'long_description' => 'Celebrate the New Year by starting your Pre-IPO investment journey with us! Get a flat 10% discount on your first investment in any active deal. This is a limited time offer valid only for the first two months of 2026. Maximum discount capped at ₹2,500. Terms and conditions apply.',
                'discount_type' => 'percentage',
                'discount_percent' => 10.00,
                'discount_amount' => null,
                'min_investment' => 5000.00,
                'max_discount' => 2500.00,
                'usage_limit' => 500,
                'usage_count' => 0,
                'user_usage_limit' => 1,
                'start_at' => now()->startOfYear(),
                'end_at' => now()->startOfYear()->addMonths(2),
                'image_url' => null,
                'hero_image' => null,
                'video_url' => null,
                'features' => [
                    '10% instant discount on first investment',
                    'Valid for 2 months from New Year',
                    'Minimum investment: ₹5,000',
                    'Maximum discount: ₹2,500',
                    'One-time use per user',
                ],
                'terms' => [
                    'Valid for new users only',
                    'One-time use per user',
                    'Cannot be combined with other offers',
                    'Minimum investment of ₹5,000 required',
                    'Maximum discount capped at ₹2,500',
                    'Valid from Jan 1, 2026 to Feb 28, 2026',
                    'PreIPOsip reserves the right to modify or cancel this campaign',
                ],
                'is_featured' => true,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
                'approved_at' => now(),
            ],
            [
                'title' => 'First Investment Cashback',
                'subtitle' => 'Get ₹500 cashback on your maiden investment',
                'code' => 'FIRST500',
                'description' => 'Welcome bonus for first-time investors. Invest ₹10,000 or more and get ₹500 cashback instantly.',
                'long_description' => 'We appreciate your trust in PreIPOsip! As a welcome gesture, get ₹500 cashback when you make your first investment of ₹10,000 or more. The cashback will be credited to your wallet within 24 hours of successful payment verification. This offer is valid for 6 months.',
                'discount_type' => 'fixed_amount',
                'discount_percent' => null,
                'discount_amount' => 500.00,
                'min_investment' => 10000.00,
                'max_discount' => null,
                'usage_limit' => null, // Unlimited
                'usage_count' => 0,
                'user_usage_limit' => 1,
                'start_at' => now()->subMonth(),
                'end_at' => now()->addMonths(6),
                'image_url' => null,
                'hero_image' => null,
                'video_url' => null,
                'features' => [
                    '₹500 instant cashback',
                    'Valid for 6 months',
                    'Minimum investment: ₹10,000',
                    'Credited within 24 hours',
                    'No code required - auto-applied',
                ],
                'terms' => [
                    'Valid for first investment only',
                    'One-time use per user',
                    'Minimum investment of ₹10,000 required',
                    'Cashback credited to wallet within 24 hours',
                    'Cannot be withdrawn immediately - must be used for investments',
                    'Valid for 6 months from activation',
                    'PreIPOsip reserves the right to modify or cancel this campaign',
                ],
                'is_featured' => false,
                'is_active' => true,
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
                'approved_at' => now(),
            ],
            [
                'title' => 'Festival Bonus Campaign 2026',
                'subtitle' => 'Celebrate festivals with 5% extra on investments',
                'code' => 'FESTIVAL2026',
                'description' => 'Special bonus during festival season. Get 5% additional value on all investments made during the campaign period.',
                'long_description' => 'Celebrate the festival season by growing your investment portfolio! Get an extra 5% bonus on all investments made during our Festival Bonus Campaign. This offer is valid on all active Pre-IPO deals and investment plans. Maximum bonus capped at ₹1,000 per transaction. Limited to 1,000 total redemptions.',
                'discount_type' => 'percentage',
                'discount_percent' => 5.00,
                'discount_amount' => null,
                'min_investment' => 5000.00,
                'max_discount' => 1000.00,
                'usage_limit' => 1000,
                'usage_count' => 0,
                'user_usage_limit' => 3, // Can use up to 3 times
                'start_at' => now()->addMonths(3),
                'end_at' => now()->addMonths(4),
                'image_url' => null,
                'hero_image' => null,
                'video_url' => null,
                'features' => [
                    '5% bonus on all investments',
                    'Valid on all active deals',
                    'Use up to 3 times',
                    'Maximum bonus: ₹1,000 per transaction',
                    'Limited time festival offer',
                ],
                'terms' => [
                    'Limited period offer during festival season',
                    'Valid on all investment plans',
                    'Maximum 3 uses per user',
                    'Maximum bonus ₹1,000 per transaction',
                    'Minimum investment of ₹5,000 required',
                    'Total 1,000 redemptions across all users',
                    'First come, first served basis',
                    'PreIPOsip reserves the right to modify or cancel this campaign',
                ],
                'is_featured' => false,
                'is_active' => false, // Scheduled for future
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
                'approved_at' => now(),
            ],
        ];

        foreach ($campaigns as $campaignData) {
            Campaign::updateOrCreate(
                ['code' => $campaignData['code']],
                $campaignData
            );
        }

        $this->command->info('  ✓ Promotional campaigns seeded: ' . count($campaigns) . ' campaigns');
        $this->command->info('  ℹ  Note: Festival campaign is scheduled for future activation');
    }
}
