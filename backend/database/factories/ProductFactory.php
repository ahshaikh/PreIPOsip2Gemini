<?php
// V-FACTORY (Created for comprehensive test coverage)

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $companies = ['TechStart', 'FinGrow', 'HealthFirst', 'EduLearn', 'GreenEnergy'];
        $company = $this->faker->randomElement($companies) . ' ' . $this->faker->unique()->word();

        return [
            'name' => $company,
            'slug' => \Illuminate\Support\Str::slug($company),
            'sector' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education', 'Energy']),
            'description' => $this->faker->paragraphs(2, true),
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'total_shares_available' => $this->faker->numberBetween(100000, 1000000),
            'price_per_share' => $this->faker->randomFloat(2, 100, 5000),
            'min_investment' => $this->faker->randomElement([1000, 5000, 10000]),
            'max_investment' => $this->faker->randomElement([100000, 500000, 1000000]),
            'is_active' => true,
            'status' => 'open',
            'opens_at' => now()->subDays(30),
            'closes_at' => now()->addDays(60),
        ];
    }

    /**
     * Product is closed for investment.
     */
    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
            'closes_at' => now()->subDays(10),
        ]);
    }

    /**
     * Product is coming soon.
     */
    public function upcoming(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'upcoming',
            'opens_at' => now()->addDays(30),
            'closes_at' => now()->addDays(90),
        ]);
    }

    /**
     * Product has limited availability.
     */
    public function limited(): static
    {
        return $this->state(fn(array $attributes) => [
            'total_shares_available' => 1000,
        ]);
    }
}
