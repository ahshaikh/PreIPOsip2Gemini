<?php
// V-FINAL-1730-486 (Created)

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\BonusTransaction;
use App\Models\Withdrawal;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * This service centralizes all complex read-only queries for analytics
 * and compliance reporting.
 */
class ReportService
{
    /**
     * FSD-REPORT-001: P&L Summary
     */
    public function getFinancialSummary($start, $end)
    {
        $revenue = Payment::where('status', 'paid')->whereBetween('paid_at', [$start, $end])->sum('amount');
        
        // Expenses are all bonuses paid
        $expenses = BonusTransaction::where('amount', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
        
        $profit = $revenue - $expenses;
        
        return [
            'revenue' => (float) $revenue,
            'expenses' => (float) $expenses,
            'profit' => (float) $profit,
        ];
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
                // Assume 18% GST is included in the amount
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
        // TDS is on *Withdrawals* (FSD-FIN-005)
        return Withdrawal::with(['user:id,username', 'user.kyc:user_id,pan_number'])
            ->where('status', 'completed')
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
}