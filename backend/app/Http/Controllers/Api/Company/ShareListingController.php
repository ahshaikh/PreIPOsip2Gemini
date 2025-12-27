<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyShareListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Company-facing Share Listing Controller.
 *
 * Companies use this to submit share offerings for admin review.
 */
class ShareListingController extends Controller
{
    /**
     * Get all share listings for this company.
     * GET /api/v1/company/share-listings
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $listings = CompanyShareListing::where('company_id', $company->id)
            ->with(['submittedBy', 'reviewedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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
     * Create new share listing submission.
     * POST /api/v1/company/share-listings
     */
    public function store(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // Check if company is verified
        if (!$company->is_verified) {
            return response()->json([
                'success' => false,
                'error' => 'Company must be verified before submitting share listings',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'listing_title' => 'required|string|max:255',
            'description' => 'required|string|min:100',
            'total_shares_offered' => 'required|numeric|min:1',
            'face_value_per_share' => 'required|numeric|min:0.01',
            'asking_price_per_share' => 'required|numeric|min:0.01',
            'minimum_purchase_value' => 'nullable|numeric|min:0',
            'current_company_valuation' => 'nullable|numeric|min:0',
            'valuation_currency' => 'nullable|string|size:3',
            'percentage_of_company' => 'nullable|numeric|min:0|max:100',
            'terms_and_conditions' => 'nullable|string',
            'offer_valid_until' => 'nullable|date|after:today',
            'lock_in_period' => 'nullable|array',
            'rights_attached' => 'nullable|array',
            'documents' => 'nullable|array',
            'documents.*' => 'string|max:500',
            'financial_documents' => 'nullable|array',
            'financial_documents.*' => 'string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Validate asking price >= face value
        if ($request->asking_price_per_share < $request->face_value_per_share) {
            return response()->json([
                'error' => 'Asking price cannot be less than face value'
            ], 422);
        }

        $listing = CompanyShareListing::create([
            'company_id' => $company->id,
            'submitted_by' => $companyUser->id,
            'listing_title' => $request->listing_title,
            'description' => $request->description,
            'total_shares_offered' => $request->total_shares_offered,
            'face_value_per_share' => $request->face_value_per_share,
            'asking_price_per_share' => $request->asking_price_per_share,
            'minimum_purchase_value' => $request->minimum_purchase_value,
            'current_company_valuation' => $request->current_company_valuation,
            'valuation_currency' => $request->valuation_currency ?? 'INR',
            'percentage_of_company' => $request->percentage_of_company,
            'terms_and_conditions' => $request->terms_and_conditions,
            'offer_valid_until' => $request->offer_valid_until,
            'lock_in_period' => $request->lock_in_period,
            'rights_attached' => $request->rights_attached,
            'documents' => $request->documents,
            'financial_documents' => $request->financial_documents,
            'status' => 'pending',
        ]);

        // Log activity
        \App\Models\CompanyShareListingActivity::create([
            'listing_id' => $listing->id,
            'actor_id' => $companyUser->id,
            'actor_type' => 'company_user',
            'action' => 'submitted',
            'notes' => 'Share listing submitted for review',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Share listing submitted successfully. Our team will review it shortly.',
            'data' => $listing,
        ], 201);
    }

    /**
     * Get single listing.
     * GET /api/v1/company/share-listings/{id}
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $listing = CompanyShareListing::where('company_id', $company->id)
            ->where('id', $id)
            ->with(['submittedBy', 'reviewedBy', 'activities'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }

    /**
     * Update listing (only if pending).
     * PUT /api/v1/company/share-listings/{id}
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $listing = CompanyShareListing::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        // Can only update if pending
        if ($listing->status !== 'pending') {
            return response()->json([
                'error' => 'Can only update pending listings'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'listing_title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|min:100',
            'total_shares_offered' => 'sometimes|required|numeric|min:1',
            'face_value_per_share' => 'sometimes|required|numeric|min:0.01',
            'asking_price_per_share' => 'sometimes|required|numeric|min:0.01',
            'minimum_purchase_value' => 'nullable|numeric|min:0',
            'current_company_valuation' => 'nullable|numeric|min:0',
            'offer_valid_until' => 'nullable|date|after:today',
            'terms_and_conditions' => 'nullable|string',
            'documents' => 'nullable|array',
            'financial_documents' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $listing->update($request->only([
            'listing_title', 'description', 'total_shares_offered',
            'face_value_per_share', 'asking_price_per_share',
            'minimum_purchase_value', 'current_company_valuation',
            'offer_valid_until', 'terms_and_conditions',
            'documents', 'financial_documents'
        ]));

        // Recalculate total_value
        $listing->total_value = $listing->total_shares_offered * $listing->asking_price_per_share;
        $listing->save();

        return response()->json([
            'success' => true,
            'message' => 'Listing updated successfully',
            'data' => $listing,
        ]);
    }

    /**
     * Withdraw listing.
     * POST /api/v1/company/share-listings/{id}/withdraw
     */
    public function withdraw(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $listing = CompanyShareListing::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!in_array($listing->status, ['pending', 'under_review'])) {
            return response()->json([
                'error' => 'Can only withdraw pending or under-review listings'
            ], 422);
        }

        $listing->update(['status' => 'withdrawn']);

        return response()->json([
            'success' => true,
            'message' => 'Listing withdrawn successfully',
        ]);
    }

    /**
     * Get listing statistics for company dashboard.
     * GET /api/v1/company/share-listings/statistics
     */
    public function statistics(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $stats = [
            'total_listings' => CompanyShareListing::where('company_id', $company->id)->count(),
            'pending_listings' => CompanyShareListing::where('company_id', $company->id)->pending()->count(),
            'approved_listings' => CompanyShareListing::where('company_id', $company->id)->approved()->count(),
            'rejected_listings' => CompanyShareListing::where('company_id', $company->id)->where('status', 'rejected')->count(),
            'total_shares_offered' => CompanyShareListing::where('company_id', $company->id)->sum('total_shares_offered'),
            'total_value_offered' => CompanyShareListing::where('company_id', $company->id)->sum('total_value'),
            'approved_value' => CompanyShareListing::where('company_id', $company->id)
                ->approved()
                ->sum(\DB::raw('approved_quantity * approved_price')),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
