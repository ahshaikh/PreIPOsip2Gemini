<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'sector' => 'required|string|max:255',
            'deal_type' => 'required|in:live,upcoming,closed',
            'description' => 'nullable|string',
            'min_investment' => 'nullable|numeric|min:0',
            'max_investment' => 'nullable|numeric|min:0',
            'valuation' => 'nullable|numeric|min:0',
            'share_price' => 'required|numeric|min:0',
            'deal_opens_at' => 'nullable|date',
            'deal_closes_at' => 'nullable|date|after:deal_opens_at',
            'highlights' => 'nullable|array',
            'documents' => 'nullable|array',
            'video_url' => 'nullable|url',
            'status' => 'required|in:draft,active,paused,closed',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        // Calculate days remaining if deal closes
        if (isset($data['deal_closes_at'])) {
            $data['days_remaining'] = now()->diffInDays($data['deal_closes_at'], false);
        }

        // Validate inventory exists for this product
        $product = \App\Models\Product::findOrFail($data['product_id']);
        $availableInventory = $product->bulkPurchases()->sum('value_remaining');

        if ($availableInventory <= 0) {
            return response()->json([
                'errors' => ['product_id' => ['No inventory available for this product. Create BulkPurchase first.']]
            ], 422);
        }

        $deal = Deal::create($data);
        $deal->load(['product', 'company']);

        // Append calculated inventory for response
        $deal->calculated_total_shares = $deal->total_shares;
        $deal->calculated_available_shares = $deal->available_shares;

        return response()->json([
            'message' => 'Deal created successfully',
            'deal' => $deal,
            'inventory_info' => [
                'available_value' => $availableInventory,
                'available_shares' => floor($availableInventory / $data['share_price'])
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
}
