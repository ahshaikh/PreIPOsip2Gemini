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
            'user_id'     => User::factory(),
            'wallet_id'   => Wallet::factory(),
            'amount'      => $amount = $this->faker->randomFloat(2, 500, 50000),

            'fee'         => 0,
            'tds_deducted'=> 0,
            'net_amount'  => $amount,

            'status'      => 'pending',

            'bank_details'=> [
                'bank_account_number' => $this->faker->bankAccountNumber(),
                'bank_ifsc'           => 'TEST0001234',
                'bank_name'           => $this->faker->company(),
                'account_holder_name' => $this->faker->name(),
            ],

            'admin_id'    => null,
            'utr_number'  => null,
            'rejection_reason' => null,
            'requested_at' => now(),
        ];
    }
}
