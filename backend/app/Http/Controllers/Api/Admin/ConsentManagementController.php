<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserConsent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsentManagementController extends Controller
{
    /**
     * Get consent statistics
     * GET /api/v1/admin/consent/stats
     */
    public function stats()
    {
        $totalUsers = User::count();

        $stats = [
            'total_users' => $totalUsers,
            'cookie_consent' => [
                'total_consents' => UserConsent::byType('cookie_consent')->active()->count(),
                'consent_rate' => $totalUsers > 0
                    ? round((UserConsent::byType('cookie_consent')->active()->distinct('user_id')->count() / $totalUsers) * 100, 2)
                    : 0,
                'analytics_accepted' => UserConsent::byType('cookie_consent')
                    ->active()
                    ->whereJsonContains('consent_data->analytics', true)
                    ->count(),
                'marketing_accepted' => UserConsent::byType('cookie_consent')
                    ->active()
                    ->whereJsonContains('consent_data->marketing', true)
                    ->count(),
                'preferences_accepted' => UserConsent::byType('cookie_consent')
                    ->active()
                    ->whereJsonContains('consent_data->preferences', true)
                    ->count(),
            ],
            'marketing_emails' => [
                'opt_in_count' => UserConsent::byType('marketing_emails')->active()->count(),
                'opt_out_count' => UserConsent::byType('marketing_emails')->revoked()->count(),
            ],
            'data_sharing' => [
                'opt_in_count' => UserConsent::byType('data_sharing')->active()->count(),
                'opt_out_count' => UserConsent::byType('data_sharing')->revoked()->count(),
            ],
            'recent_consents' => UserConsent::with('user')
                ->latest('granted_at')
                ->limit(10)
                ->get()
                ->map(function ($consent) {
                    return [
                        'id' => $consent->id,
                        'user' => [
                            'id' => $consent->user->id,
                            'name' => $consent->user->username,
                            'email' => $consent->user->email,
                        ],
                        'type' => $consent->consent_type,
                        'version' => $consent->consent_version,
                        'granted_at' => $consent->granted_at,
                        'is_active' => $consent->isValid(),
                    ];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Get all user consents with filters
     * GET /api/v1/admin/consent
     */
    public function index(Request $request)
    {
        $query = UserConsent::with('user');

        // Filter by consent type
        if ($request->filled('consent_type')) {
            $query->where('consent_type', $request->consent_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'revoked') {
                $query->revoked();
            }
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('granted_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('granted_at', '<=', $request->to_date);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'granted_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        $consents = $query->paginate($perPage);

        return response()->json($consents);
    }

    /**
     * Get consent history for a specific user
     * GET /api/v1/admin/consent/user/{userId}
     */
    public function userHistory($userId)
    {
        $user = User::findOrFail($userId);

        $consents = UserConsent::where('user_id', $userId)
            ->orderBy('granted_at', 'desc')
            ->get()
            ->groupBy('consent_type');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->username,
                'email' => $user->email,
            ],
            'consents' => $consents,
        ]);
    }

    /**
     * Revoke consent for a user
     * POST /api/v1/admin/consent/{id}/revoke
     */
    public function revokeConsent($id)
    {
        $consent = UserConsent::findOrFail($id);

        if ($consent->revoked_at) {
            return response()->json([
                'message' => 'Consent already revoked',
            ], 422);
        }

        $consent->update(['revoked_at' => now()]);

        return response()->json([
            'message' => 'Consent revoked successfully',
            'consent' => $consent,
        ]);
    }

    /**
     * Get consent types and their configurations
     * GET /api/v1/admin/consent/types
     */
    public function consentTypes()
    {
        $types = [
            [
                'type' => 'cookie_consent',
                'name' => 'Cookie Consent',
                'description' => 'User consent for cookie usage',
                'required' => true,
                'version' => setting('cookie_consent_version', '1.0'),
            ],
            [
                'type' => 'marketing_emails',
                'name' => 'Marketing Emails',
                'description' => 'Consent to receive marketing emails',
                'required' => false,
                'version' => '1.0',
            ],
            [
                'type' => 'marketing_sms',
                'name' => 'Marketing SMS',
                'description' => 'Consent to receive marketing SMS',
                'required' => false,
                'version' => '1.0',
            ],
            [
                'type' => 'data_sharing',
                'name' => 'Data Sharing',
                'description' => 'Consent for sharing data with partners',
                'required' => false,
                'version' => '1.0',
            ],
            [
                'type' => 'profiling',
                'name' => 'Profiling',
                'description' => 'Consent for automated profiling',
                'required' => false,
                'version' => '1.0',
            ],
        ];

        return response()->json(['types' => $types]);
    }

    /**
     * Export consent data
     * GET /api/v1/admin/consent/export
     */
    public function export(Request $request)
    {
        $query = UserConsent::with('user');

        if ($request->filled('consent_type')) {
            $query->where('consent_type', $request->consent_type);
        }

        if ($request->filled('from_date')) {
            $query->where('granted_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('granted_at', '<=', $request->to_date);
        }

        $consents = $query->get();

        $csvData = "ID,User ID,User Name,User Email,Consent Type,Version,Granted At,Revoked At,IP Address\n";

        foreach ($consents as $consent) {
            $csvData .= sprintf(
                "%d,%d,%s,%s,%s,%s,%s,%s,%s\n",
                $consent->id,
                $consent->user_id,
                $consent->user->username ?? 'N/A',
                $consent->user->email ?? 'N/A',
                $consent->consent_type,
                $consent->consent_version,
                $consent->granted_at->format('Y-m-d H:i:s'),
                $consent->revoked_at ? $consent->revoked_at->format('Y-m-d H:i:s') : 'Active',
                $consent->ip_address ?? 'N/A'
            );
        }

        return response($csvData, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="consents-export-' . now()->format('Y-m-d') . '.csv"');
    }

    /**
     * Get consent timeline
     * GET /api/v1/admin/consent/timeline
     */
    public function timeline(Request $request)
    {
        $days = $request->input('days', 30);

        $timeline = UserConsent::select(
                DB::raw('DATE(granted_at) as date'),
                DB::raw('COUNT(*) as count'),
                'consent_type'
            )
            ->where('granted_at', '>=', now()->subDays($days))
            ->groupBy('date', 'consent_type')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('date');

        return response()->json(['timeline' => $timeline]);
    }
}
