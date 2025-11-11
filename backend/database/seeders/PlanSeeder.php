<?php
// V-DEPLOY-1730-005
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use Illuminate\Support\Str;

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
            ],
            [
                'name' => 'Plan B - Wealth Builder',
                'monthly_amount' => 5000,
                'duration_months' => 36,
                'description' => 'Our most popular plan for young professionals looking for serious growth.',
                'is_featured' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Plan C - Growth Accelerator',
                'monthly_amount' => 10000,
                'duration_months' => 36,
                'description' => 'For serious investors aiming for significant returns.',
                'display_order' => 3,
            ],
            [
                'name' => 'Plan D - Elite Platinum',
                'monthly_amount' => 25000,
                'duration_months' => 36,
                'description' => 'Maximum investment, maximum bonus potential, and VIP perks.',
                'display_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::create(
                $planData + ['slug' => Str::slug($planData['name'])]
            );
            
            // Seed sample features
            $plan->features()->createMany([
                ['feature_text' => '10% Guaranteed Bonus'],
                ['feature_text' => 'Zero Platform Fees'],
                ['feature_text' => 'Zero Exit Fees'],
            ]);

            // Seed sample "Configurable Logic"
            $plan->configs()->createMany([
                ['config_key' => 'progressive_rate', 'value' => json_encode(['rate' => 0.5, 'start_month' => 4])],
                ['config_key' => 'milestones', 'value' => json_encode([['month' => 12, 'amount' => 500], ['month' => 24, 'amount' => 1000]])],
                ['config_key' => 'lucky_draw_entries', 'value' => json_encode(['count' => $planData['display_order'] * 2])], // 2, 4, 6, 8 entries
            ]);
        }
    }
}