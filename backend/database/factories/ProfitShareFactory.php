<?php
// V-WAVE1-FIX: Created factory for ProfitShare model

namespace Database\Factories;

use App\Models\ProfitShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfitShareFactory extends Factory
{
    protected $model = ProfitShare::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-6 months', '-1 month');
        $endDate = (clone $startDate)->modify('+1 month');

        return [
            'period_name' => $this->faker->monthName() . ' ' . $this->faker->year(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_pool' => $this->faker->randomFloat(2, 10000, 500000),
            'net_profit' => $this->faker->randomFloat(2, 5000, 250000),
            'status' => 'pending',
            'report_visibility' => 'private',
            'report_url' => null,
            'calculation_metadata' => $this->defaultCalculationMetadata(),
            'admin_id' => User::factory(),
            'published_by' => null,
            'published_at' => null,
        ];
    }

    /**
     * Default calculation metadata that satisfies validation.
     */
    protected function defaultCalculationMetadata(): array
    {
        return [
            'formula_type' => 'proportional',
            'eligibility_criteria' => [
                'min_months' => 3,
                'min_investment' => 1000,
                'require_active' => true,
            ],
            'eligible_users' => $this->faker->numberBetween(10, 100),
            'total_eligible_investment' => $this->faker->randomFloat(2, 100000, 1000000),
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Mark as calculated (ready for distribution).
     */
    public function calculated(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'calculated',
        ]);
    }

    /**
     * Mark as distributed.
     */
    public function distributed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'distributed',
            'published_by' => User::factory(),
            'published_at' => now(),
        ]);
    }

    /**
     * Mark as cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create with public visibility.
     */
    public function public(): static
    {
        return $this->state(fn(array $attributes) => [
            'report_visibility' => 'public',
        ]);
    }
}
