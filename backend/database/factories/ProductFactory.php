<?php
// V-FACTORY (Created for comprehensive test coverage)
// STORY 3.1: Updated to support company_id for disclosure tier tests

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $companies = ['TechStart', 'FinGrow', 'HealthFirst', 'EduLearn', 'GreenEnergy', 'CloudNine', 'DataPro', 'SmartSolutions'];
        $company = $this->faker->randomElement($companies) . ' ' . $this->faker->unique()->word();
        $slug = \Illuminate\Support\Str::slug($company);

        return [
            'company_id' => Company::factory(),
            'name' => $company,
            'slug' => $slug,
            'sector' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education', 'Energy', 'E-commerce', 'Fintech']),
            'face_value_per_unit' => $this->faker->randomElement([10, 100, 500, 1000]),
            'current_market_price' => $this->faker->randomFloat(2, 100, 5000),
            'last_price_update' => now()->subDays($this->faker->numberBetween(1, 30)),
            'auto_update_price' => false,
            'min_investment' => $this->faker->randomElement([1000, 5000, 10000, 25000]),
            'expected_ipo_date' => $this->faker->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
            'status' => 'active',
            'sebi_approval_number' => 'SEBI/' . $this->faker->numerify('####/####'),
            'sebi_approval_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'is_featured' => $this->faker->boolean(30),
            'display_order' => $this->faker->numberBetween(0, 100),
            'description' => json_encode([
                'overview' => $this->faker->paragraphs(2, true),
                'business_model' => $this->faker->paragraph(),
                'market_opportunity' => $this->faker->paragraph(),
            ]),
        ];
    }

    /**
     * Product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
            'display_order' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
