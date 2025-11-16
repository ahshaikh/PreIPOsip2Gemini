<?php
// V-FINAL-1730-155 (Created) | V-FINAL-1730-597 (Refactored)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use Illuminate\Http\Request;

class LuckyDrawController extends Controller
{
    /**
     * Get the current active draw and the user's entry for it.
     * Test: testUserCanViewCurrentDraw
     * Test: testUserCanViewOwnEntries
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Get the current active draw
        $activeDraw = LuckyDraw::where('status', 'open')
            ->latest('draw_date')
            ->first();

        $userEntry = null;
        if ($activeDraw) {
            // 2. Find the user's *single* entry row for this draw
            $userEntry = LuckyDrawEntry::where('user_id', $user->id)
                ->where('lucky_draw_id', $activeDraw->id)
                ->first();
        }
        
        // 3. Get user's past winning entries (history)
        $winnings = LuckyDrawEntry::where('user_id', $user->id)
            ->where('is_winner', true)
            ->with('luckyDraw:id,name,draw_date')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'active_draw' => $activeDraw,
            'user_entry' => $userEntry, // This will be { base_entries: 5, bonus_entries: 2, total_entries: 7 }
            'winnings' => $winnings,
        ]);
    }
}
