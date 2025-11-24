<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $names = ['Bronze SIP', 'Silver SIP', 'Gold SIP', 'Platinum SIP', 'Diamond SIP'];
        $name = $this->faker->randomElement($names) . ' ' . $this->faker->unique()->numberBetween(1, 100);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'monthly_amount' => $this->faker->randomElement([1000, 2500, 5000, 10000, 25000]),
            'duration_months' => $this->faker->randomElement([12, 24, 36, 60]),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'bonus_multiplier' => $this->faker->randomFloat(1, 1.0, 2.0),
            'display_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Indicate the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a premium plan.
     */
    public function premium(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Premium Plan',
            'monthly_amount' => 50000,
            'bonus_multiplier' => 2.5,
        ]);
    }
}
