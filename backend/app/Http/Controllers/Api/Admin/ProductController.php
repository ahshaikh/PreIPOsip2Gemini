<?php
// V-PHASE2-1730-056 (Created) | V-REMEDIATE-1730-288 | V-FINAL-1730-506 (Save Relations) | V-FINAL-1730-510 (Save Risks) | V-FINAL-1730-514 (Compliance Save) | V-FIX-NON-DESTRUCTIVE (Gemini)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAudit;
use App\Models\ProductPriceHistory;
use App\Services\ProductService; // V-AUDIT-MODULE6-003: Service layer for business logic
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // V-AUDIT-MODULE6-003: Inject ProductService for business logic handling
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * STORY 2.4: Get all products with "submitted" status for the admin queue.
     */
    public function submitted()
    {
        $this->authorize('viewAny', Product::class);
        $submittedProducts = Product::where('status', 'submitted')->latest()->get();
        return response()->json($submittedProducts);
    }

    /**
     * STORY 2.4: Approve a submitted product.
     */
    public function approve(Request $request, Product $product)
    {
        $this->authorize('approve', $product);

        $product->status = 'approved';
        $product->save();

        ProductAudit::log(
            $product,
            'approved',
            ['status'],
            ['status' => 'submitted'],
            ['status' => 'approved']
        );

        return response()->json($product);
    }

    /**
     * STORY 2.4: Reject a submitted product.
     */
    public function reject(Request $request, Product $product)
    {
        $this->authorize('reject', $product);

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $product->status = 'rejected';
        $product->save();

        ProductAudit::log(
            $product,
            'rejected',
            ['status'],
            ['status' => 'submitted'],
            ['status' => 'rejected'],
            $validated['reason']
        );

        return response()->json($product);
    }

    public function index(Request $request)
    {
        $query = Product::query();
        if ($request->has('with')) {
            $relations = explode(',', $request->with);
            $query->with(array_intersect($relations, [
                'highlights', 'founders', 'fundingRounds', 'keyMetrics', 'riskDisclosures'
            ]));
        }
        // FIX: Use pagination instead of loading all records
        return $query->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        // Ideally, this validation should match 'update' complexity, but sticking to audit scope fixes.
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
        return $product->load('highlights', 'founders', 'fundingRounds', 'keyMetrics', 'priceHistory', 'riskDisclosures');
    }

    public function update(Request $request, Product $product)
    {
        // V-AUDIT-MODULE6-003: Simplified controller - business logic moved to ProductService
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

            // FSD-PROD-012: Compliance Fields
            'sebi_approval_number' => 'nullable|string|max:255',
            'sebi_approval_date' => 'nullable|date',
            'compliance_notes' => 'nullable|string',
            'regulatory_warnings' => 'nullable|string',
        ]);

        // Separate product data from relationship data
        $productData = array_diff_key($validated, array_flip([
            'highlights', 'founders', 'funding_rounds', 'key_metrics', 'risk_disclosures'
        ]));

        $relationData = array_intersect_key($validated, array_flip([
            'highlights', 'founders', 'funding_rounds', 'key_metrics', 'risk_disclosures'
        ]));

        // Delegate to ProductService for all business logic
        $updatedProduct = $this->productService->updateProduct($product, $productData, $relationData);

        return response()->json($updatedProduct);
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