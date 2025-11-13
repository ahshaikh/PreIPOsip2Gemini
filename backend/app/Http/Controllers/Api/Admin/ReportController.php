<?php
// V-FINAL-1730-206 (Added CSV Exports)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Get the main financial summary for the admin dashboard.
     */
    public function getFinancialSummary(Request $request)
    {
        // 1. Get KPI Stats
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $totalUsers = User::role('user')->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalInvestments = Payment::where('status', 'paid')->count();

        // 2. Get Daily Revenue Trend (Last 30 days)
        $dailyRevenue = Payment::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get([
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(amount) as total')
            ]);

        return response()->json([
            'kpis' => [
                'total_revenue' => $totalRevenue,
                'total_users' => $totalUsers,
                'active_subscriptions' => $activeSubscriptions,
                'total_investments' => $totalInvestments,
            ],
            'charts' => [
                'daily_revenue' => $dailyRevenue,
            ]
        ]);
    }

    /**
     * Export TDS/GST Report as CSV
     */
    public function exportComplianceReport(Request $request)
    {
        $type = $request->query('type', 'gst'); // 'gst' or 'tds'
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}_report.csv\"",
        ];

        $callback = function() use ($type) {
            $handle = fopen('php://output', 'w');
            
            if ($type === 'gst') {
                // GST Header
                fputcsv($handle, ['Payment ID', 'Date', 'User', 'GSTIN', 'Total Amount', 'Taxable Value', 'GST (18%)', 'State']);
                
                Payment::with(['user.profile'])->where('status', 'paid')->chunk(100, function($payments) use ($handle) {
                    foreach ($payments as $p) {
                        // Assuming amount is inclusive of 18% GST
                        $taxable = $p->amount / 1.18;
                        $gst = $p->amount - $taxable;
                        
                        fputcsv($handle, [
                            $p->id,
                            $p->paid_at,
                            $p->user->username,
                            'Unregistered', // Or fetch from user profile if collected
                            $p->amount,
                            number_format($taxable, 2),
                            number_format($gst, 2),
                            $p->user->profile->state ?? 'N/A'
                        ]);
                    }
                });
            } else {
                // TDS Header (For withdrawals/bonuses)
                fputcsv($handle, ['User', 'PAN', 'Payout Type', 'Date', 'Gross Amount', 'TDS Deducted', 'Net Paid']);
                
                // TODO: Add logic for TDS on withdrawals if applicable
                // For now, just headers as per FSD requirements
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}