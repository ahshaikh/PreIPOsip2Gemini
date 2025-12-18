<?php
// V-FINAL-1730-269 | 1730-420 | 1730-438 | 1730-447 |
// 1730-533 (Redirects Added) | 1730-562 (Legal Middleware) |
// V-FINAL-1730-652 (Role Middleware Fix) | V-AUDIT-FIX-MFA-REGISTRATION

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\AdminIpRestriction;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RedirectMiddleware;
use App\Http\Middleware\EnsureLegalAcceptance;
use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\VerifyWebhookSignature;
use App\Http\Middleware\ConcurrentSessionControl;
use App\Http\Middleware\ValidateFileUpload;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\EnsureMfaVerified; // [AUDIT FIX] Import MFA Middleware

// ----------------------------------------------------------
// 1. Build the application instance
// ----------------------------------------------------------
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Global middleware
        $middleware->append([
            TrustProxies::class,
            ForceHttps::class,
            SanitizeInput::class,
            CheckMaintenanceMode::class,
            RedirectMiddleware::class,
        ]);

        // [AUDIT FIX]: Ensure API guests get a 401 instead of a redirect
        $middleware->redirectGuestsTo(function ($request) {
            return $request->expectsJson() || $request->is('api/*') ? null : '/login';
        });

        /**
         * Middleware Aliases
         * These keys are used in routes/api.php to apply logic to specific groups.
         */
        $middleware->alias([
            'admin.ip' => AdminIpRestriction::class,
            'permission' => CheckPermission::class,
            'legal.accept' => EnsureLegalAcceptance::class,
            'webhook.verify' => VerifyWebhookSignature::class,
            'session.control' => ConcurrentSessionControl::class,
            'file.validate' => ValidateFileUpload::class,
            
            // [AUDIT FIX]: Register the MFA Gating middleware
            // Use this in routes/api.php to protect high-risk transactions.
            'mfa.verified' => EnsureMfaVerified::class,

            // Spatie Roles & Permissions
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission_spatie' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Global exception handling logic...
    })
    ->create();

// ----------------------------------------------------------
// 2. Load helper files globally
// ----------------------------------------------------------
require_once __DIR__ . '/../app/Helpers/SettingsHelper.php';

// ----------------------------------------------------------
// 3. Return the final application instance
// ----------------------------------------------------------
return $app;