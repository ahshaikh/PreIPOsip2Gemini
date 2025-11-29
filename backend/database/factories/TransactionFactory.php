<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\UserInvestment;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        // Random reference type
        $referenceType = $this->faker->randomElement([
            'payment',
            'withdrawal',
            'investment',
            'bonus',
            'refund',
        ]);

        // Assign a matching reference_id safely
        $referenceId = match ($referenceType) {
            'payment'     => Payment::inRandomOrder()->value('id') ?? 1,
            'withdrawal'  => Withdrawal::inRandomOrder()->value('id') ?? 1,
            'investment'  => UserInvestment::inRandomOrder()->value('id') ?? 1,
            'bonus'       => 1, // You may create a Bonus model later
            'refund'      => Payment::inRandomOrder()->value('id') ?? 1,
            default       => 1,
        };

        return [
            'wallet_id'      => Wallet::factory(),
            'user_id'        => User::inRandomOrder()->value('id') ?? 1,
            'type'           => $referenceType, // matches reference_type logically
            'amount'         => $this->faker->randomFloat(2, 100, 10000),
            'balance_after'  => $this->faker->randomFloat(2, 0, 100000),
            'description'    => $this->faker->sentence(),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'transaction_id' => Str::uuid(),
            'tds_deducted'   => 0,
            'status'         => $this->faker->randomElement(['completed', 'pending', 'failed']),
        ];
    }

    /** Deposit transaction */
    public function deposit(): static
    {
        return $this->state(fn(array $attributes) => [
            'type'           => 'deposit',
            'reference_type' => 'payment',
        ]);
    }

    /** Withdrawal transaction */
    public function withdrawal(): static
    {
        return $this->state(fn(array $attributes) => [
            'type'           => 'withdrawal',
            'reference_type' => 'withdrawal',
        ]);
    }

    /** Bonus transaction */
    public function bonus(): static
    {
        return $this->state(fn(array $attributes) => [
            'type'           => 'bonus',
            'reference_type' => 'bonus',
        ]);
    }

    /** Pending transaction */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
