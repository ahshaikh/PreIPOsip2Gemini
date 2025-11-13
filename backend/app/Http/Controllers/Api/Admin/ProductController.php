<?php
// V-REMEDIATE-1730-288 (Price History Added)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPriceHistory; // <-- Import
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // ... (index and store methods remain same) ...
    public function index() { return Product::latest()->get(); }
    public function store(Request $request) { /* ... same code ... */ }
    public function show(Product $product) { return $product; }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sector' => 'nullable|string|max:100',
            'face_value_per_unit' => 'sometimes|required|numeric|min:0', // This is the price
            'min_investment' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string',
            // ... other fields
        ]);

        // --- PRICE HISTORY LOGIC ---
        // If price is changing, record the OLD price in history before updating
        if (isset($validated['face_value_per_unit']) && $product->face_value_per_unit != $validated['face_value_per_unit']) {
            ProductPriceHistory::create([
                'product_id' => $product->id,
                'price' => $product->face_value_per_unit, // The OLD price
                'recorded_at' => now()->subDay(), // Record as "until yesterday"
            ]);
            
            // Also record the NEW price for today if it doesn't exist
            ProductPriceHistory::updateOrCreate(
                ['product_id' => $product->id, 'recorded_at' => today()],
                ['price' => $validated['face_value_per_unit']]
            );
        }
        // ---------------------------

        $product->update($validated);
        return response()->json($product);
    }

    // ... (destroy method remains same) ...
}