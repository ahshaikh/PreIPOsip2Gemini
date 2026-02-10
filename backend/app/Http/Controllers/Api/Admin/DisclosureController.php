<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureClarification;
use App\Models\DisclosureModule;
use App\Services\DisclosureDiffService;
use App\Services\DisclosureFreshnessService;
use App\Services\DisclosureReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * PHASE 2 - ADMIN CONTROLLER: DisclosureController
 *
 * PURPOSE:
 * Admin API endpoints for disclosure review workflow.
 *
 * ROUTES:
 * GET    /api/admin/disclosures/pending          - List pending reviews
 * GET    /api/admin/disclosures/{id}             - Get disclosure details
 * POST   /api/admin/disclosures/{id}/start-review - Start review
 * POST   /api/admin/disclosures/{id}/clarifications - Request clarifications
 * POST   /api/admin/disclosures/{id}/approve     - Approve disclosure
 * POST   /api/admin/disclosures/{id}/reject      - Reject disclosure
 * GET    /api/admin/disclosures/{id}/diff        - Get version diff
 * GET    /api/admin/disclosures/{id}/timeline    - Get version timeline
 * POST   /api/admin/clarifications/{id}/accept   - Accept clarification answer
 * POST   /api/admin/clarifications/{id}/dispute  - Dispute clarification answer
 *
 * AUTHORIZATION:
 * All methods protected by 'admin' middleware
 */
class DisclosureController extends Controller
{
    protected DisclosureReviewService $reviewService;
    protected DisclosureDiffService $diffService;
    protected DisclosureFreshnessService $freshnessService;

    public function __construct(
        DisclosureReviewService $reviewService,
        DisclosureDiffService $diffService,
        DisclosureFreshnessService $freshnessService
    ) {
        $this->reviewService = $reviewService;
        $this->diffService = $diffService;
        $this->freshnessService = $freshnessService;
    }

    /**
     * List all disclosures with filters (primary list endpoint)
     *
     * GET /api/admin/disclosures
     *
     * Query params:
     * - status: Filter by status (comma-separated for multiple)
     * - company_id: Filter by company
     * - tier: Filter by tier (1, 2, 3)
     * - module_id: Filter by module
     *
     * Default: returns actionable statuses (submitted, resubmitted, under_review, clarification_required)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CompanyDisclosure::with(['company', 'module', 'clarifications'])
                ->orderBy('submitted_at', 'asc');

            // Status filter (comma-separated or single)
            if ($request->has('status')) {
                $statuses = explode(',', $request->input('status'));
                $query->whereIn('status', $statuses);
            } else {
                // Default: actionable items only
                $query->whereIn('status', ['submitted', 'resubmitted', 'under_review', 'clarification_required']);
            }

            if ($request->has('company_id')) {
                $query->where('company_id', $request->input('company_id'));
            }

            if ($request->has('tier')) {
                $query->whereHas('module', function ($q) use ($request) {
                    $q->where('tier', $request->input('tier'));
                });
            }

            if ($request->has('module_id')) {
                $query->where('disclosure_module_id', $request->input('module_id'));
            }

            // Freshness filter (FROZEN VOCABULARY: current|aging|stale|unstable)
            if ($request->has('freshness')) {
                $freshness = $request->input('freshness');
                if ($freshness === 'needs_attention') {
                    $query->whereIn('freshness_state', ['aging', 'stale', 'unstable']);
                } else {
                    $query->where('freshness_state', $freshness);
                }
            }

            $disclosures = $query->get();

            $data = $disclosures->map(function ($disclosure) {
                $summary = $this->reviewService->getReviewSummary($disclosure);
                return [
                    'id' => $disclosure->id,
                    'company' => [
                        'id' => $disclosure->company_id,
                        'name' => $disclosure->company?->name ?? 'Unknown Company',
                        'lifecycle_state' => $disclosure->company?->lifecycle_state ?? 'unknown',
                    ],
                    'module' => [
                        'id' => $disclosure->disclosure_module_id,
                        'code' => $disclosure->module?->code ?? 'unknown',
                        'name' => $disclosure->module?->name ?? 'Unknown Module',
                        'tier' => $disclosure->module?->tier ?? 1,
                    ],
                    'status' => $disclosure->status,
                    'submitted_at' => $disclosure->submitted_at,
                    'review_started_at' => $disclosure->review_started_at,
                    'clarifications' => $summary['clarifications'],
                    'can_start_review' => $summary['can_start_review'],
                    'can_approve' => $summary['can_approve'],
                    'can_reject' => $summary['can_reject'],
                    'can_request_clarification' => $summary['can_request_clarification'],
                    'audit_window_breached' => $summary['audit_window_breached'],
                    'is_terminal' => $summary['is_terminal'],
                    // Freshness data (FROZEN VOCABULARY)
                    'freshness_state' => $disclosure->freshness_state,
                    'days_since_approval' => $disclosure->days_since_approval,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'meta' => [
                    'total' => $data->count(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch disclosures', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch disclosures',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get list of disclosures pending admin review
     *
     * GET /api/admin/disclosures/pending
     *
     * Query params:
     * - module_id: Filter by module
     * - company_id: Filter by company
     * - tier: Filter by tier (1, 2, 3)
     * - status: Filter by status
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $filters = [];

            if ($request->has('module_id')) {
                $filters['module_id'] = $request->input('module_id');
            }

            if ($request->has('company_id')) {
                $filters['company_id'] = $request->input('company_id');
            }

            if ($request->has('tier')) {
                $filters['tier'] = $request->input('tier');
            }

            if ($request->has('status')) {
                $filters['status'] = $request->input('status');
            }

            $disclosures = $this->reviewService->getPendingReviews($filters);

            // Transform for API response
            $data = $disclosures->map(function ($disclosure) {
                $summary = $this->reviewService->getReviewSummary($disclosure);
                return [
                    'id' => $disclosure->id,
                    'company' => [
                        'id' => $disclosure->company_id,
                        'name' => $disclosure->company?->name ?? 'Unknown Company',
                        'lifecycle_state' => $disclosure->company?->lifecycle_state ?? 'unknown',
                    ],
                    'module' => [
                        'id' => $disclosure->disclosure_module_id,
                        'code' => $disclosure->module?->code ?? 'unknown',
                        'name' => $disclosure->module?->name ?? 'Unknown Module',
                        'tier' => $disclosure->module?->tier ?? 1,
                    ],
                    'status' => $disclosure->status,
                    'submitted_at' => $disclosure->submitted_at,
                    'review_started_at' => $disclosure->review_started_at,
                    'completion_percentage' => $disclosure->completion_percentage,
                    'clarifications' => $summary['clarifications'],
                    'can_approve' => $summary['can_approve'],
                    'priority' => $this->calculatePriority($disclosure, $summary),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'meta' => [
                    'total' => $data->count(),
                    'filters' => $filters,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch pending disclosures', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch pending disclosures',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get detailed disclosure information for review
     *
     * GET /api/admin/disclosures/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $disclosure = CompanyDisclosure::with([
                'company',
                'module',
                'currentApproval',
                'clarifications.asker',      // FIX: was 'askedByUser'
                'clarifications.answeredBy', // FIX: was 'answeredByUser'
                'versions',
            ])->findOrFail($id);

            $summary = $this->reviewService->getReviewSummary($disclosure);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'disclosure' => [
                        'id' => $disclosure->id,
                        'status' => $disclosure->status,
                        'version_number' => $disclosure->version_number,
                        'completion_percentage' => $disclosure->completion_percentage,
                        'disclosure_data' => $disclosure->disclosure_data,
                        'attachments' => $disclosure->attachments,
                        'submitted_at' => $disclosure->submitted_at,
                        'submitted_by' => $disclosure->submitted_by,
                        'review_started_at' => $disclosure->review_started_at,
                        'review_started_by' => $disclosure->review_started_by,
                        'approved_at' => $disclosure->approved_at,
                        'approved_by' => $disclosure->approved_by,
                        'rejected_at' => $disclosure->rejected_at,
                        'rejected_by' => $disclosure->rejected_by,
                        'rejection_reason' => $disclosure->rejection_reason,
                        'is_locked' => $disclosure->is_locked,
                        'edits_during_review' => $disclosure->edits_during_review,
                        'edit_count_during_review' => $disclosure->edit_count_during_review,
                        // Freshness data (FROZEN VOCABULARY: current|aging|stale|unstable)
                        'freshness_state' => $disclosure->freshness_state,
                        'freshness_computed_at' => $disclosure->freshness_computed_at,
                        'days_since_approval' => $disclosure->days_since_approval,
                        'update_count_in_window' => $disclosure->update_count_in_window,
                        'next_update_expected' => $disclosure->next_update_expected,
                        'has_freshness_override' => $disclosure->hasFreshnessOverride(),
                        'freshness_override' => $disclosure->freshness_override,
                    ],
                    // Freshness signal text (computed)
                    'freshness' => $disclosure->status === 'approved'
                        ? $this->freshnessService->computeArtifactFreshness($disclosure)
                        : null,
                    'company' => [
                        'id' => $disclosure->company->id,
                        'name' => $disclosure->company->name,
                        'lifecycle_state' => $disclosure->company->lifecycle_state,
                        'buying_enabled' => $disclosure->company->buying_enabled,
                    ],
                    'module' => [
                        'id' => $disclosure->module->id,
                        'code' => $disclosure->module->code,
                        'name' => $disclosure->module->name,
                        'tier' => $disclosure->module->tier,
                        'json_schema' => $disclosure->module->json_schema,
                    ],
                    'clarifications' => $disclosure->clarifications->map(fn($c) => [
                        'id' => $c->id,
                        'question_subject' => $c->question_subject,
                        'question_body' => $c->question_body,
                        'question_type' => $c->question_type,
                        'asked_by' => $c->asker ? $c->asker->name : null, // FIX: was askedByUser
                        'asked_at' => $c->asked_at,
                        'field_path' => $c->field_path,
                        'status' => $c->status,
                        'priority' => $c->priority,
                        'is_blocking' => $c->is_blocking,
                        'due_date' => $c->due_date,
                        'answer_body' => $c->answer_body,
                        'answered_by' => $c->answeredBy ? $c->answeredBy->name : null, // FIX: was answeredByUser
                        'answered_at' => $c->answered_at,
                        'resolution_notes' => $c->resolution_notes,
                    ]),
                    'summary' => $summary,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch disclosure details', [
                'disclosure_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch disclosure details',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Start admin review of disclosure
     *
     * POST /api/admin/disclosures/{id}/start-review
     */
    public function startReview(int $id): JsonResponse
    {
        try {
            $disclosure = CompanyDisclosure::findOrFail($id);

            $this->reviewService->startReview($disclosure, auth()->id());

            return response()->json([
                'status' => 'success',
                'message' => 'Review started successfully',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'status' => $disclosure->fresh()->status,
                    'review_started_at' => $disclosure->fresh()->review_started_at,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to start review', [
                'disclosure_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start review',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Request clarifications from company
     *
     * POST /api/admin/disclosures/{id}/clarifications
     *
     * Body:
     * {
     *   "clarifications": [
     *     {
     *       "question_subject": "Revenue clarification",
     *       "question_body": "Please explain...",
     *       "question_type": "verification",
     *       "priority": "high",
     *       "field_path": "disclosure_data.revenue_streams",
     *       "due_date": "2026-01-17",
     *       "is_blocking": false
     *     }
     *   ]
     * }
     */
    public function requestClarifications(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clarifications' => 'required|array|min:1',
            'clarifications.*.question_subject' => 'required|string|max:255',
            'clarifications.*.question_body' => 'required|string',
            'clarifications.*.question_type' => 'required|in:missing_data,inconsistency,insufficient_detail,verification,compliance,other',
            'clarifications.*.priority' => 'nullable|in:low,medium,high,critical',
            'clarifications.*.field_path' => 'nullable|string',
            'clarifications.*.due_date' => 'nullable|date|after:today',
            'clarifications.*.is_blocking' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $disclosure = CompanyDisclosure::findOrFail($id);

            $createdClarifications = $this->reviewService->requestClarifications(
                $disclosure,
                auth()->id(),
                $request->input('clarifications')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Clarifications requested successfully',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'status' => $disclosure->fresh()->status,
                    'clarification_count' => count($createdClarifications),
                    'clarifications' => $createdClarifications->map(fn($c) => [
                        'id' => $c->id,
                        'question_subject' => $c->question_subject,
                        'priority' => $c->priority,
                        'due_date' => $c->due_date,
                    ]),
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to request clarifications', [
                'disclosure_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to request clarifications',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approve disclosure
     *
     * POST /api/admin/disclosures/{id}/approve
     *
     * Body:
     * {
     *   "notes": "All requirements met. Approved."
     * }
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $disclosure = CompanyDisclosure::findOrFail($id);

            $version = $this->reviewService->approveDisclosure(
                $disclosure,
                auth()->id(),
                $request->input('notes')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Disclosure approved successfully',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'status' => $disclosure->fresh()->status,
                    'version_number' => $version->version_number,
                    'approved_at' => $version->approved_at,
                    'company_lifecycle_state' => $disclosure->company->fresh()->lifecycle_state,
                    'tier_completed' => $disclosure->module->tier,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to approve disclosure', [
                'disclosure_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve disclosure',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reject disclosure
     *
     * POST /api/admin/disclosures/{id}/reject
     *
     * Body:
     * {
     *   "reason": "Incomplete financial data",
     *   "internal_notes": "Missing Q3 2025 data"
     * }
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
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
            $disclosure = CompanyDisclosure::findOrFail($id);

            $this->reviewService->rejectDisclosure(
                $disclosure,
                auth()->id(),
                $request->input('reason'),
                $request->input('internal_notes')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Disclosure rejected',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'status' => $disclosure->fresh()->status,
                    'rejected_at' => $disclosure->fresh()->rejected_at,
                    'rejection_reason' => $request->input('reason'),
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to reject disclosure', [
                'disclosure_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject disclosure',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get version diff for disclosure
     *
     * GET /api/admin/disclosures/{id}/diff
     *
     * Query params:
     * - type: 'current' (default) | 'between'
     * - from_version: version number (if type=between)
     * - to_version: version number (if type=between)
     */
    public function diff(Request $request, int $id): JsonResponse
    {
        try {
            $disclosure = CompanyDisclosure::findOrFail($id);
            $type = $request->input('type', 'current');

            if ($type === 'between') {
                $fromVersion = $disclosure->versions()
                    ->where('version_number', $request->input('from_version'))
                    ->firstOrFail();
                $toVersion = $disclosure->versions()
                    ->where('version_number', $request->input('to_version'))
                    ->firstOrFail();

                $diff = $this->diffService->diffBetweenVersions($fromVersion, $toVersion);
            } else {
                $diff = $this->diffService->diffWithLastApprovedVersion($disclosure);
            }

            $visualization = $diff ? $this->diffService->generateVisualization($diff) : null;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'diff' => $diff,
                    'visualization' => $visualization,
                    'edit_history' => $this->diffService->getReviewCycleEdits($disclosure),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate diff', [
                'disclosure_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate diff',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get version timeline
     *
     * GET /api/admin/disclosures/{id}/timeline
     */
    public function timeline(int $id): JsonResponse
    {
        try {
            $disclosure = CompanyDisclosure::findOrFail($id);
            $timeline = $this->diffService->getVersionTimeline($disclosure);

            return response()->json([
                'status' => 'success',
                'data' => $timeline,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch timeline', [
                'disclosure_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch timeline',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Accept clarification answer
     *
     * POST /api/admin/clarifications/{id}/accept
     *
     * Body:
     * {
     *   "notes": "Explanation is satisfactory"
     * }
     */
    public function acceptClarification(Request $request, int $id): JsonResponse
    {
        try {
            $clarification = DisclosureClarification::findOrFail($id);

            $this->reviewService->acceptClarificationAnswer(
                $clarification,
                auth()->id(),
                $request->input('notes')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Clarification answer accepted',
                'data' => [
                    'clarification_id' => $clarification->id,
                    'status' => $clarification->fresh()->status,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to accept clarification', [
                'clarification_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to accept clarification',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Dispute clarification answer
     *
     * POST /api/admin/clarifications/{id}/dispute
     *
     * Body:
     * {
     *   "reason": "Answer is insufficient. Please provide bank statements."
     * }
     */
    public function disputeClarification(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            $clarification = DisclosureClarification::findOrFail($id);

            $this->reviewService->disputeClarificationAnswer(
                $clarification,
                auth()->id(),
                $request->input('reason')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Clarification answer disputed',
                'data' => [
                    'clarification_id' => $clarification->id,
                    'status' => $clarification->fresh()->status,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to dispute clarification', [
                'clarification_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to dispute clarification',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get pillar evidence snapshot for a company
     *
     * GET /api/admin/companies/{companyId}/pillar-evidence
     *
     * Returns per-pillar:
     * - Vitality state (FROZEN: healthy|needs_attention|at_risk)
     * - Freshness breakdown
     * - Vitality drivers (artifacts causing degradation)
     * - Coverage facts
     *
     * ADMIN USE: Decision-grade evidence for approval/rejection justification.
     * Admin decisions can cite freshness decay or instability from this data.
     */
    public function pillarEvidence(int $companyId): JsonResponse
    {
        try {
            $company = \App\Models\Company::findOrFail($companyId);

            // Determine current tier
            $tierStatus = $company->tier_status ?? [];
            $currentTier = 1;
            if ($tierStatus['tier_3_approved'] ?? false) {
                $currentTier = 3;
            } elseif ($tierStatus['tier_2_approved'] ?? false) {
                $currentTier = 2;
            } elseif ($tierStatus['tier_1_approved'] ?? false) {
                $currentTier = 1;
            }

            $freshnessSummary = $this->freshnessService->getCompanyFreshnessSummary($company, $currentTier);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'current_tier' => $currentTier,
                    'overall_vitality' => $freshnessSummary['overall_vitality'],
                    'pillars' => $freshnessSummary['pillars'],
                    'coverage_summary' => $freshnessSummary['coverage_summary'],
                    'last_computed' => $freshnessSummary['last_computed'],
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found',
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch pillar evidence', [
                'company_id' => $companyId,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch pillar evidence',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Calculate priority score for sorting
     */
    protected function calculatePriority(CompanyDisclosure $disclosure, array $summary): string
    {
        $clarifications = $summary['clarifications'];

        // Critical if has blocking clarifications
        if ($clarifications['blocking'] > 0) {
            return 'critical';
        }

        // High if has overdue clarifications
        if ($clarifications['overdue'] > 0) {
            return 'high';
        }

        // Medium if under review with clarifications
        if ($disclosure->status === 'under_review' && $clarifications['total'] > 0) {
            return 'medium';
        }

        return 'normal';
    }
}
