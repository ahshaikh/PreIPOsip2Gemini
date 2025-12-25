<?php
// V-PHASE3-1730-092 (Created) | V-FINAL-1730-463 | V-FINAL-1730-BONUSES-COMPLETE

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BonusTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BonusController extends Controller
{
    /**
     * Get User Bonus Summary with breakdown by type
     * Endpoint: /api/v1/user/bonuses
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all bonus transactions for this user
        $bonusTransactions = BonusTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate summary by type
        $allBonuses = BonusTransaction::where('user_id', $user->id)->get();

        $summary = [
            'referral_bonus' => $allBonuses->where('type', 'referral_bonus')->sum('amount'),
            'welcome_bonus' => $allBonuses->where('type', 'welcome_bonus')->sum('amount'),
            'loyalty_bonus' => $allBonuses->where('type', 'loyalty_bonus')->sum('amount'),
            'milestone_bonus' => $allBonuses->where('type', 'milestone_bonus')->sum('amount'),
            'special_bonus' => $allBonuses->where('type', 'special_bonus')->sum('amount'),
            'cashback' => $allBonuses->where('type', 'cashback')->sum('amount'),
        ];

        // Format transactions for frontend
        $transactions = $bonusTransactions->getCollection()->map(function ($bonus) {
            return [
                'id' => $bonus->id,
                'type' => $bonus->type,
                'amount' => (float) $bonus->amount,
                'tds_deducted' => (float) $bonus->tds_deducted,
                'net_amount' => (float) $bonus->net_amount,
                'description' => $bonus->description,
                'status' => 'credited', // All bonuses in the table are credited
                'created_at' => $bonus->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'summary' => $summary,
            'transactions' => [
                'data' => $transactions,
                'current_page' => $bonusTransactions->currentPage(),
                'last_page' => $bonusTransactions->lastPage(),
                'per_page' => $bonusTransactions->perPage(),
                'total' => $bonusTransactions->total(),
            ],
        ]);
    }

    /**
     * Get Paginated Bonus Transactions
     * Endpoint: /api/v1/user/bonuses/transactions
     * [PROTOCOL 7 IMPLEMENTATION]
     */
    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string',
            'page' => 'nullable|integer',
        ]);

        $userId = $request->user()->id;
        $query = BonusTransaction::where('user_id', $userId);

        // Apply type filter
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Dynamic Pagination
        $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

        $transactions = $query->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($transactions);
    }

    /**
     * Get Pending Bonuses List
     * Endpoint: /api/v1/user/bonuses/pending
     *
     * Note: Since bonus_transactions table doesn't have a status field,
     * all bonuses are credited immediately. This endpoint checks for
     * bonuses that haven't been transferred to wallet yet.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        // For now, return empty as all bonuses are credited immediately
        // In future, you could track "pending approval" bonuses in a separate table
        return response()->json([
            'total' => 0,
            'items' => [],
        ]);
    }

    /**
     * Export bonus history
     * Endpoint: /api/v1/user/bonuses/export
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $bonuses = BonusTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Create CSV
        $csv = "Date,Type,Description,Amount,TDS Deducted,Net Amount\n";

        foreach ($bonuses as $bonus) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s\n",
                $bonus->created_at->format('Y-m-d H:i:s'),
                ucfirst(str_replace('_', ' ', $bonus->type)),
                '"' . str_replace('"', '""', $bonus->description) . '"',
                number_format($bonus->amount, 2),
                number_format($bonus->tds_deducted, 2),
                number_format($bonus->net_amount, 2)
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="bonuses-' . now()->format('Y-m-d') . '.csv"');
    }
}
