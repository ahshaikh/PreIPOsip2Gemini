<?php
// V-FINAL-1730-423 (Created)
// V-AUDIT-FIX-MODULE10
// V-WAVE3-ARCH-FIX: Removed invalid per-payment job dispatch from command layer

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LuckyDrawService;
use App\Services\WalletService;
use App\Models\LuckyDraw;

class ProcessMonthlyLuckyDraw extends Command
{
    protected $signature = 'app:process-monthly-lucky-draw {--force}';
    protected $description = 'Manages the full lifecycle of the monthly lucky draw.';

    protected LuckyDrawService $service;
    protected WalletService $walletService;

    public function __construct(LuckyDrawService $service, WalletService $walletService)
    {
        parent::__construct();
        $this->service = $service;
        $this->walletService = $walletService;
    }

    public function handle()
    {
        $this->info("Checking lucky draw status...");

        // ------------------------------------------------------------
        // 1️⃣ Create Monthly Draw (if needed)
        // ------------------------------------------------------------
        if (now()->day == 1 || $this->option('force')) {
            $this->createDraw();
        }

        // ------------------------------------------------------------
        // 2️⃣ Execute Pending Draw (if ready)
        // ------------------------------------------------------------
        $draw = $this->service->getPendingDraw(now());

        if ($draw) {
            $this->executeDraw($draw);
            $draw->update(['status' => 'completed']);
        }

        $this->info("Lucky draw process complete.");
        return 0;
    }

    /**
     * Create Monthly Draw and trigger entry allocation.
     */
    private function createDraw(): void
    {
        $monthName = now()->format('F Y');
        $drawName = "{$monthName} Lucky Draw";

        if (LuckyDraw::where('name', $drawName)->exists()) {
            $this->warn("Draw for {$monthName} already exists. Skipping creation.");
            return;
        }

        $this->info("Creating draw for {$monthName}...");

        // Prize structure defaults
        $defaultStructure = [
            ['rank' => 1, 'count' => 1, 'amount' => 50000],
            ['rank' => 2, 'count' => 5, 'amount' => 10000],
            ['rank' => 3, 'count' => 25, 'amount' => 2000],
            ['rank' => 4, 'count' => 50, 'amount' => 25],
        ];

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

        // Create draw via service
        $this->service->createMonthlyDraw(
            $drawName,
            now()->endOfMonth()->subDays(2),
            $structure
        );

        // ------------------------------------------------------------
        // ARCH FIX:
        // Delegate entry allocation to service.
        // Service will fetch eligible payments and dispatch per-payment jobs.
        // ------------------------------------------------------------
        $this->service->dispatchMonthlyEntryGeneration();

        $this->info("Draw created and entry generation dispatched.");
    }

    /**
     * Execute the draw lifecycle.
     */
    private function executeDraw(LuckyDraw $draw): void
    {
        $this->info("Executing draw: {$draw->name}...");

        try {
            // 1️⃣ Select Winners
            $winnerUserIds = $this->service->selectWinners($draw);

            // 2️⃣ Distribute Prizes
            $this->service->distributePrizes(
                $draw,
                $winnerUserIds,
                $this->walletService
            );

            // 3️⃣ Send Notifications
            $this->service->sendWinnerNotifications($winnerUserIds);

            $this->info("Draw executed. " . count($winnerUserIds) . " winners paid.");
        } catch (\Exception $e) {
            $this->error("Draw Execution Failed: " . $e->getMessage());
        }
    }
}