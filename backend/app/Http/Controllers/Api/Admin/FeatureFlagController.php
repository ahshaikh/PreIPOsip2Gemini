<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    /**
     * List all feature flags
     * GET /api/v1/admin/feature-flags
     */
    public function index()
    {
        $flags = FeatureFlag::orderBy('key')->get();

        // Add usage statistics
        $flags = $flags->map(function ($flag) {
            if ($flag->percentage && $flag->percentage < 100) {
                $totalUsers = User::role('user')->count();
                $enabledCount = (int) ($totalUsers * $flag->percentage / 100);

                $flag->enabled_for = $enabledCount;
                $flag->total_users = $totalUsers;
            }

            return $flag;
        });

        return response()->json(['flags' => $flags]);
    }

    /**
     * Create feature flag
     * POST /api/v1/admin/feature-flags
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:feature_flags,key|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
            'percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $flag = FeatureFlag::create($validated);

        return response()->json([
            'message' => 'Feature flag created successfully',
            'flag' => $flag,
        ], 201);
    }

    /**
     * Show feature flag
     * GET /api/v1/admin/feature-flags/{flag}
     */
    public function show(FeatureFlag $flag)
    {
        // Calculate statistics
        $stats = null;

        if ($flag->percentage && $flag->percentage < 100) {
            $totalUsers = User::role('user')->count();
            $enabledCount = (int) ($totalUsers * $flag->percentage / 100);

            $stats = [
                'total_users' => $totalUsers,
                'enabled_for' => $enabledCount,
                'disabled_for' => $totalUsers - $enabledCount,
                'percentage' => $flag->percentage,
            ];
        } elseif ($flag->is_active) {
            $totalUsers = User::role('user')->count();
            $stats = [
                'total_users' => $totalUsers,
                'enabled_for' => $totalUsers,
                'disabled_for' => 0,
                'percentage' => 100,
            ];
        }

        return response()->json([
            'flag' => $flag,
            'stats' => $stats,
        ]);
    }

    /**
     * Update feature flag
     * PUT /api/v1/admin/feature-flags/{flag}
     */
    public function update(Request $request, FeatureFlag $flag)
    {
        $validated = $request->validate([
            'key' => 'sometimes|string|unique:feature_flags,key,' . $flag->id . '|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $flag->update($validated);

        return response()->json([
            'message' => 'Feature flag updated successfully',
            'flag' => $flag,
        ]);
    }

    /**
     * Delete feature flag
     * DELETE /api/v1/admin/feature-flags/{flag}
     */
    public function destroy(FeatureFlag $flag)
    {
        $flag->delete();

        return response()->json([
            'message' => 'Feature flag deleted successfully',
        ]);
    }

    /**
     * Toggle feature flag (quick action)
     * POST /api/v1/admin/feature-flags/{flag}/toggle
     */
    public function toggle(FeatureFlag $flag)
    {
        $flag->update([
            'is_active' => !$flag->is_active,
        ]);

        return response()->json([
            'message' => 'Feature flag toggled successfully',
            'flag' => $flag,
        ]);
    }

    /**
     * Update rollout percentage
     * POST /api/v1/admin/feature-flags/{flag}/rollout
     */
    public function updateRollout(Request $request, FeatureFlag $flag)
    {
        $validated = $request->validate([
            'percentage' => 'required|integer|min:0|max:100',
        ]);

        $flag->update([
            'percentage' => $validated['percentage'],
            'is_active' => true, // Enable flag when setting percentage
        ]);

        $totalUsers = User::role('user')->count();
        $enabledCount = (int) ($totalUsers * $validated['percentage'] / 100);

        return response()->json([
            'message' => 'Rollout percentage updated successfully',
            'flag' => $flag,
            'stats' => [
                'total_users' => $totalUsers,
                'enabled_for' => $enabledCount,
                'percentage' => $validated['percentage'],
            ],
        ]);
    }

    /**
     * Check if feature is enabled for specific user
     * GET /api/v1/admin/feature-flags/{flag}/check/{user}
     */
    public function checkForUser(FeatureFlag $flag, User $user)
    {
        $isEnabled = $flag->isEnabled($user);

        return response()->json([
            'flag_key' => $flag->key,
            'user_id' => $user->id,
            'is_enabled' => $isEnabled,
            'flag_active' => $flag->is_active,
            'percentage' => $flag->percentage,
        ]);
    }

    /**
     * Get all users affected by a percentage rollout
     * GET /api/v1/admin/feature-flags/{flag}/affected-users
     */
    public function getAffectedUsers(FeatureFlag $flag, Request $request)
    {
        if (!$flag->percentage || $flag->percentage >= 100) {
            return response()->json([
                'message' => 'This flag is not using percentage rollout',
                'enabled_for' => $flag->is_active ? 'all' : 'none',
            ]);
        }

        $users = User::role('user')->get();
        $affected = [];

        foreach ($users as $user) {
            if ($flag->isEnabled($user)) {
                $affected[] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ];
            }
        }

        // Paginate manually
        $page = $request->input('page', 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        $paginatedUsers = array_slice($affected, $offset, $perPage);

        return response()->json([
            'flag' => $flag,
            'total_affected' => count($affected),
            'total_users' => $users->count(),
            'percentage' => $flag->percentage,
            'current_page' => $page,
            'per_page' => $perPage,
            'users' => $paginatedUsers,
        ]);
    }

    /**
     * Bulk create default feature flags
     * POST /api/v1/admin/feature-flags/seed
     */
    public function seedDefaultFlags()
    {
        $defaults = [
            [
                'key' => 'new_dashboard',
                'description' => 'Enable new dashboard UI',
                'is_active' => false,
                'percentage' => 0,
            ],
            [
                'key' => 'referral_system_v2',
                'description' => 'Enable referral system version 2',
                'is_active' => false,
                'percentage' => 0,
            ],
            [
                'key' => 'advanced_analytics',
                'description' => 'Enable advanced analytics features',
                'is_active' => false,
                'percentage' => 0,
            ],
            [
                'key' => 'auto_investment',
                'description' => 'Enable automatic investment feature',
                'is_active' => false,
                'percentage' => 0,
            ],
            [
                'key' => 'push_notifications',
                'description' => 'Enable push notifications',
                'is_active' => true,
                'percentage' => 100,
            ],
        ];

        $created = 0;
        foreach ($defaults as $flag) {
            if (!FeatureFlag::where('key', $flag['key'])->exists()) {
                FeatureFlag::create($flag);
                $created++;
            }
        }

        return response()->json([
            'message' => "Created {$created} default feature flags",
            'created_count' => $created,
        ]);
    }
}
