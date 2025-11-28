<?php

namespace Database\Factories;

use App\Models\ProductRiskDisclosure;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductRiskDisclosureFactory extends Factory
{
    protected $model = ProductRiskDisclosure::class;

    public function definition(): array
    {
        $risks = [
            [
                'category' => 'Market Risk',
                'severity' => 'high',
                'title' => 'Market Volatility',
                'description' => 'The pre-IPO market is subject to significant volatility. Share prices may fluctuate based on market conditions, company performance, and investor sentiment.',
            ],
            [
                'category' => 'Liquidity Risk',
                'severity' => 'high',
                'title' => 'Limited Liquidity',
                'description' => 'Pre-IPO shares have limited liquidity. You may not be able to sell your shares quickly or at the desired price until the company goes public.',
            ],
            [
                'category' => 'Business Risk',
                'severity' => 'medium',
                'title' => 'Business Performance',
                'description' => 'The company\'s business performance may not meet expectations. Revenue, profitability, and growth targets are subject to various business risks.',
            ],
            [
                'category' => 'Regulatory Risk',
                'severity' => 'medium',
                'title' => 'Regulatory Changes',
                'description' => 'Changes in regulations affecting the industry may impact the company\'s operations, profitability, and valuation.',
            ],
            [
                'category' => 'IPO Risk',
                'severity' => 'medium',
                'title' => 'IPO Timing Uncertainty',
                'description' => 'The timing and success of the IPO are uncertain. The company may delay or cancel the IPO based on market conditions.',
            ],
        ];

        $risk = $this->faker->randomElement($risks);

        return [
            'product_id' => Product::factory(),
            'risk_category' => $risk['category'],
            'severity' => $risk['severity'],
            'risk_title' => $risk['title'],
            'risk_description' => $risk['description'],
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
