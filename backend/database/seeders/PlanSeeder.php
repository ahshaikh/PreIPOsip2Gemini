<?php
// V-REMEDIATE-1730-166

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates the four default investment plans.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan A - Family Starter',
                'monthly_amount' => 1000,
                'duration_months' => 36,
                'description' => 'The perfect starting point for new investors. Low risk, steady growth.',
                'display_order' => 1,
                'birthday_bonus' => 50,
                'anniversary_bonus' => 100,
            ],
            [
                'name' => 'Plan B - Wealth Builder',
                'monthly_amount' => 5000,
                'duration_months' => 36,
                'description' => 'Our most popular plan for young professionals looking for serious growth.',
                'is_featured' => true,
                'display_order' => 2,
                'birthday_bonus' => 100,
                'anniversary_bonus' => 250,
            ],
            [
                'name' => 'Plan C - Growth Accelerator',
                'monthly_amount' => 10000,
                'duration_months' => 36,
                'description' => 'For serious investors aiming for significant returns.',
                'display_order' => 3,
                'birthday_bonus' => 200,
                'anniversary_bonus' => 500,
            ],
            [
                'name' => 'Plan D - Elite Platinum',
                'monthly_amount' => 25000,
                'duration_months' => 36,
                'description' => 'Maximum investment, maximum bonus potential, and VIP perks.',
                'display_order' => 4,
                'birthday_bonus' => 500,
                'anniversary_bonus' => 1000,
            ],
        ];

        $defaultReferralTiers = json_encode([
            ['count' => 0, 'multiplier' => 1.0],
            ['count' => 3, 'multiplier' => 1.5],
            ['count' => 5, 'multiplier' => 2.0],
            ['count' => 10, 'multiplier' => 2.5],
            ['count' => 20, 'multiplier' => 3.0],
        ]);

        foreach ($plans as $planData) {
            // FIX: We separate the DB columns from the Config values
            // 'birthday_bonus' and 'anniversary_bonus' are NOT columns in the 'plans' table.
            // We remove them from the array used to create the Plan model.
            $planAttributes = Arr::except($planData, ['birthday_bonus', 'anniversary_bonus']);

            $plan = Plan::create(
                $planAttributes + ['slug' => Str::slug($planData['name'])]
            );
            
            $plan->features()->createMany([
                ['feature_text' => '10% Guaranteed Bonus'],
                ['feature_text' => 'Zero Platform Fees'],
                ['feature_text' => 'Zero Exit Fees'],
            ]);

            // We use the original $planData here to get the bonus values for the JSON config
            $celebrationConfig = json_encode([
                'birthday_amount' => $planData['birthday_bonus'],
                'anniversary_amount' => $planData['anniversary_bonus'],
            ]);

            $plan->configs()->createMany([
                ['config_key' => 'progressive_config', 'value' => json_encode(['rate' => 0.5, 'start_month' => 4])],
                ['config_key' => 'milestone_config', 'value' => json_encode([['month' => 12, 'amount' => 500], ['month' => 24, 'amount' => 1000]])],
                ['config_key' => 'lucky_draw_entries', 'value' => json_encode(['count' => $planData['display_order'] * 2])],
                ['config_key' => 'referral_tiers', 'value' => $defaultReferralTiers],
                ['config_key' => 'celebration_bonus_config', 'value' => $celebrationConfig],
            ]);
        }
    }
}