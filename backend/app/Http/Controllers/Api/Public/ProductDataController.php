<?php
// V-FINAL-1730-289 | V-FIX-DUPLICATE-PRICE (Gemini) | V-AUDIT-MODULE6-005 (Cache Implementation)

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // V-AUDIT-MODULE6-005: Add caching for public API

class ProductDataController extends Controller
{
    /**
     * V-AUDIT-MODULE6-005 (MEDIUM): Implemented caching for public price history API.
     *
     * Cache Strategy:
     * - TTL: 1 hour (3600 seconds) - balances freshness with performance
     * - Key: "product_price_history_{slug}" - unique per product
     * - Rationale: Price history changes infrequently (only on admin updates or cron jobs)
     *
     * Cache Invalidation:
     * - Manual: Admin can clear cache via artisan cache:forget command
     * - Automatic: ProductObserver should clear cache on product update (if implemented)
     * - TTL-based: Cache expires after 1 hour regardless
     */
    public function getPriceHistory($slug)
    {
        // Use cache with 1-hour TTL to reduce database load on public API
        $cacheKey = "product_price_history_{$slug}";
        $cacheTtl = 3600; // 1 hour in seconds

        return Cache::remember($cacheKey, $cacheTtl, function () use ($slug) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $history = $product->priceHistory()
                ->orderBy('recorded_at', 'asc') // Ensure sorted order
                ->select('price', 'recorded_at')
                ->get()
                ->map(function($record) {
                    return [
                        'date' => $record->recorded_at->format('Y-m-d'),
                        'price' => (float) $record->price
                    ];
                });

            // FIX: Check if today's date already exists in history
            $today = now()->format('Y-m-d');
            $hasToday = $history->contains('date', $today);

            if (!$hasToday) {
                $history->push([
                    'date' => $today,
                    'price' => (float) ($product->current_market_price ?? $product->face_value_per_unit)
                ]);
            }

            // Return the history array (Cache::remember will handle the JSON response)
            return response()->json($history);
        });
    }
}