<?php

namespace Database\Factories;

use App\Models\ProductHighlight;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductHighlightFactory extends Factory
{
    protected $model = ProductHighlight::class;

    public function definition(): array
    {
        $highlights = [
            'Market leader in {sector} with {number}% growth YoY',
            'Backed by top-tier investors including {investor}',
            'Revenue of ₹{amount} Cr in FY{year}',
            'User base of {number} million active users',
            'Profitable since {year} with consistent margins',
            'Expanding to {number} new markets in {year}',
            'Strong unit economics with {number}% contribution margin',
            'AI-powered platform with proprietary technology',
        ];

        return [
            'product_id' => Product::factory(),
            'content' => $this->faker->randomElement($highlights),
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
