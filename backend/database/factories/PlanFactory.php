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
        // V-FIX-PLAN-FACTORY-2026: Use UUID suffix for truly unique slugs
        // faker->unique()->numberBetween(1, 100) has limited range and resets between tests,
        // causing duplicate slug errors when tests run in random order.
        $names = ['Bronze SIP', 'Silver SIP', 'Gold SIP', 'Platinum SIP', 'Diamond SIP'];
        $uniqueSuffix = substr(md5(uniqid(mt_rand(), true)), 0, 8);
        $name = $this->faker->randomElement($names) . ' ' . $uniqueSuffix;

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'razorpay_plan_id' => 'plan_' . $this->faker->unique()->bothify('??????????'),
            'monthly_amount' => $this->faker->randomElement([1000, 2500, 5000, 10000, 25000]),
            'duration_months' => $this->faker->randomElement([12, 24, 36, 60]),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'is_featured' => $this->faker->boolean(20),
            'display_order' => $this->faker->numberBetween(1, 10),
            'available_from' => now()->subMonths($this->faker->numberBetween(1, 6)),
            'available_until' => null,
            'max_subscriptions_per_user' => $this->faker->randomElement([1, 2, 3, 5]),
            'allow_pause' => true,
            'max_pause_count' => $this->faker->randomElement([2, 3, 4]),
            'max_pause_duration_months' => $this->faker->randomElement([1, 2, 3]),
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
     * Create a featured plan.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
            'display_order' => $this->faker->numberBetween(1, 3),
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
            'is_featured' => true,
        ]);
    }
}
