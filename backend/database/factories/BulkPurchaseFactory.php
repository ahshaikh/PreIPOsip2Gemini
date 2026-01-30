<?php

namespace Database\Factories;

use App\Models\BulkPurchase;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * BulkPurchase Factory
 *
 * Updated for EPIC 4 - GAP 1 & STORY 4.2: Includes provenance fields
 * required for audit compliance.
 */
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
            // PROVENANCE fields (required for audit compliance)
            'company_id' => Company::factory(),
            'source_type' => 'manual_entry',
            'manual_entry_reason' => 'Factory-generated bulk purchase for testing purposes - this reason exceeds 50 characters as required.',
            'source_documentation' => 'Test documentation reference: FACTORY-' . $this->faker->uuid(),
            'approved_by_admin_id' => User::factory(),
            'verified_at' => now(),
            // Financial fields
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

    /**
     * Configure as a company listing source (vs manual entry).
     */
    public function fromCompanyListing(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'source_type' => 'company_listing',
                'company_share_listing_id' => 1, // Assumes a listing exists
                'manual_entry_reason' => null,
                'source_documentation' => null,
            ];
        });
    }

    /**
     * Configure with full inventory available (nothing allocated).
     */
    public function fullInventory(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'value_remaining' => $attributes['total_value_received'] ?? 100000,
            ];
        });
    }

    /**
     * Configure with no inventory available (all allocated).
     */
    public function depleted(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'value_remaining' => 0,
            ];
        });
    }
}
