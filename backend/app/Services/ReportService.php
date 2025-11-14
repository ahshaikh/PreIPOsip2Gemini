<?php
// V-FINAL-1730-408 (Created)

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\BonusTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Test: test_financial_summary_calculates_profit
     */
    public function getFinancialSummary($start, $end)
    {
        $revenue = Payment::where('status', 'paid')->whereBetween('paid_at', [$start, $end])->sum('amount');
        $expenses = BonusTransaction::whereBetween('created_at', [$start, $end])->sum('amount'); // Simplified: Bonuses are main expense
        $profit = $revenue - $expenses;
        
        return ['revenue' => $revenue, 'expenses' => $expenses, 'profit' => $profit];
    }

    /**
     * Test: test_user_growth_report_calculates_correctly
     */
    public function getUserGrowth($start, $end)
    {
        return User::whereBetween('created_at', [$start, $end])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->get();
    }

    /**
     * Test: test_user_retention_report_calculates_churn
     */
    public function getRetentionMetrics($start, $end)
    {
        // Churn = (Users lost) / (Users at start)
        $usersAtStart = User::where('created_at', '<', $start)->count();
        $usersLost = User::where('status', 'cancelled') // Assuming we have this status
                         ->whereBetween('updated_at', [$start, $end])
                         ->count();
        
        $churnRate = ($usersAtStart > 0) ? ($usersLost / $usersAtStart) * 100 : 0;
        
        return ['churn_rate' => $churnRate, 'users_lost' => $usersLost];
    }

    /**
     * Test: test_kyc_completion_report_calculates_percentage
     */
    public function getKycCompletion()
    {
        $total = User::count();
        $verified = User::whereHas('kyc', fn($q) => $q->where('status', 'verified'))->count();
        
        return ($total > 0) ? ($verified / $total) * 100 : 0;
    }

    /**
     * Test: test_tds_calculation_applies_10_percent
     * Test: test_tds_exemption_for_amounts_below_10k
     */
    public function getTdsReport($start, $end)
    {
        $tdsThreshold = 10000;
        $tdsRate = 0.10; // 10%
        
        // Find users whose *total* bonuses in this period exceed the threshold
        $eligibleUsers = BonusTransaction::whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) as total_bonus'))
            ->having('total_bonus', '>', $tdsThreshold)
            ->get();
            
        $report = [];
        foreach ($eligibleUsers as $user) {
            $tdsDeducted = $user->total_bonus * $tdsRate;
            $report[] = [
                'user_id' => $user->user_id,
                'gross_amount' => $user->total_bonus,
                'tds_deducted' => $tdsDeducted,
                'net_paid' => $user->total_bonus - $tdsDeducted
            ];
        }
        return $report;
    }

    /**
     * Test: test_aml_report_flags_suspicious_transactions
     */
    public function getAmlReport()
    {
        // FSD-SYS-116: Flag if payment > ₹50K and user registered < 7 days ago
        $flagged = Payment::where('status', 'paid')
            ->where('amount', '>=', 50000)
            ->whereHas('user', function ($q) {
                $q->where('created_at', '>=', now()->subDays(7));
            })
            ->with('user:id,username,email')
            ->get();
            
        return $flagged;
    }
}