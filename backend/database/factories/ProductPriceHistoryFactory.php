<?php

namespace Database\Factories;

use App\Models\ProductPriceHistory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceHistoryFactory extends Factory
{
    protected $model = ProductPriceHistory::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'price' => $this->faker->randomFloat(2, 100, 5000),
            'recorded_at' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
