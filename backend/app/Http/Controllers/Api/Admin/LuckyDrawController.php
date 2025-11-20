<?php
// V-PHASE3-1730-096 (Created) | V-FINAL-1730-368 | V-FINAL-1730-458 (WalletService Refactor)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LuckyDraw;
use App\Services\LuckyDrawService;
use App\Services\WalletService; // <-- IMPORT
use Illuminate\Http\Request;

class LuckyDrawController extends Controller
{
    protected $service;
    protected $walletService; // <-- ADD

    public function __construct(LuckyDrawService $service, WalletService $walletService)
    {
        $this->service = $service;
        $this->walletService = $walletService; // <-- INJECT
    }

    // ... (index, store, show methods remain same) ...
    public function index() { /* ... */ }
    public function store(Request $request) { /* ... */ }
    public function show(LuckyDraw $luckyDraw) { /* ... */ }

    public function executeDraw(Request $request, LuckyDraw $luckyDraw)
    {
        if ($luckyDraw->status !== 'open') {
            return response()->json(['message' => 'This draw is not open.'], 400);
        }

        try {
            // 1. Select Winners (Weighted)
            $winnerUserIds = $this->service->selectWinners($luckyDraw);
            
            // 2. Distribute Prizes (Pass WalletService to it)
            $this->service->distributePrizes($luckyDraw, $winnerUserIds, $this->walletService);
            
            // 3. Send Notifications
            $this->service->sendWinnerNotifications($winnerUserIds);
            
            return response()->json([
                'message' => 'Lucky draw executed successfully!',
                'winners_count' => count($winnerUserIds),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}