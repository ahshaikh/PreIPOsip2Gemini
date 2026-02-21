<?php

namespace Database\Factories;

use App\Models\UserInvestment;
use App\Models\User;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\BulkPurchase;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserInvestmentFactory extends Factory
{
    protected $model = UserInvestment::class;

    public function definition(): array
    {
        $shares = $this->faker->numberBetween(10, 1000);
        $price  = $this->faker->randomFloat(2, 100, 2000);
        $total  = $shares * $price;

        return [
            'user_id'          => User::factory(),
            'product_id'       => Product::factory(),
            'payment_id'       => null,
            'subscription_id'  => Subscription::factory(), // V-AUDIT-FIX-2026: Required field (NOT NULL)
            'bulk_purchase_id' => BulkPurchase::factory(), // V-AUDIT-FIX-2026: Required field (NOT NULL)

            // Required by DB
            'shares'           => $shares,
            'price_per_share'  => $price,
            'total_amount'     => $total,

            // Required & no default in DB
            'units_allocated'  => $shares,
            'value_allocated'  => $total,

            'source'           => 'sip',
            'status'           => 'active',
            'allocated_at'     => now(),
            'exited_at'        => null,
            'is_reversed'      => false, // V-AUDIT-FIX-2026: Required field
        ];
    }

    public function fromBulkPurchase(): static
    {
        return $this->state(fn(array $attributes) => [
            'bulk_purchase_id' => BulkPurchase::factory(),
            'source'           => 'bulk',
        ]);
    }

    public function fromSip(): static
    {
        return $this->state(fn(array $attributes) => [
            'source' => 'sip',
        ]);
    }

    public function pendingAllocation(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'       => 'pending',
            'allocated_at' => null,
        ]);
    }

    public function exited(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'    => 'exited',
            'exited_at' => now(),
        ]);
    }
}
