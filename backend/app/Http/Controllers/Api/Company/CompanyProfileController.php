<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyProfileController extends Controller
{
    /**
     * Update company profile information
     */
    public function update(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'website' => 'sometimes|url',
            'sector' => 'sometimes|string|max:255',
            'founded_year' => 'sometimes|string|max:4',
            'headquarters' => 'sometimes|string|max:255',
            'ceo_name' => 'sometimes|string|max:255',
            'latest_valuation' => 'sometimes|numeric',
            'funding_stage' => 'sometimes|string|max:255',
            'total_funding' => 'sometimes|numeric',
            'linkedin_url' => 'sometimes|url',
            'twitter_url' => 'sometimes|url',
            'facebook_url' => 'sometimes|url',
            'key_metrics' => 'sometimes|array',
            'investors' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'name', 'description', 'website', 'sector', 'founded_year',
            'headquarters', 'ceo_name', 'latest_valuation', 'funding_stage',
            'total_funding', 'linkedin_url', 'twitter_url', 'facebook_url',
            'key_metrics', 'investors',
        ]);

        // Update slug if name changed
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $company->update($data);

        // Calculate profile completion percentage
        $this->updateProfileCompletion($company);

        return response()->json([
            'success' => true,
            'message' => 'Company profile updated successfully',
            'company' => $company->fresh(),
        ], 200);
    }

    /**
     * Upload company logo
     */
    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        try {
            // Delete old logo if exists
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }

            // Store new logo
            $path = $request->file('logo')->store('company-logos', 'public');

            $company->update(['logo' => $path]);

            $this->updateProfileCompletion($company);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'logo_url' => Storage::url($path),
                'logo_path' => $path,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logo upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate and update profile completion percentage
     */
    private function updateProfileCompletion($company)
    {
        $fields = [
            'name' => 5,
            'description' => 10,
            'logo' => 10,
            'website' => 5,
            'sector' => 5,
            'founded_year' => 5,
            'headquarters' => 5,
            'ceo_name' => 5,
            'latest_valuation' => 10,
            'funding_stage' => 5,
            'total_funding' => 5,
            'linkedin_url' => 3,
            'twitter_url' => 2,
            'facebook_url' => 2,
        ];

        $completionPercentage = 0;

        foreach ($fields as $field => $weight) {
            if (!empty($company->$field)) {
                $completionPercentage += $weight;
            }
        }

        // Add bonus for having team members
        if ($company->teamMembers()->count() > 0) {
            $completionPercentage += 10;
        }

        // Add bonus for having financial reports
        if ($company->financialReports()->count() > 0) {
            $completionPercentage += 10;
        }

        // Add bonus for having funding rounds
        if ($company->fundingRounds()->count() > 0) {
            $completionPercentage += 5;
        }

        // Add bonus for having documents
        if ($company->documents()->count() > 0) {
            $completionPercentage += 3;
        }

        $completionPercentage = min($completionPercentage, 100);

        $company->update([
            'profile_completion_percentage' => $completionPercentage,
            'profile_completed' => $completionPercentage >= 80,
        ]);
    }

    /**
     * Get company dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $stats = [
            'profile_completion' => $company->profile_completion_percentage ?? 0,
            'financial_reports_count' => $company->financialReports()->count(),
            'documents_count' => $company->documents()->count(),
            'team_members_count' => $company->teamMembers()->count(),
            'funding_rounds_count' => $company->fundingRounds()->count(),
            'updates_count' => $company->updates()->count(),
            'published_updates_count' => $company->updates()->published()->count(),
            'is_verified' => $company->is_verified ?? false,
            'status' => $company->status,
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'company' => $company,
        ], 200);
    }
}
