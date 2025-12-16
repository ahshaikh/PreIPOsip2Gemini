<?php
// V-AUDIT-MODULE6-003 (Created): Service layer for Product business logic
// Extracts price history and relationship syncing logic from ProductController

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Update a product with all its related data (price history, relationships).
     * This method encapsulates complex business logic previously scattered in the controller.
     *
     * @param Product $product
     * @param array $validatedData Validated data from FormRequest
     * @param array $relationData Optional relationship data (highlights, founders, etc.)
     * @return Product Updated product instance
     */
    public function updateProduct(Product $product, array $validatedData, array $relationData = []): Product
    {
        DB::transaction(function () use ($product, $validatedData, $relationData) {

            // --- Step 1: Handle Price History Logic ---
            // Only record price history if current_market_price or face_value_per_unit changed
            $this->updatePriceHistory($product, $validatedData);

            // --- Step 2: Update the main product table ---
            $product->update($validatedData);

            // --- Step 3: Sync Relational Data (Non-Destructive) ---
            // Each relationship is updated only if explicitly provided in relationData
            $this->syncProductRelationships($product, $relationData);
        });

        return $product->fresh()->load('highlights', 'founders', 'fundingRounds', 'keyMetrics', 'riskDisclosures');
    }

    /**
     * Update product price history if price has changed.
     * Records both old and new price in the price_history table.
     *
     * @param Product $product
     * @param array $validatedData
     * @return void
     */
    private function updatePriceHistory(Product $product, array &$validatedData): void
    {
        // Determine new price from validated data
        $newPrice = $validatedData['current_market_price'] ?? $validatedData['face_value_per_unit'] ?? null;

        // Determine old price from existing product data
        $oldPrice = $product->current_market_price ?? $product->face_value_per_unit;

        // Only update history if:
        // 1. A new price is provided (not null)
        // 2. The price has actually changed (prevents duplicate history entries)
        if ($newPrice !== null && $newPrice != $oldPrice) {
            // Record the old price as of yesterday (prevents data loss)
            ProductPriceHistory::updateOrCreate(
                ['product_id' => $product->id, 'recorded_at' => today()->subDay()],
                ['price' => $oldPrice]
            );

            // Record the new price as of today
            ProductPriceHistory::updateOrCreate(
                ['product_id' => $product->id, 'recorded_at' => today()],
                ['price' => $newPrice]
            );

            // Update the timestamp of last price change
            $validatedData['last_price_update'] = now();
        }
    }

    /**
     * Sync all product relationships in a non-destructive manner.
     *
     * Logic:
     * - If relation data is null → Do nothing (preserve existing data)
     * - If relation data is empty array → Delete all related records
     * - If relation data has items → Update/Create items, delete missing ones
     *
     * @param Product $product
     * @param array $relationData Keys: highlights, founders, funding_rounds, key_metrics, risk_disclosures
     * @return void
     */
    private function syncProductRelationships(Product $product, array $relationData): void
    {
        // Define mapping between input keys and Eloquent relationship names
        $relationMap = [
            'highlights' => 'highlights',
            'founders' => 'founders',
            'funding_rounds' => 'fundingRounds',
            'key_metrics' => 'keyMetrics',
            'risk_disclosures' => 'riskDisclosures',
        ];

        // Iterate through each possible relationship
        foreach ($relationMap as $inputKey => $relationName) {
            // Only process if the relationship data was explicitly provided
            if (array_key_exists($inputKey, $relationData)) {
                $this->syncRelation($product, $relationName, $relationData[$inputKey]);
            }
        }
    }

    /**
     * Sync a single relationship for a product.
     * Uses updateOrCreate for upsert behavior and selective deletion.
     *
     * @param Product $product
     * @param string $relationName Eloquent relationship name (e.g., 'highlights', 'founders')
     * @param array|null $items Array of relation items, or null to skip
     * @return void
     */
    private function syncRelation(Product $product, string $relationName, ?array $items): void
    {
        // Null means "do not change existing data"
        if ($items === null) {
            return;
        }

        // Empty array means "delete all related records"
        // Extract IDs from items that have an 'id' field (existing records to keep)
        $existingIds = collect($items)->pluck('id')->filter()->toArray();

        // Delete items not present in the new list (cleanup orphaned records)
        $product->{$relationName}()->whereNotIn('id', $existingIds)->delete();

        // Update existing records or create new ones
        foreach ($items as $item) {
            $product->{$relationName}()->updateOrCreate(
                ['id' => $item['id'] ?? null], // Match by ID if exists
                $item // Update/Create with all provided data
            );
        }
    }
}
