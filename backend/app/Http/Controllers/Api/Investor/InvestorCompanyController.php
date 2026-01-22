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
     * FIX: Added buy_eligibility calculation just like index() method
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

        // Add suspended company acknowledgement if applicable
        if ($company->is_suspended) {
            $acknowledgements[] = [
                'type' => 'suspended_company',
                'text' => 'I understand that this company is currently suspended by the platform. Investing may carry additional risks.',
                'required' => true,
            ];
        }

        // Add early stage risk if applicable
        if ($company->funding_stage === 'seed' || $company->funding_stage === 'pre_seed') {
            $acknowledgements[] = [
                'type' => 'early_stage_risk',
                'text' => 'I understand that this is an early-stage company with higher risk and potential for total loss of investment.',
                'required' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'acknowledgements' => $acknowledgements,
            ],
        ]);
    }
}
