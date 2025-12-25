<?php
// V-FINAL-1730-155 (Created) | V-FINAL-1730-597 (Refactored)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class LuckyDrawController extends Controller
{
    /**
     * Get Lucky Draw Dashboard
     * Endpoint: /api/v1/user/lucky-draws
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // 1. Fetch Active Draw (Open)
            $activeDraw = null;
            if (Schema::hasTable('lucky_draws')) {
                $rawDraw = DB::table('lucky_draws')
                    ->where('status', 'open')
                    ->orderBy('draw_date', 'asc')
                    ->first();

                if ($rawDraw) {
                    $activeDraw = [
                        'id' => $rawDraw->id,
                        'name' => $rawDraw->name,
                        'draw_date' => Carbon::parse($rawDraw->draw_date)->format('d M Y'),
                        'status' => $rawDraw->status,

                        // FIX: Transform JSON Object -> Array for Frontend .reduce() compatibility
                        'prize_structure' => $this->transformPrizeStructure($rawDraw->prize_structure),

                        // V-AUDIT-MODULE11-001 (CRITICAL): Return array of entries, not integer count
                        // Frontend expects my_entries array to map over, not user_tickets integer
                        'my_entries' => $this->getUserEntries($rawDraw->id, $user->id),
                        'total_tickets' => $this->getUserTicketCount($rawDraw->id, $user->id) // Keep count for display
                    ];
                }
            }

            // 2. Fetch Past Draws (Completed)
            $history = [];
            if (Schema::hasTable('lucky_draws')) {
                $history = DB::table('lucky_draws')
                    ->where('status', 'completed')
                    ->orderBy('draw_date', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($draw) use ($user) {
                        return [
                            'id' => $draw->id,
                            'name' => $draw->name,
                            'draw_date' => Carbon::parse($draw->draw_date)->format('d M Y'),
                            'winners' => json_decode($draw->winners_json ?? '[]'), // Safe decode
                            'my_result' => 'Better luck next time', // Placeholder logic
                        ];
                    });
            }

            return response()->json([
                'active_draw' => $activeDraw,
                'history' => $history
            ]);

        } catch (\Throwable $e) {
            return response()->json(['active_draw' => null, 'history' => []]);
        }
    }

    /**
     * Helper: Transform Prize Structure (Object -> Array)
     * Handles both old format {"1st": "iPhone"} and new format [{"tier": "1st", "amount": 5000}]
     */
    private function transformPrizeStructure($json)
    {
        if (empty($json)) return [];
        
        $data = json_decode($json, true);
        
        // If it's already an indexed array (List), return as is
        if (array_is_list($data)) {
            // Ensure 'rank' exists even in new format
            return array_map(function($item, $index) {
                $item['rank'] = $item['rank'] ?? ($index + 1);
                return $item;
            }, $data, array_keys($data));
        }

        // If it's an associative array (Object), transform to List
        // Old Seeder Format: {"1st": "iPhone", "2nd": "iPad"}
        $list = [];
        $rankCounter = 1; // Initialize rank counter
        
        foreach ($data as $tier => $prize) {
            // Heuristic to assign a value if missing (for .reduce calculation)
            $estimatedValue = 0;
            if (stripos($prize, 'iPhone') !== false) $estimatedValue = 100000;
            elseif (stripos($prize, 'iPad') !== false) $estimatedValue = 50000;
            elseif (stripos($prize, 'Gold') !== false) $estimatedValue = 30000;
            elseif (stripos($prize, 'Voucher') !== false) $estimatedValue = 5000;

            $list[] = [
                'rank' => $rankCounter++, // Add unique rank (1, 2, 3...) for React key
                'tier' => $tier,
                'prize' => $prize,
                'count' => 1, // Default count
                'amount' => $estimatedValue // Required for .reduce() calculation on frontend
            ];
        }
        
        return $list;
    }

    /**
     * V-AUDIT-MODULE11-001 (CRITICAL): Generate virtual entry codes for frontend display
     *
     * Previous Issue:
     * - getUserTickets() returned integer count
     * - Frontend tried to .map() over integer, causing crash
     * - Frontend expected array of entries with entry_code property
     *
     * Fix:
     * - Return array of entry objects with generated codes
     * - Each entry gets a unique code like "LD-{drawId}-{userId}-{entryNum}"
     * - Frontend can now safely map over entries
     */
    private function getUserEntries($drawId, $userId)
    {
        if (!Schema::hasTable('lucky_draw_entries')) {
            return [];
        }

        $entry = DB::table('lucky_draw_entries')
            ->where('lucky_draw_id', $drawId)
            ->where('user_id', $userId)
            ->first();

        if (!$entry) {
            return [];
        }

        // Generate virtual entry codes based on base_entries + bonus_entries
        $totalEntries = ($entry->base_entries ?? 0) + ($entry->bonus_entries ?? 0);
        $entries = [];

        for ($i = 1; $i <= $totalEntries; $i++) {
            $entries[] = [
                'entry_code' => sprintf('LD-%d-%d-%04d', $drawId, $userId, $i),
                'type' => $i <= ($entry->base_entries ?? 0) ? 'base' : 'bonus',
                'entry_number' => $i,
            ];
        }

        return $entries;
    }

    /**
     * V-AUDIT-MODULE11-001: Keep ticket count method for backward compatibility
     */
    private function getUserTicketCount($drawId, $userId)
    {
        if (!Schema::hasTable('lucky_draw_entries')) {
            return 0;
        }

        $entry = DB::table('lucky_draw_entries')
            ->where('lucky_draw_id', $drawId)
            ->where('user_id', $userId)
            ->first();

        if (!$entry) {
            return 0;
        }

        return ($entry->base_entries ?? 0) + ($entry->bonus_entries ?? 0);
    }

    /**
     * Get Paginated Past Draws
     * Endpoint: /api/v1/user/lucky-draws/past-draws
     * [PROTOCOL 7 IMPLEMENTATION]
     */
    public function pastDraws(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer',
        ]);

        try {
            $user = $request->user();

            if (!Schema::hasTable('lucky_draws')) {
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ]);
            }

            // Dynamic Pagination
            $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

            $draws = DB::table('lucky_draws')
                ->where('status', 'completed')
                ->orderBy('draw_date', 'desc')
                ->paginate($perPage)
                ->through(function ($draw) use ($user) {
                    return [
                        'id' => $draw->id,
                        'name' => $draw->name,
                        'draw_date' => Carbon::parse($draw->draw_date)->format('d M Y'),
                        'status' => $draw->status,
                        'prize_structure' => $this->transformPrizeStructure($draw->prize_structure ?? '[]'),
                        'winners' => json_decode($draw->winners_json ?? '[]'),
                        'my_result' => 'Better luck next time', // Placeholder logic
                    ];
                });

            $draws->appends($request->query());

            return response()->json($draws);

        } catch (\Throwable $e) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ]);
        }
    }
}