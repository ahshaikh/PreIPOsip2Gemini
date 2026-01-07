<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyShareListing;
use App\Models\CompanyShareListingActivity;
use App\Models\BulkPurchase;
use App\Models\Product;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Admin-facing Share Listing Review Controller.
 *
 * Admins use this to review company share submissions and manage workflow.
 */
class AdminShareListingController extends Controller
{
    /**
     * Get all share listings (review queue).
     * GET /api/v1/admin/share-listings
     */
    public function index(Request $request)
    {
        $query = CompanyShareListing::query()
            ->with(['company', 'submittedBy', 'reviewedBy'])
            ->withCount('activities');

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'awaiting_review') {
                $query->awaitingReview();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by value range
        if ($request->has('min_value')) {
            $query->where('total_value', '>=', $request->min_value);
        }
        if ($request->has('max_value')) {
            $query->where('total_value', '<=', $request->max_value);
        }

        // Filter by expiry
        if ($request->has('expiring_soon')) {
            $query->whereNotNull('offer_valid_until')
                  ->where('offer_valid_until', '<=', now()->addDays(7))
                  ->where('offer_valid_until', '>=', now());
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('listing_title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'total_value') {
            $query->orderBy('total_value', $sortOrder);
        } elseif ($sortBy === 'company_name') {
            $query->join('companies', 'company_share_listings.company_id', '=', 'companies.id')
                  ->orderBy('companies.name', $sortOrder)
                  ->select('company_share_listings.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $listings = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $listings->items(),
            'pagination' => [
                'total' => $listings->total(),
                'per_page' => $listings->perPage(),
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
            ],
        ]);
    }

    /**
     * Get single listing with full details.
     * GET /api/v1/admin/share-listings/{id}
     */
    public function show(Request $request, $id)
    {
        $listing = CompanyShareListing::with([
            'company',
            'submittedBy',
            'reviewedBy',
            'bulkPurchase',
            'activities.actor'
        ])->findOrFail($id);

        // Mark as viewed
        if (!$listing->last_viewed_at || $listing->last_viewed_at->lt(now()->subMinutes(5))) {
            $listing->markAsViewed();
        }

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }

    /**
     * Update listing status to under_review.
     * POST /api/v1/admin/share-listings/{id}/review
     */
    public function startReview(Request $request, $id)
    {
        $listing = CompanyShareListing::findOrFail($id);

        if ($listing->status !== 'pending') {
            return response()->json([
                'error' => 'Can only start review for pending listings'
            ], 422);
        }

        $listing->update([
            'status' => 'under_review',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        CompanyShareListingActivity::create([
            'listing_id' => $listing->id,
            'actor_id' => auth()->id(),
            'actor_type' => 'admin',
            'action' => 'review_started',
            'notes' => 'Admin started reviewing listing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing marked as under review',
            'data' => $listing->fresh(),
        ]);
    }

    /**
     * Approve listing and create BulkPurchase.
     * POST /api/v1/admin/share-listings/{id}/approve
     */
    public function approve(Request $request, $id)
    {
        $listing = CompanyShareListing::with('company')->findOrFail($id);

        if (!in_array($listing->status, ['pending', 'under_review'])) {
            return response()->json([
                'error' => 'Can only approve pending or under-review listings'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'approved_quantity' => 'nullable|numeric|min:1',
            'approved_price' => 'nullable|numeric|min:0.01',
            'admin_notes' => 'nullable|string',
            'create_bulk_purchase' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Use listing values if not overridden
        $approvedQuantity = $request->approved_quantity ?? $listing->total_shares_offered;
        $approvedPrice = $request->approved_price ?? $listing->asking_price_per_share;

        // Validate product belongs to same company
        $product = Product::findOrFail($request->product_id);
        if ($product->company_id !== $listing->company_id) {
            return response()->json([
                'error' => 'Product must belong to the same company as the listing'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Calculate discount if price negotiated
            $discountPercentage = $listing->calculateDiscount($approvedPrice);

            // Create BulkPurchase (inventory) if requested
            $bulkPurchaseId = null;
            if ($request->get('create_bulk_purchase', true)) {
                $totalValue = $approvedQuantity * $approvedPrice;

                $bulkPurchase = BulkPurchase::create([
                    'company_id' => $listing->company_id,
                    'product_id' => $request->product_id,
                    'purchase_date' => now(),
                    'total_shares_purchased' => $approvedQuantity,
                    'price_per_share' => $approvedPrice,
                    'total_value_received' => $totalValue,
                    'value_remaining' => $totalValue,
                    'face_value_per_share' => $listing->face_value_per_share,
                    'purchase_method' => 'company_listing',
                    'payment_status' => 'completed',
                    'notes' => "Created from company listing: {$listing->listing_title}",
                    'purchase_documents' => $listing->documents,
                ]);

                $bulkPurchaseId = $bulkPurchase->id;

                // FIX 5 (P1): Create company snapshot and freeze data
                \App\Models\CompanySnapshot::create([
                    'company_id' => $listing->company_id,
                    'company_share_listing_id' => $listing->id,
                    'bulk_purchase_id' => $bulkPurchase->id,
                    'snapshot_data' => $listing->company->toArray(),
                    'snapshot_reason' => 'listing_approval',
                    'snapshot_at' => now(),
                    'snapshot_by_admin_id' => auth()->id(),
                ]);

                // Freeze company data (prevent retroactive disclosure changes)
                $listing->company->update([
                    'frozen_at' => now(),
                    'frozen_by_admin_id' => auth()->id(),
                ]);
            }

            // Update listing
            $listing->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_notes' => $request->admin_notes,
                'approved_quantity' => $approvedQuantity,
                'approved_price' => $approvedPrice,
                'discount_percentage' => $discountPercentage,
                'bulk_purchase_id' => $bulkPurchaseId,
            ]);

            // Log activity
            CompanyShareListingActivity::create([
                'listing_id' => $listing->id,
                'actor_id' => auth()->id(),
                'actor_type' => 'admin',
                'action' => 'approved',
                'notes' => $request->admin_notes ?? 'Listing approved and inventory created',
                'metadata' => [
                    'approved_quantity' => $approvedQuantity,
                    'approved_price' => $approvedPrice,
                    'discount_percentage' => $discountPercentage,
                    'bulk_purchase_id' => $bulkPurchaseId,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Listing approved successfully',
                'data' => $listing->fresh(['company', 'bulkPurchase']),
                'bulk_purchase' => $bulkPurchaseId ? BulkPurchase::find($bulkPurchaseId) : null,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to approve listing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject listing with reason.
     * POST /api/v1/admin/share-listings/{id}/reject
     */
    public function reject(Request $request, $id)
    {
        $listing = CompanyShareListing::findOrFail($id);

        if (!in_array($listing->status, ['pending', 'under_review'])) {
            return response()->json([
                'error' => 'Can only reject pending or under-review listings'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:20',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $listing->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->rejection_reason,
            'admin_notes' => $request->admin_notes,
        ]);

        CompanyShareListingActivity::create([
            'listing_id' => $listing->id,
            'actor_id' => auth()->id(),
            'actor_type' => 'admin',
            'action' => 'rejected',
            'notes' => $request->rejection_reason,
            'metadata' => [
                'admin_notes' => $request->admin_notes,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing rejected',
            'data' => $listing->fresh(),
        ]);
    }

    /**
     * Get statistics for admin dashboard.
     * GET /api/v1/admin/share-listings/statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_listings' => CompanyShareListing::count(),
            'pending_review' => CompanyShareListing::awaitingReview()->count(),
            'approved_listings' => CompanyShareListing::approved()->count(),
            'rejected_listings' => CompanyShareListing::where('status', 'rejected')->count(),
            'withdrawn_listings' => CompanyShareListing::where('status', 'withdrawn')->count(),

            'total_value_submitted' => CompanyShareListing::sum('total_value'),
            'total_value_approved' => CompanyShareListing::approved()
                ->sum(DB::raw('approved_quantity * approved_price')),

            'avg_approval_time' => CompanyShareListing::approved()
                ->whereNotNull('reviewed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
                ->value('avg_hours'),

            'total_shares_approved' => CompanyShareListing::approved()->sum('approved_quantity'),
            'avg_discount_given' => CompanyShareListing::approved()
                ->whereNotNull('discount_percentage')
                ->where('discount_percentage', '>', 0)
                ->avg('discount_percentage'),

            'listings_by_status' => CompanyShareListing::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),

            'expiring_soon' => CompanyShareListing::awaitingReview()
                ->whereNotNull('offer_valid_until')
                ->where('offer_valid_until', '<=', now()->addDays(7))
                ->where('offer_valid_until', '>=', now())
                ->count(),

            'high_value_pending' => CompanyShareListing::awaitingReview()
                ->where('total_value', '>=', 10000000) // 1 crore+
                ->count(),
        ];

        // Recent activity
        $recentListings = CompanyShareListing::with(['company', 'submittedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'recent_listings' => $recentListings,
        ]);
    }

    /**
     * Create Deal from approved listing (pre-populate form).
     * POST /api/v1/admin/share-listings/{id}/create-deal
     */
    public function createDealFromListing(Request $request, $id)
    {
        $listing = CompanyShareListing::with(['company', 'bulkPurchase'])->findOrFail($id);

        if ($listing->status !== 'approved') {
            return response()->json([
                'error' => 'Can only create deals from approved listings'
            ], 422);
        }

        if (!$listing->bulk_purchase_id) {
            return response()->json([
                'error' => 'No inventory (BulkPurchase) linked to this listing'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'deal_title' => 'nullable|string|max:255',
            'deal_type' => 'nullable|in:active,upcoming,closed',
            'share_price' => 'nullable|numeric|min:0.01',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0',
            'deal_opens_at' => 'nullable|date',
            'deal_closes_at' => 'nullable|date|after:deal_opens_at',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Pre-populate deal data from listing
            $dealData = [
                'company_id' => $listing->company_id,
                'product_id' => $listing->bulkPurchase->product_id,
                'title' => $request->deal_title ?? $listing->listing_title,
                'description' => $listing->description,
                'sector' => $listing->company->sector,
                'deal_type' => $request->deal_type ?? 'upcoming',
                'share_price' => $request->share_price ?? $listing->approved_price,
                'min_investment' => $request->min_investment ?? $listing->minimum_purchase_value ?? 10000,
                'max_investment' => $request->max_investment ?? ($listing->approved_quantity * $listing->approved_price),
                'valuation' => $listing->current_company_valuation,
                'valuation_currency' => $listing->valuation_currency,
                'deal_opens_at' => $request->deal_opens_at ?? now(),
                'deal_closes_at' => $request->deal_closes_at ?? $listing->offer_valid_until ?? now()->addDays(30),
                'highlights' => [
                    'Lock-in Period' => $listing->lock_in_period,
                    'Rights Attached' => $listing->rights_attached,
                    'Face Value' => '₹' . number_format($listing->face_value_per_share, 2) . ' per share',
                ],
                'documents' => array_merge(
                    $listing->documents ?? [],
                    $listing->financial_documents ?? []
                ),
                'status' => 'active',
            ];

            $deal = Deal::create($dealData);

            // Log activity on listing
            CompanyShareListingActivity::create([
                'listing_id' => $listing->id,
                'actor_id' => auth()->id(),
                'actor_type' => 'admin',
                'action' => 'deal_created',
                'notes' => "Deal #{$deal->id} created from this listing",
                'metadata' => [
                    'deal_id' => $deal->id,
                    'deal_title' => $deal->title,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deal created successfully from listing',
                'deal' => $deal->fresh(['company', 'product']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create deal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update multiple listings.
     * POST /api/v1/admin/share-listings/bulk-update
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'listing_ids' => 'required|array|min:1',
            'listing_ids.*' => 'exists:company_share_listings,id',
            'action' => 'required|in:mark_under_review,bulk_reject',
            'rejection_reason' => 'required_if:action,bulk_reject|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $listings = CompanyShareListing::whereIn('id', $request->listing_ids)
            ->where('status', 'pending')
            ->get();

        if ($listings->isEmpty()) {
            return response()->json([
                'error' => 'No pending listings found to update'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updatedCount = 0;

            foreach ($listings as $listing) {
                if ($request->action === 'mark_under_review') {
                    $listing->update([
                        'status' => 'under_review',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    CompanyShareListingActivity::create([
                        'listing_id' => $listing->id,
                        'actor_id' => auth()->id(),
                        'actor_type' => 'admin',
                        'action' => 'review_started',
                        'notes' => 'Bulk update: marked as under review',
                    ]);

                    $updatedCount++;

                } elseif ($request->action === 'bulk_reject') {
                    $listing->update([
                        'status' => 'rejected',
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                        'rejection_reason' => $request->rejection_reason,
                    ]);

                    CompanyShareListingActivity::create([
                        'listing_id' => $listing->id,
                        'actor_id' => auth()->id(),
                        'actor_type' => 'admin',
                        'action' => 'rejected',
                        'notes' => 'Bulk rejection: ' . $request->rejection_reason,
                    ]);

                    $updatedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} listings updated successfully",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
