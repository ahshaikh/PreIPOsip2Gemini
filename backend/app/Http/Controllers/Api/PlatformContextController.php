<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformCompanyMetric;
use App\Models\PlatformRiskFlag;
use App\Models\PlatformValuationContext;
use App\Services\ChangeTrackingService;
use App\Services\PlatformContextMakerCheckerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    protected PlatformContextMakerCheckerService $makerCheckerService;

    public function __construct(
        ChangeTrackingService $changeTrackingService,
        PlatformContextMakerCheckerService $makerCheckerService
    ) {
        $this->changeTrackingService = $changeTrackingService;
        $this->makerCheckerService = $makerCheckerService;
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

    // =========================================================================
    // P0 FIX (GAP 14): ATTRIBUTION ENDPOINTS
    // Investors can see WHO set context, WHEN it was set, WHY it changed
    // =========================================================================

    /**
     * Get platform context with full attribution
     *
     * GET /api/v1/companies/{id}/platform-context/attributed
     *
     * P0 FIX (GAP 14): Returns platform context with full transparency:
     * - Who set each piece of context (admin vs automated)
     * - When it was set
     * - Why it changed (trigger reason)
     * - Multi-admin approval status
     */
    public function getAttributedContext(int $id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);

            // Get current platform context snapshot
            $currentSnapshot = DB::table('platform_context_snapshots')
                ->where('company_id', $id)
                ->where('is_current', true)
                ->first();

            // Get attribution from governance log
            $recentGovernanceActions = DB::table('platform_governance_log')
                ->where('company_id', $id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Get maker-checker approval history (for transparency)
            $approvalHistory = $this->makerCheckerService->getInvestorVisibleHistory($id, 10);

            // Build attribution response
            $attribution = [
                'current_state_set_by' => $this->getStateAttribution($company, $currentSnapshot),
                'last_review' => $this->getLastReviewAttribution($recentGovernanceActions),
                'multi_admin_approvals' => $approvalHistory,
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $id,
                    'company_name' => $company->name,

                    // P0 FIX: Clearly separated sections
                    'platform_attribution' => $attribution,

                    // Current platform state with who/when/why
                    'current_platform_state' => [
                        'lifecycle_state' => $company->lifecycle_state,
                        'buying_enabled' => $company->buying_enabled ?? true,
                        'is_under_review' => $company->disclosure_freeze ?? false,

                        // WHO set this state
                        'state_set_by' => $this->determineStateSetBy($currentSnapshot, $recentGovernanceActions),

                        // WHEN was it set
                        'state_set_at' => $currentSnapshot?->snapshot_at,

                        // WHY was it set (trigger)
                        'state_trigger' => $currentSnapshot?->snapshot_trigger,

                        // Multi-admin approval status
                        'requires_multi_admin' => $this->requiresMultiAdminApproval($company->lifecycle_state),
                        'was_multi_admin_approved' => $this->wasMultiAdminApproved($id, $company->lifecycle_state),
                    ],

                    // Governance transparency
                    'governance_transparency' => [
                        'governance_version' => $currentSnapshot?->governance_state_version ?? 1,
                        'snapshot_id' => $currentSnapshot?->id,
                        'snapshot_locked' => $currentSnapshot?->is_locked ?? false,
                        'valid_from' => $currentSnapshot?->valid_from,
                    ],
                ],
                'disclaimer' => $this->getStandardDisclaimer(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch attributed context', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch platform attribution',
            ], 500);
        }
    }

    // =========================================================================
    // P0 FIX (GAP 16): CHANGE HISTORY ENDPOINT
    // Full history of platform context changes visible to investors
    // =========================================================================

    /**
     * Get platform context change history
     *
     * GET /api/v1/companies/{id}/platform-context/history
     *
     * P0 FIX (GAP 16): Returns complete change history that investors can browse
     * - All governance state changes
     * - Multi-admin approval decisions
     * - Risk flag changes
     * - Disclosure updates
     */
    public function getChangeHistory(int $id, Request $request): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);
            $limit = min($request->input('limit', 20), 100);
            $offset = $request->input('offset', 0);

            // Get governance log entries (investor-safe version)
            $governanceChanges = DB::table('platform_governance_log')
                ->where('company_id', $id)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($entry) {
                    return [
                        'change_type' => 'governance',
                        'action' => $entry->action_type,
                        'timestamp' => $entry->created_at,
                        'is_automated' => $entry->is_automated,
                        'category' => $this->getGovernanceCategory($entry->action_type),
                        // Hide internal reasons, show category only
                        'change_summary' => $this->getChangeTypeSummary($entry->action_type),
                        'was_multi_admin' => $entry->approval_request_id !== null,
                    ];
                });

            // Get disclosure change log entries
            $disclosureChanges = DB::table('disclosure_change_log')
                ->where('company_id', $id)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($entry) {
                    return [
                        'change_type' => 'disclosure',
                        'action' => $entry->change_type,
                        'timestamp' => $entry->created_at,
                        'module' => $entry->module_name ?? null,
                        'category' => 'disclosure_update',
                        'change_summary' => $this->getDisclosureChangeSummary($entry),
                    ];
                });

            // Get platform snapshot changes
            $snapshotChanges = DB::table('platform_context_snapshots')
                ->where('company_id', $id)
                ->where('has_material_changes', true)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($snapshot) {
                    $materialChanges = json_decode($snapshot->material_changes_summary, true) ?? [];
                    return [
                        'change_type' => 'material_change',
                        'timestamp' => $snapshot->snapshot_at,
                        'trigger' => $snapshot->snapshot_trigger,
                        'changes' => array_map(function ($change) {
                            return [
                                'field' => $change['field'] ?? 'unknown',
                                'is_material' => $change['is_material'] ?? true,
                            ];
                        }, $materialChanges),
                        'category' => 'platform_update',
                    ];
                });

            // Merge and sort all changes by timestamp
            $allChanges = $governanceChanges
                ->concat($disclosureChanges)
                ->concat($snapshotChanges)
                ->sortByDesc('timestamp')
                ->values()
                ->take($limit);

            // Get total count for pagination
            $totalCount = DB::table('platform_governance_log')
                ->where('company_id', $id)
                ->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $id,
                    'company_name' => $company->name,
                    'changes' => $allChanges,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => $totalCount,
                        'has_more' => ($offset + $limit) < $totalCount,
                    ],
                ],
                'disclaimer' => $this->getStandardDisclaimer(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch change history', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch change history',
            ], 500);
        }
    }

    // =========================================================================
    // P0 FIX (GAP 15): SEPARATED CONTEXT ENDPOINT
    // Clear visual separation between platform and company data
    // =========================================================================

    /**
     * Get clearly separated platform vs company context
     *
     * GET /api/v1/companies/{id}/context/separated
     *
     * P0 FIX (GAP 15): Returns two distinct sections:
     * - company_disclosures: What the COMPANY says about itself
     * - platform_assessment: What the PLATFORM says about the company
     */
    public function getSeparatedContext(int $id): JsonResponse
    {
        try {
            $company = Company::with(['disclosures.disclosureModule'])->findOrFail($id);

            // Section 1: COMPANY DISCLOSURES (what company says)
            $companyDisclosures = $company->disclosures
                ->where('status', 'approved')
                ->map(function ($disclosure) {
                    return [
                        'module' => $disclosure->disclosureModule?->name,
                        'submitted_at' => $disclosure->submitted_at,
                        'approved_at' => $disclosure->approved_at,
                        'version' => $disclosure->current_version_id,
                        'source' => 'COMPANY', // Clear attribution
                    ];
                });

            // Section 2: PLATFORM ASSESSMENT (what platform says)
            $metrics = PlatformCompanyMetric::where('company_id', $id)->first();
            $riskFlags = PlatformRiskFlag::visibleToInvestors()
                ->where('company_id', $id)
                ->get();
            $valuationContext = PlatformValuationContext::where('company_id', $id)
                ->current()
                ->first();

            $platformAssessment = [
                'health_metrics' => $metrics?->getInvestorSummary(),
                'risk_flags' => $riskFlags->map(fn($flag) => $flag->getInvestorSummary()),
                'valuation_context' => $valuationContext?->getInvestorSummary(),
                'source' => 'PLATFORM', // Clear attribution
                'methodology_version' => $metrics?->calculation_methodology_version ?? '1.0',
                'last_calculated' => $metrics?->last_platform_review,
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $id,
                    'company_name' => $company->name,

                    // =====================================================
                    // SECTION 1: COMPANY DISCLOSURES
                    // Information provided BY the company
                    // =====================================================
                    'company_disclosures' => [
                        'label' => 'Information Provided by Company',
                        'description' => 'The following information was submitted and certified by the company.',
                        'source_type' => 'company',
                        'disclosures' => $companyDisclosures,
                    ],

                    // =====================================================
                    // SECTION 2: PLATFORM ASSESSMENT
                    // Analysis generated BY the platform
                    // =====================================================
                    'platform_assessment' => [
                        'label' => 'Platform Analysis',
                        'description' => 'The following analysis was generated by the platform based on disclosed data and platform methodology.',
                        'source_type' => 'platform',
                        'assessment' => $platformAssessment,
                        'important_notice' => 'Platform analysis is for informational purposes only and does not constitute investment advice.',
                    ],

                    // Visual separation guidance for frontend
                    'display_guidance' => [
                        'use_distinct_styling' => true,
                        'company_section_color' => '#F0F9FF', // Light blue
                        'platform_section_color' => '#FEF3C7', // Light yellow
                        'show_source_labels' => true,
                        'render_separately' => true,
                    ],
                ],
                'disclaimer' => $this->getStandardDisclaimer(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch separated context', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch context',
            ], 500);
        }
    }

    // =========================================================================
    // HELPER METHODS FOR ATTRIBUTION
    // =========================================================================

    /**
     * Determine who set the current state
     */
    private function determineStateSetBy(?object $snapshot, $governanceActions): array
    {
        if (!$snapshot) {
            return [
                'type' => 'system',
                'description' => 'Initial state set by system',
            ];
        }

        if ($snapshot->actor_type === 'admin') {
            return [
                'type' => 'admin',
                'description' => 'State set by platform administrator',
                'requires_multi_admin' => true,
            ];
        }

        if ($snapshot->actor_type === 'automated_job') {
            return [
                'type' => 'automated',
                'description' => 'State updated by scheduled refresh',
            ];
        }

        return [
            'type' => 'system',
            'description' => 'State managed by platform',
        ];
    }

    /**
     * Get state attribution details
     */
    private function getStateAttribution(Company $company, ?object $snapshot): array
    {
        return [
            'current_state' => $company->lifecycle_state,
            'snapshot_id' => $snapshot?->id,
            'set_at' => $snapshot?->snapshot_at,
            'set_by_type' => $snapshot?->actor_type ?? 'system',
            'trigger' => $snapshot?->snapshot_trigger,
            'is_locked' => $snapshot?->is_locked ?? false,
        ];
    }

    /**
     * Get last review attribution
     */
    private function getLastReviewAttribution($governanceActions): ?array
    {
        $lastAdminAction = $governanceActions
            ->where('is_automated', false)
            ->first();

        if (!$lastAdminAction) {
            return null;
        }

        return [
            'action_type' => $lastAdminAction->action_type,
            'reviewed_at' => $lastAdminAction->created_at,
            'was_multi_admin' => $lastAdminAction->approval_request_id !== null,
        ];
    }

    /**
     * Check if state requires multi-admin approval
     */
    private function requiresMultiAdminApproval(string $state): bool
    {
        return in_array($state, ['suspended', 'frozen', 'under_investigation']);
    }

    /**
     * Check if current state was multi-admin approved
     */
    private function wasMultiAdminApproved(int $companyId, string $state): bool
    {
        if (!$this->requiresMultiAdminApproval($state)) {
            return false;
        }

        // Check if there's an approved maker-checker request for this state
        $approvedRequest = DB::table('platform_context_approval_requests')
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereRaw("JSON_EXTRACT(proposed_changes, '$.lifecycle_state') = ?", [$state])
            ->orderBy('reviewed_at', 'desc')
            ->first();

        return $approvedRequest !== null &&
               $approvedRequest->maker_user_id !== $approvedRequest->checker_user_id;
    }

    /**
     * Get governance action category
     */
    private function getGovernanceCategory(string $actionType): string
    {
        $categories = [
            'suspend_company' => 'compliance',
            'unsuspend_company' => 'compliance',
            'freeze_disclosures' => 'compliance',
            'unfreeze_disclosures' => 'compliance',
            'start_investigation' => 'investigation',
            'end_investigation' => 'investigation',
            'tier_approval' => 'governance',
            'tier_revocation' => 'governance',
            'buying_status_change' => 'trading',
        ];

        return $categories[$actionType] ?? 'other';
    }

    /**
     * Get human-readable change summary
     */
    private function getChangeTypeSummary(string $actionType): string
    {
        $summaries = [
            'suspend_company' => 'Company status changed to suspended',
            'unsuspend_company' => 'Company suspension lifted',
            'freeze_disclosures' => 'Disclosure updates temporarily paused',
            'unfreeze_disclosures' => 'Disclosure updates resumed',
            'start_investigation' => 'Platform review initiated',
            'end_investigation' => 'Platform review completed',
            'tier_approval' => 'Tier approval status updated',
            'tier_revocation' => 'Tier approval revoked',
            'buying_status_change' => 'Investment availability updated',
        ];

        return $summaries[$actionType] ?? 'Platform status updated';
    }

    /**
     * Get disclosure change summary
     */
    private function getDisclosureChangeSummary(object $entry): string
    {
        $type = $entry->change_type ?? 'update';
        $module = $entry->module_name ?? 'disclosure';

        return match($type) {
            'created' => "New {$module} submitted",
            'updated' => "{$module} information updated",
            'approved' => "{$module} approved by platform",
            'rejected' => "{$module} returned for revision",
            default => "{$module} updated",
        };
    }
}
