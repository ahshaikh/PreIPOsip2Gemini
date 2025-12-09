<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Models\ScheduledReport;
use App\Models\ReportRun;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AdvancedReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get Revenue Report
     * GET /api/v1/admin/reports/revenue
     */
    public function revenueReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getRevenueReport($start, $end));
    }

    /**
     * Get Bonus Distribution Report
     * GET /api/v1/admin/reports/bonus-distribution
     */
    public function bonusDistributionReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getBonusDistributionReport($start, $end));
    }

    /**
     * Get Investment Analysis Report
     * GET /api/v1/admin/reports/investment-analysis
     */
    public function investmentAnalysisReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getInvestmentAnalysisReport($start, $end));
    }

    /**
     * Get Cash Flow Statement
     * GET /api/v1/admin/reports/cash-flow
     */
    public function cashFlowStatement(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getCashFlowStatement($start, $end));
    }

    /**
     * Get Transaction Report
     * GET /api/v1/admin/reports/transactions
     */
    public function transactionReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getTransactionReport($start, $end));
    }

    /**
     * Get KYC Completion Report
     * GET /api/v1/admin/reports/kyc-completion
     */
    public function kycCompletionReport()
    {
        return response()->json($this->reportService->getKycCompletionReport());
    }

    /**
     * Get User Demographics Report
     * GET /api/v1/admin/reports/user-demographics
     */
    public function userDemographicsReport()
    {
        return response()->json($this->reportService->getUserDemographicsReport());
    }

    /**
     * Get Subscription Performance Report
     * GET /api/v1/admin/reports/subscription-performance
     */
    public function subscriptionPerformanceReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getSubscriptionPerformanceReport($start, $end));
    }

    /**
     * Get Payment Collection Report
     * GET /api/v1/admin/reports/payment-collection
     */
    public function paymentCollectionReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getPaymentCollectionReport($start, $end));
    }

    /**
     * Get Referral Performance Report
     * GET /api/v1/admin/reports/referral-performance
     */
    public function referralPerformanceReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getReferralPerformanceReport($start, $end));
    }

    /**
     * Get Portfolio Performance Report
     * GET /api/v1/admin/reports/portfolio-performance
     */
    public function portfolioPerformanceReport()
    {
        return response()->json($this->reportService->getPortfolioPerformanceReport());
    }

    /**
     * Get SEBI Compliance Report
     * GET /api/v1/admin/reports/sebi-compliance
     */
    public function sebiComplianceReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $start = $validated['start_date'] ?? now()->subDays(30);
        $end = $validated['end_date'] ?? now();

        return response()->json($this->reportService->getSebiComplianceReport($start, $end));
    }

    // ============================================================
    // SCHEDULED REPORTS MANAGEMENT
    // ============================================================

    /**
     * List all scheduled reports
     * GET /api/v1/admin/scheduled-reports
     */
    public function listScheduledReports()
    {
        $reports = ScheduledReport::with(['creator:id,username', 'latestRun'])
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($reports);
    }

    /**
     * Create a scheduled report
     * POST /api/v1/admin/scheduled-reports
     */
    public function createScheduledReport(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|in:revenue,bonus,investment,cash_flow,transaction,kyc,demographics,subscription,payment_collection,referral,portfolio,sebi,p_and_l,tds,aml,audit_trail',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'parameters' => 'nullable|array',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,csv,excel',
        ]);

        // Calculate next run time based on frequency
        $nextRun = match($validated['frequency']) {
            'daily' => now()->addDay()->setTime(6, 0), // 6 AM next day
            'weekly' => now()->next('Monday')->setTime(6, 0),
            'monthly' => now()->addMonth()->firstOfMonth()->setTime(6, 0),
            'quarterly' => now()->addMonths(3)->firstOfQuarter()->setTime(6, 0),
        };

        $report = ScheduledReport::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'next_run_at' => $nextRun,
        ]);

        return response()->json([
            'message' => 'Scheduled report created successfully',
            'report' => $report,
        ], 201);
    }

    /**
     * Update a scheduled report
     * PUT /api/v1/admin/scheduled-reports/{report}
     */
    public function updateScheduledReport(Request $request, ScheduledReport $report)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'parameters' => 'nullable|array',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,csv,excel',
            'is_active' => 'required|boolean',
        ]);

        // Recalculate next run if frequency changed
        if (isset($validated['frequency']) && $validated['frequency'] !== $report->frequency) {
            $nextRun = match($validated['frequency']) {
                'daily' => now()->addDay()->setTime(6, 0),
                'weekly' => now()->next('Monday')->setTime(6, 0),
                'monthly' => now()->addMonth()->firstOfMonth()->setTime(6, 0),
                'quarterly' => now()->addMonths(3)->firstOfQuarter()->setTime(6, 0),
            };
            $validated['next_run_at'] = $nextRun;
        }

        $report->update($validated);

        return response()->json([
            'message' => 'Scheduled report updated successfully',
            'report' => $report,
        ]);
    }

    /**
     * Delete a scheduled report
     * DELETE /api/v1/admin/scheduled-reports/{report}
     */
    public function deleteScheduledReport(ScheduledReport $report)
    {
        $report->delete();

        return response()->json(['message' => 'Scheduled report deleted successfully']);
    }

    /**
     * Get report runs history
     * GET /api/v1/admin/scheduled-reports/{report}/runs
     */
    public function getReportRuns(ScheduledReport $report)
    {
        $runs = $report->runs()->orderByDesc('created_at')->paginate(20);

        return response()->json($runs);
    }

    /**
     * Manually trigger a scheduled report
     * POST /api/v1/admin/scheduled-reports/{report}/run
     */
    public function runScheduledReport(ScheduledReport $report)
    {
        // Create a report run record
        $run = ReportRun::create([
            'scheduled_report_id' => $report->id,
            'status' => 'pending',
            'started_at' => now(),
        ]);

        // Dispatch job to generate report
        \App\Jobs\GenerateScheduledReportJob::dispatch($report, $run);

        return response()->json([
            'message' => 'Report generation started',
            'run_id' => $run->id,
        ]);
    }

    // ============================================================
    // CUSTOM REPORT BUILDER
    // ============================================================

    /**
     * Custom Report Builder - Get available metrics
     * GET /api/v1/admin/reports/custom/metrics
     */
    public function getAvailableMetrics()
    {
        return response()->json([
            'metrics' => [
                ['id' => 'revenue_total', 'name' => 'Total Revenue', 'category' => 'financial'],
                ['id' => 'revenue_by_gateway', 'name' => 'Revenue by Gateway', 'category' => 'financial'],
                ['id' => 'revenue_by_plan', 'name' => 'Revenue by Plan', 'category' => 'financial'],
                ['id' => 'bonus_total', 'name' => 'Total Bonuses', 'category' => 'bonuses'],
                ['id' => 'bonus_by_type', 'name' => 'Bonuses by Type', 'category' => 'bonuses'],
                ['id' => 'user_count', 'name' => 'Total Users', 'category' => 'users'],
                ['id' => 'user_growth', 'name' => 'User Growth', 'category' => 'users'],
                ['id' => 'kyc_completion', 'name' => 'KYC Completion Rate', 'category' => 'compliance'],
                ['id' => 'subscription_active', 'name' => 'Active Subscriptions', 'category' => 'subscriptions'],
                ['id' => 'payment_success_rate', 'name' => 'Payment Success Rate', 'category' => 'payments'],
                ['id' => 'withdrawal_pending', 'name' => 'Pending Withdrawals', 'category' => 'withdrawals'],
                ['id' => 'cash_flow_net', 'name' => 'Net Cash Flow', 'category' => 'financial'],
            ],
            'filters' => [
                ['id' => 'date_range', 'name' => 'Date Range', 'type' => 'date_range'],
                ['id' => 'plan_id', 'name' => 'Plan', 'type' => 'select'],
                ['id' => 'gateway', 'name' => 'Payment Gateway', 'type' => 'select'],
                ['id' => 'status', 'name' => 'Status', 'type' => 'select'],
            ],
        ]);
    }

    /**
     * Custom Report Builder - Generate custom report
     * POST /api/v1/admin/reports/custom/generate
     */
    public function generateCustomReport(Request $request)
    {
        $validated = $request->validate([
            'metrics' => 'required|array',
            'metrics.*' => 'string',
            'filters' => 'nullable|array',
            'group_by' => 'nullable|string',
        ]);

        try {
            $data = $this->buildCustomReport($validated);

            return response()->json([
                'data' => $data,
                'generated_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Custom report generation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate report'], 500);
        }
    }

    /**
     * Helper to build custom reports based on selected metrics
     */
    private function buildCustomReport(array $config)
    {
        $result = [];
        $start = $config['filters']['start_date'] ?? now()->subDays(30);
        $end = $config['filters']['end_date'] ?? now();

        foreach ($config['metrics'] as $metric) {
            $result[$metric] = match($metric) {
                'revenue_total' => $this->reportService->getRevenueReport($start, $end)['summary'],
                'bonus_total' => $this->reportService->getBonusDistributionReport($start, $end)['summary'],
                'user_count' => \App\Models\User::count(),
                'kyc_completion' => $this->reportService->getKycCompletionReport()['summary'],
                'cash_flow_net' => $this->reportService->getCashFlowStatement($start, $end)['summary'],
                default => null,
            };
        }

        return $result;
    }
}
