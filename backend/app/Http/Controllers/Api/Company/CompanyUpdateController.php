<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyUpdateController extends Controller
{
    /**
     * Get all company updates
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $query = CompanyUpdate::where('company_id', $company->id)
            ->with('createdBy:id,contact_person_name');

        if ($request->filled('update_type')) {
            $query->ofType($request->update_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $updates = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $updates->items(),
            'pagination' => [
                'total' => $updates->total(),
                'per_page' => $updates->perPage(),
                'current_page' => $updates->currentPage(),
                'last_page' => $updates->lastPage(),
            ],
        ], 200);
    }

    /**
     * Create a new company update
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'update_type' => 'required|in:news,milestone,funding,product_launch,partnership,other',
            'media' => 'nullable|array',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        try {
            $data = $request->only(['title', 'content', 'update_type', 'media', 'is_featured', 'status']);
            $data['company_id'] = $company->id;
            $data['created_by'] = $companyUser->id;

            // Set published_at if status is published
            if (isset($data['status']) && $data['status'] === 'published') {
                $data['published_at'] = now();
            }

            $update = CompanyUpdate::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Company update created successfully',
                'update' => $update,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific company update
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $update = CompanyUpdate::where('company_id', $company->id)
            ->where('id', $id)
            ->with('createdBy:id,contact_person_name')
            ->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'Company update not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'update' => $update,
        ], 200);
    }

    /**
     * Update a company update
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $update = CompanyUpdate::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'Company update not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'update_type' => 'sometimes|in:news,milestone,funding,product_launch,partnership,other',
            'media' => 'nullable|array',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only(['title', 'content', 'update_type', 'media', 'is_featured', 'status']);

            // Set published_at if status changed to published
            if (isset($data['status']) && $data['status'] === 'published' && $update->status !== 'published') {
                $data['published_at'] = now();
            }

            $update->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Company update updated successfully',
                'update' => $update->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a company update
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $update = CompanyUpdate::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'Company update not found',
            ], 404);
        }

        try {
            $update->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company update deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company update',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
