<?php
// V-FINAL-1730-465 (Created)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use App\Models\UserKyc;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    /**
     * FSD-ADMIN-001: Aggregate key statistics for the dashboard.
     */
    public function index(Request $request)
    {
        $stats = Cache::remember('admin_dashboard_kpis', 60, function () {
            // 60-second cache to prevent DB hammering

            // 1. Total Revenue
            $totalRevenue = Payment::where('status', 'paid')->sum('amount');

            // 2. Total Users
            $totalUsers = User::role('user')->count();

            // 3. Pending KYC
            $pendingKyc = UserKyc::where('status', 'submitted')->count();

            // 4. Pending Withdrawals
            $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

            return [
                'total_revenue' => (float) $totalRevenue,
                'total_users' => $totalUsers,
                'pending_kyc' => $pendingKyc,
                'pending_withdrawals' => $pendingWithdrawals,
            ];
        });

        return response()->json($stats);
    }
}