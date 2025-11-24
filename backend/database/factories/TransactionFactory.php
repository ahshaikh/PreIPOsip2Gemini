<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'type' => $this->faker->randomElement(['deposit', 'withdrawal', 'bonus', 'investment', 'refund']),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'balance_after' => $this->faker->randomFloat(2, 0, 100000),
            'description' => $this->faker->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'status' => 'completed',
        ];
    }

    /**
     * Create a deposit transaction.
     */
    public function deposit(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'deposit',
            'description' => 'SIP Payment deposit',
        ]);
    }

    /**
     * Create a withdrawal transaction.
     */
    public function withdrawal(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'withdrawal',
            'description' => 'Withdrawal request',
        ]);
    }

    /**
     * Create a bonus transaction.
     */
    public function bonus(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'bonus',
            'description' => 'Bonus credit',
        ]);
    }

    /**
     * Create a pending transaction.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
