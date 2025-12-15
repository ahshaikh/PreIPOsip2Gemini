<?php
// V-FINAL-1730-486 (Created) | V-FINAL-1730-500 (AML Report Added) | V-FIX-MODULE-18-PERFORMANCE (Gemini)

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
        // ADDED: Optimized query selection to reduce memory footprint.
        return Payment::with(['user:id,username', 'user.profile:user_id,state'])
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->latest()
            ->get()
            ->map(function($p) {
                // DELETED: $taxable = (float)$p->amount / 1.18;
                // REASON: Hardcoded 18% tax rate is risky for future compliance.
                
                // ADDED: Dynamic tax calculation.
                // Checks if 'tax_rate' column exists (future-proofing), defaults to 18% if not.
                $rate = $p->tax_rate ?? 18; 
                $divisor = 1 + ($rate / 100);
                
                $taxable = (float)$p->amount / $divisor;
                $gst = (float)$p->amount - $taxable;
                
                return [
                    'id' => $p->id,
                    'date' => $p->paid_at->format('Y-m-d'),
                    'user' => $p->user->username ?? 'N/A', // Added null check safety
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
                    'user' => $w->user->username ?? 'N/A',
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
        /* * DELETED: The In-Memory Aggregation Logic.
         * * Old Code:
         * $subscriptions = Subscription::whereBetween('start_date', [$start, $end])
         * ->with(['plan', 'user'])
         * ->get(); // <-- CRITICAL: Loads ALL rows into RAM. 50k rows = crash.
         * * $byPlan = $subscriptions->groupBy('plan_id')->map(...);
         * * REASON: This O(N) memory approach fails at scale.
         */

        // ADDED: Database-level Aggregation (O(1) Memory)
        
        // 1. Get Summary Totals
        $summary = Subscription::whereBetween('start_date', [$start, $end])
            ->selectRaw('SUM(amount) as total_invested, COUNT(DISTINCT user_id) as total_investors, AVG(amount) as average_investment')
            ->first();

        // 2. Group By Plan using SQL
        $byPlan = Subscription::whereBetween('start_date', [$start, $end])
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('plans.name as plan_name, COUNT(*) as count, SUM(subscriptions.amount) as total, AVG(subscriptions.amount) as avg')
            ->groupBy('plans.id', 'plans.name')
            ->get();

        // 3. Group By Status using SQL
        $byStatus = Subscription::whereBetween('start_date', [$start, $end])
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('status')
            ->get();

        return [
            'summary' => [
                'total_invested' => (float) ($summary->total_invested ?? 0),
                'total_investors' => (int) ($summary->total_investors ?? 0),
                'average_investment' => (float) ($summary->average_investment ?? 0),
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

        /*
         * DELETED: The N+1 Query Loop.
         * * Old Code:
         * $monthlyFlow = Payment::...->get()->map(function ($item) {
         * $withdrawals = Withdrawal::where(...)->sum(...); // <-- Query inside loop!
         * $bonuses = BonusTransaction::where(...)->sum(...); // <-- Query inside loop!
         * });
         * * REASON: Iterating through months and running queries inside causes severe DB thrashing.
         */

        // ADDED: Fetch aggregates separately and merge in PHP.
        // This reduces queries from (Months * 3) to just 3 queries total.

        // 1. Get Monthly Inflows
        $inflowsByMonth = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        // 2. Get Monthly Withdrawals
        $withdrawalsByMonth = Withdrawal::where('status', 'completed')
            ->whereBetween('updated_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as month, SUM(net_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        // 3. Get Monthly Bonuses
        $bonusesByMonth = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->where('amount', '>', 0)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(net_amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        // 4. Merge Data
        $monthlyTrend = collect();
        $allMonths = $inflowsByMonth->keys()
            ->merge($withdrawalsByMonth->keys())
            ->merge($bonusesByMonth->keys())
            ->unique()
            ->sort();

        foreach ($allMonths as $month) {
            $in = $inflowsByMonth[$month] ?? 0;
            $out = ($withdrawalsByMonth[$month] ?? 0) + ($bonusesByMonth[$month] ?? 0);
            
            $monthlyTrend->push([
                'month' => $month,
                'inflow' => (float)$in,
                'outflow' => (float)$out,
                'net' => (float)($in - $out)
            ]);
        }

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
            'monthly_trend' => $monthlyTrend->values(),
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
        
        // Optimized: Fetch counts in single query via Group By
        $stats = DB::table('user_kyc')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $submitted = ($stats['submitted'] ?? 0) + ($stats['verified'] ?? 0) + ($stats['rejected'] ?? 0);
        $verified = $stats['verified'] ?? 0;
        $pending = $stats['pending'] ?? 0;
        $rejected = $stats['rejected'] ?? 0;

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
            ->whereNotNull('state')
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
        /*
         * DELETED: In-Memory Aggregation.
         * $subscriptions = Subscription::whereBetween('start_date', [$start, $end])->get();
         * $byStatus = $subscriptions->groupBy('status')...
         */

        // ADDED: SQL Group By for Status breakdown
        $byStatus = Subscription::whereBetween('start_date', [$start, $end])
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_value')
            ->groupBy('status')
            ->get();

        // ADDED: SQL Counts for KPI calculation (Efficient)
        $totalSubscriptions = Subscription::whereBetween('start_date', [$start, $end])->count();
        
        $activeCount = Subscription::whereBetween('start_date', [$start, $end])
            ->where('status', 'active')
            ->count();
            
        $churnedCount = Subscription::whereBetween('start_date', [$start, $end])
            ->where('status', 'cancelled')
            ->count();
            
        // Note: Simple renewal rate proxy logic preserved
        $expiredCount = Subscription::whereBetween('start_date', [$start, $end])
            ->where('end_date', '<', now())
            ->count();

        $renewalRate = ($expiredCount > 0) ? ($activeCount / $expiredCount) * 100 : 0;
        $churnRate = ($totalSubscriptions > 0) ? ($churnedCount / $totalSubscriptions) * 100 : 0;

        return [
            'summary' => [
                'total_subscriptions' => $totalSubscriptions,
                'active_subscriptions' => $activeCount,
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
        /*
         * DELETED: In-Memory Aggregation.
         * $payments = Payment::whereBetween('created_at', [$start, $end])->get();
         */

        // ADDED: SQL Aggregation
        $stats = Payment::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status'); // Returns key-value pair of status => total

        $counts = Payment::whereBetween('created_at', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $collected = $stats['paid'] ?? 0;
        $pending = $stats['pending'] ?? 0;
        $failedCount = $counts['failed'] ?? 0;
        $totalCount = $counts->sum();

        $collectionRate = $totalCount > 0 ? (($counts['paid'] ?? 0) / $totalCount) * 100 : 0;

        $byMethod = Payment::whereBetween('created_at', [$start, $end])
            ->where('status', 'paid')
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get();

        return [
            'summary' => [
                'total_collected' => (float) $collected,
                'total_pending' => (float) $pending,
                'failed_count' => (int) $failedCount,
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
        // Optimized: DB-level aggregation for top referrers
        $topReferrers = User::whereNotNull('referred_by')
            ->whereBetween('created_at', [$start, $end])
            ->select('referred_by', DB::raw('COUNT(*) as referrals_count'))
            ->groupBy('referred_by')
            ->orderByDesc('referrals_count')
            ->limit(10)
            ->with('referrer:id,username,email')
            ->get()
            ->map(function ($row) {
                return [
                    'referrer' => $row->referrer->username ?? 'N/A',
                    'email' => $row->referrer->email ?? 'N/A',
                    'referrals_count' => $row->referrals_count,
                ];
            });

        $totalReferrals = User::whereNotNull('referred_by')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $referralBonuses = BonusTransaction::where('bonus_type', 'referral')
            ->whereBetween('created_at', [$start, $end])
            ->sum('net_amount');

        $uniqueReferrers = User::whereNotNull('referred_by')
            ->whereBetween('created_at', [$start, $end])
            ->distinct('referred_by')
            ->count('referred_by');

        return [
            'summary' => [
                'total_referrals' => $totalReferrals,
                'total_bonus_paid' => (float) $referralBonuses,
                'unique_referrers' => $uniqueReferrers,
            ],
            'top_referrers' => $topReferrers,
        ];
    }

    /**
     * Portfolio Performance Report
     */
    public function getPortfolioPerformanceReport()
    {
        /*
         * DELETED: In-Memory Aggregation.
         * $subscriptions = Subscription::where('status', 'active')->with(['plan', 'user'])->get();
         */

        // ADDED: SQL Aggregation
        $summary = Subscription::where('status', 'active')
            ->selectRaw('SUM(amount) as total_portfolio_value, COUNT(DISTINCT user_id) as total_investors, COUNT(DISTINCT plan_id) as total_plans')
            ->first();

        $byPlan = Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('plans.name as plan_name, COUNT(*) as investors_count, SUM(subscriptions.amount) as total_value, AVG(subscriptions.amount) as avg_investment')
            ->groupBy('plans.id', 'plans.name')
            ->get();

        return [
            'summary' => [
                'total_portfolio_value' => (float) ($summary->total_portfolio_value ?? 0),
                'total_investors' => (int) ($summary->total_investors ?? 0),
                'total_plans' => (int) ($summary->total_plans ?? 0),
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