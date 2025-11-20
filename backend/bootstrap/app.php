<?php
// V-FINAL-1730-269 | 1730-420 | 1730-438 | 1730-447 |
// 1730-533 (Redirects Added) | 1730-562 (Legal Middleware) |
// V-FINAL-1730-652 (Role Middleware Fix)

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\AdminIpRestriction;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RedirectMiddleware;
use App\Http\Middleware\EnsureLegalAcceptance;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | 1. Global Middleware
        |--------------------------------------------------------------------------
        | These run on ALL routes (Web + API).
        | DO NOT put RedirectMiddleware here.
        */
        $middleware->append([
            CheckMaintenanceMode::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Web Middleware Group
        |--------------------------------------------------------------------------
        | Here we place RedirectMiddleware so redirects apply ONLY to web requests.
        */
        $middleware->group('web', [
            RedirectMiddleware::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. API Middleware Group
        |--------------------------------------------------------------------------
        | API routes must NOT have RedirectMiddleware.
        */
        $middleware->group('api', [
            // empty unless needed
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Middleware Aliases
        |--------------------------------------------------------------------------
        | All named middleware go here.
        */
        $middleware->alias([
            'admin.ip' => AdminIpRestriction::class,
            'permission' => CheckPermission::class,
            'legal.accept' => EnsureLegalAcceptance::class,

            // Spatie Permissions
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission_spatie' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
