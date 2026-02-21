<?php
// V-FACTORY (Created for comprehensive test coverage)
// STORY 3.1: Updated to support company_id for disclosure tier tests
// V-AUDIT-FIX-2026: Moved inventory creation to explicit activeWithInventory() state

namespace Database\Factories;

use App\Models\BulkPurchase;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Configure the model factory.
     *
     * V-AUDIT-FIX-2026: Removed automatic inventory creation from configure().
     * Use activeWithInventory() state explicitly when you need inventory.
     * This prevents unintended side effects in tests.
     */
    public function configure(): static
    {
        return $this;
    }

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
            'status' => 'draft', // V-AUDIT-FIX-2026: New products must start with 'draft' per Product model lifecycle
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

    /**
     * V-AUDIT-FIX-2026: Active product WITH inventory.
     *
     * Use this when you need a product that can actually be purchased.
     * Creates the necessary BulkPurchase inventory record.
     */
    public function activeWithInventory(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ])->afterCreating(function (Product $product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'admin_id' => User::factory(),
                'approved_by_admin_id' => User::factory(),
                'face_value_purchased' => 1000000,
                'actual_cost_paid' => 800000,
                'discount_percentage' => 20,
                'extra_allocation_percentage' => 0,
                'total_value_received' => 1000000,
                'value_remaining' => 1000000, // Full inventory available
            ]);
        });
    }

    /**
     * V-AUDIT-FIX-2026: Product with custom inventory amount.
     *
     * @param int $inventoryValue Total inventory value in rupees
     * @param int $discountPercent Discount percentage
     */
    public function withInventory(int $inventoryValue = 1000000, int $discountPercent = 20): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ])->afterCreating(function (Product $product) use ($inventoryValue, $discountPercent) {
            $actualCost = $inventoryValue * (1 - $discountPercent / 100);

            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'admin_id' => User::factory(),
                'approved_by_admin_id' => User::factory(),
                'face_value_purchased' => $inventoryValue,
                'actual_cost_paid' => $actualCost,
                'discount_percentage' => $discountPercent,
                'extra_allocation_percentage' => 0,
                'total_value_received' => $inventoryValue,
                'value_remaining' => $inventoryValue,
            ]);
        });
    }

    /**
     * V-AUDIT-FIX-2026: Product with depleted inventory.
     * For testing insufficient inventory scenarios.
     */
    public function depletedInventory(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ])->afterCreating(function (Product $product) {
            BulkPurchase::factory()->create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'admin_id' => User::factory(),
                'approved_by_admin_id' => User::factory(),
                'face_value_purchased' => 1000000,
                'actual_cost_paid' => 800000,
                'discount_percentage' => 20,
                'extra_allocation_percentage' => 0,
                'total_value_received' => 1000000,
                'value_remaining' => 0, // Fully depleted
            ]);
        });
    }

    /**
     * GAP 3 FIX: Backward compatibility alias for legacy tests.
     *
     * @deprecated Use activeWithInventory() instead
     */
    public function legacyActive(): static
    {
        return $this->activeWithInventory();
    }
}
