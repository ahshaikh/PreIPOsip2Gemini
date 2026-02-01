<?php
// V-PHASE2-1730-056 (Created) | V-REMEDIATE-1730-288 | V-FINAL-1730-506 (Save Relations) | V-FINAL-1730-510 (Save Risks) | V-FINAL-1730-514 (Compliance Save) | V-FIX-NON-DESTRUCTIVE (Gemini)
// PHASE 1 AUDIT FIX: Removed admin product creation capability. Admins can only approve/reject/override.

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAudit;
use App\Models\ProductPriceHistory;
use App\Services\ProductService;
use App\Exceptions\ProductOwnershipViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 AUDIT: Admin Product Controller
 *
 * INVARIANTS ENFORCED:
 * - Admins CANNOT create products (store() blocked)
 * - Admins CAN ONLY: view, approve, reject, or perform audited overrides
 * - All product creation MUST occur via Company\ProductController
 * - Products MUST have company_id (enforced at model level)
 *
 * ADMIN CAPABILITIES:
 * - View all products (for review queue)
 * - Approve submitted products
 * - Reject submitted products with reason
 * - Perform field-level overrides with mandatory justification (typos/formatting only)
 */
class ProductController extends Controller
{
    protected ProductService $productService;

    /**
     * Fields that admins can override (typos, formatting corrections only)
     * Content changes MUST go through the company resubmission flow.
     */
    protected const ADMIN_OVERRIDE_ALLOWLIST = [
        'name',                 // Typo corrections
        'slug',                 // URL corrections
        'sector',               // Sector classification corrections
        'regulatory_warnings',  // Admin-authored warnings
        'compliance_notes',     // Admin-authored notes
    ];

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

        $submittedProducts = Product::with('company')
            ->where('status', 'submitted')
            ->latest()
            ->get();

        return response()->json($submittedProducts);
    }

    /**
     * STORY 2.4: Approve a submitted product.
     */
    public function approve(Request $request, Product $product)
    {
        $this->authorize('approve', $product);

        // PHASE 1 AUDIT: Validate product has company ownership
        if (!$product->company_id) {
            Log::critical('PHASE 1 AUDIT: Attempt to approve orphan product', [
                'product_id' => $product->id,
                'admin_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Cannot approve product without company ownership.',
                'error_code' => 'ORPHAN_PRODUCT',
            ], 422);
        }

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

    /**
     * List all products (for admin review queue).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('company');

        if ($request->has('with')) {
            $relations = explode(',', $request->with);
            $query->with(array_intersect($relations, [
                'highlights', 'founders', 'fundingRounds', 'keyMetrics', 'riskDisclosures'
            ]));
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * PHASE 1 AUDIT FIX: BLOCK admin product creation.
     *
     * INVARIANT: Admins CANNOT author products.
     * Products MUST be created by company representatives via Company\ProductController.
     * This ensures chain of custody and ownership accountability.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        Log::warning('PHASE 1 AUDIT: Blocked admin product creation attempt', [
            'admin_id' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempted_data' => $request->except(['password', 'token']),
        ]);

        // Create audit log for security review
        ProductAudit::create([
            'product_id' => null,
            'action' => 'blocked_creation_attempt',
            'changed_fields' => [],
            'old_values' => [],
            'new_values' => $request->except(['password', 'token']),
            'performed_by' => Auth::id(),
            'performed_by_type' => 'Admin',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'reason' => 'PHASE 1 AUDIT: Admin product creation is prohibited',
        ]);

        return response()->json([
            'message' => 'Product creation by administrators is not permitted. Products must be created by company representatives.',
            'error_code' => 'ADMIN_CREATION_PROHIBITED',
            'guidance' => 'Please instruct the company to create the product via their Company Portal.',
        ], 403);
    }

    /**
     * View a single product with all relationships.
     */
    public function show(Product $product)
    {
        $this->authorize('viewAny', Product::class);

        return $product->load([
            'company',
            'highlights',
            'founders',
            'fundingRounds',
            'keyMetrics',
            'priceHistory',
            'riskDisclosures'
        ]);
    }

    /**
     * PHASE 1 AUDIT FIX: Restrict admin updates.
     *
     * Admins can ONLY:
     * - Add/update regulatory warnings (admin-authored)
     * - Add/update compliance notes (admin-authored)
     * - Perform field-level overrides via performOverride() with justification
     *
     * Admins CANNOT:
     * - Modify disclosure content (business description, highlights, founders, etc.)
     * - Change pricing (face value, market price, min investment)
     * - Change status (use approve/reject methods instead)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('viewAny', Product::class);

        // PHASE 1 AUDIT: Only allow admin-specific fields
        $validated = $request->validate([
            // Admin-authored fields only
            'regulatory_warnings' => 'nullable|string',
            'compliance_notes' => 'nullable|string',
        ]);

        // Check if any non-allowed fields were attempted
        $attemptedFields = array_keys($request->except(['regulatory_warnings', 'compliance_notes', '_token', '_method']));
        $blockedFields = array_diff($attemptedFields, ['regulatory_warnings', 'compliance_notes']);

        if (!empty($blockedFields)) {
            Log::warning('PHASE 1 AUDIT: Admin attempted to modify restricted product fields', [
                'product_id' => $product->id,
                'admin_id' => Auth::id(),
                'blocked_fields' => $blockedFields,
            ]);

            return response()->json([
                'message' => 'Administrators cannot modify product content directly. Use the Override function for corrections.',
                'error_code' => 'ADMIN_UPDATE_RESTRICTED',
                'blocked_fields' => $blockedFields,
                'guidance' => 'For content changes, reject the product and request company resubmission. For typo corrections, use the Override endpoint.',
            ], 403);
        }

        // Update only admin-authored fields
        if (!empty($validated)) {
            $oldValues = $product->only(array_keys($validated));
            $product->update($validated);

            ProductAudit::log(
                $product,
                'admin_notes_updated',
                array_keys($validated),
                $oldValues,
                $validated,
                'Admin updated compliance/regulatory notes'
            );
        }

        return response()->json($product->fresh());
    }

    /**
     * PHASE 1 AUDIT: Perform an audited field-level override.
     *
     * PURPOSE:
     * Allow admins to correct typos and formatting issues WITHOUT
     * requiring full company resubmission.
     *
     * CONSTRAINTS:
     * - Only fields in ADMIN_OVERRIDE_ALLOWLIST can be modified
     * - Mandatory justification required
     * - Full audit trail with old/new values
     * - Only for approved/submitted products (not drafts)
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function performOverride(Request $request, Product $product)
    {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validate([
            'field' => 'required|string|in:' . implode(',', self::ADMIN_OVERRIDE_ALLOWLIST),
            'new_value' => 'required|string|max:1000',
            'justification' => 'required|string|min:20|max:500',
        ]);

        // Only allow overrides on submitted/approved products
        if (!in_array($product->status, ['submitted', 'approved'])) {
            return response()->json([
                'message' => 'Overrides are only permitted on submitted or approved products.',
                'error_code' => 'INVALID_STATE_FOR_OVERRIDE',
                'current_status' => $product->status,
            ], 422);
        }

        $field = $validated['field'];
        $oldValue = $product->{$field};
        $newValue = $validated['new_value'];
        $justification = $validated['justification'];

        // Prevent no-op overrides
        if ($oldValue === $newValue) {
            return response()->json([
                'message' => 'No change detected. Override not required.',
                'error_code' => 'NO_CHANGE',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Apply the override
            $product->{$field} = $newValue;
            $product->save();

            // Create detailed audit trail
            ProductAudit::create([
                'product_id' => $product->id,
                'action' => 'admin_override',
                'changed_fields' => [$field],
                'old_values' => [$field => $oldValue],
                'new_values' => [$field => $newValue],
                'performed_by' => Auth::id(),
                'performed_by_type' => 'Admin',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'reason' => $justification,
                'metadata' => json_encode([
                    'override_type' => 'field_level',
                    'product_status' => $product->status,
                    'company_id' => $product->company_id,
                ]),
            ]);

            Log::info('PHASE 1 AUDIT: Admin override performed', [
                'product_id' => $product->id,
                'field' => $field,
                'admin_id' => Auth::id(),
                'justification' => $justification,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Override applied successfully.',
                'product' => $product->fresh(),
                'audit' => [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'justification' => $justification,
                    'performed_by' => Auth::id(),
                    'performed_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('PHASE 1 AUDIT: Override failed', [
                'product_id' => $product->id,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Override failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PHASE 1 AUDIT FIX: Restrict product deletion.
     *
     * Deletion is blocked if:
     * - Product has active subscriptions/investments
     * - Product is in approved/locked state
     * - Product has bulk purchase inventory
     *
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product)
    {
        $this->authorize('viewAny', Product::class);

        // Check for active subscriptions
        if (method_exists($product, 'subscriptions') && $product->subscriptions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product with active subscriptions.',
                'error_code' => 'HAS_SUBSCRIPTIONS',
            ], 409);
        }

        // Check for investments
        if ($product->investments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product with investor allocations.',
                'error_code' => 'HAS_INVESTMENTS',
            ], 409);
        }

        // Check for bulk purchases
        if ($product->bulkPurchases()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product with bulk purchase inventory.',
                'error_code' => 'HAS_INVENTORY',
            ], 409);
        }

        // Block deletion of approved/locked products
        if (in_array($product->status, ['approved', 'locked'])) {
            return response()->json([
                'message' => 'Cannot delete approved or locked products. Archive instead.',
                'error_code' => 'INVALID_STATE_FOR_DELETION',
                'current_status' => $product->status,
            ], 409);
        }

        // Audit the deletion
        ProductAudit::create([
            'product_id' => $product->id,
            'action' => 'deleted',
            'changed_fields' => [],
            'old_values' => $product->toArray(),
            'new_values' => [],
            'performed_by' => Auth::id(),
            'performed_by_type' => 'Admin',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => 'Product deleted by admin',
        ]);

        $product->delete();

        return response()->noContent();
    }
}