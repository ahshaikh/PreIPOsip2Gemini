<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseOptimizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $table;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @param string $table The name of the table to optimize/repair
     * @param string $action 'optimize' or 'repair'
     */
    public function __construct($table, $action)
    {
        $this->table = $table;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tableName = str_replace(['`', ';'], '', $this->table); // Basic sanitization

        try {
            if ($this->action === 'optimize') {
                // OPTIMIZE TABLE can lock tables for writes
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                Log::info("Database Optimization: Table {$tableName} optimized successfully.");
            } elseif ($this->action === 'repair') {
                DB::statement("REPAIR TABLE `{$tableName}`");
                Log::info("Database Optimization: Table {$tableName} repaired successfully.");
            }
        } catch (\Exception $e) {
            Log::error("Database Optimization Failed for table {$tableName}: " . $e->getMessage());
            // Optionally notify admin via notification system here
        }
    }
}