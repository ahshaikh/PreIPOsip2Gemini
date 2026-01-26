<?php
/**
 * Company Version History API Controller
 *
 * Provides endpoints for viewing and managing company version history.
 * Part of FIX 33, 34, 35: Company Versioning and Immutability implementation.
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyVersionController extends Controller
{
    /**
     * Get all company versions with filtering
     * GET /api/v1/admin/company-versions
     */
    public function index(Request $request)
    {
        $query = CompanyVersion::with(['company:id,name,slug', 'creator:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by approval snapshots only
        if ($request->has('approval_snapshots') && $request->approval_snapshots) {
            $query->where('is_approval_snapshot', true);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('change_summary', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $versions = $query->paginate($request->get('per_page', 50));

        // Get statistics - FIX: Use consistent field names with stats() method
        $stats = [
            'total_versions' => CompanyVersion::count(),
            'total_companies' => Company::count(),
            'companies_with_versions' => CompanyVersion::distinct('company_id')->count(),
            'approval_snapshots' => CompanyVersion::where('is_approval_snapshot', true)->count(),
            'protected_companies' => Company::whereHas('deals', function ($q) {
                $q->where('status', 'active');
            })->count(),
            'versions_today' => CompanyVersion::whereDate('created_at', today())->count(),
            'recent_changes' => CompanyVersion::whereDate('created_at', '>=', now()->subDays(7))->count(),
        ];

        return response()->json([
            'versions' => $versions,
            'stats' => $stats,
        ]);
    }

    /**
     * Get version history for a specific company
     * GET /api/v1/admin/companies/{company}/versions
     */
    public function companyVersions(Company $company, Request $request)
    {
        $query = $company->versions()->with('creator:id,name,email');

        // Filter by approval snapshots
        if ($request->has('approval_snapshots') && $request->approval_snapshots) {
            $query->where('is_approval_snapshot', true);
        }

        $versions = $query->paginate($request->get('per_page', 50));

        // Company-specific stats
        $stats = [
            'total_versions' => $company->getVersionCount(),
            'latest_version' => $company->getLatestVersion()?->version_number,
            'has_approved_listing' => $company->hasApprovedListing(),
            'approval_snapshots_count' => $company->approvalSnapshots()->count(),
            'first_version_date' => $company->versions()->oldest()->first()?->created_at,
            'last_version_date' => $company->versions()->latest()->first()?->created_at,
        ];

        return response()->json([
            'company' => $company->only(['id', 'name', 'slug', 'status', 'is_verified']),
            'versions' => $versions,
            'stats' => $stats,
        ]);
    }

    /**
     * Get specific version details
     * GET /api/v1/admin/company-versions/{version}
     */
    public function show(CompanyVersion $version)
    {
        $version->load(['company', 'creator']);

        // Get previous and next versions for navigation
        $previousVersion = $version->getPreviousVersion();
        $nextVersion = $version->getNextVersion();

        return response()->json([
            'version' => $version,
            'previous_version' => $previousVersion ?
                ['id' => $previousVersion->id, 'version_number' => $previousVersion->version_number] : null,
            'next_version' => $nextVersion ?
                ['id' => $nextVersion->id, 'version_number' => $nextVersion->version_number] : null,
        ]);
    }

    /**
     * Compare two versions
     * GET /api/v1/admin/company-versions/compare
     */
    public function compare(Request $request)
    {
        $request->validate([
            'version_id_1' => 'required|exists:company_versions,id',
            'version_id_2' => 'required|exists:company_versions,id',
        ]);

        $version1 = CompanyVersion::with('company')->findOrFail($request->version_id_1);
        $version2 = CompanyVersion::with('company')->findOrFail($request->version_id_2);

        // Ensure both versions are for the same company
        if ($version1->company_id !== $version2->company_id) {
            return response()->json([
                'error' => 'Cannot compare versions from different companies'
            ], 400);
        }

        // Get differences
        $differences = [];
        $allFields = array_unique(array_merge(
            array_keys($version1->snapshot_data ?? []),
            array_keys($version2->snapshot_data ?? [])
        ));

        foreach ($allFields as $field) {
            $value1 = $version1->snapshot_data[$field] ?? null;
            $value2 = $version2->snapshot_data[$field] ?? null;

            if ($value1 !== $value2) {
                $differences[$field] = [
                    'version_1_value' => $value1,
                    'version_2_value' => $value2,
                    'changed' => true,
                ];
            }
        }

        return response()->json([
            'version_1' => $version1,
            'version_2' => $version2,
            'differences' => $differences,
            'changed_fields_count' => count($differences),
        ]);
    }

    /**
     * Get approval snapshots for a company
     * GET /api/v1/admin/companies/{company}/approval-snapshots
     */
    public function approvalSnapshots(Company $company)
    {
        $snapshots = $company->approvalSnapshots()
            ->with('creator:id,name,email')
            ->get();

        // Get original approval snapshot
        $originalSnapshot = $company->getOriginalApprovalSnapshot();

        return response()->json([
            'company' => $company->only(['id', 'name']),
            'snapshots' => $snapshots,
            'original_snapshot' => $originalSnapshot,
            'total_snapshots' => $snapshots->count(),
        ]);
    }

    /**
     * Get version timeline for visualization
     * GET /api/v1/admin/companies/{company}/version-timeline
     */
    public function timeline(Company $company)
    {
        $timeline = $company->versions()
            ->select('id', 'version_number', 'changed_fields', 'change_summary', 'is_approval_snapshot', 'created_at')
            ->with('creator:id,name')
            ->orderBy('version_number', 'asc')
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version_number' => $version->version_number,
                    'timestamp' => $version->created_at->toISOString(),
                    'user' => $version->creator?->name ?? 'System',
                    'fields_changed' => $version->getChangedFieldsList(),
                    'summary' => $version->change_summary,
                    'is_approval_snapshot' => $version->is_approval_snapshot,
                ];
            });

        return response()->json([
            'company' => $company->only(['id', 'name']),
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get field change history for specific field
     * GET /api/v1/admin/companies/{company}/field-history/{field}
     */
    public function fieldHistory(Company $company, string $field)
    {
        // Validate field exists
        $validFields = ['name', 'sector', 'ceo_name', 'latest_valuation', 'total_funding', 'founded_year'];

        if (!in_array($field, $validFields)) {
            return response()->json([
                'error' => 'Invalid field name',
                'valid_fields' => $validFields,
            ], 400);
        }

        $history = $company->versions()
            ->whereJsonContains('changed_fields', $field)
            ->orderBy('version_number', 'asc')
            ->get()
            ->map(function ($version) use ($field) {
                return [
                    'version_number' => $version->version_number,
                    'timestamp' => $version->created_at,
                    'value' => $version->getSnapshotValue($field),
                    'changed_by' => $version->creator?->name ?? 'System',
                ];
            });

        return response()->json([
            'company' => $company->only(['id', 'name']),
            'field' => $field,
            'current_value' => $company->$field,
            'history' => $history,
            'total_changes' => $history->count(),
        ]);
    }

    /**
     * Check if company data is protected (immutable)
     * GET /api/v1/admin/companies/{company}/protection-status
     */
    public function protectionStatus(Company $company)
    {
        $hasApprovedListing = $company->hasApprovedListing();

        $protectedFields = $hasApprovedListing ? [
            'name',
            'sector',
            'founded_year',
            'ceo_name',
            'latest_valuation',
            'total_funding',
        ] : [];

        return response()->json([
            'company' => $company->only(['id', 'name', 'status']),
            'has_approved_listing' => $hasApprovedListing,
            'is_protected' => $hasApprovedListing,
            'protected_fields' => $protectedFields,
            'version_count' => $company->getVersionCount(),
            'latest_version' => $company->getLatestVersion()?->version_number,
        ]);
    }

    /**
     * Get statistics about versioning
     * GET /api/v1/admin/company-versions/stats
     */
    public function stats()
    {
        $stats = [
            'total_versions' => CompanyVersion::count(),
            'total_companies' => Company::count(),
            'companies_with_versions' => CompanyVersion::distinct('company_id')->count(),
            'companies_without_versions' => Company::count() - CompanyVersion::distinct('company_id')->count(),

            'approval_snapshots' => CompanyVersion::where('is_approval_snapshot', true)->count(),
            'protected_companies' => Company::whereHas('deals', function ($q) {
                $q->where('status', 'active');
            })->count(),

            'versions_today' => CompanyVersion::whereDate('created_at', today())->count(),
            'versions_this_week' => CompanyVersion::whereDate('created_at', '>=', now()->subDays(7))->count(),
            'versions_this_month' => CompanyVersion::whereDate('created_at', '>=', now()->subDays(30))->count(),

            'most_versioned_companies' => CompanyVersion::select('company_id', DB::raw('count(*) as version_count'))
                ->with('company:id,name')
                ->groupBy('company_id')
                ->orderBy('version_count', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Export version history to CSV
     * GET /api/v1/admin/companies/{company}/versions/export
     */
    public function export(Company $company)
    {
        $versions = $company->versions()
            ->with('creator:id,name')
            ->orderBy('version_number', 'asc')
            ->get();

        $csvData = [];
        $csvData[] = ['Version', 'Date', 'Changed By', 'Summary', 'Changed Fields', 'Is Approval Snapshot'];

        foreach ($versions as $version) {
            $csvData[] = [
                $version->version_number,
                $version->created_at->toDateTimeString(),
                $version->creator?->name ?? 'System',
                $version->change_summary,
                implode(', ', $version->getChangedFieldsList()),
                $version->is_approval_snapshot ? 'Yes' : 'No',
            ];
        }

        $filename = 'company_' . $company->id . '_versions_' . now()->format('Y-m-d_His') . '.csv';

        return response()->json([
            'filename' => $filename,
            'data' => $csvData,
            'row_count' => count($csvData) - 1, // Exclude header
        ]);
    }
}
