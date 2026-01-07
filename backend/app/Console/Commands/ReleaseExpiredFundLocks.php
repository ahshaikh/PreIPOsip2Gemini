<?php

namespace App\Console\Commands;

use App\Models\FundLock;
use Illuminate\Console\Command;

/**
 * FIX 18: Release Expired Fund Locks Command
 *
 * Automatically releases fund locks that have expired
 * Should be scheduled to run hourly
 *
 * Usage: php artisan locks:release-expired
 */
class ReleaseExpiredFundLocks extends Command
{
    protected $signature = 'locks:release-expired';

    protected $description = 'Release expired fund locks';

    public function handle()
    {
        $this->info('Checking for expired fund locks...');

        $count = FundLock::releaseExpiredLocks();

        if ($count > 0) {
            $this->info("✓ Released {$count} expired fund locks");
        } else {
            $this->info('No expired locks found');
        }

        return 0;
    }
}
