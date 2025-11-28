<?php

namespace Database\Factories;

use App\Models\ProductFundingRound;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFundingRoundFactory extends Factory
{
    protected $model = ProductFundingRound::class;

    public function definition(): array
    {
        $rounds = ['Seed', 'Series A', 'Series B', 'Series C', 'Series D', 'Pre-IPO'];
        $investors = [
            'Sequoia Capital, Accel Partners',
            'Tiger Global, SoftBank Vision Fund',
            'Matrix Partners, Lightspeed Venture',
            'Elevation Capital, Peak XV Partners',
            'Y Combinator, Nexus Venture Partners',
        ];

        return [
            'product_id' => Product::factory(),
            'round_name' => $this->faker->randomElement($rounds),
            'date' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'amount' => $this->faker->randomFloat(2, 1000000, 500000000),
            'valuation' => $this->faker->randomFloat(2, 10000000, 5000000000),
            'investors' => $this->faker->randomElement($investors),
        ];
    }
}
