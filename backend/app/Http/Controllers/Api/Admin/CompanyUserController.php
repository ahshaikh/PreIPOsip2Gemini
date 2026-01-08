<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyUserController extends Controller
{
    /**
     * Get all company users
     */
    public function index(Request $request)
    {
        $query = CompanyUser::with('company');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('contact_person_name', 'like', "%{$search}%")
                  ->orWhereHas('company', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $companyUsers = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $companyUsers->items(),
            'pagination' => [
                'total' => $companyUsers->total(),
                'per_page' => $companyUsers->perPage(),
                'current_page' => $companyUsers->currentPage(),
                'last_page' => $companyUsers->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get company user statistics
     */
    public function statistics()
    {
        return response()->json([
            'success' => true,
            'stats' => [
                'total_companies' => CompanyUser::count(),
                'pending_approval' => CompanyUser::pending()->count(),
                'active_companies' => CompanyUser::active()->count(),
                'verified_companies' => CompanyUser::where('is_verified', true)->count(),
                'suspended_companies' => CompanyUser::where('status', 'suspended')->count(),
            ],
        ], 200);
    }

    /**
     * Get a specific company user
     */
    public function show($id)
    {
        $companyUser = CompanyUser::with(['company', 'financialReports', 'documents', 'updates'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $companyUser,
        ], 200);
    }

    /**
     * Approve a company user
     * FIX 19: Now requires email verification before approval
     */
    public function approve(Request $request, $id)
    {
        $companyUser = CompanyUser::findOrFail($id);

        if ($companyUser->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending company users can be approved',
            ], 400);
        }

        try {
            // FIX 19: This will throw exception if email not verified
            $companyUser->approve();

            // Also activate the company
            if ($companyUser->company) {
                $companyUser->company->update([
                    'status' => 'active',
                    'is_verified' => true,
                ]);
            }

            // Send approval email notification
            \App\Jobs\SendEmailNotification::dispatch(
                $companyUser->email,
                $companyUser->contact_person_name,
                'company-user-approved',
                'Company Account Approved',
                [
                    'name' => $companyUser->contact_person_name,
                    'company_name' => $companyUser->company->name ?? 'Your Company',
                ],
                null
            );

            return response()->json([
                'success' => true,
                'message' => 'Company user approved successfully',
                'data' => $companyUser->fresh(),
            ], 200);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a company user
     * FIX 19: Updated to use CompanyUser::reject() method with notification
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $companyUser = CompanyUser::findOrFail($id);

        if ($companyUser->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending company users can be rejected',
            ], 400);
        }

        // FIX 19: Use new reject method
        $companyUser->reject($request->rejection_reason);

        // Send rejection email notification
        \App\Jobs\SendEmailNotification::dispatch(
            $companyUser->email,
            $companyUser->contact_person_name,
            'company-user-rejected',
            'Company Account Registration Update',
            [
                'name' => $companyUser->contact_person_name,
                'reason' => $request->rejection_reason,
            ],
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Company user rejected',
            'data' => $companyUser->fresh(),
        ], 200);
    }

    /**
     * Suspend a company user
     */
    public function suspend(Request $request, $id)
    {
        $request->validate([
            'suspension_reason' => 'required|string',
        ]);

        $companyUser = CompanyUser::findOrFail($id);

        $companyUser->update([
            'status' => 'suspended',
            'rejection_reason' => $request->suspension_reason, // Reusing field for suspension reason
        ]);

        // TODO: Send suspension email notification

        return response()->json([
            'success' => true,
            'message' => 'Company user suspended',
            'data' => $companyUser->fresh(),
        ], 200);
    }

    /**
     * Reactivate a suspended company user
     */
    public function reactivate($id)
    {
        $companyUser = CompanyUser::findOrFail($id);

        if ($companyUser->status !== 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Only suspended company users can be reactivated',
            ], 400);
        }

        $companyUser->update([
            'status' => 'active',
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Company user reactivated successfully',
            'data' => $companyUser->fresh(),
        ], 200);
    }

    /**
     * Delete a company user
     */
    public function destroy($id)
    {
        $companyUser = CompanyUser::findOrFail($id);

        // Prevent deleting active verified companies
        if ($companyUser->is_verified && $companyUser->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active verified company users. Suspend them first.',
            ], 400);
        }

        $companyUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Company user deleted successfully',
        ], 200);
    }
}
