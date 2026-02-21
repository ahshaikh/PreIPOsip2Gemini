<?php
// V-FACTORY (Created for comprehensive test coverage)
// V-CANONICAL-PAISE-2026: Updated for paise-only schema
// V-AUDIT-FIX-2026: Fixed reference_id to always create valid model references

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
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
        // V-AUDIT-FIX-2026: Default to 'bonus_credit' which doesn't require a related model
        // V-FIX-TRANSACTION-FACTORY-2026: Use correct enum value 'bonus_credit' not 'bonus'
        // Use specific state methods to create transactions with proper references
        $amountPaise = $this->faker->numberBetween(10000, 1000000); // 100 to 10000 rupees in paise
        $balanceBeforePaise = $this->faker->numberBetween(0, 10000000);
        $balanceAfterPaise = $balanceBeforePaise + $amountPaise;

        return [
            'wallet_id'           => Wallet::factory(),
            'user_id'             => User::factory(),
            'type'                => 'bonus_credit', // V-FIX: Use valid TransactionType enum value
            'amount_paise'        => $amountPaise,
            'balance_before_paise'=> $balanceBeforePaise,
            'balance_after_paise' => $balanceAfterPaise,
            'tds_deducted_paise'  => 0,
            'description'         => $this->faker->sentence(),
            'reference_type'      => 'bonus_credit',
            'reference_id'        => null, // V-AUDIT-FIX-2026: No reference for bonus_credit
            'transaction_id'      => Str::uuid(),
            'status'              => $this->faker->randomElement(['completed', 'pending', 'failed']),
            'is_reversed'         => false,
        ];
    }

    /**
     * V-AUDIT-FIX-2026: Transaction linked to a payment.
     * Creates the payment to ensure valid reference.
     */
    public function forPayment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'payment',
                'reference_type' => 'payment',
                'reference_id' => Payment::factory(),
            ];
        });
    }

    /**
     * V-AUDIT-FIX-2026: Transaction linked to a withdrawal.
     * Creates the withdrawal to ensure valid reference.
     */
    public function forWithdrawal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'withdrawal',
                'reference_type' => 'withdrawal',
                'reference_id' => Withdrawal::factory(),
            ];
        });
    }

    /**
     * V-AUDIT-FIX-2026: Transaction linked to an investment.
     * Creates the investment to ensure valid reference.
     */
    public function forInvestment(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'investment',
                'reference_type' => 'investment',
                'reference_id' => UserInvestment::factory(),
            ];
        });
    }

    /**
     * V-AUDIT-FIX-2026: Refund transaction linked to original payment.
     */
    public function refund(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'refund',
                'reference_type' => 'payment',
                'reference_id' => Payment::factory(),
            ];
        });
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

    /** Bonus credit transaction */
    public function bonus(): static
    {
        return $this->state(fn(array $attributes) => [
            'type'           => 'bonus_credit', // V-FIX: Use valid TransactionType enum value
            'reference_type' => 'bonus_credit',
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
