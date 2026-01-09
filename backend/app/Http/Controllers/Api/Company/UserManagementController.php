<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * UserManagementController
 * * [AUDIT FIX]: Implements strict multi-tenant user management.
 */
class UserManagementController extends Controller
{
    /**
     * List all users in the company.
     * The 'BelongsToCompany' trait automatically filters this to the correct company.
     */
    public function index(): JsonResponse
    {
        $users = User::all(); // Scoped to company_id via Trait
        return response()->json($users);
    }

    /**
     * Add a new employee to the enterprise.
     * [AUDIT FIX]: Validates against enterprise user quotas.
     */
    public function store(Request $request): JsonResponse
    {
        $company = auth()->user()->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        if ($company->users()->count() >= $company->max_users_quota) {
            return response()->json(['message' => 'User quota exceeded for your organization.'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'company_id' => $company->id,
            'role' => 'employee'
        ]);

        return response()->json(['message' => 'Employee added successfully', 'user' => $user], 201);
    }
}