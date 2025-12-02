<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\InvestorInterest;
use Illuminate\Http\Request;

class InvestorInterestController extends Controller
{
    /**
     * Get all investor interests for company
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $query = InvestorInterest::where('company_id', $company->id)
            ->with('user');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by interest level
        if ($request->filled('interest_level')) {
            $query->where('interest_level', $request->interest_level);
        }

        $interests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $interests->items(),
            'pagination' => [
                'total' => $interests->total(),
                'per_page' => $interests->perPage(),
                'current_page' => $interests->currentPage(),
                'last_page' => $interests->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $stats = [
            'total' => InvestorInterest::where('company_id', $company->id)->count(),
            'pending' => InvestorInterest::where('company_id', $company->id)->pending()->count(),
            'contacted' => InvestorInterest::where('company_id', $company->id)->where('status', 'contacted')->count(),
            'qualified' => InvestorInterest::where('company_id', $company->id)->qualified()->count(),
            'not_interested' => InvestorInterest::where('company_id', $company->id)->where('status', 'not_interested')->count(),
            'high_interest' => InvestorInterest::where('company_id', $company->id)->where('interest_level', 'high')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ], 200);
    }

    /**
     * Update interest status
     */
    public function updateStatus(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $interest = InvestorInterest::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'status' => 'required|in:pending,contacted,qualified,not_interested',
            'admin_notes' => 'nullable|string',
        ]);

        $interest->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'interest' => $interest->fresh(),
        ], 200);
    }

    /**
     * Get a specific interest
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $interest = InvestorInterest::where('company_id', $company->id)
            ->where('id', $id)
            ->with('user')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'interest' => $interest,
        ], 200);
    }
}
