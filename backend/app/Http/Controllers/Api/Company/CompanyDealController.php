<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyDealController extends Controller
{
    /**
     * Get all deals for the company
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $deals = Deal::where('company_name', $company->name)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deals,
        ], 200);
    }

    /**
     * Create a new deal/share offering
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'deal_type' => 'required|in:live,upcoming,closed',
            'min_investment' => 'nullable|numeric',
            'max_investment' => 'nullable|numeric',
            'total_shares_available' => 'nullable|integer',
            'price_per_share' => 'nullable|numeric',
            'valuation' => 'nullable|numeric',
            'deal_opens_at' => 'nullable|date',
            'deal_closes_at' => 'nullable|date',
            'highlights' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        // Check if company is verified and active
        if ($company->status !== 'active' || !$company->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Your company must be verified and active to create deal listings.',
            ], 403);
        }

        try {
            $deal = Deal::create([
                'company_name' => $company->name,
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'description' => $request->description,
                'deal_type' => $request->deal_type,
                'min_investment' => $request->min_investment,
                'max_investment' => $request->max_investment,
                'total_shares_available' => $request->total_shares_available,
                'price_per_share' => $request->price_per_share,
                'valuation' => $request->valuation,
                'deal_opens_at' => $request->deal_opens_at,
                'deal_closes_at' => $request->deal_closes_at,
                'highlights' => $request->highlights,
                'status' => 'draft', // Deals start as draft, require admin approval
                'is_featured' => false,
                'sector' => $company->sector,
                'logo' => $company->logo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Deal created successfully. Pending admin approval.',
                'deal' => $deal,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific deal
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $deal = Deal::where('company_name', $company->name)
            ->where('id', $id)
            ->first();

        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'deal' => $deal,
        ], 200);
    }

    /**
     * Update a deal
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $deal = Deal::where('company_name', $company->name)
            ->where('id', $id)
            ->first();

        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);
        }

        // Don't allow editing if deal is live (only drafts can be edited)
        if ($deal->deal_type === 'live' && $deal->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit live deals. Please contact admin for changes.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'deal_type' => 'sometimes|in:live,upcoming,closed',
            'min_investment' => 'nullable|numeric',
            'max_investment' => 'nullable|numeric',
            'total_shares_available' => 'nullable|integer',
            'price_per_share' => 'nullable|numeric',
            'valuation' => 'nullable|numeric',
            'deal_opens_at' => 'nullable|date',
            'deal_closes_at' => 'nullable|date',
            'highlights' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'title', 'description', 'deal_type', 'min_investment', 'max_investment',
            'total_shares_available', 'price_per_share', 'valuation',
            'deal_opens_at', 'deal_closes_at', 'highlights'
        ]);

        // Update slug if title changed
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // If updating a draft to live/upcoming, set status back to draft for admin review
        if (isset($data['deal_type']) && in_array($data['deal_type'], ['live', 'upcoming']) && $deal->status === 'draft') {
            $data['status'] = 'draft';
        }

        $deal->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Deal updated successfully',
            'deal' => $deal->fresh(),
        ], 200);
    }

    /**
     * Delete a deal
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $deal = Deal::where('company_name', $company->name)
            ->where('id', $id)
            ->first();

        if (!$deal) {
            return response()->json([
                'success' => false,
                'message' => 'Deal not found',
            ], 404);
        }

        // Don't allow deleting live deals
        if ($deal->deal_type === 'live' && $deal->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete live deals. Please contact admin.',
            ], 403);
        }

        $deal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deal deleted successfully',
        ], 200);
    }

    /**
     * Get deal statistics
     */
    public function statistics(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $stats = [
            'total_deals' => Deal::where('company_name', $company->name)->count(),
            'live_deals' => Deal::where('company_name', $company->name)->live()->count(),
            'upcoming_deals' => Deal::where('company_name', $company->name)->upcoming()->count(),
            'draft_deals' => Deal::where('company_name', $company->name)->where('status', 'draft')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ], 200);
    }
}
