<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDealRequest;
use App\Models\{Deal, BulkPurchase, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DealController extends Controller
{
    /**
     * Display a listing of deals
     */
    public function index(Request $request)
    {
        $query = Deal::query()->with(['product', 'company']);

        // Filter by deal type
        if ($request->filled('deal_type')) {
            $query->where('deal_type', $request->deal_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by sector
        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $deals = $query->paginate($request->get('per_page', 15));

        return response()->json($deals);
    }

    /**
     * Store a newly created deal
     *
     * FIX 7 (P1): Uses StoreDealRequest for cross-entity validation
     * GAP 3 FIX: Inventory sufficiency is now enforced at Deal model level
     *
     * NOTE: The Deal model's saving hook will throw DomainException if:
     * - No inventory exists for the product
     * - max_investment exceeds available inventory (on activation)
     *
     * This ensures the invariant holds regardless of entry path.
     */
    public function store(StoreDealRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        // Calculate days remaining if deal closes
        if (isset($data['deal_closes_at'])) {
            $data['days_remaining'] = now()->diffInDays($data['deal_closes_at'], false);
        }

        // GAP 3: Inventory check moved to Deal model saving hook
        // The model will throw DomainException if inventory is insufficient

        $deal = Deal::create($data);
        $deal->load(['product', 'company']);

        // Calculate inventory info for response
        $product = $deal->product;
        $availableInventory = $product->bulkPurchases()->sum('value_remaining');

        // Append calculated inventory for response
        $deal->calculated_total_shares = $deal->total_shares;
        $deal->calculated_available_shares = $deal->available_shares;

        return response()->json([
            'message' => 'Deal created successfully',
            'deal' => $deal,
            'inventory_info' => [
                'available_value' => $availableInventory,
                'available_shares' => $data['share_price'] > 0 ? floor($availableInventory / $data['share_price']) : 0
            ]
        ], 201);
    }

    /**
     * Display the specified deal
     */
    public function show($id)
    {
        $deal = Deal::with(['product', 'company'])->findOrFail($id);
        return response()->json($deal);
    }

    /**
     * Update the specified deal
     */
    public function update(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'company_id' => 'sometimes|required|exists:companies,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'sector' => 'sometimes|required|string|max:255',
            'deal_type' => 'sometimes|required|in:live,upcoming,closed',
            'description' => 'nullable|string',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0',
            'valuation' => 'nullable|numeric|min:0',
            'share_price' => 'sometimes|required|numeric|min:0',
            'deal_opens_at' => 'nullable|date',
            'deal_closes_at' => 'nullable|date',
            'highlights' => 'nullable|array',
            'documents' => 'nullable|array',
            'video_url' => 'nullable|url',
            'status' => 'sometimes|required|in:draft,active,paused,closed',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Update slug if title changed
        if (isset($data['title']) && $data['title'] !== $deal->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        }

        // Recalculate days remaining
        if (isset($data['deal_closes_at'])) {
            $data['days_remaining'] = now()->diffInDays($data['deal_closes_at'], false);
        }

        $deal->update($data);

        return response()->json([
            'message' => 'Deal updated successfully',
            'deal' => $deal
        ]);
    }

    /**
     * Remove the specified deal
     */
    public function destroy($id)
    {
        $deal = Deal::findOrFail($id);
        $deal->delete();

        return response()->json([
            'message' => 'Deal deleted successfully'
        ]);
    }

    /**
     * Get deal statistics
     */
    public function statistics()
    {
        $stats = [
            'total_deals' => Deal::count(),
            'live_deals' => Deal::live()->count(),
            'upcoming_deals' => Deal::upcoming()->count(),
            'closed_deals' => Deal::where('deal_type', 'closed')->count(),
            'featured_deals' => Deal::featured()->count(),
            'deals_by_sector' => Deal::selectRaw('sector, COUNT(*) as count')
                ->groupBy('sector')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * FIX 6 (P1): Approve a draft deal
     * POST /api/v1/admin/deals/{id}/approve
     *
     * GAP 3 FIX: Inventory sufficiency is now enforced at Deal model level
     *
     * NOTE: The Deal model's saving hook will throw DomainException if:
     * - No inventory exists for the product (when setting status='active')
     * - max_investment exceeds available inventory
     *
     * This ensures the invariant holds regardless of entry path.
     */
    public function approve(Request $request, $id)
    {
        $deal = Deal::with(['product', 'company'])->findOrFail($id);

        if ($deal->status !== 'draft') {
            return response()->json([
                'error' => 'Only draft deals can be approved'
            ], 422);
        }

        // GAP 3: Inventory checks moved to Deal model saving hook
        // The model will throw DomainException if:
        // - No inventory available (cannot activate)
        // - max_investment exceeds available inventory

        // Calculate available inventory for audit log
        $availableValue = BulkPurchase::where('product_id', $deal->product_id)
            ->where('value_remaining', '>', 0)
            ->sum('value_remaining');

        // Approve deal - model hooks will enforce inventory invariant
        $deal->update([
            'status' => 'active',
            'approved_by_admin_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Log audit
        AuditLog::create([
            'action' => 'deal.approved',
            'actor_id' => auth()->id(),
            'description' => "Approved deal: {$deal->title}",
            'metadata' => [
                'deal_id' => $deal->id,
                'company_id' => $deal->company_id,
                'product_id' => $deal->product_id,
                'available_inventory' => $availableValue,
            ],
        ]);

        // TODO: Notify company user about approval
        // $deal->company->companyUsers()
        //     ->where('status', 'active')
        //     ->each(fn($user) => $user->notify(new DealApprovedNotification($deal)));

        return response()->json([
            'success' => true,
            'message' => 'Deal approved successfully',
            'data' => $deal->fresh(['product', 'company']),
        ]);
    }

    /**
     * FIX 6 (P1): Reject a draft deal
     * POST /api/v1/admin/deals/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $deal = Deal::with(['product', 'company'])->findOrFail($id);

        if ($deal->status !== 'draft') {
            return response()->json([
                'error' => 'Only draft deals can be rejected'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:50|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Reject deal
        $deal->update([
            'status' => 'rejected',
            'rejected_by_admin_id' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Log audit
        AuditLog::create([
            'action' => 'deal.rejected',
            'actor_id' => auth()->id(),
            'description' => "Rejected deal: {$deal->title}",
            'metadata' => [
                'deal_id' => $deal->id,
                'company_id' => $deal->company_id,
                'rejection_reason' => $request->rejection_reason,
            ],
        ]);

        // TODO: Notify company user about rejection
        // $deal->company->companyUsers()
        //     ->where('status', 'active')
        //     ->each(fn($user) => $user->notify(new DealRejectedNotification($deal, $request->rejection_reason)));

        return response()->json([
            'success' => true,
            'message' => 'Deal rejected successfully',
            'data' => $deal->fresh(['product', 'company']),
        ]);
    }
}
