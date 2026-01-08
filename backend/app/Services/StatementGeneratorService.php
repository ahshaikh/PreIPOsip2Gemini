<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use App\Models\Withdrawal;
use App\Models\UserInvestment;
use App\Models\BonusTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * FIX 14 (P3): User Transaction Statement Generator
 *
 * Generates regulatory-compliant PDF statements for users
 * Includes payments, withdrawals, investments, and bonuses
 */
class StatementGeneratorService
{
    /**
     * Generate user transaction statement
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $type (all|payments|withdrawals|investments|bonuses)
     * @return string Path to generated PDF
     */
    public function generateStatement(User $user, Carbon $startDate, Carbon $endDate, string $type = 'all'): string
    {
        $data = [
            'user' => $user,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => now(),
            'statement_number' => $this->generateStatementNumber($user, $startDate, $endDate),
        ];

        // Fetch transaction data based on type
        if ($type === 'all' || $type === 'payments') {
            $data['payments'] = $this->getPayments($user, $startDate, $endDate);
        }

        if ($type === 'all' || $type === 'withdrawals') {
            $data['withdrawals'] = $this->getWithdrawals($user, $startDate, $endDate);
        }

        if ($type === 'all' || $type === 'investments') {
            $data['investments'] = $this->getInvestments($user, $startDate, $endDate);
        }

        if ($type === 'all' || $type === 'bonuses') {
            $data['bonuses'] = $this->getBonuses($user, $startDate, $endDate);
        }

        // Calculate summary
        $data['summary'] = $this->calculateSummary($data);

        // Generate PDF
        $pdf = Pdf::loadView('statements.transaction', $data);
        $pdf->setPaper('A4', 'portrait');

        // Save to storage
        $filename = $this->generateFilename($user, $startDate, $endDate, $type);
        $path = "statements/{$user->id}/{$filename}";
        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Get payments within date range
     */
    protected function getPayments(User $user, Carbon $startDate, Carbon $endDate)
    {
        return Payment::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['success', 'completed'])
            ->with('subscription.plan')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->created_at,
                    'type' => 'Payment',
                    'description' => 'Subscription Payment - ' . ($payment->subscription->plan->name ?? 'N/A'),
                    'reference' => $payment->payment_id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                ];
            });
    }

    /**
     * Get withdrawals within date range
     */
    protected function getWithdrawals(User $user, Carbon $startDate, Carbon $endDate)
    {
        return Withdrawal::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'completed'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($withdrawal) {
                return [
                    'date' => $withdrawal->created_at,
                    'type' => 'Withdrawal',
                    'description' => 'Wallet Withdrawal',
                    'reference' => $withdrawal->transaction_id,
                    'amount' => -$withdrawal->amount,
                    'fee' => $withdrawal->fee,
                    'net_amount' => -($withdrawal->amount - $withdrawal->fee),
                    'status' => $withdrawal->status,
                ];
            });
    }

    /**
     * Get investments within date range
     */
    protected function getInvestments(User $user, Carbon $startDate, Carbon $endDate)
    {
        return UserInvestment::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('is_reversed', false)
            ->with(['product', 'plan'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($investment) {
                return [
                    'date' => $investment->created_at,
                    'type' => 'Investment',
                    'description' => $investment->product->name . ' - ' . $investment->plan->name,
                    'reference' => 'INV-' . $investment->id,
                    'shares' => $investment->shares_allocated,
                    'price_per_share' => $investment->price_per_share,
                    'amount' => $investment->invested_amount,
                    'status' => 'Allocated',
                ];
            });
    }

    /**
     * Get bonuses within date range
     */
    protected function getBonuses(User $user, Carbon $startDate, Carbon $endDate)
    {
        return BonusTransaction::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'credited'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($bonus) {
                return [
                    'date' => $bonus->created_at,
                    'type' => 'Bonus',
                    'description' => ucfirst($bonus->bonus_type) . ' Bonus',
                    'reference' => 'BONUS-' . $bonus->id,
                    'amount' => $bonus->amount,
                    'status' => $bonus->status,
                ];
            });
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateSummary(array $data): array
    {
        $summary = [
            'total_payments' => 0,
            'total_withdrawals' => 0,
            'total_investments' => 0,
            'total_bonuses' => 0,
            'net_cash_flow' => 0,
        ];

        if (isset($data['payments'])) {
            $summary['total_payments'] = $data['payments']->sum('amount');
        }

        if (isset($data['withdrawals'])) {
            $summary['total_withdrawals'] = abs($data['withdrawals']->sum('amount'));
        }

        if (isset($data['investments'])) {
            $summary['total_investments'] = $data['investments']->sum('amount');
        }

        if (isset($data['bonuses'])) {
            $summary['total_bonuses'] = $data['bonuses']->sum('amount');
        }

        $summary['net_cash_flow'] = $summary['total_payments'] + $summary['total_bonuses'] - $summary['total_withdrawals'];

        return $summary;
    }

    /**
     * Generate unique statement number
     */
    protected function generateStatementNumber(User $user, Carbon $startDate, Carbon $endDate): string
    {
        $year = $startDate->format('Y');
        $month = $startDate->format('m');
        $userId = str_pad($user->id, 6, '0', STR_PAD_LEFT);
        $hash = substr(md5($user->id . $startDate . $endDate), 0, 4);

        return "STMT-{$year}{$month}-{$userId}-{$hash}";
    }

    /**
     * Generate filename for PDF
     */
    protected function generateFilename(User $user, Carbon $startDate, Carbon $endDate, string $type): string
    {
        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
        $timestamp = now()->format('YmdHis');

        return "statement_{$type}_{$start}_to_{$end}_{$timestamp}.pdf";
    }

    /**
     * Generate monthly statement
     */
    public function generateMonthlyStatement(User $user, int $year, int $month): string
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return $this->generateStatement($user, $startDate, $endDate, 'all');
    }

    /**
     * Generate financial year statement
     */
    public function generateFinancialYearStatement(User $user, int $year): string
    {
        $startDate = Carbon::create($year, 4, 1); // Financial year starts April 1
        $endDate = Carbon::create($year + 1, 3, 31); // Ends March 31 next year

        return $this->generateStatement($user, $startDate, $endDate, 'all');
    }

    /**
     * Clean up old statement files (older than 90 days)
     */
    public function cleanupOldStatements(): int
    {
        $cutoffDate = now()->subDays(90);
        $deleted = 0;

        $files = Storage::allFiles('statements');

        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffDate->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
