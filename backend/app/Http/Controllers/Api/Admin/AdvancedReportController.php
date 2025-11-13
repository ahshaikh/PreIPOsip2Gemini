<?php
// V-FINAL-1730-221

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicTableExport;
use Barryvdh\DomPDF\Facade\Pdf;

class AdvancedReportController extends Controller
{
    /**
     * REPORT 1: User Growth & Churn Analysis
     */
    public function getUserAnalytics(Request $request)
    {
        // 1. Monthly Acquisitions (New Users)
        $acquisitions = User::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), 
            DB::raw('count(*) as count')
        )
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->limit(12)
        ->get();

        // 2. Churn Rate (Cancelled Subscriptions / Total Active at start of month)
        // Simplified calculation for V1
        $totalActive = Subscription::where('status', 'active')->count();
        $totalCancelled = Subscription::where('status', 'cancelled')->count();
        $churnRate = $totalActive > 0 ? ($totalCancelled / ($totalActive + $totalCancelled)) * 100 : 0;

        return response()->json([
            'acquisition_chart' => $acquisitions->reverse()->values(),
            'churn_rate' => round($churnRate, 2),
            'active_subscribers' => $totalActive,
            'cancelled_subscribers' => $totalCancelled
        ]);
    }

    /**
     * REPORT 2: Product Performance
     */
    public function getProductPerformance(Request $request)
    {
        $performance = Product::withSum('bulkPurchases as total_inventory_value', 'total_value_received')
            ->withSum('bulkPurchases as remaining_inventory_value', 'value_remaining')
            ->get()
            ->map(function ($product) {
                $total = $product->total_inventory_value ?? 0;
                $remaining = $product->remaining_inventory_value ?? 0;
                $sold = $total - $remaining;
                $soldPercentage = $total > 0 ? ($sold / $total) * 100 : 0;

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
     * EXPORT HANDLER: GST, TDS, User List, etc.
     * Supports .xlsx and .pdf
     */
    public function exportReport(Request $request)
    {
        $type = $request->query('report_type'); // 'gst', 'tds', 'users', 'products'
        $format = $request->query('format', 'xlsx'); // 'xlsx', 'pdf'
        
        $data = collect([]);
        $headings = [];
        $fileName = "report_{$type}_" . date('Y-m-d');

        // 1. Switch logic to build data based on report type
        switch ($type) {
            case 'gst':
                $headings = ['Payment ID', 'Date', 'User Name', 'Amount (Inc. GST)', 'Taxable Value', 'GST (18%)', 'State'];
                $data = Payment::with(['user.profile'])->where('status', 'paid')->latest()->get()->map(function($p) {
                    $taxable = $p->amount / 1.18;
                    return [
                        'id' => $p->id,
                        'date' => $p->paid_at->format('Y-m-d'),
                        'user' => $p->user->username,
                        'amount' => $p->amount,
                        'taxable' => number_format($taxable, 2),
                        'gst' => number_format($p->amount - $taxable, 2),
                        'state' => $p->user->profile->state ?? 'N/A'
                    ];
                });
                break;

            case 'tds':
                $headings = ['Withdrawal ID', 'Date', 'User', 'PAN', 'Gross Amount', 'TDS Deducted', 'Net Paid'];
                $data = DB::table('withdrawals')
                    ->join('users', 'withdrawals.user_id', '=', 'users.id')
                    ->leftJoin('user_kyc', 'users.id', '=', 'user_kyc.user_id')
                    ->where('withdrawals.status', 'completed')
                    ->select('withdrawals.id', 'withdrawals.created_at', 'users.username', 'user_kyc.pan_number', 'withdrawals.amount', 'withdrawals.fee', 'withdrawals.net_amount')
                    ->get();
                break;
                
            case 'products':
                $headings = ['Product', 'Sector', 'Total Inventory', 'Sold Value', 'Investors'];
                $data = $this->getProductPerformance($request)->original->map(function($p) {
                    return [
                        'name' => $p['name'],
                        'sector' => $p['sector'],
                        'total' => $p['total_inventory'],
                        'sold' => $p['sold_value'],
                        'investors' => $p['investor_count']
                    ];
                });
                break;
        }

        // 2. Generate PDF
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.generic_pdf', ['headings' => $headings, 'data' => $data, 'title' => strtoupper($type) . ' REPORT']);
            return $pdf->download("$fileName.pdf");
        }

        // 3. Generate Excel/CSV
        return Excel::download(new DynamicTableExport($data, $headings), "$fileName.xlsx");
    }
}