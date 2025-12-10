<?php
// V-PHASE2-1730-056 (Created) | V-REMEDIATE-1730-288 | V-FINAL-1730-506 (Save Relations) | V-FINAL-1730-510 (Save Risks) | V-FINAL-1730-514 (Compliance Save) | V-PRODUCT-EXTENDED-1210 (Media, Docs, News, Allocation)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        if ($request->has('with')) {
            $relations = explode(',', $request->with);
            $query->with(array_intersect($relations, [
                'highlights', 'founders', 'fundingRounds', 'keyMetrics', 'riskDisclosures',
                'media', 'documents', 'news' // V-PRODUCT-EXTENDED-1210
            ]));
        }
        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            'face_value_per_unit' => 'required|numeric|min:0.01',
            'min_investment' => 'required|numeric|min:0',
        ]);
        
        $product = Product::create($validated);
        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product->load('highlights', 'founders', 'fundingRounds', 'keyMetrics', 'priceHistory', 'riskDisclosures', 'media', 'documents', 'news');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|unique:products,slug,'.$product->id,
            'sector' => 'nullable|string|max:100',
            'face_value_per_unit' => 'sometimes|required|numeric|min:0.01',
            'current_market_price' => 'nullable|numeric|min:0',
            'min_investment' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string',
            'auto_update_price' => 'boolean',
            'price_api_endpoint' => 'nullable|string',
            'description' => 'nullable|string',
            
            // Relational Data
            'highlights' => 'nullable|array',
            'founders' => 'nullable|array',
            'funding_rounds' => 'nullable|array',
            'key_metrics' => 'nullable|array',
            'risk_disclosures' => 'nullable|array',
            // V-PRODUCT-EXTENDED-1210: New Relations
            'media' => 'nullable|array',
            'documents' => 'nullable|array',
            'news' => 'nullable|array',

            // FSD-PROD-012: Compliance Fields
            'sebi_approval_number' => 'nullable|string|max:255',
            'sebi_approval_date' => 'nullable|date',
            'compliance_notes' => 'nullable|string',
            'regulatory_warnings' => 'nullable|string',

            // V-PRODUCT-ALLOCATION-1210: Allocation Fields
            'allocation_method' => 'nullable|in:auto,manual,hybrid',
            'allocation_rules' => 'nullable|array',
            'max_allocation_per_user' => 'nullable|numeric|min:0',
            'total_units_available' => 'nullable|numeric|min:0',
            'enable_waitlist' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($product, $validated, $request) {
            
            // --- Price History Logic ---
            $newPrice = $validated['current_market_price'] ?? $validated['face_value_per_unit'];
            $oldPrice = $product->current_market_price ?? $product->face_value_per_unit;
            
            if (isset($validated['face_value_per_unit']) && $newPrice != $oldPrice) {
                ProductPriceHistory::updateOrCreate(
                    ['product_id' => $product->id, 'recorded_at' => today()->subDay()],
                    ['price' => $oldPrice]
                );
                ProductPriceHistory::updateOrCreate(
                    ['product_id' => $product->id, 'recorded_at' => today()],
                    ['price' => $newPrice]
                );
                $validated['last_price_update'] = now();
            }
            
            // 1. Update the main product table
            $product->update($validated);

            // 2. Sync Relational Data
            if ($request->has('highlights')) {
                $product->highlights()->delete();
                $product->highlights()->createMany($validated['highlights']);
            }
            if ($request->has('founders')) {
                $product->founders()->delete();
                $product->founders()->createMany($validated['founders']);
            }
            if ($request->has('funding_rounds')) {
                $product->fundingRounds()->delete();
                $product->fundingRounds()->createMany($validated['funding_rounds']);
            }
            if ($request->has('key_metrics')) {
                $product->keyMetrics()->delete();
                $product->keyMetrics()->createMany($validated['key_metrics']);
            }
            if ($request->has('risk_disclosures')) {
                $product->riskDisclosures()->delete();
                $product->riskDisclosures()->createMany($validated['risk_disclosures']);
            }
            // V-PRODUCT-EXTENDED-1210: Handle new relations
            if ($request->has('media')) {
                $product->media()->delete();
                $product->media()->createMany($validated['media']);
            }
            if ($request->has('documents')) {
                $product->documents()->delete();
                $product->documents()->createMany($validated['documents']);
            }
            if ($request->has('news')) {
                $product->news()->delete();
                $product->news()->createMany($validated['news']);
            }
        });

        return response()->json($product->load('highlights', 'founders', 'fundingRounds', 'keyMetrics', 'riskDisclosures', 'media', 'documents', 'news'));
    }

    public function destroy(Product $product)
    {
        if ($product->subscriptions()->exists()) {
             return response()->json(['message' => 'Cannot delete product with active subscriptions.'], 409);
        }
        $product->delete();
        return response()->noContent();
    }
}