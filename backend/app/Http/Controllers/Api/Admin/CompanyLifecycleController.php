<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLifecycleLog;
use App\Services\CompanyLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * PHASE 2 - ADMIN CONTROLLER: CompanyLifecycleController
 *
 * PURPOSE:
 * Admin API endpoints for company lifecycle management and suspension.
 *
 * ROUTES:
 * GET    /api/admin/companies/{id}/lifecycle         - Get lifecycle status
 * GET    /api/admin/companies/{id}/lifecycle/logs    - Get lifecycle history
 * POST   /api/admin/companies/{id}/suspend           - Suspend company
 * POST   /api/admin/companies/{id}/unsuspend         - Unsuspend company
 * POST   /api/admin/companies/{id}/lifecycle/transition - Force state transition
 * GET    /api/admin/companies/suspended              - List suspended companies
 *
 * CRITICAL OPERATIONS:
 * - Suspension immediately disables buying
 * - All actions logged to audit trail
 * - Public warning banners for suspended companies
 *
 * AUTHORIZATION:
 * All methods protected by 'admin' middleware
 */
class CompanyLifecycleController extends Controller
{
    protected CompanyLifecycleService $lifecycleService;

    public function __construct(CompanyLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Get company lifecycle status
     *
     * GET /api/admin/companies/{id}/lifecycle
     */
    public function show(int $id): JsonResponse
    {
        try {
            $company = Company::with(['disclosures.module'])->findOrFail($id);

            // Get tier completion status
            $tierStatus = [
                'tier_1' => [
                    'is_complete' => $this->lifecycleService->isTierComplete($company, 1),
                    'approved_at' => $company->tier_1_approved_at,
                ],
                'tier_2' => [
                    'is_complete' => $this->lifecycleService->isTierComplete($company, 2),
                    'approved_at' => $company->tier_2_approved_at,
                ],
                'tier_3' => [
                    'is_complete' => $this->lifecycleService->isTierComplete($company, 3),
                    'approved_at' => $company->tier_3_approved_at,
                ],
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'lifecycle_state' => $company->lifecycle_state,
                    'current_tier' => $this->lifecycleService->getCurrentTier($company),
                    'buying_enabled' => $company->buying_enabled,
                    'can_accept_investments' => $this->lifecycleService->canAcceptInvestments($company),
                    'tier_status' => $tierStatus,
                    'suspension' => [
                        'is_suspended' => $company->lifecycle_state === 'suspended',
                        'suspended_at' => $company->suspended_at,
                        'suspended_by' => $company->suspended_by,
                        'suspension_reason' => $company->suspension_reason,
                        'show_warning_banner' => $company->show_warning_banner,
                        'warning_banner_message' => $company->warning_banner_message,
                    ],
                    'state_history' => [
                        'lifecycle_state_changed_at' => $company->lifecycle_state_changed_at,
                        'lifecycle_state_changed_by' => $company->lifecycle_state_changed_by,
                        'lifecycle_state_change_reason' => $company->lifecycle_state_change_reason,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch company lifecycle status', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch lifecycle status',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get company lifecycle history
     *
     * GET /api/admin/companies/{id}/lifecycle/logs
     */
    public function logs(int $id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);

            $logs = CompanyLifecycleLog::byCompany($id)
                ->with(['triggeredBy'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'from_state' => $log->from_state,
                    'to_state' => $log->to_state,
                    'state_change_description' => $log->state_change_description,
                    'trigger' => $log->trigger,
                    'trigger_label' => $log->trigger_label,
                    'triggered_by' => $log->triggeredBy ? $log->triggeredBy->name : 'System',
                    'reason' => $log->reason,
                    'metadata' => $log->metadata,
                    'is_upgrade' => $log->is_upgrade,
                    'is_suspension' => $log->is_suspension,
                    'created_at' => $log->created_at,
                    'ip_address' => $log->ip_address,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'current_state' => $company->lifecycle_state,
                    'logs' => $data,
                    'meta' => [
                        'total_transitions' => $logs->count(),
                        'upgrades' => $logs->where('is_upgrade', true)->count(),
                        'suspensions' => $logs->where('is_suspension', true)->count(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch lifecycle logs', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch lifecycle logs',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Suspend company
     *
     * POST /api/admin/companies/{id}/suspend
     *
     * Body:
     * {
     *   "public_reason": "Pending regulatory investigation",
     *   "internal_notes": "SEC inquiry ref #12345"
     * }
     *
     * CRITICAL: This immediately disables buying and shows warning banner
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'public_reason' => 'required|string|max:500',
            'internal_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($id);

            if ($company->lifecycle_state === 'suspended') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company is already suspended',
                ], 422);
            }

            $this->lifecycleService->suspend(
                $company,
                auth()->id(),
                $request->input('public_reason'),
                $request->input('internal_notes')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Company suspended successfully',
                'data' => [
                    'company_id' => $company->id,
                    'lifecycle_state' => $company->fresh()->lifecycle_state,
                    'buying_enabled' => $company->fresh()->buying_enabled,
                    'suspended_at' => $company->fresh()->suspended_at,
                    'public_reason' => $request->input('public_reason'),
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to suspend company', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to suspend company',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Unsuspend company
     *
     * POST /api/admin/companies/{id}/unsuspend
     *
     * Body:
     * {
     *   "target_state": "live_investable",
     *   "reason": "Investigation completed, no issues found"
     * }
     */
    public function unsuspend(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'target_state' => 'required|in:draft,live_limited,live_investable,live_fully_disclosed',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($id);

            $this->lifecycleService->unsuspend(
                $company,
                auth()->id(),
                $request->input('target_state'),
                $request->input('reason')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Company unsuspended successfully',
                'data' => [
                    'company_id' => $company->id,
                    'lifecycle_state' => $company->fresh()->lifecycle_state,
                    'buying_enabled' => $company->fresh()->buying_enabled,
                    'suspension_cleared_at' => now(),
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to unsuspend company', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unsuspend company',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Force state transition (admin override)
     *
     * POST /api/admin/companies/{id}/lifecycle/transition
     *
     * Body:
     * {
     *   "new_state": "live_investable",
     *   "reason": "Emergency approval for urgent fundraising"
     * }
     *
     * CAUTION: Use only when automatic transition logic needs override
     */
    public function transition(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'new_state' => 'required|in:draft,live_limited,live_investable,live_fully_disclosed,suspended',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($id);

            $this->lifecycleService->transitionTo(
                $company,
                $request->input('new_state'),
                'admin_action',
                auth()->id(),
                $request->input('reason')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'State transition completed',
                'data' => [
                    'company_id' => $company->id,
                    'lifecycle_state' => $company->fresh()->lifecycle_state,
                    'buying_enabled' => $company->fresh()->buying_enabled,
                    'changed_at' => now(),
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to transition company state', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'new_state' => $request->input('new_state'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to transition state',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List all suspended companies
     *
     * GET /api/admin/companies/suspended
     */
    public function suspended(): JsonResponse
    {
        try {
            $companies = Company::where('lifecycle_state', 'suspended')
                ->orderBy('suspended_at', 'desc')
                ->get();

            $data = $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'suspended_at' => $company->suspended_at,
                    'suspended_by' => $company->suspended_by,
                    'suspension_reason' => $company->suspension_reason,
                    'suspension_internal_notes' => $company->suspension_internal_notes,
                    'show_warning_banner' => $company->show_warning_banner,
                    'warning_banner_message' => $company->warning_banner_message,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'meta' => [
                    'total' => $companies->count(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch suspended companies', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch suspended companies',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Preview visibility change impact (GAP 5 FIX)
     *
     * POST /api/admin/companies/{id}/preview-visibility-change
     *
     * Shows impact of visibility change before applying.
     * Used by admin UI to display confirmation modal.
     */
    public function previewVisibilityChange(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_visible_public' => 'sometimes|boolean',
            'is_visible_subscribers' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($id);

            $currentState = [
                'is_visible_public' => $company->is_visible_public,
                'is_visible_subscribers' => $company->is_visible_subscribers,
            ];

            $proposedChange = [];
            if ($request->has('is_visible_public')) {
                $proposedChange['is_visible_public'] = $request->boolean('is_visible_public');
            }
            if ($request->has('is_visible_subscribers')) {
                $proposedChange['is_visible_subscribers'] = $request->boolean('is_visible_subscribers');
            }

            // Calculate impact
            $activeInvestors = $company->investments()->where('status', 'active')->distinct('user_id')->count('user_id');
            $pendingInvestments = $company->investments()->where('status', 'pending')->count();

            $impact = [
                'current_state' => $currentState,
                'proposed_change' => $proposedChange,
                'affected_investors' => $activeInvestors,
                'existing_investors_unaffected' => true, // Always true - visibility doesn't affect existing investors
                'pending_investments' => $pendingInvestments,
                'warnings' => [],
            ];

            // Add warnings
            if (isset($proposedChange['is_visible_public']) && !$proposedChange['is_visible_public']) {
                $impact['warnings'][] = 'Company will be HIDDEN from public site. New users cannot discover it.';
            }
            if (isset($proposedChange['is_visible_subscribers']) && !$proposedChange['is_visible_subscribers']) {
                $impact['warnings'][] = 'Company will be HIDDEN from authenticated subscribers. They cannot invest.';
            }

            return response()->json([
                'status' => 'success',
                'data' => $impact,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to preview visibility change', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview visibility change',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update company visibility (GAP 5 FIX - CRITICAL)
     *
     * PUT /api/admin/companies/{id}/visibility
     *
     * AUDIT TRAIL: Records WHO, WHAT, WHEN, WHY to immutable audit_logs table
     * DEFENSIVE: Requires explicit reason for all visibility changes
     */
    public function updateVisibility(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_visible_public' => 'required|boolean',
            'is_visible_subscribers' => 'required|boolean',
            'reason' => 'required|string|min:10|max:500', // CRITICAL: Reason required for audit
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($id);
            $admin = $request->user();

            // Capture old values for audit trail
            $oldValues = [
                'is_visible_public' => $company->is_visible_public,
                'is_visible_subscribers' => $company->is_visible_subscribers,
            ];

            $newValues = [
                'is_visible_public' => $request->boolean('is_visible_public'),
                'is_visible_subscribers' => $request->boolean('is_visible_subscribers'),
            ];

            // Update company visibility
            $company->update($newValues);

            // GAP 5 FIX: IMMUTABLE AUDIT TRAIL
            // Record to audit_logs table with full attribution
            \App\Models\AuditLog::create([
                'admin_id' => $admin->id,
                'actor_type' => 'admin',
                'actor_id' => $admin->id,
                'actor_name' => $admin->name,
                'actor_email' => $admin->email,
                'action' => 'visibility_change',
                'module' => 'companies',
                'target_type' => 'Company',
                'target_id' => $company->id,
                'target_name' => $company->name,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => $request->input('reason'), // Explicit reason from admin
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'risk_level' => 'high', // Visibility changes are high-risk
                'requires_review' => false,
                'metadata' => [
                    'reason' => $request->input('reason'),
                    'changed_fields' => array_keys(array_filter($newValues, fn($v, $k) => $v !== $oldValues[$k], ARRAY_FILTER_USE_BOTH)),
                ],
            ]);

            \Log::info('[AUDIT] Company visibility changed', [
                'company_id' => $company->id,
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'reason' => $request->input('reason'),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Company visibility updated successfully',
                'data' => [
                    'company_id' => $company->id,
                    'is_visible_public' => $company->is_visible_public,
                    'is_visible_subscribers' => $company->is_visible_subscribers,
                    'updated_by' => $admin->name,
                    'updated_at' => $company->updated_at->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update company visibility', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update company visibility',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Preview platform context change impact (ISSUE 2 FIX)
     *
     * POST /api/admin/companies/{id}/preview-platform-context-change
     *
     * Shows impact of suspend/freeze/buying changes before applying.
     * Used by admin UI to display confirmation modal.
     */
    public function previewPlatformContextChange(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_suspended' => 'sometimes|boolean',
            'is_frozen' => 'sometimes|boolean',
            'buying_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = Company::findOrFail($id);

            // Get current state
            $currentState = [
                'lifecycle_state' => $company->lifecycle_state,
                'is_suspended' => $company->lifecycle_state === 'suspended',
                'is_frozen' => $company->is_frozen ?? false,
                'buying_enabled' => $company->buying_enabled,
            ];

            // Build proposed change
            $proposedChange = [];
            if ($request->has('is_suspended')) {
                $proposedChange['is_suspended'] = $request->boolean('is_suspended');
            }
            if ($request->has('is_frozen')) {
                $proposedChange['is_frozen'] = $request->boolean('is_frozen');
            }
            if ($request->has('buying_enabled')) {
                $proposedChange['buying_enabled'] = $request->boolean('buying_enabled');
            }

            // Calculate impact metrics
            $activeInvestors = $company->investments()
                ->where('status', 'active')
                ->distinct('user_id')
                ->count('user_id');

            $activeSubscriptions = $company->subscriptions()
                ->where('status', 'active')
                ->count();

            $pendingInvestments = $company->investments()
                ->where('status', 'pending')
                ->count();

            // Determine blocked actions
            $blockedIssuerActions = [];
            $blockedInvestorActions = [];
            $warnings = [];

            // Check if suspension is being enabled
            if (isset($proposedChange['is_suspended']) && $proposedChange['is_suspended'] && !$currentState['is_suspended']) {
                $blockedIssuerActions = [
                    'edit_disclosure',
                    'submit_disclosure',
                    'answer_clarification',
                ];
                $blockedInvestorActions = [
                    'create_investment',
                    'new_subscription',
                ];
                $warnings[] = 'Suspending will BLOCK all issuer actions and prevent new investments.';
                $warnings[] = 'Existing investors are UNAFFECTED (historical snapshots remain accessible).';
            }

            // Check if freeze is being enabled
            if (isset($proposedChange['is_frozen']) && $proposedChange['is_frozen'] && !$currentState['is_frozen']) {
                $blockedIssuerActions = array_unique(array_merge($blockedIssuerActions, [
                    'edit_disclosure',
                    'submit_disclosure',
                ]));
                $warnings[] = 'Freezing will prevent issuer from editing or submitting disclosures.';
            }

            // Check if buying is being disabled
            if (isset($proposedChange['buying_enabled']) && !$proposedChange['buying_enabled'] && $currentState['buying_enabled']) {
                $blockedInvestorActions = array_unique(array_merge($blockedInvestorActions, [
                    'create_investment',
                ]));
                $warnings[] = 'Disabling buying will prevent new investments (existing investors unaffected).';
            }

            $impact = [
                'current_state' => $currentState,
                'proposed_change' => $proposedChange,
                'active_investors' => $activeInvestors,
                'active_investors_unaffected' => true, // Always true - platform actions don't affect existing investments
                'active_subscriptions' => $activeSubscriptions,
                'pending_investments' => $pendingInvestments,
                'blocked_issuer_actions' => $blockedIssuerActions,
                'blocked_investor_actions' => $blockedInvestorActions,
                'warnings' => $warnings,
                'impact_summary' => $this->buildImpactSummary($proposedChange, $currentState),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $impact,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to preview platform context change', [
                'company_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview platform context change',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Build human-readable impact summary
     */
    private function buildImpactSummary(array $proposedChange, array $currentState): string
    {
        $changes = [];

        if (isset($proposedChange['is_suspended']) && $proposedChange['is_suspended'] !== $currentState['is_suspended']) {
            $changes[] = $proposedChange['is_suspended']
                ? 'Company will be SUSPENDED (blocks all issuer actions and new investments)'
                : 'Company will be UNSUSPENDED (restores normal operations)';
        }

        if (isset($proposedChange['is_frozen']) && $proposedChange['is_frozen'] !== $currentState['is_frozen']) {
            $changes[] = $proposedChange['is_frozen']
                ? 'Disclosures will be FROZEN (issuer cannot edit or submit)'
                : 'Disclosures will be UNFROZEN (issuer can edit and submit)';
        }

        if (isset($proposedChange['buying_enabled']) && $proposedChange['buying_enabled'] !== $currentState['buying_enabled']) {
            $changes[] = $proposedChange['buying_enabled']
                ? 'Buying will be ENABLED (investors can make new investments)'
                : 'Buying will be DISABLED (investors cannot make new investments)';
        }

        return implode(' | ', $changes) ?: 'No changes detected.';
    }
}
