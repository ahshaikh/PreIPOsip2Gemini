<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformCompanyMetric;
use App\Models\PlatformRiskFlag;
use App\Models\PlatformValuationContext;
use App\Services\ChangeTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 4 - CONTROLLER: PlatformContextController
 *
 * PURPOSE:
 * Expose platform-generated analysis and context to investors.
 *
 * ROUTES (PUBLIC):
 * GET /api/v1/companies/{id}/platform-context     - All platform analysis
 * GET /api/v1/companies/{id}/metrics              - Company health metrics
 * GET /api/v1/companies/{id}/risk-flags           - Risk detection flags
 * GET /api/v1/companies/{id}/valuation-context    - Peer comparison data
 *
 * ROUTES (AUTHENTICATED):
 * GET /api/v1/companies/{id}/whats-new            - Changes since last visit
 *
 * CRITICAL REGULATORY SAFEGUARDS:
 * - All responses clearly labeled as "platform-generated"
 * - Methodology transparency in every response
 * - Disclaimers about non-advisory nature
 * - Clear separation from company data
 */
class PlatformContextController extends Controller
{
    protected ChangeTrackingService $changeTrackingService;

    public function __construct(ChangeTrackingService $changeTrackingService)
    {
        $this->changeTrackingService = $changeTrackingService;
    }

    /**
     * Get complete platform context for a company
     *
     * GET /api/v1/companies/{id}/platform-context
     *
     * Returns:
     * - Health metrics (completeness, financial, governance, risk)
     * - Risk flags (automated detection)
     * - Valuation context (peer comparison)
     * - Data freshness indicators
     *
     * REGULATORY NOTE: Response clearly separates platform analysis from company data
     */
    public function getCompanyContext(int $id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);

            // Get all platform-generated data
            $metrics = PlatformCompanyMetric::where('company_id', $id)->first();
            $riskFlags = PlatformRiskFlag::visibleToInvestors()
                ->where('company_id', $id)
                ->get();
            $valuationContext = PlatformValuationContext::where('company_id', $id)
                ->current()
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $id,
                    'company_name' => $company->name,

                    // CLEARLY LABELED AS PLATFORM-GENERATED
                    'platform_analysis' => [
                        'health_metrics' => $metrics?->getInvestorSummary(),
                        'risk_flags' => [
                            'total_flags' => $riskFlags->count(),
                            'high_severity_count' => $riskFlags->where('severity', 'high')->count() +
                                                     $riskFlags->where('severity', 'critical')->count(),
                            'flags' => $riskFlags->map(fn($flag) => $flag->getInvestorSummary()),
                        ],
                        'valuation_context' => $valuationContext?->getInvestorSummary(),
                    ],

                    // Data freshness indicators
                    'data_quality' => [
                        'metrics_last_updated' => $metrics?->last_platform_review,
                        'valuation_last_updated' => $valuationContext?->calculated_at,
                        'is_under_admin_review' => $metrics?->is_under_admin_review ?? false,
                    ],

                    // CRITICAL: Disclaimer on EVERY response
                    'disclaimer' => $this->getStandardDisclaimer(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch platform context', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch platform analysis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get health metrics only
     *
     * GET /api/v1/companies/{id}/metrics
     */
    public function getMetrics(int $id): JsonResponse
    {
        try {
            $metrics = PlatformCompanyMetric::where('company_id', $id)->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $metrics->getInvestorSummary(),
                'disclaimer' => $this->getStandardDisclaimer(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Metrics not available for this company',
            ], 404);
        }
    }

    /**
     * Get risk flags only
     *
     * GET /api/v1/companies/{id}/risk-flags
     */
    public function getRiskFlags(int $id): JsonResponse
    {
        try {
            $flags = PlatformRiskFlag::visibleToInvestors()
                ->where('company_id', $id)
                ->get();

            // Group by category
            $groupedFlags = $flags->groupBy('category')->map(function ($categoryFlags, $category) {
                return [
                    'category' => $category,
                    'display_name' => $categoryFlags->first()->getCategoryDisplayName(),
                    'count' => $categoryFlags->count(),
                    'highest_severity' => $this->getHighestSeverity($categoryFlags),
                    'flags' => $categoryFlags->map(fn($flag) => $flag->getInvestorSummary()),
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_flags' => $flags->count(),
                    'by_category' => $groupedFlags,
                    'by_severity' => [
                        'critical' => $flags->where('severity', 'critical')->count(),
                        'high' => $flags->where('severity', 'high')->count(),
                        'medium' => $flags->where('severity', 'medium')->count(),
                        'low' => $flags->where('severity', 'low')->count(),
                    ],
                ],
                'disclaimer' => $this->getStandardDisclaimer(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch risk flags',
            ], 500);
        }
    }

    /**
     * Get valuation context only
     *
     * GET /api/v1/companies/{id}/valuation-context
     */
    public function getValuationContext(int $id): JsonResponse
    {
        try {
            $context = PlatformValuationContext::where('company_id', $id)
                ->current()
                ->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => $context->getInvestorSummary(),
                'disclaimer' => $this->getStandardDisclaimer(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Valuation context not available for this company',
            ], 404);
        }
    }

    /**
     * Get "what's new" since investor's last visit
     *
     * GET /api/v1/companies/{id}/whats-new
     *
     * REQUIRES: Authentication
     */
    public function getWhatsNew(int $id): JsonResponse
    {
        try {
            $investor = auth()->user();

            if (!$investor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Authentication required',
                ], 401);
            }

            $company = Company::findOrFail($id);

            // Get changes since last visit
            $changesData = $this->changeTrackingService->getChangesSinceLastVisit($investor, $company);

            // Record this visit
            $currentSnapshot = $this->getCurrentDataSnapshot($company);
            $this->changeTrackingService->recordInvestorView(
                $investor,
                $company,
                'platform_context',
                $currentSnapshot
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'is_first_visit' => $changesData['first_visit'],
                    'last_visit_at' => $changesData['last_visit_at'] ?? null,
                    'changes_since_last_visit' => $changesData['changes'] ?? [],
                    'changes_count' => $changesData['changes_count'] ?? 0,
                    'current_snapshot' => $currentSnapshot,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch what\'s new', [
                'company_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch updates',
            ], 500);
        }
    }

    /**
     * Get current data snapshot for change tracking
     */
    private function getCurrentDataSnapshot(Company $company): array
    {
        $metrics = PlatformCompanyMetric::where('company_id', $company->id)->first();
        $flags = PlatformRiskFlag::visibleToInvestors()->where('company_id', $company->id)->get();

        return [
            'disclosures' => $company->disclosures()->pluck('id', 'disclosure_module_id')->toArray(),
            'metrics' => $metrics ? [
                'completeness' => $metrics->disclosure_completeness_score,
                'financial_band' => $metrics->financial_health_band,
                'governance_band' => $metrics->governance_quality_band,
                'risk_band' => $metrics->risk_intensity_band,
            ] : null,
            'risk_flags' => $flags->pluck('id')->toArray(),
            'under_review' => $metrics?->is_under_admin_review ?? false,
        ];
    }

    /**
     * Get highest severity from collection of flags
     */
    private function getHighestSeverity($flags): string
    {
        if ($flags->where('severity', 'critical')->isNotEmpty()) return 'critical';
        if ($flags->where('severity', 'high')->isNotEmpty()) return 'high';
        if ($flags->where('severity', 'medium')->isNotEmpty()) return 'medium';
        if ($flags->where('severity', 'low')->isNotEmpty()) return 'low';
        return 'info';
    }

    /**
     * Get standard regulatory disclaimer
     *
     * CRITICAL: This disclaimer appears on EVERY response
     *
     * @return array
     */
    private function getStandardDisclaimer(): array
    {
        return [
            'important_notice' => 'This information is provided for informational purposes only.',
            'not_advice' => 'Platform-generated metrics, risk flags, and comparative data do not constitute investment advice, recommendations, or endorsements.',
            'methodology_transparency' => 'All calculations are based on disclosed company data and platform methodology. Methodology details are included in each metric.',
            'investor_responsibility' => 'Investors must conduct their own due diligence and should not rely solely on platform-generated analysis.',
            'regulatory_status' => 'This platform is not a registered investment advisor. Platform analysis is for comparative context only.',
        ];
    }
}
