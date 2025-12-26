<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateOffersToCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:migrate-offers
                            {--dry-run : Run migration in dry-run mode without making changes}
                            {--force : Force migration even if campaigns table already has data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from old offers table to new campaigns table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('===========================================');
        $this->info('  Offer → Campaign Migration Tool');
        $this->info('===========================================');
        $this->newLine();

        // Check if offers table exists
        if (!Schema::hasTable('offers')) {
            $this->error('❌ Offers table does not exist. Nothing to migrate.');
            return 1;
        }

        // Check if campaigns table exists
        if (!Schema::hasTable('campaigns')) {
            $this->error('❌ Campaigns table does not exist. Please run migrations first.');
            $this->info('   Run: php artisan migrate');
            return 1;
        }

        // Check if campaigns table already has data
        $existingCampaigns = DB::table('campaigns')->count();
        if ($existingCampaigns > 0 && !$isForce) {
            $this->warn("⚠️  Campaigns table already has {$existingCampaigns} record(s).");
            $this->warn('   Use --force to migrate anyway, or delete existing campaigns first.');
            return 1;
        }

        // Get offers to migrate
        $offers = DB::table('offers')->get();

        if ($offers->isEmpty()) {
            $this->info('✅ No offers found to migrate.');
            return 0;
        }

        $this->info("Found {$offers->count()} offer(s) to migrate");
        $this->newLine();

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $bar = $this->output->createProgressBar($offers->count());
        $bar->start();

        $migrated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($offers as $offer) {
            try {
                // Check if campaign with same code already exists
                $existing = DB::table('campaigns')->where('code', $offer->code)->first();
                if ($existing) {
                    $skipped++;
                    $this->newLine();
                    $this->warn("   ⏭️  Skipped: {$offer->code} (already exists)");
                    $bar->advance();
                    continue;
                }

                // Map offer data to campaign structure
                $campaignData = [
                    'title' => $offer->title ?? $offer->code,
                    'subtitle' => $offer->subtitle,
                    'code' => $offer->code,
                    'description' => $offer->description ?? '',
                    'long_description' => $offer->long_description,
                    'discount_type' => $offer->discount_type ?? 'fixed_amount',
                    'discount_percent' => $offer->discount_percent,
                    'discount_amount' => $offer->discount_amount,
                    'min_investment' => $offer->min_investment,
                    'max_discount' => $offer->max_discount,
                    'usage_limit' => $offer->usage_limit,
                    'usage_count' => $offer->usage_count ?? 0,
                    'user_usage_limit' => $offer->user_usage_limit,
                    'start_at' => now(), // Set to now since old offers didn't have start_at
                    'end_at' => $offer->expiry,
                    'image_url' => $offer->image_url,
                    'hero_image' => $offer->hero_image,
                    'video_url' => $offer->video_url,
                    'features' => $offer->features,
                    'terms' => $offer->terms,
                    'is_featured' => $offer->is_featured ?? false,
                    'is_active' => $offer->status === 'active',
                    'is_archived' => $offer->status === 'expired',
                    'created_by' => null, // Will need to be set manually
                    'approved_by' => null, // Will need to be set manually
                    'approved_at' => $offer->status === 'active' ? now() : null, // Auto-approve active offers
                    'created_at' => $offer->created_at ?? now(),
                    'updated_at' => $offer->updated_at ?? now(),
                ];

                if (!$isDryRun) {
                    DB::table('campaigns')->insert($campaignData);
                }

                $migrated++;
                $bar->advance();

            } catch (\Exception $e) {
                $errors[] = "Failed to migrate {$offer->code}: {$e->getMessage()}";
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('===========================================');
        $this->info('  Migration Summary');
        $this->info('===========================================');
        $this->info("✅ Migrated: {$migrated}");
        $this->info("⏭️  Skipped: {$skipped}");
        $this->info("❌ Errors: " . count($errors));

        if (!empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('🔍 This was a DRY RUN - no changes were made');
            $this->info('   Run without --dry-run to perform actual migration');
        } else {
            $this->newLine();
            $this->info('✅ Migration completed successfully!');
            $this->newLine();
            $this->info('Next steps:');
            $this->info('  1. Review migrated campaigns in admin panel');
            $this->info('  2. Set created_by and approved_by for migrated campaigns');
            $this->info('  3. Verify campaign configurations');
            $this->info('  4. Archive the old offers table (DO NOT DELETE immediately)');
        }

        return 0;
    }
}
