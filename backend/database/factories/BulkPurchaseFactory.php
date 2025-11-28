<?php

namespace Database\Factories;

use App\Models\BulkPurchase;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkPurchaseFactory extends Factory
{
    protected $model = BulkPurchase::class;

    public function definition(): array
    {
        $faceValue = $this->faker->randomFloat(2, 100000, 10000000);
        $discountPercentage = $this->faker->randomFloat(2, 5, 30);
        $extraAllocationPercentage = $this->faker->randomFloat(2, 0, 20);
        $actualCostPaid = $faceValue * (1 - $discountPercentage / 100);
        $totalValueReceived = $faceValue * (1 + $extraAllocationPercentage / 100);

        return [
            'product_id' => Product::factory(),
            'admin_id' => User::factory(),
            'face_value_purchased' => $faceValue,
            'actual_cost_paid' => $actualCostPaid,
            'discount_percentage' => $discountPercentage,
            'extra_allocation_percentage' => $extraAllocationPercentage,
            'total_value_received' => $totalValueReceived,
            'value_remaining' => $totalValueReceived * $this->faker->randomFloat(2, 0.1, 1.0),
            'seller_name' => $this->faker->company(),
            'purchase_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
