<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\BonusTransaction;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class BonusTransactionFactory extends Factory
{
    protected $model = BonusTransaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subscription_id' => Subscription::factory(),
            'payment_id' => Payment::factory(),
            'type' => $this->faker->randomElement(['progressive', 'milestone', 'consistency', 'referral', 'celebration']),
            'amount' => $this->faker->randomFloat(2, 50, 5000),
            'multiplier_applied' => $this->faker->randomFloat(1, 1.0, 2.0),
            'base_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Create a progressive bonus.
     */
    public function progressive(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'progressive',
            'description' => 'Progressive Monthly Bonus',
        ]);
    }

    /**
     * Create a milestone bonus.
     */
    public function milestone(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'milestone',
            'description' => 'Milestone Achievement Bonus',
        ]);
    }

    /**
     * Create a referral bonus.
     */
    public function referral(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'referral',
            'description' => 'Referral Bonus',
        ]);
    }

    /**
     * Create a consistency bonus.
     */
    public function consistency(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'consistency',
            'description' => 'On-Time Payment Bonus',
        ]);
    }

    /**
     * Create a celebration bonus.
     */
    public function celebration(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'celebration',
            'description' => 'Festival Celebration Bonus',
        ]);
    }
}
