<?php
// V-PHASE2-1730-052


namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserKyc;
// ... other models
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Get dashboard metrics.
     */
    public function index()
    {
        // This will be built out more in later phases
        $totalUsers = User::role('user')->count();
        $pendingKyc = UserKyc::where('status', 'submitted')->count();
        // $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

        return response()->json([
            'totalUsers' => $totalUsers,
            'pendingKyc' => $pendingKyc,
            'pendingWithdrawals' => 0, // Placeholder
            'totalInvested' => 0, // Placeholder
        ]);
    }
}