<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->filled('sector')) {
            $query->bySector($request->sector);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($companies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'website' => 'nullable|url',
            'founded_year' => 'nullable|string|max:4',
            'headquarters' => 'nullable|string|max:255',
            'ceo_name' => 'nullable|string|max:255',
            'latest_valuation' => 'nullable|numeric|min:0',
            'funding_stage' => 'nullable|string|max:255',
            'total_funding' => 'nullable|numeric|min:0',
            'linkedin_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'key_metrics' => 'nullable|array',
            'investors' => 'nullable|array',
            'is_featured' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // V-AUDIT-MODULE18-HIGH: Removed manual slug generation
        // PROBLEM: Admin controller was overriding Model's incremental slug logic
        // (spacex-1, spacex-2) with random strings (spacex-x9z8q2), creating messy,
        // unpredictable URLs and data hygiene issues.
        // SOLUTION: Let Company model's booted() method handle slug generation
        // using the centralized generateUniqueSlug() method for consistency.
        // $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6); // REMOVED

        $company = Company::create($data);

        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company
        ], 201);
    }

    public function show($id)
    {
        $company = Company::with('deals')->findOrFail($id);

        // Build platform_context for admin frontend
        $company->platform_context = [
            'lifecycle_state' => $company->lifecycle_state ?? 'draft',
            'buying_enabled' => $company->buying_enabled ?? false,
            'is_suspended' => $company->suspended_at !== null || $company->lifecycle_state === 'suspended',
            'is_frozen' => $company->is_frozen ?? false,
            'tier_status' => [
                'tier_1_approved' => $company->tier_1_approved_at !== null,
                'tier_2_approved' => $company->tier_2_approved_at !== null,
                'tier_3_approved' => $company->tier_3_approved_at !== null,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    /**
     * V-AUDIT-MODULE18-LOW: Protected sensitive fields from mass assignment
     */
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'sector' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'website' => 'nullable|url',
            'founded_year' => 'nullable|string|max:4',
            'headquarters' => 'nullable|string|max:255',
            'ceo_name' => 'nullable|string|max:255',
            'latest_valuation' => 'nullable|numeric|min:0',
            'funding_stage' => 'nullable|string|max:255',
            'total_funding' => 'nullable|numeric|min:0',
            'linkedin_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'key_metrics' => 'nullable|array',
            'investors' => 'nullable|array',
            'is_featured' => 'boolean',
            'status' => 'sometimes|required|in:active,inactive',
            // V-AUDIT-MODULE18-LOW: Added explicit validation for sensitive fields
            'is_verified' => 'sometimes|boolean', // Admin-only: verification status
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // V-AUDIT-MODULE18-HIGH: Removed manual slug generation on name change
        // PROBLEM: When updating company name, admin controller was manually generating
        // random slugs (google-x9z8q2) instead of using Model's consistent logic (google-1).
        // SOLUTION: Model's updating() hook in booted() method automatically handles
        // slug regeneration when name changes, ensuring consistency across the platform.
        // if (isset($data['name']) && $data['name'] !== $company->name) {
        //     $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6); // REMOVED
        // }

        // V-AUDIT-MODULE18-LOW: Extract sensitive fields and set them explicitly
        // PROBLEM: Mass-assignment of all validated data could be risky if validator rules
        // are ever loosened. Sensitive administrative flags should be set explicitly.
        // SOLUTION: Separate sensitive fields (is_verified, status) from general data updates.

        $sensitiveFields = [];

        // V-AUDIT-MODULE18-LOW: Only admins can modify verification status
        if (isset($data['is_verified'])) {
            $sensitiveFields['is_verified'] = $data['is_verified'];
            unset($data['is_verified']);
        }

        // V-AUDIT-MODULE18-LOW: Status changes should be explicit and logged
        if (isset($data['status'])) {
            $sensitiveFields['status'] = $data['status'];
            unset($data['status']);
        }

        // Update general fields first (safe mass-assignment)
        $company->update($data);

        // V-AUDIT-MODULE18-LOW: Set sensitive fields explicitly with admin authorization
        if (!empty($sensitiveFields)) {
            $company->update($sensitiveFields);
            // TODO: Add audit log for sensitive field changes
            // AuditLog::create(['action' => 'company_status_changed', 'company_id' => $company->id, ...]);
        }

        return response()->json([
            'message' => 'Company updated successfully',
            'company' => $company
        ]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company deleted successfully']);
    }
}
