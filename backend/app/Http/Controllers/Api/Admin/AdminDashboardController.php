<?php
// V-PHASE2-1730-052 (Created) | V-FINAL-1730-465 (Created) | V-FINAL-1730-594 (Full Data Added) | V-FINAL-1730-595 (Full Data Added)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use App\Models\ActivityLog; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // <-- IMPORT

class AdminDashboardController extends Controller
{
    /**
     * FSD-ADMIN-001: Aggregate key statistics for the dashboard.
     */
    public function index(Request $request)
    {
        // We cache this data for 10 minutes to keep the dashboard fast
        $stats = Cache::remember('admin_dashboard_v2', 600, function () {
            
            // --- 1. KPIs (Test: testDashboardShows...) ---
            $totalRevenue = Payment::where('status', 'paid')->sum('amount');
            $totalUsers = User::role('user')->count();
            $pendingKyc = UserKyc::whereIn('status', ['submitted', 'processing'])->count();
            $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

            // Additional KPIs for frontend dashboard
            $activeSubscriptions = Subscription::where('status', 'active')->count();
            $monthlyRevenue = Payment::where('status', 'paid')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount');
            $newUsers30d = User::role('user')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            $pendingPayments = Payment::where('status', 'pending')->count();

            // --- 2. Charts (Test: testDashboardChartsLoadCorrectly) ---

            // V-AUDIT-MODULE19-LOW: Fixed Database Coupling (MySQL-Specific DATE() Function)
            // PROBLEM: Using DB::raw('DATE(column)') and groupBy(DB::raw(...)) is MySQL-specific
            // and breaks on PostgreSQL (use column::date) and may have issues on other databases.
            // This creates vendor lock-in and makes database migration difficult.
            //
            // SOLUTION: Fetch records and group in PHP using Carbon (database-agnostic).
            // Since this data is:
            // 1. Cached for 10 minutes (not real-time)
            // 2. Limited to 30 days (max ~1K-10K records)
            // 3. Already filtered by date range
            // Performance impact is negligible, and we gain full database portability.

            // V-AUDIT-MODULE19-LOW: Revenue chart - fetch and group in PHP
            $revenueData = Payment::where('status', 'paid')
                ->where('paid_at', '>=', now()->subDays(30))
                ->orderBy('paid_at', 'asc')
                ->select('paid_at', 'amount')
                ->get();

            $revenueChart = $revenueData
                ->groupBy(function ($payment) {
                    // Use Carbon to format date (works with any database)
                    return $payment->paid_at->format('Y-m-d');
                })
                ->map(function ($group, $date) {
                    return [
                        'date' => $date,
                        'total' => $group->sum('amount'),
                    ];
                })
                ->sortBy('date')
                ->values(); // Reset keys to 0, 1, 2... for JSON array

            // V-AUDIT-MODULE19-LOW: User growth chart - fetch and group in PHP
            $userData = User::role('user')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'asc')
                ->select('id', 'created_at')
                ->get();

            $userGrowthChart = $userData
                ->groupBy(function ($user) {
                    // Use Carbon to format date (works with any database)
                    return $user->created_at->format('Y-m-d');
                })
                ->map(function ($group, $date) {
                    return [
                        'date' => $date,
                        'count' => $group->count(),
                    ];
                })
                ->sortBy('date')
                ->values(); // Reset keys to 0, 1, 2... for JSON array

            // --- 3. Activity (Test: testDashboardShowsRecentActivity) ---
            $recentActivity = ActivityLog::with('user:id,username')
                ->latest()
                ->limit(5) // Get the 5 most recent actions
                ->get();

            return [
                'kpis' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_users' => $totalUsers,
                    'pending_kyc' => $pendingKyc,
                    'pending_withdrawals' => $pendingWithdrawals,
                    'active_subscriptions' => $activeSubscriptions,
                    'monthly_revenue' => (float) $monthlyRevenue,
                    'new_users_30d' => $newUsers30d,
                    'pending_payments' => $pendingPayments,
                ],
                'charts' => [
                    'revenue_over_time' => $revenueChart,
                    'user_growth' => $userGrowthChart,
                ],
                'recent_activity' => $recentActivity,
            ];
        });

        return response()->json($stats);
    }
}