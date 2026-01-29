<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\DisclosureTier;
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
     *
     * FIX: Updated response structure to match frontend expectations
     * - Frontend expects: response.data.data.companies (not response.data.data)
     * - Added sectors list to response
     * - Fixed 'upcoming' filter to use Deal::upcoming() scope for consistency
     */
    public function index(Request $request)
    {
        // STORY 3.3: Use disclosure_tier for visibility, not deal availability
        $query = Company::where('status', 'active')
            ->publiclyVisible(); // Enforces disclosure_tier >= tier_2_live

        // Optional filter by deal type (for UI filtering, not visibility)
        $filter = $request->get('filter', 'all');

        switch ($filter) {
            case 'live':
                // Filter to companies with currently live deals
                $query->whereHas('deals', function ($q) {
                    $q->live();
                });
                break;
            case 'upcoming':
                // Filter to companies with upcoming deals
                $query->whereHas('deals', function ($q) {
                    $q->upcoming();
                });
                break;
            case 'all':
            default:
                // All publicly visible companies (disclosure_tier based)
                // No additional deal filtering
                break;
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

        // STORY 3.3: Get available sectors from publicly visible companies
        $sectors = Company::where('status', 'active')
            ->publiclyVisible() // Enforces disclosure_tier >= tier_2_live
            ->whereNotNull('sector')
            ->distinct()
            ->pluck('sector')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        // FIX: Match frontend expected structure: response.data.data.companies
        return response()->json([
            'success' => true,
            'data' => [
                'companies' => $companies->items(),
                'total' => $companies->total(),
                'sectors' => $sectors,
            ],
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
     *
     * FIX: Updated response structure to match frontend expectations
     * - Frontend expects: response.data.data.sectors (not response.data.sectors)
     * - Filter sectors to only show those with active deals
     */
    public function sectors()
    {
        // STORY 3.3: Return sectors from publicly visible companies (disclosure_tier based)
        $sectors = Company::where('status', 'active')
            ->publiclyVisible() // Enforces disclosure_tier >= tier_2_live
            ->whereNotNull('sector')
            ->distinct()
            ->pluck('sector')
            ->filter()
            ->sort()
            ->values();

        // FIX: Match frontend expected structure: response.data.data.sectors
        return response()->json([
            'success' => true,
            'data' => [
                'sectors' => $sectors,
            ],
        ], 200);
    }
}
