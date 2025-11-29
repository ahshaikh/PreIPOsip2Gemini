<?php

namespace Database\Factories;

use App\Models\ReferralCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReferralCampaignFactory extends Factory
{
    protected $model = ReferralCampaign::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->words(3, true),
            'start_date'  => now()->subDays(10),
            'end_date'    => now()->addDays(30),
            'bonus_amount'=> $this->faker->randomFloat(2, 100, 500),
            'multiplier'  => $this->faker->randomFloat(1, 1.0, 2.0),
            'is_active'   => true,
        ];
    }

    public function expired(): static
    {
        return $this->state([
            'start_date' => now()->subDays(60),
            'end_date'   => now()->subDays(30),
            'is_active'  => false,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state([
            'start_date' => now()->addDays(7),
            'end_date'   => now()->addDays(37),
        ]);
    }

    public function highBonus(): static
    {
        return $this->state([
            'bonus_amount' => 1000,
            'multiplier'   => 2.5,
        ]);
    }
}
