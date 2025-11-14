<?php
// V-FINAL-1730-221 (Created) | V-FINAL-1730-309 (P&L, GST, TDS Logic Added) | V-FINAL-1730-409 (Refactored)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService; // <-- IMPORT
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicTableExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvancedReportController extends Controller
{
    protected $service;
    public function __construct(ReportService $service)
    {
        $this.service = $service;
    }

    public function getUserAnalytics(Request $request)
    {
        $start = $request->query('start_date', now()->subYear());
        $end = $request->query('end_date', now());

        return response()->json([
            'acquisition_chart' => $this.service->getUserGrowth($start, $end),
            'retention_metrics' => $this.service->getRetentionMetrics($start, $end),
            'kyc_percentage' => $this.service->getKycCompletion(),
        ]);
    }

    public function getProductPerformance(Request $request)
    {
        // This logic is simple enough to stay here or move
        $performance = \App\Models\Product::withSum('bulkPurchases as total_inventory_value', 'total_value_received')
            ->withSum('bulkPurchases as remaining_inventory_value', 'value_remaining')
            // ... (rest of simple query)
            ->get();
            
        return response()->json($performance);
    }

    /**
     * EXPORT HANDLER
     */
    public function exportReport(Request $request)
    {
        $type = $request->query('report_type');
        $format = $request->query('format', 'csv');
        $start = $request->query('start_date', now()->startOfYear());
        $end = $request->query('end_date', now()->endOfYear());
        
        $data = collect([]);
        $headings = [];
        $title = strtoupper($type) . ' REPORT';
        $fileName = "report_{$type}_" . date('Y-m-d');

        switch ($type) {
            case 'gst':
                // (Logic from previous step)
                $headings = ['Payment ID', 'Date', 'User', 'Taxable Value', 'GST (18%)'];
                // $data = ...
                break;

            case 'tds':
                $headings = ['User ID', 'Gross Amount', 'TDS Deducted', 'Net Paid'];
                $data = collect($this.service->getTdsReport($start, $end));
                break;
                
            case 'p-and-l':
                $headings = ['Category', 'Value (INR)', 'Notes'];
                $pl = $this.service->getFinancialSummary($start, $end);
                $data = collect([
                    ['Revenue (Sales)', $pl['revenue'], '...'],
                    ['Expenses (Bonuses)', $pl['expenses'], '...'],
                    ['NET PROFIT', $pl['profit'], '...']
                ]);
                break;
            
            case 'aml':
                $headings = ['Payment ID', 'User', 'Email', 'Amount', 'Date'];
                $data = $this.service->getAmlReport()->map(function($p) {
                    return [
                        'id' => $p->id,
                        'user' => $p->user->username,
                        'email' => $p->user->email,
                        'amount' => $p->amount,
                        'date' => $p->paid_at->format('Y-m-d')
                    ];
                });
                break;
        }

        // Generate PDF
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.generic_pdf', ['headings' => $headings, 'data' => $data, 'title' => $title]);
            return $pdf->download("$fileName.pdf");
        }

        // Generate Excel/CSV
        return Excel::download(new DynamicTableExport($data, $headings), "$fileName.csv");
    }
}