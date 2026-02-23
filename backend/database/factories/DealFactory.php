<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for Deal model.
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $title = $this->faker->company() . ' ' . $this->faker->randomElement(['Series A', 'Series B', 'Pre-IPO', 'Growth']);

        return [
            // V-WAVE2-FIX: Product must have inventory for Deal creation
            'product_id' => Product::factory()->activeWithInventory(),
            // V-WAVE2-FIX: Company must have at least TIER_1_UPCOMING for Deal creation
            'company_id' => Company::factory()->tierUpcoming(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'description' => $this->faker->paragraph(),
            'sector' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Consumer']),
            'deal_type' => 'live',
            'min_investment' => 10000,
            'max_investment' => 1000000,
            'valuation' => $this->faker->numberBetween(100000000, 10000000000),
            'valuation_currency' => 'INR',
            'share_price' => $this->faker->randomFloat(2, 100, 10000),
            'deal_opens_at' => now(),
            'deal_closes_at' => now()->addMonths(3),
            'status' => 'draft',
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * Configure as a draft deal.
     */
    public function draft(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Configure as an active deal.
     */
    public function active(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Configure as a live deal type.
     */
    public function live(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'deal_type' => 'live',
            'status' => 'active',
        ]);
    }

    /**
     * Configure as upcoming deal type.
     */
    public function upcoming(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'deal_type' => 'upcoming',
        ]);
    }
}
