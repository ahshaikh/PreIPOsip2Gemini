<?php
// V-WAVE2-FIX: Created missing factory for FeatureFlag model

namespace Database\Factories;

use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(3),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'percentage' => null, // null = 100% rollout when active
        ];
    }

    /**
     * Flag is globally disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Flag is enabled with percentage rollout.
     */
    public function percentageRollout(int $percentage = 50): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Flag is enabled for all users.
     */
    public function enabledForAll(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
            'percentage' => 100,
        ]);
    }
}
