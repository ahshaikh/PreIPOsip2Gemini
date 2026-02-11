<?php
// V-DEPLOY-1730-005 (Created) | V-FINAL-1730-344 (5-Tier Referral Logic) | V-FINAL-1730-621 (JSON Encode Fix)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan A - Family Starter',
                'monthly_amount' => 1000,
                'duration_months' => 36,
                'description' => 'Perfect for new investors.',
                'display_order' => 1,
            ],
            [
                'name' => 'Plan B - Wealth Builder',
                'monthly_amount' => 5000,
                'duration_months' => 36,
                'description' => 'Our most popular plan.',
                'is_featured' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Plan C - Growth Accelerator',
                'monthly_amount' => 10000,
                'duration_months' => 36,
                'description' => 'For serious investors.',
                'display_order' => 3,
            ],
            [
                'name' => 'Plan D - Elite Platinum',
                'monthly_amount' => 25000,
                'duration_months' => 36,
                'description' => 'Maximum bonus potential.',
                'display_order' => 4,
            ],
        ];

        // --- DEFINE THE CONFIGS AS PHP ARRAYS ---
        $defaultReferralTiers = [
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 3, 'multiplier' => 1.5],
            ['count' => 5, 'multiplier' => 2.0],
            ['count' => 10, 'multiplier' => 2.5],
            ['count' => 20, 'multiplier' => 3.0],
        ];
        
        $progressiveConfig = ['rate' => 0.5, 'start_month' => 4, 'max_percentage' => 20, 'overrides' => []];
        $milestoneConfig = [['month' => 12, 'amount' => 500], ['month' => 24, 'amount' => 1000], ['month' => 36, 'amount' => 2000]];
        $consistencyConfig = ['amount_per_payment' => 10, 'streaks' => [['months' => 6, 'multiplier' => 3]]];

        foreach ($plans as $planData) {
            $plan = Plan::create([
                'name' => $planData['name'],
                'slug' => Str::slug($planData['name']),
                'monthly_amount' => $planData['monthly_amount'],
                'duration_months' => $planData['duration_months'],
                'description' => $planData['description'],
                'is_featured' => $planData['is_featured'] ?? false,
                'display_order' => $planData['display_order'],
            ]);
            
            $plan->features()->createMany([
                ['feature_text' => 'Maximum reward eligibility'],
                ['feature_text' => 'Access to exclusive listings'],
                ['feature_text' => 'Maximum participation in incentive programs'],
            ]);

            // --- THE FIX: Use json_encode() to store as JSON strings ---
            $plan->configs()->createMany([
                ['config_key' => 'progressive_config', 'value' => json_encode($progressiveConfig)],
                ['config_key' => 'milestone_config', 'value' => json_encode($milestoneConfig)],
                ['config_key' => 'consistency_config', 'value' => json_encode($consistencyConfig)],
                ['config_key' => 'lucky_draw_entries', 'value' => json_encode(['count' => $planData['display_order'] * 2])],
                ['config_key' => 'referral_tiers', 'value' => json_encode($defaultReferralTiers)],
                ['config_key' => 'profit_share', 'value' => json_encode(['percentage' => ($planData['display_order'] * 5)])]
            ]);
        }
    }
}