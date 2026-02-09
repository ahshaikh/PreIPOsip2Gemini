<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Manages users within a specific company.
 * Accessed by users with the 'company_admin' role.
 *
 * SECURITY: All operations are scoped to the authenticated user's company.
 * Company admins can only manage users belonging to their own company.
 */
class UserManagementController extends Controller
{
    /**
     * Available roles for company users (excluding company_admin by default for new users).
     * company_admin must be explicitly requested and validated.
     */
    private const ASSIGNABLE_ROLES = [
        'company_admin',
        'company_finance',
        'company_legal',
        'company_marketing',
        'company_viewer',
    ];

    /**
     * Default role for new users - enforces least privilege principle.
     */
    private const DEFAULT_ROLE = 'company_viewer';

    /**
     * Get company from authenticated user with validation.
     * Returns [CompanyUser, Company] or JsonResponse on error.
     */
    private function getAuthenticatedCompany(Request $request): array|JsonResponse
    {
        $companyUser = $request->user();

        if (!$companyUser || !($companyUser instanceof \App\Models\CompanyUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication error - please re-login to company portal',
            ], 401);
        }

        $company = $companyUser->company;
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found for your account',
            ], 404);
        }

        return [$companyUser, $company];
    }

    /**
     * List all users in the authenticated user's company.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->getAuthenticatedCompany($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        [$companyUser, $company] = $result;

        $query = $company->companyUsers()->with('roles');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contact_person_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_person_designation', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        // Add computed fields for each user
        $users = $users->map(function ($user) use ($companyUser) {
            $userData = $user->toArray();
            $userData['is_current_user'] = $user->id === $companyUser->id;
            $userData['is_admin'] = $user->hasRole('company_admin');
            $userData['role_name'] = $user->roles->first()?->name ?? self::DEFAULT_ROLE;
            return $userData;
        });

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Get statistics for company user management.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $result = $this->getAuthenticatedCompany($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        [$companyUser, $company] = $result;

        $users = $company->companyUsers();

        $stats = [
            'total_users' => $users->count(),
            'active_users' => (clone $users)->where('status', 'active')->count(),
            'pending_users' => (clone $users)->where('status', 'pending')->count(),
            'suspended_users' => (clone $users)->where('status', 'suspended')->count(),
            'admin_users' => $company->companyUsers()
                ->whereHas('roles', fn($q) => $q->where('name', 'company_admin'))
                ->count(),
            'quota_limit' => $company->max_users_quota ?? null,
            'quota_used' => $users->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * List available roles for assignment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function roles(Request $request): JsonResponse
    {
        $roles = Role::where('guard_name', 'company_api')
            ->whereIn('name', self::ASSIGNABLE_ROLES)
            ->get()
            ->map(fn($role) => [
                'name' => $role->name,
                'display_name' => $this->getRoleDisplayName($role->name),
                'description' => $this->getRoleDescription($role->name),
                'is_admin' => $role->name === 'company_admin',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    /**
     * Get human-readable display name for role.
     */
    private function getRoleDisplayName(string $role): string
    {
        return match($role) {
            'company_admin' => 'Company Admin',
            'company_finance' => 'Finance',
            'company_legal' => 'Legal',
            'company_marketing' => 'Marketing',
            'company_viewer' => 'Viewer',
            default => ucfirst(str_replace('company_', '', $role)),
        };
    }

    /**
     * Get description for role.
     */
    private function getRoleDescription(string $role): string
    {
        return match($role) {
            'company_admin' => 'Full access to all company features including user management',
            'company_finance' => 'Access to financial reports and funding information',
            'company_legal' => 'Access to legal documents and compliance features',
            'company_marketing' => 'Access to marketing materials and company updates',
            'company_viewer' => 'Read-only access to company information',
            default => 'Standard company user access',
        };
    }

    /**
     * Create a new user for the authenticated user's company.
     *
     * SECURITY: Enforces least-privilege by defaulting to company_viewer role.
     * company_admin role requires explicit assignment and is logged for audit.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $companyUser = $request->user();

        // Validate user type
        if (!$companyUser || !($companyUser instanceof \App\Models\CompanyUser)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication error - please re-login to company portal',
            ], 401);
        }

        $company = $companyUser->company;

        // Validate company exists
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found for your account',
            ], 404);
        }

        // Check user quota (with null-safe check)
        $maxQuota = $company->max_users_quota ?? null;
        if ($maxQuota && $company->companyUsers()->count() >= $maxQuota) {
            return response()->json([
                'status' => 'error',
                'message' => 'User quota for your organization has been exceeded.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:company_users,email',
            'password' => 'required|string|min:8|confirmed',
            'designation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => [
                'nullable', // Role is optional, defaults to company_viewer
                'string',
                Rule::in(self::ASSIGNABLE_ROLES),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Determine role - default to company_viewer for least privilege
        $assignedRole = $request->role ?? self::DEFAULT_ROLE;

        // Log if assigning admin role for audit trail
        if ($assignedRole === 'company_admin') {
            \Log::info('Company admin role assigned to new user', [
                'assigned_by' => $request->user()->id,
                'assigned_by_email' => $request->user()->email,
                'company_id' => $company->id,
                'new_user_email' => $request->email,
            ]);
        }

        try {
            DB::beginTransaction();

            $newUser = $company->companyUsers()->create([
                'contact_person_name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'contact_person_designation' => $request->designation,
                'phone' => $request->phone,
                'status' => 'active', // New users created by admin are active by default
                'is_verified' => true, // Verified since created internally by admin
                'email_verified_at' => now(),
            ]);

            $newUser->assignRole($assignedRole);

            // Map Spatie role to CompanyUserRole for disclosure permissions
            $disclosureRoleMap = [
                'company_admin' => 'founder',
                'company_finance' => 'finance',
                'company_legal' => 'legal',
                'company_viewer' => 'viewer',
                'company_marketing' => 'viewer', // Marketing gets viewer access to disclosures
            ];

            $disclosureRole = $disclosureRoleMap[$assignedRole] ?? 'viewer';

            \App\Models\CompanyUserRole::create([
                'user_id' => $newUser->id,
                'company_id' => $company->id,
                'role' => $disclosureRole,
                'is_active' => true,
                'assigned_by' => $companyUser->id, // Founder/admin who created the user
                'assigned_at' => now(),
            ]);

            \Log::info('CompanyUserRole assigned during user creation', [
                'company_id' => $company->id,
                'new_user_id' => $newUser->id,
                'spatie_role' => $assignedRole,
                'disclosure_role' => $disclosureRole,
                'assigned_by' => $companyUser->id,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Company user created successfully.',
                'data' => array_merge($newUser->fresh('roles')->toArray(), [
                    'role_name' => $assignedRole,
                    'is_admin' => $assignedRole === 'company_admin',
                ]),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create company user', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user. Please try again.',
            ], 500);
        }
    }

    /**
     * Display a specific user from the company.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function show(Request $request, $userId): JsonResponse
    {
        $result = $this->getAuthenticatedCompany($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        [$companyUser, $company] = $result;

        $user = $company->companyUsers()->with('roles')->findOrFail($userId);

        return response()->json([
            'status' => 'success',
            'data' => array_merge($user->toArray(), [
                'is_current_user' => $user->id === $companyUser->id,
                'is_admin' => $user->hasRole('company_admin'),
                'role_name' => $user->roles->first()?->name ?? self::DEFAULT_ROLE,
            ]),
        ]);
    }

    /**
     * Update a user within the company.
     *
     * SECURITY SAFEGUARDS:
     * 1. Cannot remove own admin role
     * 2. Cannot remove last admin from company
     * 3. All role changes are logged for audit
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function update(Request $request, $userId): JsonResponse
    {
        $result = $this->getAuthenticatedCompany($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        [$currentUser, $company] = $result;

        $user = $company->companyUsers()->findOrFail($userId);

        // SAFEGUARD 1: Prevent company admin from downgrading themselves
        if (
            $request->has('role') &&
            $currentUser->id == $user->id &&
            $user->hasRole('company_admin') &&
            $request->role !== 'company_admin'
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot remove your own admin role. Ask another admin to change your role.',
            ], 403);
        }

        // SAFEGUARD 2: Prevent removing the last admin
        if (
            $request->has('role') &&
            $request->role !== 'company_admin' &&
            $user->hasRole('company_admin')
        ) {
            $adminCount = $company->companyUsers()
                ->where('status', 'active')
                ->whereHas('roles', fn($q) => $q->where('name', 'company_admin'))
                ->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot remove the last admin. At least one admin must remain in the company.',
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:company_users,email,' . $user->id,
            'designation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::in(self::ASSIGNABLE_ROLES),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldRole = $user->roles->first()?->name;
            $newRole = $request->role ?? $oldRole;

            if ($request->has('name')) {
                $user->contact_person_name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('designation')) {
                $user->contact_person_designation = $request->designation;
            }
            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }

            $user->save();

            // Update role if changed
            if ($request->has('role') && $oldRole !== $newRole) {
                $user->syncRoles([$newRole]);

                // Log role changes for audit trail
                \Log::info('Company user role changed', [
                    'changed_by' => $currentUser->id,
                    'changed_by_email' => $currentUser->email,
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Company user updated successfully.',
                'data' => array_merge($user->fresh('roles')->toArray(), [
                    'role_name' => $newRole,
                    'is_admin' => $newRole === 'company_admin',
                ]),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update company user', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user. Please try again.',
            ], 500);
        }
    }

    /**
     * Suspend a user from the company.
     *
     * SECURITY SAFEGUARDS:
     * 1. Cannot suspend yourself
     * 2. Cannot suspend last admin
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function destroy(Request $request, $userId): JsonResponse
    {
        $result = $this->getAuthenticatedCompany($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        [$currentUser, $company] = $result;

        // SAFEGUARD 1: Cannot suspend yourself
        if ($currentUser->id == $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot suspend your own account.',
            ], 403);
        }

        $user = $company->companyUsers()->findOrFail($userId);

        // SAFEGUARD 2: Cannot suspend the last admin
        if ($user->hasRole('company_admin')) {
            $adminCount = $company->companyUsers()
                ->where('status', 'active')
                ->whereHas('roles', fn($q) => $q->where('name', 'company_admin'))
                ->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot suspend the last admin. At least one admin must remain active.',
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            $user->update(['status' => 'suspended']);

            // Revoke all API tokens
            $user->tokens()->delete();

            // Log for audit trail
            \Log::info('Company user suspended', [
                'suspended_by' => $currentUser->id,
                'suspended_by_email' => $currentUser->email,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Company user has been suspended.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to suspend company user', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to suspend user. Please try again.',
            ], 500);
        }
    }

    /**
     * Reactivate a suspended user.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function reactivate(Request $request, $userId): JsonResponse
    {
        $result = $this->getAuthenticatedCompany($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        [$currentUser, $company] = $result;

        $user = $company->companyUsers()->findOrFail($userId);

        if ($user->status !== 'suspended') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only suspended users can be reactivated.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user->update(['status' => 'active']);

            // Log for audit trail
            \Log::info('Company user reactivated', [
                'reactivated_by' => $currentUser->id,
                'reactivated_by_email' => $currentUser->email,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Company user has been reactivated.',
                'data' => array_merge($user->fresh('roles')->toArray(), [
                    'role_name' => $user->roles->first()?->name ?? self::DEFAULT_ROLE,
                    'is_admin' => $user->hasRole('company_admin'),
                ]),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to reactivate company user', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reactivate user. Please try again.',
            ], 500);
        }
    }
}