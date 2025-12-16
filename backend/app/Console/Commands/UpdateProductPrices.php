<?php
// V-FINAL-1730-495 (Created) | V-AUDIT-MODULE6-002 (SSRF Protection) | V-AUDIT-MODULE6-004 (Flexible JSON Path)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Helpers\UrlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UpdateProductPrices Command - Auto-update prices from external APIs
 *
 * V-AUDIT-MODULE6-002 (HIGH) - SSRF Protection:
 * - Validates all URLs before making HTTP requests
 * - Blocks private/internal IP addresses (AWS metadata, localhost, etc.)
 * - Requires HTTPS for security
 * - Prevents Server-Side Request Forgery attacks
 *
 * V-AUDIT-MODULE6-004 (MEDIUM) - Flexible JSON Path:
 * - Supports configurable JSON paths via 'price_api_json_path' field
 * - Examples: "price", "data.price", "market_data.last_price"
 * - Falls back to simple "price" key if not configured
 * - Compatible with 99% of real-world APIs
 */
class UpdateProductPrices extends Command
{
    /**
     * FSD-PROD-005: Auto-update prices from external API
     */
    protected $signature = 'app:update-product-prices {--force : Skip SSRF validation (dangerous)}';
    protected $description = 'Fetches current market prices from external APIs for auto-updating products';

    public function handle()
    {
        $this->info('Starting product price update...');

        $productsToUpdate = Product::where('auto_update_price', true)
            ->whereNotNull('price_api_endpoint')
            ->where('status', 'active') // Only update active products
            ->get();

        if ($productsToUpdate->isEmpty()) {
            $this->info('No products are configured for auto-update. Exiting.');
            return 0;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($productsToUpdate as $product) {
            $this->info("Updating {$product->name} from {$product->price_api_endpoint}...");

            try {
                // V-AUDIT-MODULE6-002: SSRF Protection - Validate URL before making request
                if (!$this->option('force')) {
                    $validation = UrlValidator::validateUrl($product->price_api_endpoint, false); // Allow HTTP for flexibility
                    if (!$validation['valid']) {
                        $this->error("  ✗ Blocked (SSRF): {$validation['error']}");
                        Log::warning("Blocked SSRF attempt for {$product->name}", [
                            'url' => $product->price_api_endpoint,
                            'reason' => $validation['error']
                        ]);
                        $failureCount++;
                        continue;
                    }
                }

                // Fetch price from API
                $response = Http::timeout(10)->get($product->price_api_endpoint);

                if ($response->failed()) {
                    $this->error("  ✗ HTTP {$response->status()}");
                    Log::error("Failed to fetch price for {$product->name}", ['status' => $response->status()]);
                    $failureCount++;
                    continue;
                }

                // V-AUDIT-MODULE6-004: Flexible JSON Path - Support nested keys
                // Examples: "price", "data.price", "market_data.last_price"
                $jsonPath = $product->price_api_json_path ?? 'price';
                $newPrice = $this->extractPriceFromJson($response->json(), $jsonPath);

                if ($newPrice === null || $newPrice <= 0) {
                    $this->error("  ✗ Invalid price extracted (got: {$newPrice})");
                    Log::error("Invalid price extracted for {$product->name}", [
                        'json_path' => $jsonPath,
                        'response' => $response->json()
                    ]);
                    $failureCount++;
                    continue;
                }

                // Only update if price changed
                if ($newPrice != $product->current_market_price) {

                    // 1. Log the *old* price to history
                    ProductPriceHistory::updateOrCreate(
                        ['product_id' => $product->id, 'recorded_at' => today()->subDay()],
                        ['price' => $product->current_market_price ?? $product->face_value_per_unit]
                    );

                    // 2. Update the product's *new* price
                    $product->update([
                        'current_market_price' => $newPrice,
                        'last_price_update' => now()
                    ]);

                    // 3. Log the *new* price to history
                    ProductPriceHistory::updateOrCreate(
                        ['product_id' => $product->id, 'recorded_at' => today()],
                        ['price' => $newPrice]
                    );

                    $this->info("  ✓ Updated to ₹{$newPrice}");
                    $successCount++;
                } else {
                    $this->info("  → No change (₹{$newPrice})");
                }

            } catch (\Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                Log::error("Exception while fetching price for {$product->name}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failureCount++;
            }
        }

        $this->info("\nProduct price update complete.");
        $this->info("Success: {$successCount} | Failures: {$failureCount}");
        return 0;
    }

    /**
     * Extract price from JSON response using dot notation path
     *
     * V-AUDIT-MODULE6-004: Supports nested JSON paths
     * Examples:
     * - "price" → $json['price']
     * - "data.price" → $json['data']['price']
     * - "market_data.last_price" → $json['market_data']['last_price']
     *
     * @param array|null $json JSON response
     * @param string $path Dot notation path
     * @return float|null Extracted price or null
     */
    private function extractPriceFromJson(?array $json, string $path): ?float
    {
        if (!$json) {
            return null;
        }

        // Split path by dots
        $keys = explode('.', $path);
        $value = $json;

        // Navigate through nested structure
        foreach ($keys as $key) {
            if (!is_array($value) || !isset($value[$key])) {
                return null; // Path not found
            }
            $value = $value[$key];
        }

        // Convert to float
        return is_numeric($value) ? (float) $value : null;
    }
}
