<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'status' => 'pending',
            'bank_account_number' => $this->faker->numerify('##########'),
            'bank_ifsc' => strtoupper($this->faker->lexify('????')) . $this->faker->numerify('0######'),
            'bank_name' => $this->faker->company() . ' Bank',
            'account_holder_name' => $this->faker->name(),
            'requested_at' => now(),
        ];
    }

    /**
     * Withdrawal is approved.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Withdrawal is processed.
     */
    public function processed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'processed',
            'approved_at' => now()->subHours(2),
            'processed_at' => now(),
            'utr_number' => $this->faker->uuid(),
        ]);
    }

    /**
     * Withdrawal is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
