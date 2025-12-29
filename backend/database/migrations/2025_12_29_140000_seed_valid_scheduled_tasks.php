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

        // Seed ONLY commands verified to:
        // 1. Exist (checked Console/Commands signatures)
        // 2. Not require missing DB columns
        // 3. Be safe for automated execution
        $validTasks = [
            [
                'name' => 'Database Backup',
                'command' => 'backup:database',
                'expression' => '0 2 * * *', // 2 AM daily
                'description' => 'Creates automatic database backup',
                'is_active' => false, // Disabled - enable manually after testing
                'created_by' => $adminId,
            ],
            [
                'name' => 'Generate Sitemap',
                'command' => 'sitemap:generate',
                'expression' => '0 4 * * 0', // 4 AM Sunday
                'description' => 'Generates XML sitemap for SEO',
                'is_active' => false,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Aggregate Alerts',
                'command' => 'alerts:aggregate',
                'expression' => '0 * * * *', // Hourly
                'description' => 'Aggregates system alerts',
                'is_active' => false,
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
