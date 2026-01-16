<?php

namespace App\Http\Middleware;

use App\Services\Protocol1\Protocol1Validator;
use App\Services\Protocol1\Protocol1ViolationException;
use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * PROTOCOL-1 HTTP MIDDLEWARE
 *
 * PURPOSE:
 * Request-level enforcement of Protocol-1 governance rules.
 * Intercepts all critical API requests and validates them against
 * the Protocol-1 specification before allowing controller execution.
 *
 * SCOPE:
 * Applied to protected API routes where governance enforcement is required.
 * Can be selectively applied via route middleware groups.
 *
 * ENFORCEMENT FLOW:
 * 1. Extract actor_type, action, and context from request
 * 2. Call Protocol1Validator::validate()
 * 3. If validation fails in strict mode, return 403 Forbidden
 * 4. If validation passes, attach validation result to request
 * 5. Continue to controller
 *
 * USAGE:
 * In routes/api.php:
 * Route::middleware(['auth:sanctum', 'protocol1'])->group(function () {
 *     // Protected routes
 * });
 *
 * Or selectively:
 * Route::post('/issuer/disclosures/{id}/submit')
 *     ->middleware(['auth:sanctum', 'protocol1:issuer,submit_disclosure']);
 */
class Protocol1Middleware
{
    protected Protocol1Validator $validator;

    public function __construct()
    {
        $this->validator = new Protocol1Validator();
    }

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $actorTypeOverride Optional actor type override
     * @param string|null $actionOverride Optional action override
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $actorTypeOverride = null, ?string $actionOverride = null)
    {
        // Extract validation context from request
        $context = $this->buildValidationContext($request, $actorTypeOverride, $actionOverride);

        Log::debug('[PROTOCOL-1 MIDDLEWARE] Validating request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'actor_type' => $context['actor_type'],
            'action' => $context['action'],
            'company_id' => $context['company']?->id,
            'user_id' => $context['user']?->id,
        ]);

        try {
            // Validate request against Protocol-1 rules
            $validationResult = $this->validator->validate($context);

            // Attach validation result to request for downstream use
            $request->attributes->set('protocol1_validation', $validationResult);

            // Log successful validation
            Log::info('[PROTOCOL-1 MIDDLEWARE] Validation passed', [
                'url' => $request->fullUrl(),
                'actor_type' => $context['actor_type'],
                'action' => $context['action'],
                'violations_count' => $validationResult['violations']['total'],
                'validation_duration_ms' => $validationResult['validation_duration_ms'],
            ]);

            // Continue to controller
            return $next($request);

        } catch (Protocol1ViolationException $e) {
            // Validation failed - block request
            $validationResult = $e->getValidationResult();

            Log::warning('[PROTOCOL-1 MIDDLEWARE] Request blocked', [
                'url' => $request->fullUrl(),
                'actor_type' => $context['actor_type'],
                'action' => $context['action'],
                'block_reason' => $validationResult['block_reason'],
                'critical_count' => count($validationResult['violations']['critical']),
                'high_count' => count($validationResult['violations']['high']),
            ]);

            // Return 403 Forbidden with violation details
            return response()->json([
                'status' => 'error',
                'message' => 'Protocol-1 Governance Violation',
                'error' => $validationResult['block_reason'],
                'protocol_version' => $validationResult['protocol_version'],
                'enforcement_mode' => $validationResult['enforcement_mode'],
                'violations' => [
                    'critical' => $this->formatViolationsForResponse($validationResult['violations']['critical']),
                    'high' => $this->formatViolationsForResponse($validationResult['violations']['high']),
                ],
                'support_message' => 'This action violates platform governance rules. Please contact support if you believe this is an error.',
            ], Response::HTTP_FORBIDDEN);

        } catch (\Exception $e) {
            // Unexpected error during validation
            Log::error('[PROTOCOL-1 MIDDLEWARE] Validation error', [
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // In production, fail open (allow request) to avoid blocking legitimate traffic
            if (app()->environment('production')) {
                Log::alert('[PROTOCOL-1 MIDDLEWARE] Failing open due to validation error');
                return $next($request);
            }

            // In development/staging, return error
            return response()->json([
                'status' => 'error',
                'message' => 'Protocol-1 validation system error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Build validation context from request
     *
     * Extracts actor_type, action, company, user, and data from request
     *
     * @param Request $request HTTP request
     * @param string|null $actorTypeOverride Manual actor type
     * @param string|null $actionOverride Manual action
     * @return array Validation context
     */
    protected function buildValidationContext(Request $request, ?string $actorTypeOverride = null, ?string $actionOverride = null): array
    {
        // Get authenticated user
        $user = $request->user();

        // Determine actor type
        $actorType = $actorTypeOverride ?? $this->inferActorType($request, $user);

        // Determine action
        $action = $actionOverride ?? $this->inferAction($request);

        // Extract company if present in route parameters
        $company = $this->extractCompany($request);

        // Extract request data
        $data = $request->all();

        // Add actor_type to data if not present
        if (!isset($data['actor_type'])) {
            $data['actor_type'] = $actorType;
        }

        return [
            'actor_type' => $actorType,
            'action' => $action,
            'company' => $company,
            'user' => $user,
            'data' => $data,
            'request' => $request,
        ];
    }

    /**
     * Infer actor type from request
     *
     * @param Request $request
     * @param User|null $user
     * @return string Actor type
     */
    protected function inferActorType(Request $request, ?User $user): string
    {
        // Check if explicitly provided in request
        if ($request->has('actor_type')) {
            return $request->input('actor_type');
        }

        // Check URL path to determine actor type
        $path = $request->path();

        if (str_starts_with($path, 'api/admin/')) {
            return 'admin_judgment';
        }

        if (str_starts_with($path, 'api/issuer/') || str_starts_with($path, 'api/company/')) {
            return 'issuer';
        }

        if (str_starts_with($path, 'api/investor/') || str_starts_with($path, 'api/user/')) {
            return 'investor';
        }

        // Default to system if no user
        if (!$user) {
            return 'system_enforcement';
        }

        // Check user role
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return 'admin_judgment';
        }

        if ($user->hasRole('company_user') || $user->hasRole('issuer')) {
            return 'issuer';
        }

        // Default to investor
        return 'investor';
    }

    /**
     * Infer action from request
     *
     * @param Request $request
     * @return string Action identifier
     */
    protected function inferAction(Request $request): string
    {
        // Check if explicitly provided in request
        if ($request->has('action')) {
            return $request->input('action');
        }

        $method = $request->method();
        $path = $request->path();

        // Map common URL patterns to actions
        if (preg_match('/disclosures\/\d+\/submit/', $path)) {
            return 'submit_disclosure';
        }

        if (preg_match('/disclosures\/\d+\/edit/', $path) || ($method === 'PUT' && str_contains($path, 'disclosures'))) {
            return 'edit_disclosure';
        }

        if (preg_match('/disclosures\/\d+\/approve/', $path)) {
            return 'approve_disclosure';
        }

        if (preg_match('/clarifications\/\d+\/answer/', $path)) {
            return 'answer_clarification';
        }

        if (preg_match('/companies\/\d+\/suspend/', $path)) {
            return 'suspend_company';
        }

        if (preg_match('/companies\/\d+\/visibility/', $path)) {
            return 'change_visibility';
        }

        if (preg_match('/companies\/\d+\/platform-context/', $path)) {
            return 'update_platform_context';
        }

        if (preg_match('/investments/', $path) && $method === 'POST') {
            return 'create_investment';
        }

        if (preg_match('/wallet\/allocate/', $path)) {
            return 'allocate_wallet';
        }

        // Generic action based on HTTP method
        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            'GET' => 'read',
            default => 'unknown',
        };
    }

    /**
     * Extract company from request
     *
     * @param Request $request
     * @return Company|null
     */
    protected function extractCompany(Request $request): ?Company
    {
        // Check route parameters
        if ($request->route('id') && $request->route()->hasParameter('id')) {
            $companyId = $request->route('id');
            return Company::find($companyId);
        }

        if ($request->route('company_id') && $request->route()->hasParameter('company_id')) {
            $companyId = $request->route('company_id');
            return Company::find($companyId);
        }

        // Check request data
        if ($request->has('company_id')) {
            return Company::find($request->input('company_id'));
        }

        // Check for company slug
        if ($request->route('slug') && $request->route()->hasParameter('slug')) {
            $slug = $request->route('slug');
            return Company::where('slug', $slug)->first();
        }

        // If issuer user, get their company
        $user = $request->user();
        if ($user && $user->company_id) {
            return Company::find($user->company_id);
        }

        return null;
    }

    /**
     * Format violations for API response
     *
     * Remove sensitive internal details, keep user-facing messages
     *
     * @param array $violations
     * @return array Formatted violations
     */
    protected function formatViolationsForResponse(array $violations): array
    {
        return array_map(function ($violation) {
            return [
                'rule_name' => $violation['rule_name'] ?? 'Unknown Rule',
                'message' => $violation['message'] ?? 'Violation detected',
                'severity' => $violation['severity'] ?? 'UNKNOWN',
            ];
        }, $violations);
    }
}
