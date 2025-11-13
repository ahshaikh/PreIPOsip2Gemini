<?php
// V-FINAL-1730-269 (FIXED - No CORS method)

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminIpRestriction;
use App\Http\Middleware\CheckMaintenanceMode;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // --- SECURITY MIDDLEWARE ---
        
        // 1. Global Maintenance Check (Runs on every request)
        $middleware->append(CheckMaintenanceMode::class);

        // 2. Admin IP Check (Aliased for use in routes)
        $middleware->alias([
            'admin.ip' => AdminIpRestriction::class,
        ]);
        
        // NOTE: CORS is handled automatically by Laravel reading 'config/cors.php'.
        // Do NOT add $middleware->cors() here.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();