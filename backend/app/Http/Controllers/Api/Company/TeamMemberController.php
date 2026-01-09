<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyTeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class TeamMemberController extends Controller
{
    /**
     * Get all team members
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $teamMembers = CompanyTeamMember::where('company_id', $company->id)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teamMembers,
        ], 200);
    }

    /**
     * Create a new team member
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'linkedin_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'display_order' => 'nullable|integer',
            'is_key_member' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        try {
            $data = $request->only(['name', 'designation', 'bio', 'linkedin_url', 'twitter_url', 'display_order', 'is_key_member']);
            $data['company_id'] = $company->id;

            // Handle photo upload
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('team-photos/' . $company->id, 'public');
                $data['photo_path'] = $path;
            }

            $teamMember = CompanyTeamMember::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Team member added successfully',
                'team_member' => $teamMember,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add team member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a team member
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $teamMember = CompanyTeamMember::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$teamMember) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'designation' => 'sometimes|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'linkedin_url' => 'nullable|url',
            'twitter_url' => 'nullable|url',
            'display_order' => 'nullable|integer',
            'is_key_member' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['name', 'designation', 'bio', 'linkedin_url', 'twitter_url', 'display_order', 'is_key_member']);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo
                if ($teamMember->photo_path && Storage::disk('public')->exists($teamMember->photo_path)) {
                    Storage::disk('public')->delete($teamMember->photo_path);
                }

                $path = $request->file('photo')->store('team-photos/' . $company->id, 'public');
                $data['photo_path'] = $path;
            }

            $teamMember->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Team member updated successfully',
                'team_member' => $teamMember->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update team member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a team member
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $teamMember = CompanyTeamMember::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$teamMember) {
            return response()->json([
                'success' => false,
                'message' => 'Team member not found',
            ], 404);
        }

        try {
            // Delete photo
            if ($teamMember->photo_path && Storage::disk('public')->exists($teamMember->photo_path)) {
                Storage::disk('public')->delete($teamMember->photo_path);
            }

            $teamMember->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team member deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete team member',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
