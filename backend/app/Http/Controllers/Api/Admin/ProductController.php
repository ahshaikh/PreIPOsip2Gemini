// V-PHASE2-1730-056
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        return Product::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sector' => 'nullable|string|max:100',
            'face_value_per_unit' => 'required|numeric|min:0',
            'min_investment' => 'required|numeric|min:0',
            'expected_ipo_date' => 'nullable|date',
            'description' => 'nullable|json',
            'status' => 'required|in:active,upcoming,listed',
            'is_featured' => 'required|boolean',
        ]);
        
        $product = Product::create($validated + ['slug' => Str::slug($validated['name'])]);
        
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product;
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sector' => 'nullable|string|max:100',
            // ... other fields
        ]);

        $product->update($validated);
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        // TODO: Add check for active investments before deleting
        $product->delete();
        return response()->noContent();
    }
}