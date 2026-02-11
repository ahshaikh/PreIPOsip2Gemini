<?php

namespace Database\Seeders;

use App\Models\ReferralCampaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Referral Campaign Seeder (Post-Audit - Phase 2)
 *
 * Seeds referral campaigns for user acquisition and engagement.
 *
 * Creates:
 * - 2 referral campaigns (Standard & Premium)
 *
 * IMPORTANT:
 * - All campaigns follow the "Zero Hardcoded Values" principle
 * - Campaign rules are database-driven and admin-editable
 * - Idempotent: Safe to run multiple times
 * - Production-safe: Uses updateOrCreate
 */
class ReferralCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedReferralCampaigns();
        });

        $this->command->info('✓ Referral campaigns seeded successfully');
    }

    /**
     * Seed referral campaigns
     */
    private function seedReferralCampaigns(): void
    {
        $campaigns = [
            [
                'name' => 'Standard Referral Program',
                'slug' => 'standard-referral',
                'description' => 'Earn ₹500 for each successful referral who completes KYC and makes their first investment of ₹5,000 or more.',
                'start_date' => now()->subMonths(6),
                'end_date' => now()->addYear(),
                'multiplier' => 1.0,
                'bonus_amount' => 500.00,
                'is_active' => true,
                'max_referrals' => null, // Unlimited
            ],
            [
                'name' => 'Premium Referral Campaign',
                'slug' => 'premium-referral',
                'description' => 'Earn ₹1,500 for each referral who invests ₹25,000 or more in Premium plans. Limited time offer!',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(3),
                'multiplier' => 1.5,
                'bonus_amount' => 1500.00,
                'is_active' => true,
                'max_referrals' => 1000, // Limited to 1000 referrals
            ],
        ];

        foreach ($campaigns as $campaignData) {
            ReferralCampaign::updateOrCreate(
                ['slug' => $campaignData['slug']],
                $campaignData
            );
        }

        $this->command->info('  ✓ Referral campaigns seeded: ' . count($campaigns) . ' campaigns');
    }
}
