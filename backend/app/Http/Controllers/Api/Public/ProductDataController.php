<?php
// V-FINAL-1730-289 | V-FIX-DUPLICATE-PRICE (Gemini)

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductDataController extends Controller
{
    public function getPriceHistory($slug)
    {
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

        return response()->json($history);
    }
}