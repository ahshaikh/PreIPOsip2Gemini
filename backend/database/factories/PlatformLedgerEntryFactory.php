<?php

namespace Database\Factories;

use App\Models\PlatformLedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for PlatformLedgerEntry model (EPIC 4).
 */
class PlatformLedgerEntryFactory extends Factory
{
    protected $model = PlatformLedgerEntry::class;

    public function definition(): array
    {
        $amountPaise = $this->faker->numberBetween(100000, 10000000); // ₹1,000 to ₹100,000

        return [
            'type' => PlatformLedgerEntry::TYPE_DEBIT,
            'amount_paise' => $amountPaise,
            'balance_before_paise' => 0,
            'balance_after_paise' => -$amountPaise,
            'currency' => 'INR',
            'source_type' => PlatformLedgerEntry::SOURCE_BULK_PURCHASE,
            'source_id' => $this->faker->randomNumber(5),
            'description' => $this->faker->sentence(),
            'entry_pair_id' => null,
            'actor_id' => User::factory(),
            'metadata' => [
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ],
        ];
    }

    /**
     * Configure as a debit entry.
     */
    public function debit(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => PlatformLedgerEntry::TYPE_DEBIT,
            ];
        });
    }

    /**
     * Configure as a credit entry.
     */
    public function credit(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => PlatformLedgerEntry::TYPE_CREDIT,
            ];
        });
    }
}
