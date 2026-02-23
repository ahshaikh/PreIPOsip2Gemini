<?php
// V-WAVE2-FIX: Created InvestmentFactory for Investment model

namespace Database\Factories;

use App\Models\Investment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Deal;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Investment>
 */
class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'deal_id' => Deal::factory(),  // V-WAVE2-FIX: Added required deal_id FK
            'company_id' => Company::factory(),
            'investment_code' => 'INV-' . Str::upper(Str::random(10)),
            'shares_allocated' => $this->faker->numberBetween(1, 100),
            'price_per_share' => $this->faker->randomFloat(2, 10, 1000),
            'total_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'status' => 'pending',
            'invested_at' => now(),
        ];
    }

    /**
     * Active investment state
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Pending investment state
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Exited investment state
     */
    public function exited(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'exited',
            'exited_at' => now(),
            'exit_price_per_share' => $this->faker->randomFloat(2, 100, 5000),
            'exit_amount' => $this->faker->randomFloat(2, 5000, 500000),
            'profit_loss' => $this->faker->randomFloat(2, -10000, 50000),
        ]);
    }
}
