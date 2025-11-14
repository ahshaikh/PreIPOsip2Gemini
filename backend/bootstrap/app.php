<?php
// V-FINAL-1730-269 (No CORS) | V-FINAL-1730-420 (Permission Added) | V-FINAL-1730-438 (Corrected) | V-FINAL-1730-447 (IP Whitelist Added)

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminIpRestriction; // <-- 1. IMPORT
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckPermission;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        $middleware->append(CheckMaintenanceMode::class);

        $middleware->alias([
            'admin.ip' => AdminIpRestriction::class, // <-- 2. ADD ALIAS
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();