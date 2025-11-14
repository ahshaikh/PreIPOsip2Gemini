<?php
// V-FINAL-1730-463 (Created)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BonusController extends Controller
{
    /**
     * FSD-BONUS-001: Get bonus summary and recent transactions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Get total earned (sum of all positive bonuses)
        $totalEarned = $user->bonuses()
            ->where('amount', '>', 0)
            ->sum('amount');

        // 2. Get totals grouped by type
        $breakdown = $user->bonuses()
            ->where('amount', '>', 0)
            ->groupBy('type')
            ->select('type', DB::raw('SUM(amount) as total_amount'))
            ->pluck('total_amount', 'type');

        // 3. Get recent transactions (paginated)
        $recent = $user->bonuses()
            ->latest()
            ->paginate(20);

        return response()->json([
            'total_earned' => (float) $totalEarned,
            'breakdown' => $breakdown,
            'recent_transactions' => $recent,
        ]);
    }
}