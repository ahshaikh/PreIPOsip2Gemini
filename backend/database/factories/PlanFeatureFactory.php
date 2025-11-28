<?php

namespace Database\Factories;

use App\Models\PlanFeature;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFeatureFactory extends Factory
{
    protected $model = PlanFeature::class;

    public function definition(): array
    {
        $features = [
            'Access to exclusive pre-IPO opportunities',
            'Priority allocation in popular offerings',
            'Dedicated relationship manager',
            'Research reports and market insights',
            'Zero transaction fees',
            'Early access to new listings',
            'Portfolio analytics dashboard',
            'Tax advisory support',
            'Educational webinars and workshops',
            'Quarterly investment reviews',
        ];

        return [
            'plan_id' => Plan::factory(),
            'feature_text' => $this->faker->randomElement($features),
            'icon' => $this->faker->randomElement(['check-circle', 'star', 'shield', 'trending-up', 'award']),
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
