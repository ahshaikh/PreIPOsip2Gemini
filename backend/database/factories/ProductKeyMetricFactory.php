<?php

namespace Database\Factories;

use App\Models\ProductKeyMetric;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductKeyMetricFactory extends Factory
{
    protected $model = ProductKeyMetric::class;

    public function definition(): array
    {
        $metrics = [
            ['metric_name' => 'Revenue', 'value' => $this->faker->numberBetween(10, 500), 'unit' => 'Cr'],
            ['metric_name' => 'GMV', 'value' => $this->faker->numberBetween(100, 5000), 'unit' => 'Cr'],
            ['metric_name' => 'Monthly Active Users', 'value' => $this->faker->numberBetween(1, 50), 'unit' => 'Million'],
            ['metric_name' => 'EBITDA Margin', 'value' => $this->faker->numberBetween(10, 40), 'unit' => '%'],
            ['metric_name' => 'Customer Retention', 'value' => $this->faker->numberBetween(70, 95), 'unit' => '%'],
            ['metric_name' => 'YoY Growth', 'value' => $this->faker->numberBetween(30, 200), 'unit' => '%'],
        ];

        $metric = $this->faker->randomElement($metrics);

        return [
            'product_id' => Product::factory(),
            'metric_name' => $metric['metric_name'],
            'value' => (string) $metric['value'],
            'unit' => $metric['unit'],
        ];
    }
}
