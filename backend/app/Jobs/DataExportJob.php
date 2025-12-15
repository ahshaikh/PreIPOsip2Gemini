<?php

namespace App\Jobs;

use App\Models\DataExportJob as DataExportModel;
use App\Models\ActivityLog;
use App\Services\ReportService;
use App\Exports\DynamicTableExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class DataExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $exportJob;
    protected $startDate;
    protected $endDate;

    /**
     * Create a new job instance.
     *
     * @param DataExportModel $exportJob The DB record tracking this job
     * @param string $startDate
     * @param string $endDate
     */
    public function __construct(DataExportModel $exportJob, $startDate, $endDate)
    {
        $this->exportJob = $exportJob;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        try {
            // Update status to processing so user sees "Generating..."
            $this->exportJob->update(['status' => 'processing']);

            $type = $this->exportJob->report_type;
            $format = $this->exportJob->format;
            $start = $this->startDate;
            $end = $this->endDate;

            $data = collect([]);
            $headings = [];
            $title = strtoupper(str_replace('-', ' ', $type)) . ' REPORT';
            $fileName = "reports/export_{$this->exportJob->id}_{$type}_" . date('Y-m-d_His') . ".{$format}";

            // --- DATA GATHERING (The logic moved from Controller) ---
            switch ($type) {
                case 'gst':
                    $headings = ['Payment ID', 'Date', 'User', 'Amount', 'Taxable', 'GST', 'State'];
                    $data = $reportService->getGstReportData($start, $end);
                    break;

                case 'tds':
                    $headings = ['ID', 'Date', 'User', 'PAN', 'Gross', 'TDS', 'Net'];
                    $data = $reportService->getTdsReportData($start, $end);
                    break;

                case 'p-and-l':
                    $headings = ['Category', 'Value', 'Notes'];
                    $pl = $reportService->getFinancialSummary($start, $end);
                    $data = collect([
                        ['Revenue', $pl['revenue'], 'Total Sales'],
                        ['Expenses', $pl['expenses'], 'Bonus/Costs'],
                        ['Profit', $pl['profit'], 'Net']
                    ]);
                    break;

                // ADDED: The AML Logic (Restored)
                case 'aml':
                    $headings = ['Payment ID', 'User', 'Email', 'User Created', 'Amount', 'Payment Date'];
                    // Using the optimized getAmlReport from Service
                    $data = $reportService->getAmlReport()->map(function($p) {
                        return [
                            'id' => $p->id,
                            'user' => $p->user->username ?? 'N/A',
                            'email' => $p->user->email ?? 'N/A',
                            'user_created' => $p->user->created_at->toDateTimeString(),
                            'amount' => $p->amount,
                            'date' => $p->paid_at->toDateTimeString()
                        ];
                    });
                    break;

                case 'audit-trail':
                    $headings = ['Time', 'User', 'Action', 'Description', 'IP'];
                    // Direct model access for logs (High Volume)
                    $data = ActivityLog::with('user:id,username')
                        ->whereBetween('created_at', [$start, $end])
                        ->latest()
                        ->get()
                        ->map(fn($l) => [
                            $l->created_at, $l->user->username ?? 'Sys', $l->action, $l->description, $l->ip_address
                        ]);
                    break;

                default:
                    throw new \Exception("Unknown report type: $type");
            }

            // --- FILE GENERATION (Heavy CPU Task) ---
            $content = null;

            if ($format === 'pdf') {
                // PDF generation is the main cause of timeouts
                $pdf = Pdf::loadView('reports.generic_pdf', [
                    'headings' => $headings, 'data' => $data, 'title' => $title
                ]);
                $content = $pdf->output();
            } elseif ($format === 'csv') {
                $content = Excel::raw(new DynamicTableExport($data, $headings), \Maatwebsite\Excel\Excel::CSV);
            } else {
                $content = Excel::raw(new DynamicTableExport($data, $headings), \Maatwebsite\Excel\Excel::XLSX);
            }

            // --- STORAGE ---
            Storage::disk('public')->put($fileName, $content);

            // --- COMPLETION ---
            $this->exportJob->update([
                'status' => 'completed',
                'file_path' => $fileName, // Frontend can download via /storage/$fileName
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Async Export Job Failed: " . $e->getMessage());
            $this->exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}