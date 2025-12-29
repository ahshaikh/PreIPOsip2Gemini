<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any invalid tasks first
        DB::table('scheduled_tasks')->truncate();

        // Get first user (admin or system user)
        $adminId = DB::table('users')->orderBy('id')->value('id') ?? 1;

        // Seed valid tasks from actual commands that exist
        $validTasks = [
            [
                'name' => 'Database Backup',
                'command' => 'backup:database',
                'expression' => '0 2 * * *', // 2 AM daily
                'description' => 'Creates automatic database backup',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'System Health Monitor',
                'command' => 'system:health',
                'expression' => '*/15 * * * *', // Every 15 minutes
                'description' => 'Monitors system health and logs metrics',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Process Monthly Lucky Draw',
                'command' => 'luckydraw:process',
                'expression' => '0 0 1 * *', // 1st of every month
                'description' => 'Processes monthly lucky draw winners',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Process Auto Debits',
                'command' => 'auto-debit:process',
                'expression' => '0 3 * * *', // 3 AM daily
                'description' => 'Processes automatic SIP debits',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Generate Sitemap',
                'command' => 'sitemap:generate',
                'expression' => '0 4 * * 0', // 4 AM every Sunday
                'description' => 'Generates XML sitemap for SEO',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Wallet Audit',
                'command' => 'wallet:audit',
                'expression' => '0 5 * * *', // 5 AM daily
                'description' => 'Audits wallet balances and transactions',
                'is_active' => true,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Reconcile Ledgers',
                'command' => 'ledger:reconcile',
                'expression' => '0 6 * * *', // 6 AM daily
                'description' => 'Reconciles financial ledgers',
                'is_active' => true,
                'created_by' => $adminId,
            ],
        ];

        foreach ($validTasks as $task) {
            DB::table('scheduled_tasks')->insert(array_merge($task, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('scheduled_tasks')->truncate();
    }
};
