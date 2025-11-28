<?php

namespace Database\Factories;

use App\Models\PlanConfig;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanConfigFactory extends Factory
{
    protected $model = PlanConfig::class;

    public function definition(): array
    {
        $configs = [
            ['config_key' => 'bonus_multiplier', 'value' => ['base' => 1.0, 'max' => 1.5]],
            ['config_key' => 'transaction_fee', 'value' => ['percentage' => 0.5, 'min' => 10, 'max' => 500]],
            ['config_key' => 'withdrawal_limit', 'value' => ['daily' => 100000, 'monthly' => 500000]],
            ['config_key' => 'features', 'value' => ['priority_support' => true, 'research_access' => true]],
        ];

        $config = $this->faker->randomElement($configs);

        return [
            'plan_id' => Plan::factory(),
            'config_key' => $config['config_key'],
            'value' => json_encode($config['value']),
        ];
    }
}
