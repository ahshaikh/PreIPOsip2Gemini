<?php
// V-REMEDIATE-1730-157

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\LuckyDraw;
use Illuminate\Http\Request;

class LuckyDrawController extends Controller
{
    /**
     * Get active and past lucky draws.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get the current open draw
        $activeDraw = LuckyDraw::where('status', 'open')
            ->orderBy('draw_date', 'desc')
            ->first();

        // Get the user's entries for this draw
        $myEntries = [];
        if ($activeDraw) {
            $myEntries = $activeDraw->entries()
                ->where('user_id', $user->id)
                ->get();
        }

        // Get past completed draws
        $pastDraws = LuckyDraw::where('status', 'completed')
            ->orderBy('draw_date', 'desc')
            ->paginate(5);

        return response()->json([
            'active_draw' => $activeDraw,
            'my_entries' => $myEntries,
            'past_draws' => $pastDraws,
        ]);
    }
}