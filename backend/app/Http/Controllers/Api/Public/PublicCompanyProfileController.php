<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Public Company Profile Controller
 *
 * Handles public-facing company listings without authentication.
 * Shows only publicly visible companies with active deals.
 *
 * V-AUDIT-MODULE6-005: Implements caching for public endpoints
 *
 * FIX: Corrected to work with actual Company model fields
 * - 'sector' is a string field, not a relationship
 * - Public visibility is determined by having active deals
 * - Removed references to non-existent 'is_visible_public' field
 */
class PublicCompanyProfileController extends Controller
{
    /**
     * Get all publicly visible companies
     * Supports filtering by deal status (live/upcoming) and sector
     *
     * GET /public/companies
     *
     * Query params:
     * - filter: 'all' | 'live' | 'upcoming' (default: 'all')
     * - sector: sector name to filter by
     * - page: pagination page number
     * - per_page: items per page (default: 20)
     */
    public function index(Request $request)
    {
        // Cache key includes all filter parameters for proper cache segregation
        $cacheKey = 'public_companies_' . md5(json_encode($request->only(['filter', 'sector', 'page', 'per_page'])));
        $cacheTtl = 1800; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($request) {
            $filter = $request->get('filter', 'all'); // 'all', 'live', 'upcoming'
            $sector = $request->get('sector');
            $perPage = min($request->get('per_page', 20), 100); // Max 100 per page

            // Base query: only active, verified companies
            // FIX: Removed 'is_visible_public' check - doesn't exist in table
            // Public visibility controlled by presence of active deals
            $query = Company::where('status', 'active')
                ->where('is_verified', true);

            // Filter by sector if provided
            // FIX: 'sector' is a string field in companies table, not a relationship
            if ($sector) {
                $query->where('sector', $sector);
            }

            // Filter by deal type if specified
            if ($filter === 'live') {
                // Companies with active live deals
                $query->whereHas('deals', function($q) {
                    $q->live(); // Uses Deal::scopeLive() which validates dates
                });
            } elseif ($filter === 'upcoming') {
                // Companies with upcoming deals
                $query->whereHas('deals', function($q) {
                    $q->upcoming(); // Uses Deal::scopeUpcoming()
                });
            } else {
                // 'all' filter: companies with any active deal (live or upcoming)
                $query->whereHas('deals', function($q) {
                    $q->where('status', 'active')
                      ->whereIn('deal_type', ['live', 'upcoming']);
                });
            }

            // Order by featured companies first, then by name
            // FIX: Check if is_featured column exists
            if (\Schema::hasColumn('companies', 'is_featured')) {
                $query->orderByRaw('CASE WHEN is_featured = 1 THEN 0 ELSE 1 END')
                      ->orderBy('name', 'asc');
            } else {
                $query->orderBy('name', 'asc');
            }

            // Paginate results
            $companies = $query->paginate($perPage);

            // Get all available sectors from companies with active deals
            // FIX: 'sector' is a string field in companies table, not a Sector model relationship
            $sectors = Company::where('status', 'active')
                ->where('is_verified', true)
                ->whereNotNull('sector')
                ->whereHas('deals', function($dealQuery) {
                    $dealQuery->where('status', 'active')
                             ->whereIn('deal_type', ['live', 'upcoming']);
                })
                ->distinct()
                ->pluck('sector')
                ->filter() // Remove empty values
                ->sort()
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'companies' => $companies->items(),
                    'total' => $companies->total(),
                    'sectors' => $sectors,
                ],
                'pagination' => [
                    'current_page' => $companies->currentPage(),
                    'last_page' => $companies->lastPage(),
                    'per_page' => $companies->perPage(),
                    'total' => $companies->total(),
                ],
            ]);
        });
    }

    /**
     * Get single company detail (public view)
     * Shows only approved, public information
     *
     * GET /public/companies/{slug}
     */
    public function show(Request $request, $slug)
    {
        // Cache individual company pages
        $cacheKey = "public_company_detail_{$slug}";
        $cacheTtl = 1800; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($slug) {
            // FIX: Removed 'is_visible_public' check - doesn't exist in table
            $company = Company::where('slug', $slug)
                ->where('status', 'active')
                ->where('is_verified', true)
                ->with([
                    'deals' => function($query) {
                        // Only show active deals (live or upcoming)
                        $query->where('status', 'active')
                            ->whereIn('deal_type', ['live', 'upcoming'])
                            ->orderBy('is_featured', 'desc')
                            ->orderBy('deal_opens_at', 'desc');
                    }
                ])
                ->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found or not publicly visible',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $company,
            ]);
        });
    }

    /**
     * Get available sectors for filtering
     *
     * GET /public/sectors
     *
     * FIX: Simplified to work with 'sector' as string field
     */
    public function sectors()
    {
        // Cache sectors list
        $cacheKey = 'public_sectors_list';
        $cacheTtl = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () {
            // Get distinct sectors from companies with active deals
            // FIX: 'sector' is a string field in companies table
            $sectors = Company::where('status', 'active')
                ->where('is_verified', true)
                ->whereNotNull('sector')
                ->whereHas('deals', function($dealQuery) {
                    $dealQuery->where('status', 'active')
                             ->whereIn('deal_type', ['live', 'upcoming']);
                })
                ->distinct()
                ->pluck('sector')
                ->filter() // Remove empty values
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'sectors' => $sectors,
                ],
            ]);
        });
    }
}
