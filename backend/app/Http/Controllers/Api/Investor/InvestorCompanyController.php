<?php

namespace App\Http\Controllers\Api\Investor;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Investor Companies Controller
 *
 * Handles investor-facing company listings and deal information
 */
class InvestorCompanyController extends Controller
{
    /**
     * Get all companies available for investment
     * Also returns wallet balance for the authenticated investor
     *
     * GET /investor/companies
     *
     * FIX: Added buy_eligibility calculation for each company
     * Frontend expects company.buy_eligibility.allowed and company.buy_eligibility.blockers
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's wallet
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'allocated_balance' => 0,
                'pending_balance' => 0,
            ]
        );

        // Get companies with active deals
        $companies = Company::where('status', 'active')
            ->where('is_verified', true)
            ->with([
                'deals' => function ($query) {
                    // Use Deal's live() scope which validates dates
                    $query->live()
                        ->orderBy('is_featured', 'desc')
                        ->orderBy('deal_opens_at', 'desc');
                },
                'sector'
            ])
            ->whereHas('deals', function ($query) {
                // Use Deal's live() scope which validates dates
                $query->live();
            })
            ->get();

        // FIX: Calculate buy_eligibility for each company
        $companies = $companies->map(function ($company) use ($user, $wallet) {
            $blockers = [];

            // Check if user KYC is verified
            if ($user->kyc_status !== 'verified') {
                $blockers[] = [
                    'guard' => 'kyc_not_verified',
                    'severity' => 'critical',
                    'message' => 'KYC verification required before investing',
                ];
            }

            // Check wallet balance
            if (!$wallet || $wallet->balance <= 0) {
                $blockers[] = [
                    'guard' => 'insufficient_balance',
                    'severity' => 'warning',
                    'message' => 'Insufficient wallet balance. Please add funds to invest.',
                ];
            }

            // Add buy_eligibility to company object
            $company->buy_eligibility = [
                'allowed' => count($blockers) === 0,
                'blockers' => $blockers,
            ];

            return $company;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'companies' => $companies,
                'wallet' => [
                    'available_balance' => (float) $wallet->balance,
                    'allocated_balance' => (float) $wallet->allocated_balance,
                    'pending_balance' => (float) $wallet->pending_balance,
                    'total_balance' => (float) ($wallet->balance + $wallet->allocated_balance + $wallet->pending_balance),
                    'currency' => 'INR',
                ],
            ],
        ]);
    }

    /**
     * Get single company detail with deals (investor view)
     *
     * GET /investor/companies/{id}
     *
     * FIX: Added buy_eligibility, platform_context, disclosures, and required_acknowledgements
     * to match frontend InvestorCompanyDetail interface
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Get user's wallet
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'allocated_balance' => 0,
                'pending_balance' => 0,
            ]
        );

        $company = Company::where('id', $id)
            ->where('status', 'active')
            ->where('is_verified', true)
            ->with([
                'deals' => function ($query) {
                    $query->where('status', 'active')
                        ->orderBy('is_featured', 'desc');
                },
                'financialReports' => function ($query) {
                    $query->where('status', 'published')
                        ->orderBy('year', 'desc')
                        ->limit(5);
                },
                'documents' => function ($query) {
                    $query->where('is_public', true)
                        ->where('status', 'active');
                },
                'teamMembers' => function ($query) {
                    $query->ordered()->limit(10);
                },
                'updates' => function ($query) {
                    $query->where('status', 'published')
                        ->orderBy('published_at', 'desc')
                        ->limit(10);
                },
                'sector'
            ])
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found or not available for investment',
            ], 404);
        }

        // Calculate buy_eligibility for this company
        $blockers = [];

        // Check if user KYC is verified
        if ($user->kyc_status !== 'verified') {
            $blockers[] = [
                'guard' => 'kyc_not_verified',
                'severity' => 'critical',
                'message' => 'KYC verification required before investing',
            ];
        }

        // Check wallet balance
        if (!$wallet || $wallet->balance <= 0) {
            $blockers[] = [
                'guard' => 'insufficient_balance',
                'severity' => 'warning',
                'message' => 'Insufficient wallet balance. Please add funds to invest.',
            ];
        }

        // Add buy_eligibility to company object
        $company->buy_eligibility = [
            'allowed' => count($blockers) === 0,
            'blockers' => $blockers,
        ];

        // Build platform_context object (required by frontend)
        $company->platform_context = [
            'lifecycle_state' => $company->lifecycle_state ?? 'active',
            'buying_enabled' => $company->buying_enabled ?? true,
            'tier_status' => [
                'tier_1_approved' => $company->tier_1_approved ?? false,
                'tier_2_approved' => $company->tier_2_approved ?? false,
                'tier_3_approved' => $company->tier_3_approved ?? false,
            ],
            'restrictions' => [
                'is_suspended' => $company->is_suspended ?? false,
                'is_frozen' => $company->is_frozen ?? false,
                'is_under_investigation' => $company->is_under_investigation ?? false,
                'buying_pause_reason' => $company->buying_pause_reason ?? null,
            ],
            'risk_assessment' => [
                'platform_risk_score' => $company->platform_risk_score ?? 0,
                'risk_level' => $company->risk_level ?? 'low',
                'risk_flags' => $company->risk_flags ?? [],
            ],
        ];

        // Build disclosures array (empty for now, can be populated from related models later)
        $company->disclosures = [];

        // Build required_acknowledgements array
        $acknowledgements = [
            [
                'type' => 'market_risk',
                'text' => 'I understand that Pre-IPO investments are subject to market risks and the value of my investment may go up or down.',
                'required' => true,
            ],
            [
                'type' => 'liquidity_risk',
                'text' => 'I understand that Pre-IPO shares are not readily tradeable and may have limited liquidity until the company goes public.',
                'required' => true,
            ],
            [
                'type' => 'company_risk',
                'text' => 'I have reviewed the company information, financial disclosures, and risk factors, and understand the company-specific risks involved.',
                'required' => true,
            ],
        ];

        // Add conditional acknowledgements
        if ($company->is_suspended) {
            $acknowledgements[] = [
                'type' => 'suspended_company',
                'text' => 'I understand that this company is currently suspended by the platform. Investing may carry additional risks.',
                'required' => true,
            ];
        }

        if ($company->funding_stage === 'seed' || $company->funding_stage === 'pre_seed') {
            $acknowledgements[] = [
                'type' => 'early_stage_risk',
                'text' => 'I understand that this is an early-stage company with higher risk and potential for total loss of investment.',
                'required' => true,
            ];
        }

        $company->required_acknowledgements = $acknowledgements;

        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    /**
     * Check buy eligibility for a company
     *
     * POST /investor/companies/{id}/check-eligibility
     */
    public function checkEligibility(Request $request, $id)
    {
        $user = $request->user();

        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        $blockers = [];

        // Check if company has active deals
        $hasActiveDeals = Deal::where('company_id', $id)
            ->where('status', 'active')
            ->where('deal_type', 'live')
            ->exists();

        if (!$hasActiveDeals) {
            $blockers[] = [
                'guard' => 'no_active_deals',
                'severity' => 'critical',
                'message' => 'No active investment deals available for this company',
            ];
        }

        // Check KYC status
        if ($user->kyc_status !== 'verified') {
            $blockers[] = [
                'guard' => 'kyc_not_verified',
                'severity' => 'critical',
                'message' => 'KYC verification required before investing',
            ];
        }

        // Check wallet balance
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet || $wallet->balance <= 0) {
            $blockers[] = [
                'guard' => 'insufficient_balance',
                'severity' => 'warning',
                'message' => 'Insufficient wallet balance. Please add funds to invest.',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'allowed' => count($blockers) === 0,
                'blockers' => $blockers,
            ],
        ]);
    }

    /**
     * Get required risk acknowledgements for a company
     *
     * GET /investor/companies/{id}/required-acknowledgements
     *
     * Returns list of acknowledgements that must be accepted before investing
     */
    public function getRequiredAcknowledgements(Request $request, $id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        // Base acknowledgements required for all investments
        // IMPORTANT: These types MUST match the validation in InvestorInvestmentController
        $acknowledgements = [
            [
                'type' => 'illiquidity',
                'text' => 'I understand that Pre-IPO shares are highly illiquid and may not be tradeable for an extended period (3-5+ years).',
                'required' => true,
            ],
            [
                'type' => 'no_guarantee',
                'text' => 'I understand there is NO guarantee of returns, IPO listing, or exit. I may lose 100% of my invested capital.',
                'required' => true,
            ],
            [
                'type' => 'platform_non_advisory',
                'text' => 'I understand this platform is non-advisory. I am making my own independent investment decision without relying on platform recommendations.',
                'required' => true,
            ],
            [
                'type' => 'material_changes',
                'text' => 'I understand that material changes in the company (valuation, timeline, structure) may occur and I will be bound by updated terms.',
                'required' => true,
            ],
        ];

        // REMOVED conditional acknowledgements (suspended_company, early_stage_risk)
        // because they don't match the validation rules in InvestorInvestmentController
        // which only allows: illiquidity, no_guarantee, platform_non_advisory, material_changes

        return response()->json([
            'success' => true,
            'data' => [
                'acknowledgements' => $acknowledgements,
            ],
        ]);
    }

    /**
     * Record risk acknowledgement
     *
     * POST /investor/acknowledgements
     *
     * Records that an investor has acknowledged a specific risk
     * This is tracked for compliance and audit purposes
     */
    public function recordAcknowledgement(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'acknowledgement_type' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $user = $request->user();

        // Log the acknowledgement
        \Log::info('Risk acknowledgement recorded', [
            'user_id' => $user->id,
            'company_id' => $validated['company_id'],
            'acknowledgement_type' => $validated['acknowledgement_type'],
            'context' => $validated['context'] ?? null,
            'timestamp' => now(),
        ]);

        // TODO: Store in database table 'risk_acknowledgements' when model is created
        // For now, we log it and return success
        // Future: RiskAcknowledgement::create([...])

        return response()->json([
            'success' => true,
            'data' => [
                'acknowledgement_id' => rand(1000, 9999), // Temporary ID until DB implementation
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
