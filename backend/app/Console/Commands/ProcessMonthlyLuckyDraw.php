<?php
// V-FINAL-1730-423 (Created) | V-AUDIT-FIX-MODULE10 (Dependency Injection)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LuckyDrawService;
use App\Services\WalletService; // <-- Added
use App\Models\LuckyDraw;
use Illuminate\Support\Carbon;

class ProcessMonthlyLuckyDraw extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:process-monthly-lucky-draw {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Manages the full lifecycle of the monthly lucky draw.';
    
    protected $service;
    protected $walletService; // <-- Added property

    // MODULE 10 FIX: Inject WalletService to prevent ArgumentCountError
    public function __construct(LuckyDrawService $service, WalletService $walletService)
    {
        parent::__construct();
        $this->service = $service;
        $this->walletService = $walletService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking lucky draw status...");
        
        // --- 1. Check if a new draw needs to be created ---
        // (Runs on the 1st of the month)
        if (now()->day == 1 || $this->option('force')) {
            $this->createDraw();
        }

        // --- 2. Check if a draw needs to be executed ---
        // (Runs on the draw date, e.g., 28th of month)
        $draw = LuckyDraw::where('status', 'open')
                         ->whereDate('draw_date', '<=', now())
                         ->first();
                         
        if ($draw) {
            $this->executeDraw($draw);
        }

        $this->info("Lucky draw process complete.");
        return 0;
    }

    private function createDraw()
    {
        $monthName = now()->format('F Y');
        $drawName = "{$monthName} Lucky Draw";
        
        if (LuckyDraw::where('name', $drawName)->exists()) {
            $this->warn("Draw for {$monthName} already exists. Skipping creation.");
            return;
        }

        $this->info("Creating draw for {$monthName}...");

        // FSD-DRAW-005: Prize pool from settings
        $prizePool = (float) setting('lucky_draw_prize_pool', 152500);

        // V-AUDIT-MODULE11-003 (HIGH): Move prize structure to settings instead of hardcoding
        //
        // Previous Issue:
        // - Prize structure was hardcoded in command
        // - Changing monthly prize mix required code deployment
        // - Admin couldn't adjust prizes without developer intervention
        //
        // Fix:
        // - Read prize structure from settings table
        // - Setting key: 'lucky_draw_default_structure'
        // - Store as JSON array in database
        // - Admin can now configure via settings panel
        // - Fallback to sensible default if setting not found

        $defaultStructure = [
            ['rank' => 1, 'count' => 1, 'amount' => 50000],
            ['rank' => 2, 'count' => 5, 'amount' => 10000],
            ['rank' => 3, 'count' => 25, 'amount' => 2000],
            ['rank' => 4, 'count' => 50, 'amount' => 25], // 1250 total
        ];

        // V-AUDIT-MODULE11-003: Read from settings with fallback to default
        $structureJson = setting('lucky_draw_default_structure', null);

        if ($structureJson) {
            try {
                $structure = json_decode($structureJson, true);
                if (!is_array($structure) || empty($structure)) {
                    $this->warn("Invalid prize structure in settings, using default.");
                    $structure = $defaultStructure;
                }
            } catch (\Exception $e) {
                $this->warn("Failed to parse prize structure from settings: " . $e->getMessage());
                $structure = $defaultStructure;
            }
        } else {
            $structure = $defaultStructure;
        }

        $this->service->createMonthlyDraw(
            $drawName,
            now()->endOfMonth()->subDays(2), // Draw date = 28th/29th
            $structure
        );
    }

    private function executeDraw(LuckyDraw $draw)
    {
        $this->info("Executing draw: {$draw->name}...");
        
        try {
            // 1. Select Winners
            $winnerUserIds = $this->service->selectWinners($draw);
            
            // 2. Distribute Prizes (MODULE 10 FIX: Pass WalletService)
            $this->service->distributePrizes($draw, $winnerUserIds, $this->walletService);
            
            // 3. Send Notifications
            $this->service->sendWinnerNotifications($winnerUserIds);
            
            $this->info("Draw executed. " . count($winnerUserIds) . " winners paid.");

        } catch (\Exception $e) {
            $this->error("Draw Execution Failed: " . $e->getMessage());
            // Optionally mark draw as failed to prevent infinite retry loop
            // $draw->update(['status' => 'failed']);
        }
    }
}