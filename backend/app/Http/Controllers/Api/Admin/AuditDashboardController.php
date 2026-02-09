<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserInvestment;
use App\Models\User;
use App\Models\Company;
use App\Models\AuditLog;
use App\Models\CompanyDisclosure;
use Illuminate\Http\Request;

/**
 * Audit Dashboard Controller
 *
 * Provides list endpoints for the audit dashboard pages.
 */
class AuditDashboardController extends Controller
{
    /**
     * List investments for audit review
     * GET /api/v1/audit/investments
     */
    public function listInvestments(Request $request)
    {
        $query = UserInvestment::with(['user:id,name,email', 'product:id,name,slug'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $investments = $query->paginate($request->get('per_page', 20));

        // Transform to AuditListItem format
        $investments->getCollection()->transform(function ($investment) {
            return [
                'id' => $investment->id,
                'type' => 'investment',
                'title' => $investment->product->name ?? 'Unknown Product',
                'subtitle' => ($investment->user->name ?? 'Unknown') . ' - ₹' . number_format($investment->value_allocated, 2),
                'timestamp' => $investment->created_at->toIso8601String(),
                'status' => $investment->status,
            ];
        });

        return response()->json($investments);
    }

    /**
     * List investors for audit review
     * GET /api/v1/audit/investors
     */
    public function listInvestors(Request $request)
    {
        $query = User::withCount('userInvestments')
            ->withSum('userInvestments', 'value_allocated')
            ->having('user_investments_count', '>', 0)
            ->orderBy('user_investments_sum_value_allocated', 'desc');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $investors = $query->paginate($request->get('per_page', 20));

        // Transform to AuditListItem format
        $investors->getCollection()->transform(function ($investor) {
            return [
                'id' => $investor->id,
                'type' => 'investor',
                'title' => $investor->name ?? 'Unknown',
                'subtitle' => $investor->user_investments_count . ' investments - ₹' . number_format($investor->user_investments_sum_value_allocated ?? 0, 2),
                'timestamp' => $investor->created_at->toIso8601String(),
                'status' => $investor->status ?? 'active',
            ];
        });

        return response()->json($investors);
    }

    /**
     * List companies for audit review
     * GET /api/v1/audit/companies
     */
    public function listCompanies(Request $request)
    {
        $query = Company::withCount('deals')
            ->orderBy('updated_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('legal_name', 'like', "%{$search}%");
            });
        }

        $companies = $query->paginate($request->get('per_page', 20));

        // Transform to AuditListItem format
        $companies->getCollection()->transform(function ($company) {
            return [
                'id' => $company->id,
                'type' => 'company',
                'title' => $company->name ?? $company->legal_name ?? 'Unknown',
                'subtitle' => ($company->deals_count ?? 0) . ' deals',
                'timestamp' => $company->updated_at->toIso8601String(),
                'status' => $company->status ?? 'active',
            ];
        });

        return response()->json($companies);
    }

    /**
     * Show company governance audit details
     * GET /api/v1/audit/companies/{id}
     */
    public function showCompany(Company $company)
    {
        // Current governance state
        $currentState = [
            'lifecycle_state' => $company->status ?? 'active',
            'disclosure_tier' => $company->disclosure_tier ?? 1,
            'is_suspended' => $company->is_suspended ?? false,
            'is_frozen' => $company->is_frozen ?? false,
            'is_under_investigation' => $company->is_under_investigation ?? false,
            'buying_enabled' => $company->buying_enabled ?? true,
        ];

        // Disclosure history
        $disclosureHistory = CompanyDisclosure::where('company_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($disclosure) {
                return [
                    'module_name' => $disclosure->module_name ?? $disclosure->type ?? 'General',
                    'version_number' => $disclosure->version ?? 1,
                    'status' => $disclosure->status,
                    'submitted_at' => $disclosure->submitted_at?->toIso8601String() ?? $disclosure->created_at->toIso8601String(),
                    'reviewed_at' => $disclosure->reviewed_at?->toIso8601String(),
                    'reviewed_by' => $disclosure->reviewer?->name ?? null,
                    'reason' => $disclosure->review_notes ?? null,
                ];
            });

        // Tier transitions (from company versions if available)
        $tierTransitions = [];
        if (method_exists($company, 'versions')) {
            $versions = $company->versions()->orderBy('created_at', 'desc')->limit(20)->get();
            foreach ($versions as $version) {
                if (isset($version->changes['disclosure_tier'])) {
                    $tierTransitions[] = [
                        'from_tier' => $version->changes['disclosure_tier']['old'] ?? 0,
                        'to_tier' => $version->changes['disclosure_tier']['new'] ?? 0,
                        'transitioned_at' => $version->created_at->toIso8601String(),
                        'authority' => 'platform',
                        'reason' => $version->reason ?? 'Tier update',
                    ];
                }
            }
        }

        // Risk flag history (placeholder - expand based on actual model)
        $riskFlagHistory = [];

        // Platform actions (from audit logs related to this company)
        $platformActions = AuditLog::where('target_type', 'Company')
            ->where('target_id', $company->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->action ?? 'action',
                    'description' => $log->description ?? '',
                    'occurred_at' => $log->created_at->toIso8601String(),
                    'authority' => 'platform',
                    'actor_id' => $log->admin_id ?? $log->actor_id,
                    'actor_name' => $log->actor_name ?? 'Admin',
                ];
            });

        // Investor impact - count investors and investments for this company's deals
        $dealIds = $company->deals()->pluck('id');
        $investorImpact = [
            'total_investors' => UserInvestment::whereIn('product_id', $dealIds)->distinct('user_id')->count('user_id'),
            'total_investments' => UserInvestment::whereIn('product_id', $dealIds)->count(),
            'total_invested_amount' => number_format(UserInvestment::whereIn('product_id', $dealIds)->sum('value_allocated') ?? 0, 2),
        ];

        return response()->json([
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name ?? $company->legal_name,
                    'slug' => $company->slug ?? '',
                    'created_at' => $company->created_at->toIso8601String(),
                ],
                'current_state' => $currentState,
                'disclosure_history' => $disclosureHistory,
                'tier_transitions' => $tierTransitions,
                'risk_flag_history' => $riskFlagHistory,
                'platform_actions' => $platformActions,
                'investor_impact' => $investorImpact,
            ],
        ]);
    }

    /**
     * List admin actions for audit review
     * GET /api/v1/audit/actions
     */
    public function listActions(Request $request)
    {
        $query = AuditLog::orderBy('created_at', 'desc');

        // Filter by admin
        if ($request->has('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filter by action type
        if ($request->has('action_type')) {
            $query->where('action', $request->action_type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%");
            });
        }

        $actions = $query->paginate($request->get('per_page', 20));

        // Transform to AuditListItem format
        $actions->getCollection()->transform(function ($action) {
            return [
                'id' => $action->id,
                'type' => 'admin_action',
                'title' => $action->action ?? 'Unknown Action',
                'subtitle' => ($action->actor_name ?? 'Unknown Admin') . ' - ' . ($action->module ?? 'N/A'),
                'timestamp' => $action->created_at->toIso8601String(),
                'status' => null,
            ];
        });

        return response()->json($actions);
    }

    /**
     * Show admin action detail
     * GET /api/v1/audit/actions/{id}
     */
    public function showAction(int $action)
    {
        $action = AuditLog::findOrFail($action);

        return response()->json([
            'data' => [
                'id' => $action->id,
                'action_type' => $action->action ?? 'unknown',
                'action_description' => $action->description ?? '',
                'performed_at' => $action->created_at->toIso8601String(),

                'actor' => [
                    'admin_id' => $action->admin_id ?? $action->actor_id ?? 0,
                    'admin_name' => $action->actor_name ?? 'Unknown',
                    'admin_email' => $action->actor_email ?? '',
                ],

                'target' => [
                    'type' => strtolower($action->target_type ?? 'system'),
                    'id' => $action->target_id ?? 0,
                    'name' => $action->target_name ?? null,
                ],

                'decision' => [
                    'authority' => 'platform',
                    'reason' => $action->description ?? '',
                    'is_mandatory_reason' => false,
                ],

                'state_change' => $action->old_values || $action->new_values ? [
                    'before' => $action->old_values ?? [],
                    'after' => $action->new_values ?? [],
                    'changed_fields' => array_keys(array_merge($action->old_values ?? [], $action->new_values ?? [])),
                ] : null,

                'impact_captured' => null,

                'is_immutable' => true,
                'snapshot_hash' => md5(json_encode([
                    $action->id,
                    $action->action,
                    $action->created_at,
                ])),
            ],
        ]);
    }
}
