<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\ReferralCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralCampaignFactory extends Factory
{
    protected $model = ReferralCampaign::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true) . ' Campaign';

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->paragraph(),
            'bonus_amount' => $this->faker->randomFloat(2, 100, 500),
            'multiplier' => $this->faker->randomFloat(1, 1.0, 2.0),
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(30),
            'is_active' => true,
            'max_referrals' => $this->faker->optional()->numberBetween(10, 100),
        ];
    }

    /**
     * Campaign is expired.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
            'is_active' => false,
        ]);
    }

    /**
     * Campaign is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn(array $attributes) => [
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(37),
        ]);
    }

    /**
     * Campaign with high bonus.
     */
    public function highBonus(): static
    {
        return $this->state(fn(array $attributes) => [
            'bonus_amount' => 1000,
            'multiplier' => 2.5,
        ]);
    }
}
