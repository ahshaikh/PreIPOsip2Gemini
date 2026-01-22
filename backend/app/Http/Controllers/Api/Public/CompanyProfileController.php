<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyAnalytics;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    /**
     * Get public company profile
     */
    public function show($slug)
    {
        $company = Company::where('slug', $slug)
            ->where('status', 'active')
            ->where('is_verified', true)
            ->with([
                'financialReports' => function($query) {
                    $query->where('status', 'published')->orderBy('year', 'desc')->limit(5);
                },
                'documents' => function($query) {
                    $query->where('is_public', true)->where('status', 'active');
                },
                'teamMembers' => function($query) {
                    $query->ordered()->limit(10);
                },
                'fundingRounds' => function($query) {
                    $query->latest()->limit(5);
                },
                'updates' => function($query) {
                    $query->where('status', 'published')->orderBy('published_at', 'desc')->limit(10);
                },
            ])
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        // Track view
        CompanyAnalytics::incrementMetric($company->id, 'profile_views');

        // Get deals for this company
        // Use Deal's live() scope to validate dates
        $deals = \App\Models\Deal::where('company_id', $company->id)
            ->live()
            ->get();

        return response()->json([
            'success' => true,
            'company' => $company,
            'deals' => $deals,
        ], 200);
    }

    /**
     * Get all active companies (for listing/comparison)
     */
    public function index(Request $request)
    {
        $query = Company::where('status', 'active')
            ->where('is_verified', true);

        // Filter by deal availability
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'live':
                    // Only show companies with currently live deals
                    $query->whereHas('deals', function ($q) {
                        $q->live(); // Uses Deal's live() scope which validates dates
                    });
                    break;
                case 'upcoming':
                    // Companies with deals opening in the future
                    $query->whereHas('deals', function ($q) {
                        $q->where('status', 'active')
                          ->where('deal_opens_at', '>', now());
                    });
                    break;
                // 'all' or any other value shows all companies (no filter)
            }
        }

        // Filter by sector
        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'latest');
        switch ($sortBy) {
            case 'valuation_high':
                $query->orderBy('latest_valuation', 'desc');
                break;
            case 'valuation_low':
                $query->orderBy('latest_valuation', 'asc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $companies = $query->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $companies->items(),
            'pagination' => [
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get company sectors for filtering
     */
    public function sectors()
    {
        $sectors = Company::where('status', 'active')
            ->where('is_verified', true)
            ->distinct()
            ->pluck('sector')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'sectors' => $sectors,
        ], 200);
    }
}
