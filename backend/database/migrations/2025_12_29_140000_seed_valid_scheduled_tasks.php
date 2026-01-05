<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // FK-safe cleanup
        DB::table('scheduled_tasks')->delete();

        // Resolve a real user ID
        $adminId = DB::table('users')->orderBy('id')->value('id');

        if (!$adminId) {
            // No users exist yet — cannot assign ownership
            return;
        }

        $validTasks = [
            [
                'name' => 'Database Backup',
                'command' => 'backup:database',
                'expression' => '0 2 * * *',
                'description' => 'Creates automatic database backup',
                'is_active' => false,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Generate Sitemap',
                'command' => 'sitemap:generate',
                'expression' => '0 4 * * 0',
                'description' => 'Generates XML sitemap for SEO',
                'is_active' => false,
                'created_by' => $adminId,
            ],
            [
                'name' => 'Aggregate Alerts',
                'command' => 'alerts:aggregate',
                'expression' => '0 * * * *',
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
        DB::table('scheduled_tasks')->delete();
    }
};
