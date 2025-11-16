<?php
// V-FINAL-1730-269 (No CORS) | V-FINAL-1730-420 (Permission Add) | V-FINAL-1730-438 | V-FINAL-1730-447 IP Whitelist | V-FINAL-1730-533 (Redirects Added) | V-FINAL-1730-562 (Legal Middleware)

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\HttpHttp\Middleware\AdminIpRestriction;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RedirectMiddleware;
use App\Http\Middleware\EnsureLegalAcceptance; // <-- 1. IMPORT

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->append([
            CheckMaintenanceMode::class,
            RedirectMiddleware::class,
        ]);

        $middleware->alias([
            'admin.ip' => AdminIpRestriction::class,
            'permission' => CheckPermission::class,
            'legal.accept' => EnsureLegalAcceptance::class, // <-- 2. ADD ALIAS
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();