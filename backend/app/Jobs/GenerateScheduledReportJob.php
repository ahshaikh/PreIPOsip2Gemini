<?php

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Models\ReportRun;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scheduledReport;
    protected $reportRun;

    public function __construct(ScheduledReport $scheduledReport, ReportRun $reportRun)
    {
        $this->scheduledReport = $scheduledReport;
        $this->reportRun = $reportRun;
    }

    public function handle(ReportService $reportService)
    {
        try {
            $this->reportRun->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Get report data
            $data = $this->generateReportData($reportService);

            // Generate file
            $filePath = $this->generateReportFile($data);

            // Send email to recipients
            $this->sendReportEmail($filePath);

            // Update report run
            $this->reportRun->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => Storage::size($filePath),
                'completed_at' => now(),
            ]);

            // Update scheduled report
            $this->scheduledReport->update([
                'last_run_at' => now(),
                'next_run_at' => $this->calculateNextRun(),
            ]);

        } catch (\Exception $e) {
            Log::error('Report generation failed: ' . $e->getMessage());

            $this->reportRun->update([
                'status' => 'failed',
                'error_details' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
                'completed_at' => now(),
            ]);
        }
    }

    private function generateReportData(ReportService $reportService)
    {
        $params = $this->scheduledReport->parameters ?? [];
        $start = $params['start_date'] ?? now()->subDays(30);
        $end = $params['end_date'] ?? now();

        return match($this->scheduledReport->report_type) {
            'revenue' => $reportService->getRevenueReport($start, $end),
            'bonus' => $reportService->getBonusDistributionReport($start, $end),
            'investment' => $reportService->getInvestmentAnalysisReport($start, $end),
            'cash_flow' => $reportService->getCashFlowStatement($start, $end),
            'transaction' => $reportService->getTransactionReport($start, $end),
            'kyc' => $reportService->getKycCompletionReport(),
            'demographics' => $reportService->getUserDemographicsReport(),
            'subscription' => $reportService->getSubscriptionPerformanceReport($start, $end),
            'payment_collection' => $reportService->getPaymentCollectionReport($start, $end),
            'referral' => $reportService->getReferralPerformanceReport($start, $end),
            'portfolio' => $reportService->getPortfolioPerformanceReport(),
            'sebi' => $reportService->getSebiComplianceReport($start, $end),
            'p_and_l' => $reportService->getFinancialSummary($start, $end),
            'tds' => $reportService->getTdsReportData($start, $end),
            'aml' => $reportService->getAmlReport(),
            default => [],
        };
    }

    private function generateReportFile($data)
    {
        $filename = "reports/{$this->scheduledReport->id}/" . now()->format('Y-m-d_His') . ".{$this->scheduledReport->format}";

        if ($this->scheduledReport->format === 'csv') {
            $csv = $this->convertToCSV($data);
            Storage::put($filename, $csv);
        } elseif ($this->scheduledReport->format === 'pdf') {
            // PDF generation would go here
            $pdf = \PDF::loadView('reports.scheduled', ['data' => $data]);
            Storage::put($filename, $pdf->output());
        } else {
            // Excel format
            Storage::put($filename, json_encode($data));
        }

        return $filename;
    }

    private function convertToCSV($data)
    {
        // Simple CSV conversion
        $csv = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $csv .= "$key:\n";
                foreach ($value as $row) {
                    if (is_array($row)) {
                        $csv .= implode(',', $row) . "\n";
                    }
                }
            }
        }
        return $csv;
    }

    private function sendReportEmail($filePath)
    {
        foreach ($this->scheduledReport->recipients as $recipient) {
            Mail::to($recipient)->send(
                new \App\Mail\ScheduledReportMail($this->scheduledReport, $filePath)
            );
        }
    }

    private function calculateNextRun()
    {
        return match($this->scheduledReport->frequency) {
            'daily' => now()->addDay()->setTime(6, 0),
            'weekly' => now()->next('Monday')->setTime(6, 0),
            'monthly' => now()->addMonth()->firstOfMonth()->setTime(6, 0),
            'quarterly' => now()->addMonths(3)->firstOfQuarter()->setTime(6, 0),
        };
    }
}
