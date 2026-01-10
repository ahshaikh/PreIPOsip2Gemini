<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureClarification;
use App\Models\DisclosureModule;
use App\Services\CompanyDisclosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * PHASE 3 - COMPANY CONTROLLER: DisclosureController
 *
 * PURPOSE:
 * Company-side API for disclosure submission workflows.
 *
 * ROUTES:
 * GET    /api/company/dashboard               - Dashboard summary
 * GET    /api/company/disclosures             - List all disclosures
 * GET    /api/company/disclosures/{id}        - Get disclosure details
 * POST   /api/company/disclosures             - Create/update draft
 * POST   /api/company/disclosures/{id}/submit - Submit for review
 * POST   /api/company/disclosures/{id}/report-error - Report error in approved
 * POST   /api/company/disclosures/{id}/attach - Attach documents
 * GET    /api/company/clarifications/{id}     - Get clarification details
 * POST   /api/company/clarifications/{id}/answer - Answer clarification
 *
 * AUTHORIZATION:
 * All methods protected by CompanyDisclosurePolicy
 */
class DisclosureController extends Controller
{
    protected CompanyDisclosureService $disclosureService;

    public function __construct(CompanyDisclosureService $disclosureService)
    {
        $this->disclosureService = $disclosureService;
    }

    /**
     * Get issuer dashboard summary
     *
     * GET /api/company/dashboard
     *
     * Returns:
     * - Tier progress (% complete)
     * - Blockers (rejected, clarifications)
     * - Next actions
     * - Statistics
     */
    public function dashboard(): JsonResponse
    {
        try {
            $user = auth()->user();
            $company = $user->company; // Assuming user belongs to one company

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not associated with any company',
                ], 403);
            }

            $summary = $this->disclosureService->getDashboardSummary($company);

            return response()->json([
                'status' => 'success',
                'data' => $summary,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch company dashboard', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List all disclosures for company
     *
     * GET /api/company/disclosures
     */
    public function index(): JsonResponse
    {
        try {
            $user = auth()->user();
            $company = $user->company;

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not associated with any company',
                ], 403);
            }

            $disclosures = CompanyDisclosure::where('company_id', $company->id)
                ->with(['module', 'clarifications'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $disclosures->map(function ($disclosure) {
                return [
                    'id' => $disclosure->id,
                    'module' => [
                        'id' => $disclosure->module->id,
                        'code' => $disclosure->module->code,
                        'name' => $disclosure->module->name,
                        'tier' => $disclosure->module->tier,
                    ],
                    'status' => $disclosure->status,
                    'completion_percentage' => $disclosure->completion_percentage,
                    'version_number' => $disclosure->version_number,
                    'submitted_at' => $disclosure->submitted_at,
                    'approved_at' => $disclosure->approved_at,
                    'rejected_at' => $disclosure->rejected_at,
                    'rejection_reason' => $disclosure->rejection_reason,
                    'is_locked' => $disclosure->is_locked,
                    'open_clarifications' => $disclosure->clarifications()
                        ->whereIn('status', ['open', 'disputed'])
                        ->count(),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch disclosures', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch disclosures',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get disclosure details
     *
     * GET /api/company/disclosures/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $disclosure = CompanyDisclosure::with(['module', 'clarifications', 'currentVersion'])
                ->findOrFail($id);

            $this->authorize('view', $disclosure);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'disclosure' => [
                        'id' => $disclosure->id,
                        'status' => $disclosure->status,
                        'completion_percentage' => $disclosure->completion_percentage,
                        'disclosure_data' => $disclosure->disclosure_data,
                        'attachments' => $disclosure->attachments,
                        'version_number' => $disclosure->version_number,
                        'is_locked' => $disclosure->is_locked,
                        'submitted_at' => $disclosure->submitted_at,
                        'approved_at' => $disclosure->approved_at,
                        'rejected_at' => $disclosure->rejected_at,
                        'rejection_reason' => $disclosure->rejection_reason,
                        'submission_notes' => $disclosure->submission_notes,
                        'draft_edit_history' => $disclosure->draft_edit_history,
                    ],
                    'module' => [
                        'id' => $disclosure->module->id,
                        'code' => $disclosure->module->code,
                        'name' => $disclosure->module->name,
                        'tier' => $disclosure->module->tier,
                        'json_schema' => $disclosure->module->json_schema,
                        'description' => $disclosure->module->description,
                    ],
                    'clarifications' => $disclosure->clarifications->map(fn($c) => [
                        'id' => $c->id,
                        'question_subject' => $c->question_subject,
                        'question_body' => $c->question_body,
                        'question_type' => $c->question_type,
                        'asked_at' => $c->asked_at,
                        'field_path' => $c->field_path,
                        'status' => $c->status,
                        'priority' => $c->priority,
                        'is_blocking' => $c->is_blocking,
                        'due_date' => $c->due_date,
                        'answer_body' => $c->answer_body,
                        'answered_at' => $c->answered_at,
                        'resolution_notes' => $c->resolution_notes,
                    ]),
                    'permissions' => [
                        'can_edit' => auth()->user()->can('update', $disclosure),
                        'can_submit' => auth()->user()->can('submit', $disclosure),
                        'can_report_error' => auth()->user()->can('reportError', $disclosure),
                        'can_attach_documents' => auth()->user()->can('attachDocuments', $disclosure),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch disclosure details', [
                'disclosure_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch disclosure',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create or update disclosure draft
     *
     * POST /api/company/disclosures
     *
     * Body:
     * {
     *   "module_id": 1,
     *   "disclosure_data": {...},
     *   "edit_reason": "Updated revenue figures"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'module_id' => 'required|exists:disclosure_modules,id',
            'disclosure_data' => 'required|array',
            'edit_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $company = $user->company;

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not associated with any company',
                ], 403);
            }

            // Check if disclosure exists
            $existingDisclosure = CompanyDisclosure::where('company_id', $company->id)
                ->where('disclosure_module_id', $request->module_id)
                ->first();

            if ($existingDisclosure) {
                $this->authorize('update', $existingDisclosure);
            } else {
                $this->authorize('create', $company);
            }

            $disclosure = $this->disclosureService->saveDraft(
                $company,
                $request->module_id,
                $request->disclosure_data,
                $user->id,
                $request->edit_reason
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Draft saved successfully',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'status' => $disclosure->status,
                    'completion_percentage' => $disclosure->completion_percentage,
                    'can_submit' => $disclosure->completion_percentage === 100,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to save disclosure draft', [
                'user_id' => auth()->id(),
                'module_id' => $request->module_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save draft',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Submit disclosure for admin review
     *
     * POST /api/company/disclosures/{id}/submit
     *
     * Body:
     * {
     *   "submission_notes": "Optional notes for reviewer"
     * }
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'submission_notes' => 'nullable|string',
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
            $this->authorize('submit', $disclosure);

            $this->disclosureService->submitForReview(
                $disclosure,
                auth()->id(),
                $request->submission_notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Disclosure submitted for review',
                'data' => [
                    'disclosure_id' => $disclosure->id,
                    'status' => $disclosure->fresh()->status,
                    'submitted_at' => $disclosure->fresh()->submitted_at,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to submit disclosure', [
                'disclosure_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit disclosure',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Report error in approved disclosure
     *
     * POST /api/company/disclosures/{id}/report-error
     *
     * Body:
     * {
     *   "error_description": "Revenue figure was incorrect",
     *   "corrected_data": {...},
     *   "correction_reason": "Accounting error discovered during audit"
     * }
     *
     * CRITICAL: Does NOT overwrite approved data
     */
    public function reportError(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'error_description' => 'required|string',
            'corrected_data' => 'required|array',
            'correction_reason' => 'required|string',
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
            $this->authorize('reportError', $disclosure);

            $newDraft = $this->disclosureService->reportErrorInApprovedDisclosure(
                $disclosure,
                auth()->id(),
                $request->error_description,
                $request->corrected_data,
                $request->correction_reason
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Error reported successfully. A new draft has been created with your corrections.',
                'data' => [
                    'original_disclosure_id' => $disclosure->id,
                    'new_draft_id' => $newDraft->id,
                    'new_draft_status' => $newDraft->status,
                    'completion_percentage' => $newDraft->completion_percentage,
                    'message' => 'Admin has been notified. The original approved data is preserved.',
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to report error', [
                'disclosure_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to report error',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Answer clarification
     *
     * POST /api/company/clarifications/{id}/answer
     *
     * Body:
     * {
     *   "answer_body": "Here is our explanation...",
     *   "supporting_documents": [...]
     * }
     */
    public function answerClarification(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answer_body' => 'required|string',
            'supporting_documents' => 'nullable|array',
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
            $this->authorize('answerClarification', $clarification);

            $this->disclosureService->answerClarification(
                $clarification,
                auth()->id(),
                $request->answer_body,
                $request->supporting_documents
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Clarification answered successfully',
                'data' => [
                    'clarification_id' => $clarification->id,
                    'status' => $clarification->fresh()->status,
                    'answered_at' => $clarification->fresh()->answered_at,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Failed to answer clarification', [
                'clarification_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to answer clarification',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
