<?php
// V-FACTORY (Created for comprehensive test coverage)
// V-CANONICAL-PAISE-2026: Updated for paise-only schema
// V-AUDIT-FIX-2026: Added state methods for approved/processed/rejected

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
        $amountPaise = $this->faker->numberBetween(50000, 5000000); // 500 to 50000 rupees in paise

        return [
            'user_id'           => User::factory(),
            'wallet_id'         => Wallet::factory(),
            'amount_paise'      => $amountPaise,
            'fee_paise'         => 0,
            'tds_deducted_paise'=> 0,
            'net_amount_paise'  => $amountPaise,
            'status'            => 'pending',
            'bank_details'      => [
                'bank_account_number' => $this->faker->bankAccountNumber(),
                'bank_ifsc'           => 'TEST0001234',
                'bank_name'           => $this->faker->company(),
                'account_holder_name' => $this->faker->name(),
            ],
            'admin_id'          => null,
            'utr_number'        => null,
            'rejection_reason'  => null,
        ];
    }

    /**
     * V-AUDIT-FIX-2026: Withdrawal has been approved by admin.
     */
    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'approved',
            'admin_id' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * V-AUDIT-FIX-2026: Withdrawal has been processed (funds transferred).
     */
    public function processed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'processed',
            'admin_id' => User::factory(),
            'approved_at' => now()->subHours(2),
            'processed_at' => now(),
            'utr_number' => 'UTR' . $this->faker->numerify('##########'),
        ]);
    }

    /**
     * V-AUDIT-FIX-2026: Withdrawal has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'rejected',
            'admin_id' => User::factory(),
            'rejection_reason' => $this->faker->randomElement([
                'Insufficient KYC verification',
                'Bank details mismatch',
                'Suspicious activity detected',
                'Amount exceeds daily limit',
            ]),
        ]);
    }

    /**
     * V-AUDIT-FIX-2026: Withdrawal is on hold pending review.
     */
    public function onHold(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'on_hold',
            'admin_notes' => 'Pending additional verification',
        ]);
    }
}
