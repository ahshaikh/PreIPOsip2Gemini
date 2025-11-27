<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// SCHEDULED TASKS
// ============================================

// Daily database backup at 2:00 AM
Schedule::command('backup:database --compress --storage=local')
    ->dailyAt('02:00')
    ->timezone(config('app.timezone', 'UTC'))
    ->onSuccess(function () {
        \Log::info('Daily database backup completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Daily database backup failed');
    });
