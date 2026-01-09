<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyQna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyQnaController extends Controller
{
    /**
     * Get all Q&A for company
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


        $query = CompanyQna::where('company_id', $company->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $qnas = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $qnas->items(),
            'pagination' => [
                'total' => $qnas->total(),
                'per_page' => $qnas->perPage(),
                'current_page' => $qnas->currentPage(),
                'last_page' => $qnas->lastPage(),
            ],
        ], 200);
    }

    /**
     * Answer a question
     */
    public function answer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'answer' => 'required|string',
            'is_public' => 'sometimes|boolean',
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


        $qna = CompanyQna::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $qna->update([
            'answer' => $request->answer,
            'answered_by' => $companyUser->id,
            'answered_at' => now(),
            'status' => 'answered',
            'is_public' => $request->get('is_public', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question answered successfully',
            'qna' => $qna->fresh(),
        ], 200);
    }

    /**
     * Update Q&A settings
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


        $qna = CompanyQna::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'is_public' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:pending,answered,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $qna->update($request->only(['is_public', 'is_featured', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Q&A updated successfully',
            'qna' => $qna->fresh(),
        ], 200);
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
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


        $stats = [
            'total' => CompanyQna::where('company_id', $company->id)->count(),
            'pending' => CompanyQna::where('company_id', $company->id)->pending()->count(),
            'answered' => CompanyQna::where('company_id', $company->id)->where('status', 'answered')->count(),
            'public' => CompanyQna::where('company_id', $company->id)->where('is_public', true)->count(),
            'featured' => CompanyQna::where('company_id', $company->id)->featured()->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ], 200);
    }

    /**
     * Delete a Q&A
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


        $qna = CompanyQna::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $qna->delete();

        return response()->json([
            'success' => true,
            'message' => 'Q&A deleted successfully',
        ], 200);
    }
}
