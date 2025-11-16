<?php
// V-FINAL-1730-465 (Created) | V-FINAL-1730-594 (Full Data Added) | V-FINAL-1730-595 (Full Data Added)

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
            $pendingKyc = UserKyc::where('status', 'submitted')->count();
            $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

            // --- 2. Charts (Test: testDashboardChartsLoadCorrectly) ---
            $revenueChart = Payment::where('status', 'paid')
                ->where('paid_at', '>=', now()->subDays(30))
                ->groupBy(DB::raw('DATE(paid_at)'))
                ->orderBy('date', 'asc')
                ->select(
                    DB::raw('DATE(paid_at) as date'),
                    DB::raw('SUM(amount) as total')
                )
                ->get();
            
            $userGrowthChart = User::role('user')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(id) as count')
                )
                ->get();

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