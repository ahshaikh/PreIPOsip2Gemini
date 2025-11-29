<?php
// V-PHASE3-1730-092 (Created) | V-FINAL-1730-463 

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class BonusController extends Controller
{
    /**
     * Get User Bonus Summary
     * Endpoint: /api/v1/user/bonuses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Safe Defaults
            $totalEarned = 0;
            $pendingPayout = 0;
            $history = [];

            // Defensive: Check if table exists
            if (Schema::hasTable('bonus_transactions')) {
                // Use DB Query for speed and safety (no Model relation crashes)
                $txs = DB::table('bonus_transactions')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $totalEarned = $txs->where('status', 'paid')->sum('amount');
                $pendingPayout = $txs->where('status', 'pending')->sum('amount');
                
                $history = $txs->map(function($t) {
                    return [
                        'id' => $t->id,
                        'type' => ucfirst(str_replace('_', ' ', $t->type)), // e.g. "referral_bonus" -> "Referral Bonus"
                        'amount' => (float) $t->amount,
                        'status' => $t->status,
                        'description' => $t->description,
                        'date' => Carbon::parse($t->created_at)->format('d M Y'),
                    ];
                });
            }

            return response()->json([
                'total_earned' => (float) $totalEarned,
                'pending_payout' => (float) $pendingPayout,
                'history' => $history
            ]);

        } catch (\Throwable $e) {
            // Return empty state on crash
            return response()->json([
                'total_earned' => 0, 
                'pending_payout' => 0, 
                'history' => []
            ]);
        }
    }

    /**
     * Get Pending Bonuses List
     * Endpoint: /api/v1/user/bonuses/pending
     * Fixes 404 Error
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $data = [];

            if (Schema::hasTable('bonus_transactions')) {
                $data = DB::table('bonus_transactions')
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($t) {
                        return [
                            'id' => $t->id,
                            'type' => ucfirst(str_replace('_', ' ', $t->type)),
                            'amount' => (float) $t->amount,
                            'description' => $t->description,
                            'date' => Carbon::parse($t->created_at)->format('d M Y'),
                        ];
                    });
            }

            return response()->json(['data' => $data]);

        } catch (\Throwable $e) {
            return response()->json(['data' => []]);
        }
    }
}