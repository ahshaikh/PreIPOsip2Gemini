<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * V-AUDIT-MODULE19-MEDIUM: Pruning Policy Required
 * 
 * PROBLEM: This table grows indefinitely as health checks run every 30-60 seconds.
 * Without pruning, the table will accumulate millions of records over months, causing:
 * - Database bloat (GB of storage for diagnostic logs)
 * - Slow queries on dashboard (pagination over millions of rows)
 * - Backup size explosion (database dumps grow unnecessarily)
 * - Index fragmentation (slower inserts over time)
 * 
 * RECOMMENDED SOLUTION: Implement Laravel Model Pruning to auto-delete old records.
 * 
 * IMPLEMENTATION GUIDE:
 * 1. Add Prunable trait to this model:
 *    use Illuminate\Database\Eloquent\Prunable;
 *    class SystemHealthCheck extends Model
 *    {
 *        use HasFactory, Prunable;
 * 
 * 2. Define prunable query (keep last 7-30 days):
 *    public function prunable()
 *    {
 *        return static::where('checked_at', '<=', now()->subDays(30));
 *    }
 * 
 * 3. Schedule in app/Console/Kernel.php:
 *    protected function schedule(Schedule $schedule)
 *    {
 *        $schedule->command('model:prune', ['--model' => SystemHealthCheck::class])
 *                 ->daily();
 *    }
 * 
 * 4. Run manually to test:
 *    php artisan model:prune --model="App\Models\SystemHealthCheck"
 * 
 * RETENTION POLICY RECOMMENDATION:
 * - Keep 7 days for troubleshooting recent issues
 * - Keep 30 days for trend analysis (optional, configurable via setting('health_check_retention_days'))
 * - Delete anything older to prevent table bloat
 * 
 * IMPACT: Without pruning, this table will grow by ~1M records/month at 30-second check intervals.
 *
 * @mixin IdeHelperSystemHealthCheck
 */
class SystemHealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_name',
        'status',
        'message',
        'details',
        'response_time',
        'checked_at',
    ];

    protected $casts = [
        'details' => 'array',
        'response_time' => 'integer',
        'checked_at' => 'datetime',
    ];
}
