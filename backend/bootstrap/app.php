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
use App\Http\Middleware\CheckPlanEligibility; // [ENHANCEMENT] Plan eligibility check
use App\Http\Middleware\ThrottlePublicApi; // [FIX 16 (P3)] Rate limiting for public endpoints
use App\Http\Middleware\EnsureCompanyInvestable; // [PHASE 2] Buying guard for company lifecycle states
use App\Http\Middleware\Protocol1Middleware; // [PROTOCOL-1] Governance enforcement framework

// V-CONTRACT-HARDENING-FINAL: Contract violation exception handling
use App\Exceptions\ContractIntegrityException;
use App\Exceptions\SnapshotImmutabilityViolationException;
use App\Exceptions\OverrideSchemaViolationException;
use App\Exceptions\PaymentAmountMismatchException;
use Illuminate\Support\Facades\Log;

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

            // [ENHANCEMENT]: Plan eligibility middleware for product access control
            'plan.eligible' => CheckPlanEligibility::class,

            // [FIX 16 (P3)]: Rate limiting for public API endpoints
            'throttle.public' => ThrottlePublicApi::class,

            // [PHASE 2]: Company lifecycle state guard for investments
            // Blocks buying when company is not in live_investable or live_fully_disclosed states
            'company.investable' => EnsureCompanyInvestable::class,

            // [PROTOCOL-1]: Governance enforcement framework
            // Validates all actions against Protocol-1 rules before execution
            'protocol1' => Protocol1Middleware::class,

            // Spatie Roles & Permissions
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission_spatie' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // V-CONTRACT-HARDENING-FINAL: Centralized contract violation handling
        // These exceptions MUST NEVER silently degrade to generic 500 errors.
        // All contract violations are logged to the financial_contract channel.

        // ContractIntegrityException: CRITICAL - potential tampering detected
        $exceptions->renderable(function (ContractIntegrityException $e, $request) {
            Log::channel('financial_contract')->critical('CONTRACT INTEGRITY FAILURE', array_merge(
                $e->reportContext(),
                [
                    'message' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'timestamp' => now()->toIso8601String(),
                ]
            ));

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'contract_integrity_failure',
                    'message' => 'Financial contract verification failed. This incident has been logged.',
                    'subscription_id' => $e->getSubscriptionId(),
                ], 500);
            }

            abort(500, 'Financial contract verification failed.');
        });

        // SnapshotImmutabilityViolationException: HIGH - code bug attempting to modify frozen fields
        $exceptions->renderable(function (SnapshotImmutabilityViolationException $e, $request) {
            Log::channel('financial_contract')->error('SNAPSHOT IMMUTABILITY VIOLATION', array_merge(
                $e->reportContext(),
                [
                    'message' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'timestamp' => now()->toIso8601String(),
                ]
            ));

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'snapshot_immutability_violation',
                    'message' => 'Subscription snapshot fields are immutable and cannot be modified.',
                    'violated_fields' => $e->getViolatedFields(),
                ], 500);
            }

            abort(500, 'Subscription snapshot fields are immutable.');
        });

        // OverrideSchemaViolationException: MEDIUM - admin submitted invalid override
        $exceptions->renderable(function (OverrideSchemaViolationException $e, $request) {
            Log::channel('financial_contract')->warning('OVERRIDE SCHEMA VIOLATION', array_merge(
                $e->reportContext(),
                [
                    'message' => $e->getMessage(),
                    'admin_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'request_payload' => $request->except(['password', 'token']),
                    'timestamp' => now()->toIso8601String(),
                ]
            ));

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'override_schema_violation',
                    'message' => $e->getMessage(),
                ], 422);
            }

            abort(422, $e->getMessage());
        });

        // PaymentAmountMismatchException: CRITICAL - webhook amount doesn't match contract
        $exceptions->renderable(function (PaymentAmountMismatchException $e, $request) {
            // Note: Logging already done in PaymentWebhookService before throwing
            // This handler ensures proper HTTP response for webhook endpoints
            Log::channel('financial_contract')->critical('PAYMENT AMOUNT MISMATCH (Handler)', array_merge(
                $e->reportContext(),
                [
                    'message' => $e->getMessage(),
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'timestamp' => now()->toIso8601String(),
                ]
            ));

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'payment_amount_mismatch',
                    'message' => 'Payment amount does not match subscription contract. Payment rejected.',
                    'subscription_id' => $e->getSubscriptionId(),
                    'expected_amount' => $e->getExpectedAmount(),
                    'received_amount' => $e->getWebhookAmount(),
                ], 500);
            }

            abort(500, 'Payment amount mismatch. Contract enforcement active.');
        });
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