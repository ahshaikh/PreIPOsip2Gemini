<?php
// V-FINAL-1730-486 (Created) | V-FINAL-1730-500 (AML Report Added)

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * FSD-REPORT-001: P&L Summary
     */
    public function getFinancialSummary($start, $end)
    {
        $revenue = Payment::where('status', 'paid')->whereBetween('paid_at', [$start, $end])->sum('amount');
        $expenses = BonusTransaction::where('amount', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
        $profit = $revenue - $expenses;
        
        return ['revenue' => (float) $revenue, 'expenses' => (float) $expenses, 'profit' => (float) $profit];
    }

    /**
     * FSD-REPORT-005: User Growth (Cohort)
     */
    public function getUserGrowth($start, $end)
    {
        return User::whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->orderBy('date')
            ->get();
    }

    /**
     * FSD-REPORT-006: Churn Analysis
     */
    public function getRetentionMetrics($start, $end)
    {
        $usersAtStart = User::where('created_at', '<', $start)->count();
        $usersLost = Subscription::where('status', 'cancelled')
                         ->whereBetween('cancelled_at', [$start, $end])
                         ->count();
        
        $churnRate = ($usersAtStart > 0) ? ($usersLost / $usersAtStart) * 100 : 0;
        
        return ['churn_rate' => round($churnRate, 2), 'users_lost' => $usersLost];
    }

    /**
     * FSD-REPORT-017: GST Report Data
     */
    public function getGstReportData($start, $end)
    {
        return Payment::with(['user:id,username', 'user.profile:user_id,state'])
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->latest()
            ->get()
            ->map(function($p) {
                $taxable = (float)$p->amount / 1.18;
                $gst = (float)$p->amount - $taxable;
                return [
                    'id' => $p->id,
                    'date' => $p->paid_at->format('Y-m-d'),
                    'user' => $p->user->username,
                    'amount' => $p->amount,
                    'taxable' => number_format($taxable, 2),
                    'gst' => number_format($gst, 2),
                    'state' => $p->user->profile->state ?? 'N/A'
                ];
            });
    }

    /**
     * FSD-REPORT-017: TDS Report Data
     */
    public function getTdsReportData($start, $end)
    {
        // Now reads from the pre-calculated column
        return Withdrawal::with(['user:id,username', 'user.kyc:user_id,pan_number'])
            ->where('status', 'completed')
            ->where('tds_deducted', '>', 0) // Only those with TDS
            ->whereBetween('updated_at', [$start, $end])
            ->latest()
            ->get()
            ->map(function($w) {
                return [
                    'id' => $w->id,
                    'date' => $w->updated_at->format('Y-m-d'),
                    'user' => $w->user->username,
                    'pan' => $w->user->kyc->pan_number ?? 'N/A',
                    'gross_amount' => $w->amount,
                    'tds_deducted' => $w->tds_deducted,
                    'net_paid' => $w->net_amount
                ];
            });
    }

    /**
     * NEW: FSD-SYS-116: AML Report (Suspicious Payments)
     */
    public function getAmlReport()
    {
        // Flag if payment > ₹50K and user registered < 7 days ago
        $threshold = (float) setting('fraud_amount_threshold', 50000);
        $days = (int) setting('fraud_new_user_days', 7);

        $flagged = Payment::where('status', 'paid')
            ->where('amount', '>=', $threshold)
            ->whereHas('user', function ($q) use ($days) {
                $q->where('created_at', '>=', now()->subDays($days));
            })
            ->with('user:id,username,email,created_at')
            ->get();

        return $flagged;
    }

    /**
     * Revenue Report - Comprehensive breakdown by source
     */
    public function getRevenueReport($start, $end)
    {
        $paymentRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('SUM(amount) as total, COUNT(*) as count, AVG(amount) as avg_amount')
            ->first();

        $revenueByGateway = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('gateway, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('gateway')
            ->get();

        $revenueByPlan = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->join('subscriptions', 'payments.subscription_id', '=', 'subscriptions.id')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('plans.name as plan_name, SUM(payments.amount) as total, COUNT(payments.id) as count')
            ->groupBy('plans.id', 'plans.name')
            ->get();

        $dailyRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'total_revenue' => (float) ($paymentRevenue->total ?? 0),
                'total_transactions' => (int) ($paymentRevenue->count ?? 0),
                'average_transaction' => (float) ($paymentRevenue->avg_amount ?? 0),
            ],
            'by_gateway' => $revenueByGateway,
            'by_plan' => $revenueByPlan,
            'daily_trend' => $dailyRevenue,
        ];
    }

    /**
     * Bonus Distribution Report
     */
    public function getBonusDistributionReport($start, $end)
    {
        $totalBonuses = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('SUM(gross_amount) as total_gross, SUM(net_amount) as total_net, COUNT(*) as count')
            ->first();

        $bonusByType = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('bonus_type, SUM(net_amount) as total, COUNT(*) as count')
            ->groupBy('bonus_type')
            ->get();

        $bonusByUser = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->select('user_id', DB::raw('SUM(net_amount) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('user:id,username,email')
            ->get();

        $dailyDistribution = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(net_amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'total_gross' => (float) ($totalBonuses->total_gross ?? 0),
                'total_net' => (float) ($totalBonuses->total_net ?? 0),
                'total_count' => (int) ($totalBonuses->count ?? 0),
            ],
            'by_type' => $bonusByType,
            'top_recipients' => $bonusByUser,
            'daily_trend' => $dailyDistribution,
        ];
    }

    /**
     * Investment Analysis Report
     */
    public function getInvestmentAnalysisReport($start, $end)
    {
        $subscriptions = Subscription::whereBetween('start_date', [$start, $end])
            ->with(['plan', 'user'])
            ->get();

        $totalInvested = $subscriptions->sum('amount');
        $avgInvestment = $subscriptions->avg('amount');

        $byPlan = $subscriptions->groupBy('plan_id')->map(function ($group) {
            return [
                'plan_name' => $group->first()->plan->name ?? 'N/A',
                'count' => $group->count(),
                'total' => $group->sum('amount'),
                'avg' => $group->avg('amount'),
            ];
        })->values();

        $byStatus = $subscriptions->groupBy('status')->map(function ($group, $status) {
            return [
                'status' => $status,
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        })->values();

        return [
            'summary' => [
                'total_invested' => (float) $totalInvested,
                'total_investors' => $subscriptions->unique('user_id')->count(),
                'average_investment' => (float) $avgInvestment,
            ],
            'by_plan' => $byPlan,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Cash Flow Statement
     */
    public function getCashFlowStatement($start, $end)
    {
        // Inflows
        $paymentInflows = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        // Outflows
        $withdrawalOutflows = Withdrawal::where('status', 'completed')
            ->whereBetween('updated_at', [$start, $end])
            ->sum('net_amount');

        $bonusOutflows = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->where('amount', '>', 0)
            ->sum('net_amount');

        $netCashFlow = $paymentInflows - ($withdrawalOutflows + $bonusOutflows);

        $monthlyFlow = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount) as inflow')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) use ($start, $end) {
                $monthStart = Carbon::parse($item->month . '-01')->startOfMonth();
                $monthEnd = Carbon::parse($item->month . '-01')->endOfMonth();

                $withdrawals = Withdrawal::where('status', 'completed')
                    ->whereBetween('updated_at', [$monthStart, $monthEnd])
                    ->sum('net_amount');

                $bonuses = BonusTransaction::whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('amount', '>', 0)
                    ->sum('net_amount');

                return [
                    'month' => $item->month,
                    'inflow' => (float) $item->inflow,
                    'outflow' => (float) ($withdrawals + $bonuses),
                    'net' => (float) ($item->inflow - ($withdrawals + $bonuses)),
                ];
            });

        return [
            'summary' => [
                'total_inflows' => (float) $paymentInflows,
                'total_outflows' => (float) ($withdrawalOutflows + $bonusOutflows),
                'net_cash_flow' => (float) $netCashFlow,
            ],
            'breakdown' => [
                'payment_revenue' => (float) $paymentInflows,
                'withdrawals_paid' => (float) $withdrawalOutflows,
                'bonuses_paid' => (float) $bonusOutflows,
            ],
            'monthly_trend' => $monthlyFlow,
        ];
    }

    /**
     * Transaction Report
     */
    public function getTransactionReport($start, $end)
    {
        $transactions = DB::table('transactions')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('type, status, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type', 'status')
            ->get();

        $dailyTransactions = DB::table('transactions')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, type, COUNT(*) as count')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        return [
            'summary' => $transactions,
            'daily_trend' => $dailyTransactions,
        ];
    }

    /**
     * KYC Completion Report
     */
    public function getKycCompletionReport()
    {
        $total = User::count();
        $submitted = DB::table('user_kyc')->whereIn('status', ['submitted', 'verified', 'rejected'])->count();
        $verified = DB::table('user_kyc')->where('status', 'verified')->count();
        $pending = DB::table('user_kyc')->where('status', 'pending')->count();
        $rejected = DB::table('user_kyc')->where('status', 'rejected')->count();

        $completionRate = $total > 0 ? ($submitted / $total) * 100 : 0;
        $verificationRate = $submitted > 0 ? ($verified / $submitted) * 100 : 0;

        $dailySubmissions = DB::table('user_kyc')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'total_users' => $total,
                'kyc_submitted' => $submitted,
                'kyc_verified' => $verified,
                'kyc_pending' => $pending,
                'kyc_rejected' => $rejected,
                'completion_rate' => round($completionRate, 2),
                'verification_rate' => round($verificationRate, 2),
            ],
            'daily_submissions' => $dailySubmissions,
        ];
    }

    /**
     * User Demographics Report
     */
    public function getUserDemographicsReport()
    {
        $byState = DB::table('user_profiles')
            ->select('state', DB::raw('COUNT(*) as count'))
            ->groupBy('state')
            ->orderByDesc('count')
            ->get();

        $byAgeGroup = DB::table('user_profiles')
            ->selectRaw("
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                    ELSE '60+'
                END as age_group,
                COUNT(*) as count
            ")
            ->whereNotNull('date_of_birth')
            ->groupBy('age_group')
            ->get();

        $byGender = DB::table('user_profiles')
            ->select('gender', DB::raw('COUNT(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        return [
            'by_state' => $byState,
            'by_age_group' => $byAgeGroup,
            'by_gender' => $byGender,
        ];
    }

    /**
     * Subscription Performance Report
     */
    public function getSubscriptionPerformanceReport($start, $end)
    {
        $subscriptions = Subscription::whereBetween('start_date', [$start, $end])->get();

        $byStatus = $subscriptions->groupBy('status')->map(function ($group, $status) {
            return [
                'status' => $status,
                'count' => $group->count(),
                'total_value' => $group->sum('amount'),
            ];
        })->values();

        $renewalRate = $subscriptions->where('status', 'active')->count() /
            max($subscriptions->where('end_date', '<', now())->count(), 1) * 100;

        $churnedSubscriptions = $subscriptions->where('status', 'cancelled')->count();
        $churnRate = $subscriptions->count() > 0 ? ($churnedSubscriptions / $subscriptions->count()) * 100 : 0;

        return [
            'summary' => [
                'total_subscriptions' => $subscriptions->count(),
                'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
                'renewal_rate' => round($renewalRate, 2),
                'churn_rate' => round($churnRate, 2),
            ],
            'by_status' => $byStatus,
        ];
    }

    /**
     * Payment Collection Report
     */
    public function getPaymentCollectionReport($start, $end)
    {
        $payments = Payment::whereBetween('created_at', [$start, $end])->get();

        $collected = $payments->where('status', 'paid')->sum('amount');
        $pending = $payments->where('status', 'pending')->sum('amount');
        $failed = $payments->where('status', 'failed')->count();

        $collectionRate = $payments->count() > 0 ?
            ($payments->where('status', 'paid')->count() / $payments->count()) * 100 : 0;

        $byMethod = $payments->where('status', 'paid')
            ->groupBy('method')
            ->map(function ($group, $method) {
                return [
                    'method' => $method ?? 'N/A',
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            })->values();

        return [
            'summary' => [
                'total_collected' => (float) $collected,
                'total_pending' => (float) $pending,
                'failed_count' => (int) $failed,
                'collection_rate' => round($collectionRate, 2),
            ],
            'by_method' => $byMethod,
        ];
    }

    /**
     * Referral Performance Report
     */
    public function getReferralPerformanceReport($start, $end)
    {
        $referrals = User::whereNotNull('referred_by')
            ->whereBetween('created_at', [$start, $end])
            ->with('referrer:id,username,email')
            ->get();

        $topReferrers = $referrals->groupBy('referred_by')
            ->map(function ($group) {
                return [
                    'referrer' => $group->first()->referrer->username ?? 'N/A',
                    'email' => $group->first()->referrer->email ?? 'N/A',
                    'referrals_count' => $group->count(),
                ];
            })
            ->sortByDesc('referrals_count')
            ->take(10)
            ->values();

        $referralBonuses = BonusTransaction::where('bonus_type', 'referral')
            ->whereBetween('created_at', [$start, $end])
            ->sum('net_amount');

        return [
            'summary' => [
                'total_referrals' => $referrals->count(),
                'total_bonus_paid' => (float) $referralBonuses,
                'unique_referrers' => $referrals->unique('referred_by')->count(),
            ],
            'top_referrers' => $topReferrers,
        ];
    }

    /**
     * Portfolio Performance Report
     */
    public function getPortfolioPerformanceReport()
    {
        $subscriptions = Subscription::where('status', 'active')
            ->with(['plan', 'user'])
            ->get();

        $totalPortfolioValue = $subscriptions->sum('amount');

        $byPlan = $subscriptions->groupBy('plan_id')->map(function ($group) {
            $plan = $group->first()->plan;
            return [
                'plan_name' => $plan->name ?? 'N/A',
                'investors_count' => $group->count(),
                'total_value' => $group->sum('amount'),
                'avg_investment' => $group->avg('amount'),
            ];
        })->values();

        return [
            'summary' => [
                'total_portfolio_value' => (float) $totalPortfolioValue,
                'total_investors' => $subscriptions->unique('user_id')->count(),
                'total_plans' => $subscriptions->unique('plan_id')->count(),
            ],
            'by_plan' => $byPlan,
        ];
    }

    /**
     * SEBI Compliance Report
     */
    public function getSebiComplianceReport($start, $end)
    {
        $totalInvestors = User::role('user')->count();
        $kycVerified = DB::table('user_kyc')->where('status', 'verified')->count();
        $kycComplianceRate = $totalInvestors > 0 ? ($kycVerified / $totalInvestors) * 100 : 0;

        $largeTransactions = Payment::where('status', 'paid')
            ->where('amount', '>=', 1000000) // ₹10L+ transactions
            ->whereBetween('paid_at', [$start, $end])
            ->with('user:id,username,email')
            ->get();

        $suspiciousPatterns = $this->getAmlReport();

        return [
            'kyc_compliance' => [
                'total_investors' => $totalInvestors,
                'kyc_verified' => $kycVerified,
                'compliance_rate' => round($kycComplianceRate, 2),
            ],
            'large_transactions' => $largeTransactions->map(function ($payment) {
                return [
                    'payment_id' => $payment->id,
                    'user' => $payment->user->username ?? 'N/A',
                    'amount' => $payment->amount,
                    'date' => $payment->paid_at->format('Y-m-d'),
                ];
            }),
            'suspicious_patterns_count' => $suspiciousPatterns->count(),
        ];
    }
}