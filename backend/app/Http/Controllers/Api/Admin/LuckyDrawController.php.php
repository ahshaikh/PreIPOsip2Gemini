<?php
// V-REMEDIATE-1730-156

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\BonusTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LuckyDrawController extends Controller
{
    /**
     * Display a listing of all lucky draws.
     */
    public function index()
    {
        return LuckyDraw::latest()->paginate(25);
    }

    /**
     * Create a new lucky draw.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'draw_date' => 'required|date',
            'prize_structure' => 'required|array|min:1',
            'prize_structure.*.rank' => 'required|integer',
            'prize_structure.*.count' => 'required|integer|min:1',
            'prize_structure.*.amount' => 'required|numeric|min:0',
        ]);

        $draw = LuckyDraw::create([
            'name' => $validated['name'],
            'draw_date' => $validated['draw_date'],
            'prize_structure' => $validated['prize_structure'],
            'status' => 'open',
        ]);

        return response()->json($draw, 201);
    }

    /**
     * Display the specified lucky draw with entries.
     */
    public function show(LuckyDraw $luckyDraw)
    {
        return $luckyDraw->load('entries.user:id,username');
    }

    /**
     * Execute the lucky draw.
     * This is the core logic.
     */
    public function executeDraw(Request $request, LuckyDraw $luckyDraw)
    {
        if ($luckyDraw->status !== 'open') {
            return response()->json(['message' => 'This draw is not open for execution.'], 400);
        }

        $prizeStructure = $luckyDraw->prize_structure;
        $totalWinnersNeeded = collect($prizeStructure)->sum('count');

        // Get all eligible entries that haven't won yet
        $entries = $luckyDraw->entries()->where('is_winner', false)->get();

        // Ensure we don't try to pick more winners than we have entries
        if ($entries->count() < $totalWinnersNeeded) {
            return response()->json(['message' => 'Not enough unique entries to fulfill the prize structure.'], 400);
        }

        // Get a randomized list of unique user IDs from the entries
        $eligibleUserIds = $entries->pluck('user_id')->unique()->shuffle();

        if ($eligibleUserIds->count() < $totalWinnersNeeded) {
            return response()->json(['message' => 'Not enough unique users to fulfill the prize structure. One user cannot win multiple prizes.'], 400);
        }
        
        $winners = [];
        $selectedUserIds = [];
        
        DB::beginTransaction();
        try {
            foreach ($prizeStructure as $tier) {
                $rank = $tier['rank'];
                $count = $tier['count'];
                $amount = $tier['amount'];

                for ($i = 0; $i < $count; $i++) {
                    // Get the next winner from our shuffled list
                    $winnerUserId = $eligibleUserIds->pop();
                    $selectedUserIds[] = $winnerUserId;

                    // Find one of their entries to mark as the "winning entry"
                    $winningEntry = $entries->where('user_id', $winnerUserId)->first();
                    
                    $winningEntry->update([
                        'is_winner' => true,
                        'prize_rank' => $rank,
                        'prize_amount' => $amount,
                    ]);

                    // Create the bonus transaction
                    $bonus = BonusTransaction::create([
                        'user_id' => $winnerUserId,
                        'subscription_id' => $winningEntry->payment->subscription_id, // Get sub from payment
                        'payment_id' => $winningEntry->payment_id,
                        'type' => 'lucky_draw',
                        'amount' => $amount,
                        'multiplier_applied' => 1,
                        'description' => "Lucky Draw Winner - Rank {$rank}",
                    ]);

                    // Credit their wallet
                    $wallet = $winningEntry->user->wallet;
                    $wallet->balance += $amount;
                    $wallet->save();
                    
                    Transaction::create([
                       'user_id' => $winnerUserId,
                       'wallet_id' => $wallet->id,
                       'type' => 'bonus_credit',
                       'amount' => $amount,
                       'balance_after' => $wallet->balance,
                       'description' => "Lucky Draw Prize (Rank {$rank})",
                       'reference_type' => BonusTransaction::class,
                       'reference_id' => $bonus->id,
                    ]);

                    $winners[] = $winningEntry->load('user:id,username');
                }
            }

            // Mark the draw as completed
            $luckyDraw->update(['status' => 'completed']);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lucky Draw Execution Failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'An error occurred during draw execution.'], 500);
        }
        
        // TODO: Dispatch a job to notify all winners

        return response()->json([
            'message' => 'Lucky draw executed successfully!',
            'winners' => $winners,
        ]);
    }
}