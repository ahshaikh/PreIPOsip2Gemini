<?php
// V-FINAL-1730-289

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
            ->select('price', 'recorded_at')
            ->get()
            ->map(function($record) {
                return [
                    'date' => $record->recorded_at->format('Y-m-d'),
                    'price' => (float) $record->price
                ];
            });

        // Add current price as "today"
        $history->push([
            'date' => now()->format('Y-m-d'),
            'price' => (float) $product->face_value_per_unit
        ]);

        return response()->json($history);
    }
}