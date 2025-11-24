<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\UserInvestment;
use App\Models\User;
use App\Models\Product;
use App\Models\BulkPurchase;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserInvestmentFactory extends Factory
{
    protected $model = UserInvestment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'bulk_purchase_id' => null,
            'shares' => $this->faker->numberBetween(10, 1000),
            'price_per_share' => $this->faker->randomFloat(2, 100, 2000),
            'total_amount' => function (array $attributes) {
                return $attributes['shares'] * $attributes['price_per_share'];
            },
            'source' => 'sip',
            'status' => 'active',
            'allocated_at' => now(),
        ];
    }

    /**
     * Investment from bulk purchase.
     */
    public function fromBulkPurchase(): static
    {
        return $this->state(fn(array $attributes) => [
            'bulk_purchase_id' => BulkPurchase::factory(),
            'source' => 'bulk',
        ]);
    }

    /**
     * Investment from SIP.
     */
    public function fromSip(): static
    {
        return $this->state(fn(array $attributes) => [
            'source' => 'sip',
        ]);
    }

    /**
     * Investment is pending allocation.
     */
    public function pendingAllocation(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'allocated_at' => null,
        ]);
    }

    /**
     * Investment is sold/exited.
     */
    public function exited(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'exited',
            'exited_at' => now(),
        ]);
    }
}
