<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Manages users within a specific company.
 * Accessed by users with the 'company_admin' role.
 */
class UserManagementController extends Controller
{
    /**
     * List all users in the authenticated user's company.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $users = $company->companyUsers()->with('roles')->get();

        return response()->json($users);
    }

    /**
     * Create a new user for the authenticated user's company.
     */
    public function store(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if ($company->max_users_quota && $company->companyUsers()->count() >= $company->max_users_quota) {
            return response()->json(['message' => 'User quota for your organization has been exceeded.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:company_users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'company_api');
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $newUser = $company->companyUsers()->create([
            'contact_person_name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active', // New users created by admin are active by default
            'is_verified' => true, // Assume verified as they are created internally
            'email_verified_at' => now(),
        ]);

        $newUser->assignRole($request->role);

        return response()->json(['message' => 'Company user created successfully.', 'user' => $newUser->load('roles')], 201);
    }

    /**
     * Display a specific user from the company.
     */
    public function show(Request $request, $userId): JsonResponse
    {
        $company = $request->user()->company;
        $user = $company->companyUsers()->with('roles')->findOrFail($userId);

        return response()->json($user);
    }

    /**
     * Update a user within the company.
     */
    public function update(Request $request, $userId): JsonResponse
    {
        $company = $request->user()->company;
        $user = $company->companyUsers()->findOrFail($userId);

        // FIX 2: Prevent company admin from downgrading themselves
        if (
            $request->has('role') &&
            $request->user()->id == $user->id &&
            $user->hasRole('company_admin') &&
            $request->role !== 'company_admin'
        ) {
            return response()->json([
                'message' => 'You cannot remove your own company_admin role.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:company_users,email,' . $user->id,
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->where('guard_name', 'company_api');
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $user->contact_person_name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        $user->save();

        if ($request->has('role')) {
            $user->syncRoles([$request->role]);
        }

        return response()->json(['message' => 'Company user updated successfully.', 'user' => $user->fresh('roles')]);
    }

    /**
     * Soft-delete (suspend) a user from the company.
     */
    public function destroy(Request $request, $userId): JsonResponse
    {
        $company = $request->user()->company;

        if ($request->user()->id == $userId) {
            return response()->json(['message' => 'You cannot suspend your own account.'], 403);
        }

        $user = $company->companyUsers()->findOrFail($userId);

        // Instead of deleting, we suspend the user.
        $user->update(['status' => 'suspended']);

        // Optional: Revoke all API tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Company user has been suspended.']);
    }
}