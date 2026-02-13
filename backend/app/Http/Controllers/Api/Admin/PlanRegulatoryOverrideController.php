<?php
// V-CONTRACT-HARDENING-005: Admin controller for regulatory overrides
// Provides CRUD operations for plan regulatory overrides with full audit trail.

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanRegulatoryOverride;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PlanRegulatoryOverrideController extends Controller
{
    /**
     * List all regulatory overrides (optionally filtered by plan)
     */
    public function index(Request $request)
    {
        $query = PlanRegulatoryOverride::with(['plan:id,name,slug', 'approvedByAdmin:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->input('plan_id'));
        }

        if ($request->has('active_only') && $request->boolean('active_only')) {
            $query->active();
        }

        if ($request->has('scope')) {
            $query->forScope($request->input('scope'));
        }

        return $query->paginate($request->input('per_page', 20));
    }

    /**
     * Get a single regulatory override
     */
    public function show(PlanRegulatoryOverride $override)
    {
        return $override->load([
            'plan:id,name,slug',
            'approvedByAdmin:id,name,email',
            'revokedByAdmin:id,name,email',
        ]);
    }

    /**
     * Create a new regulatory override
     *
     * This is a HIGH-RISK operation that requires:
     * - Valid regulatory reference
     * - Explicit reason
     * - Admin authentication
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'override_scope' => ['required', Rule::in([
                PlanRegulatoryOverride::SCOPE_PROGRESSIVE,
                PlanRegulatoryOverride::SCOPE_MILESTONE,
                PlanRegulatoryOverride::SCOPE_CONSISTENCY,
                PlanRegulatoryOverride::SCOPE_WELCOME,
                PlanRegulatoryOverride::SCOPE_REFERRAL,
                PlanRegulatoryOverride::SCOPE_MULTIPLIER_CAP,
                PlanRegulatoryOverride::SCOPE_GLOBAL_RATE,
                PlanRegulatoryOverride::SCOPE_FULL,
            ])],
            'override_payload' => ['required', 'array'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'regulatory_reference' => ['required', 'string', 'min:5', 'max:255'],
            'effective_from' => ['required', 'date', 'after_or_equal:today'],
            'expires_at' => ['nullable', 'date', 'after:effective_from'],
        ]);

        // Verify admin is authenticated
        $admin = auth()->user();
        if (!$admin || !$admin instanceof \App\Models\Admin) {
            return response()->json([
                'message' => 'Regulatory overrides require admin authentication.',
            ], 403);
        }

        // Load plan to include in audit log
        $plan = Plan::findOrFail($validated['plan_id']);

        // Create the override
        $override = PlanRegulatoryOverride::create([
            'plan_id' => $validated['plan_id'],
            'override_scope' => $validated['override_scope'],
            'override_payload' => $validated['override_payload'],
            'reason' => $validated['reason'],
            'regulatory_reference' => $validated['regulatory_reference'],
            'approved_by_admin_id' => $admin->id,
            'effective_from' => $validated['effective_from'],
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        Log::info('Regulatory override created', [
            'override_id' => $override->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'scope' => $override->override_scope,
            'regulatory_reference' => $override->regulatory_reference,
            'approved_by_admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'effective_from' => $override->effective_from,
            'expires_at' => $override->expires_at,
        ]);

        return response()->json([
            'message' => 'Regulatory override created successfully.',
            'override' => $override->load('plan:id,name,slug', 'approvedByAdmin:id,name,email'),
            'audit' => [
                'action' => 'regulatory_override_created',
                'admin_id' => $admin->id,
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Revoke a regulatory override
     *
     * Overrides are never deleted - only revoked with audit trail.
     */
    public function revoke(Request $request, PlanRegulatoryOverride $override)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        // Verify admin is authenticated
        $admin = auth()->user();
        if (!$admin || !$admin instanceof \App\Models\Admin) {
            return response()->json([
                'message' => 'Regulatory override revocation requires admin authentication.',
            ], 403);
        }

        if ($override->revoked_at !== null) {
            return response()->json([
                'message' => 'This override has already been revoked.',
            ], 409);
        }

        $override->revoke($admin, $validated['reason']);

        Log::info('Regulatory override revoked', [
            'override_id' => $override->id,
            'plan_id' => $override->plan_id,
            'scope' => $override->override_scope,
            'revoked_by_admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'revocation_reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Regulatory override revoked successfully.',
            'override' => $override->fresh()->load('plan:id,name,slug', 'revokedByAdmin:id,name,email'),
            'audit' => [
                'action' => 'regulatory_override_revoked',
                'admin_id' => $admin->id,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get active overrides for a specific plan
     */
    public function activeForPlan(Plan $plan)
    {
        $overrides = $plan->regulatoryOverrides()
            ->active()
            ->with('approvedByAdmin:id,name,email')
            ->orderBy('effective_from', 'desc')
            ->get();

        return response()->json([
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'active_overrides' => $overrides,
            'count' => $overrides->count(),
        ]);
    }

    /**
     * Get bonus transactions that used a specific override
     */
    public function transactions(PlanRegulatoryOverride $override)
    {
        $transactions = $override->bonusTransactions()
            ->with(['user:id,name,email', 'subscription:id,subscription_code'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'override_id' => $override->id,
            'regulatory_reference' => $override->regulatory_reference,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Validate override payload structure before creation
     */
    public function validatePayload(Request $request)
    {
        $validated = $request->validate([
            'override_scope' => ['required', Rule::in([
                PlanRegulatoryOverride::SCOPE_PROGRESSIVE,
                PlanRegulatoryOverride::SCOPE_MILESTONE,
                PlanRegulatoryOverride::SCOPE_CONSISTENCY,
                PlanRegulatoryOverride::SCOPE_WELCOME,
                PlanRegulatoryOverride::SCOPE_REFERRAL,
                PlanRegulatoryOverride::SCOPE_MULTIPLIER_CAP,
                PlanRegulatoryOverride::SCOPE_GLOBAL_RATE,
                PlanRegulatoryOverride::SCOPE_FULL,
            ])],
            'override_payload' => ['required', 'array'],
        ]);

        $errors = $this->validatePayloadStructure(
            $validated['override_scope'],
            $validated['override_payload']
        );

        if (!empty($errors)) {
            return response()->json([
                'valid' => false,
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Payload structure is valid.',
        ]);
    }

    /**
     * Validate payload structure based on scope
     */
    private function validatePayloadStructure(string $scope, array $payload): array
    {
        $errors = [];

        switch ($scope) {
            case PlanRegulatoryOverride::SCOPE_PROGRESSIVE:
                if (isset($payload['rate']) && !is_numeric($payload['rate'])) {
                    $errors[] = 'rate must be numeric';
                }
                if (isset($payload['max_percentage']) && !is_numeric($payload['max_percentage'])) {
                    $errors[] = 'max_percentage must be numeric';
                }
                break;

            case PlanRegulatoryOverride::SCOPE_MILESTONE:
                foreach ($payload as $index => $milestone) {
                    if (!isset($milestone['month']) || !is_int($milestone['month'])) {
                        $errors[] = "milestone[$index].month must be an integer";
                    }
                    if (!isset($milestone['amount']) || !is_numeric($milestone['amount'])) {
                        $errors[] = "milestone[$index].amount must be numeric";
                    }
                }
                break;

            case PlanRegulatoryOverride::SCOPE_GLOBAL_RATE:
                if (!isset($payload['factor']) || !is_numeric($payload['factor'])) {
                    $errors[] = 'factor is required and must be numeric';
                }
                if (isset($payload['factor']) && ($payload['factor'] < 0 || $payload['factor'] > 10)) {
                    $errors[] = 'factor must be between 0 and 10';
                }
                break;

            case PlanRegulatoryOverride::SCOPE_MULTIPLIER_CAP:
                if (!isset($payload['max_multiplier']) || !is_numeric($payload['max_multiplier'])) {
                    $errors[] = 'max_multiplier is required and must be numeric';
                }
                break;
        }

        return $errors;
    }
}
