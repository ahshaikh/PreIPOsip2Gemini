<?php
// V-FINAL-1730-495 (Created)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateProductPrices extends Command
{
    /**
     * FSD-PROD-005: Auto-update prices from external API
     */
    protected $signature = 'app:update-product-prices';
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

        foreach ($productsToUpdate as $product) {
            $this->info("Updating {$product->name} from {$product->price_api_endpoint}...");
            
            try {
                $response = Http::timeout(10)->get($product->price_api_endpoint);

                if ($response->failed()) {
                    Log::error("Failed to fetch price for {$product->name}", ['status' => $response->status()]);
                    continue;
                }

                // Assume API returns JSON { "price": 123.45 }
                $newPrice = (float) $response->json('price');

                if ($newPrice > 0 && $newPrice != $product->current_market_price) {
                    
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
                    
                    $this->info("Updated {$product->name} to ₹{$newPrice}");
                }
                
            } catch (\Exception $e) {
                Log::error("Exception while fetching price for {$product->name}", ['error' => $e->getMessage()]);
            }
        }
        
        $this->info('Product price update complete.');
        return 0;
    }
}