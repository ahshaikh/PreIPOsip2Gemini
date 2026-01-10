<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\CompanyLifecycleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHASE 2 - MIDDLEWARE: EnsureCompanyInvestable
 *
 * PURPOSE:
 * HTTP-level middleware to block investment routes for non-investable companies.
 * Provides defense-in-depth alongside InvestmentPolicy.
 *
 * USAGE:
 * Apply to routes that involve investment transactions:
 * Route::post('/invest/{company}', [InvestmentController::class, 'invest'])
 *     ->middleware('ensure.company.investable');
 *
 * CRITICAL:
 * - This is a HARD BLOCK at HTTP layer
 * - Works even if policy checks are bypassed
 * - Returns 403 Forbidden with clear error message
 * - Logs all blocked attempts for fraud detection
 */
class EnsureCompanyInvestable
{
    protected CompanyLifecycleService $lifecycleService;

    public function __construct(CompanyLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract company from route parameter
        $company = $this->resolveCompany($request);

        if (!$company) {
            \Log::error('EnsureCompanyInvestable middleware: Company not found in route', [
                'route' => $request->route()->getName(),
                'params' => $request->route()->parameters(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Company not found',
            ], 404);
        }

        // GUARD: Check if company can accept investments
        if (!$this->lifecycleService->canAcceptInvestments($company)) {
            $reason = $this->lifecycleService->getBuyingBlockedReason($company);

            \Log::warning('Investment route blocked by middleware', [
                'user_id' => auth()->id(),
                'company_id' => $company->id,
                'lifecycle_state' => $company->lifecycle_state,
                'buying_enabled' => $company->buying_enabled,
                'route' => $request->route()->getName(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Investment not allowed',
                'reason' => $reason,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'lifecycle_state' => $company->lifecycle_state,
                    'buying_enabled' => $company->buying_enabled,
                ],
                'help' => $this->getHelpMessage($company),
            ], 403);
        }

        // Company is investable - proceed
        return $next($request);
    }

    /**
     * Resolve company from request
     *
     * @param Request $request
     * @return Company|null
     */
    protected function resolveCompany(Request $request): ?Company
    {
        // Try route parameter 'company' (route model binding)
        if ($request->route('company') instanceof Company) {
            return $request->route('company');
        }

        // Try route parameter 'company_id'
        if ($companyId = $request->route('company_id')) {
            return Company::find($companyId);
        }

        // Try request input 'company_id'
        if ($companyId = $request->input('company_id')) {
            return Company::find($companyId);
        }

        // Try route parameter 'id' (for generic resource routes)
        if ($id = $request->route('id')) {
            return Company::find($id);
        }

        return null;
    }

    /**
     * Get helpful message for investor
     *
     * @param Company $company
     * @return string
     */
    protected function getHelpMessage(Company $company): string
    {
        return match ($company->lifecycle_state) {
            'draft' => 'This company is still preparing their profile. Check back soon!',
            'live_limited' => 'This company has completed basic disclosures but has not yet completed financial disclosures required for investment. They are working on it!',
            'suspended' => 'Investment in this company is temporarily suspended. Reason: ' . $company->suspension_reason,
            default => 'This company is not currently accepting investments. Please contact support if you believe this is an error.',
        };
    }
}
