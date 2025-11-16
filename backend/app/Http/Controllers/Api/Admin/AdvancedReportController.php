<?php
// V-FINAL-1730-221 (Created) | V-FINAL-1730-309 (P&L, GST, TDS Logic Added) | V-FINAL-1730-409 (Refactored) | V-FINAL-1730-487 (Service Integrated)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService; // <-- IMPORT
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicTableExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvancedReportController extends Controller
{
    protected $service;
    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Get User Growth & Churn Analytics
     */
    public function getUserAnalytics(Request $request)
    {
        $start = $request->query('start_date', now()->subYear());
        $end = $request->query('end_date', now());

        return response()->json([
            'acquisition_chart' => $this->service->getUserGrowth($start, $end),
            'retention_metrics' => $this->service->getRetentionMetrics($start, $end),
        ]);
    }

    /**
     * Get Product Performance
     */
    public function getProductPerformance(Request $request)
    {
        // This query is simple enough to live here for now
        $performance = Product::withSum('bulkPurchases as total_inventory_value', 'total_value_received')
            ->withSum('bulkPurchases as remaining_inventory_value', 'value_remaining')
            ->get()
            ->map(function ($product) {
                $total = (float)($product->total_inventory_value ?? 0);
                $remaining = (float)($product->remaining_inventory_value ?? 0);
                $sold = $total - $remaining;
                $soldPercentage = ($total > 0) ? ($sold / $total) * 100 : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sector' => $product->sector,
                    'total_inventory' => $total,
                    'sold_value' => $sold,
                    'sold_percentage' => round($soldPercentage, 2),
                    'investor_count' => DB::table('user_investments')->where('product_id', $product->id)->distinct('user_id')->count()
                ];
            });
            
        return response()->json($performance);
    }

    /**
     * EXPORT HANDLER: GST, TDS, P&L, etc.
     */
    public function exportReport(Request $request)
    {
        $type = $request->query('report_type');
        $format = $request->query('format', 'csv');
        $start = $request->query('start_date', now()->startOfYear());
        $end = $request->query('end_date', now()->endOfYear());
        
        $data = collect([]);
        $headings = [];
        $title = strtoupper(str_replace('-', ' ', $type)) . ' REPORT';
        $fileName = "report_{$type}_" . date('Y-m-d');

        switch ($type) {
            case 'gst':
                $headings = ['Payment ID', 'Date', 'User', 'Total Amount', 'Taxable Value', 'GST (18%)', 'State'];
                $data = $this->service->getGstReportData($start, $end);
                break;

            case 'tds':
                $headings = ['Withdrawal ID', 'Date', 'User', 'PAN', 'Gross Amount', 'TDS Deducted', 'Net Paid'];
                $data = $this->service->getTdsReportData($start, $end);
                break;
                
            case 'p-and-l':
                $headings = ['Category', 'Value (INR)', 'Notes'];
                $pl = $this->service->getFinancialSummary($start, $end);
                $data = collect([
                    ['Revenue (Sales)', number_format($pl['revenue'], 2), 'Total Payments Received'],
                    ['Expenses (Bonuses)', number_format($pl['expenses'], 2), 'Customer Rewards Paid'],
                    ['NET PROFIT', number_format($pl['profit'], 2), 'Revenue - Expenses']
                ]);
                break;
            

	    // AML Report Export 
            case 'aml':
                $headings = ['Payment ID', 'User', 'Email', 'User Created', 'Amount', 'Payment Date'];
                $data = $this.service->getAmlReport()->map(function($p) {
                    return [
                        'id' => $p->id,
                        'user' => $p->user->username,
                        'email' => $p->user->email,
                        'user_created' => $p->user->created_at->toDateTimeString(),
                        'amount' => $p->amount,
                        'date' => $p->paid_at->toDateTimeString()
                    ];
                });
                break;
            // -----------------------------
            case 'audit-trail':
                $headings = ['Time', 'User', 'Action', 'Description', 'IP'];
                $data = \App\Models\ActivityLog::with('user:id,username')
                        ->whereBetween('created_at', [$start, $end])
                        ->latest()
                        ->get()
                        ->map(fn($log) => [
                            'time' => $log->created_at->toDateTimeString(),
                            'user' => $log->user?->username ?? 'System',
                            'action' => $log->action,
                            'desc' => $log->description,
                            'ip' => $log->ip_address
                        ]);
                break;
        }

        // 2. Generate PDF
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.generic_pdf', [
                'headings' => $headings, 
                'data' => $data, 
                'title' => $title
            ]);
            return $pdf->download("$fileName.pdf");
        }

        // 3. Generate Excel/CSV
        return Excel::download(new DynamicTableExport($data, $headings), "$fileName.csv");
    }
}