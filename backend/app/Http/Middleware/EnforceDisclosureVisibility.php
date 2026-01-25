<?php

namespace App\Http\Middleware;

use App\Services\DisclosureVisibilityGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P0 FIX (GAP 25-27): Enforce Disclosure Visibility Middleware
 *
 * Automatically validates disclosure visibility for investor-facing routes.
 *
 * Apply to routes that serve disclosure content:
 *   Route::get('/disclosures/{id}', ...)->middleware('disclosure.visibility');
 *
 * This middleware:
 * 1. Extracts disclosure ID from route
 * 2. Validates current user can view it
 * 3. Blocks access with appropriate error if not visible
 * 4. Logs violation attempts for security audit
 */
class EnforceDisclosureVisibility
{
    protected DisclosureVisibilityGuard $visibilityGuard;

    public function __construct(DisclosureVisibilityGuard $visibilityGuard)
    {
        $this->visibilityGuard = $visibilityGuard;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $paramName Route parameter name containing disclosure ID
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $paramName = 'disclosure'): Response
    {
        $disclosureId = $request->route($paramName);

        if (!$disclosureId) {
            // No disclosure in route, continue
            return $next($request);
        }

        $user = $request->user();
        $result = $this->visibilityGuard->canInvestorViewDisclosure((int) $disclosureId, $user);

        if (!$result['visible']) {
            // Log the attempt
            $this->visibilityGuard->logViolationAttempt(
                attemptType: 'middleware_block',
                userId: $user?->id,
                disclosureId: (int) $disclosureId,
                reason: $result['reason']
            );

            // Return appropriate error based on reason
            if ($result['reason'] === 'Authentication required') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please log in to view this content',
                    'code' => 'AUTHENTICATION_REQUIRED',
                ], 401);
            }

            if (str_contains($result['reason'], 'only available to investors')) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['reason'],
                    'code' => 'SUBSCRIBER_ONLY',
                ], 403);
            }

            // Generic "not found" for security (don't reveal existence of drafts)
            return response()->json([
                'status' => 'error',
                'message' => 'Disclosure not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        // Add disclosure to request for controller use
        $request->attributes->set('validated_disclosure', $result['disclosure']);

        return $next($request);
    }
}
