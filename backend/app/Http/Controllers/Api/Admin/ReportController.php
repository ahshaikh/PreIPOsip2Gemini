<?php
// V-REMEDIATE-1730-143

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get the main financial summary for the admin dashboard.
     */
    public function getFinancialSummary(Request $request)
    {
        // 1. Get KPI Stats
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $totalUsers = User::role('user')->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalInvestments = Payment::where('status', 'paid')->count();

        // 2. Get Daily Revenue Trend (Last 30 days)
        $dailyRevenue = Payment::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get([
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(amount) as total')
            ]);

        return response()->json([
            'kpis' => [
                'total_revenue' => $totalRevenue,
                'total_users' => $totalUsers,
                'active_subscriptions' => $activeSubscriptions,
                'total_investments' => $totalInvestments,
            ],
            'charts' => [
                'daily_revenue' => $dailyRevenue,
            ]
        ]);
    }
}