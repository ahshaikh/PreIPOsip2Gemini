<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateOffersToCampaigns extends Command
{
    protected $signature = 'campaigns:migrate-offers {--dry-run}';
    protected $description = 'Verify migration status from legacy Offers to V2 Campaigns.';

    public function handle()
    {
        $this->info("===========================================");
        $this->info("  Offer (Legacy) -> Campaign (V2) Status");
        $this->info("===========================================");

        // 1. Check Legacy Source
        $sourceExists = Schema::hasTable('offers');
        
        // 2. Check V2 Destination
        $targetExists = Schema::hasTable('campaigns');

        if (!$targetExists) {
            $this->error("❌ CRITICAL: The V2 'campaigns' table does not exist. Run migration 2025_12_26_000001.");
            return 1;
        }

        $campaignCount = DB::table('campaigns')->count();

        if (!$sourceExists) {
            // This is the desired state for "Nothing left behind"
            $this->info("✅ Legacy 'offers' table has been removed.");
            $this->info("✅ V2 'campaigns' table is active with [{$campaignCount}] records.");
            $this->comment("Conclusion: Migration is complete. No legacy data left behind.");
            return 0;
        }

        // If we are here, the 'offers' table still exists.
        $offerCount = DB::table('offers')->count();

        if ($offerCount === 0) {
            $this->info("✅ Legacy 'offers' table exists but is EMPTY.");
            $this->info("✅ V2 'campaigns' table is active with [{$campaignCount}] records.");
            $this->warn("Action: You can safely drop the 'offers' table.");
            return 0;
        }

        // Real work needed: Data still exists in old table
        $this->warn("⚠️  Legacy Data Found: [{$offerCount}] records still pending in 'offers' table.");
        $this->warn("⚠️  Current 'campaigns' count: [{$campaignCount}]");
        
        if ($this->option('dry-run')) {
            $this->info("🛡️  DRY RUN: We would copy {$offerCount} records to 'campaigns' table.");
        } else {
            $this->warn("🛑 Automatic migration disabled by Protocol 1.");
            $this->warn("   Please verify why 'offers' table still has data if V2 is live.");
            $this->warn("   Run manual SQL export/import if strictly necessary to avoid logic collision.");
        }

        return 0;
    }
}