<?php

namespace App\Services;

use App\Models\User;
use App\Models\TdsDeduction;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FIX 13 (P3): TDS Service
 *
 * Handles TDS calculations, deductions, and reporting for regulatory compliance
 */
class TdsService
{
    /**
     * TDS rates for different sections (as per IT Act)
     */
    const TDS_RATES = [
        '194' => 10.0,  // Interest other than on securities
        '194A' => 10.0, // Interest on securities
        '194J' => 10.0, // Professional or technical services
        '194H' => 5.0,  // Commission and brokerage
        '194I' => 10.0, // Rent
    ];

    /**
     * TDS threshold limits (in rupees)
     */
    const THRESHOLDS = [
        '194' => 40000,   // Interest threshold per year
        '194A' => 10000,  // Interest on securities per year
        '194J' => 30000,  // Professional services per year
        '194H' => 15000,  // Commission per year
    ];

    /**
     * Calculate TDS on withdrawal amount
     *
     * @param User $user
     * @param float $grossAmount
     * @param string $transactionType
     * @return array ['tds_amount', 'net_amount', 'tds_rate', 'section_code']
     */
    public function calculateTds(User $user, float $grossAmount, string $transactionType = 'withdrawal'): array
    {
        // Determine TDS section and rate
        $sectionCode = $this->getSectionCode($transactionType);
        $tdsRate = $this->getTdsRate($user, $sectionCode);

        // Check if amount exceeds threshold
        $threshold = self::THRESHOLDS[$sectionCode] ?? 0;

        if ($grossAmount < $threshold) {
            return [
                'tds_amount' => 0,
                'net_amount' => $grossAmount,
                'tds_rate' => 0,
                'section_code' => $sectionCode,
                'applicable' => false,
            ];
        }

        // Calculate TDS
        $tdsAmount = ($grossAmount * $tdsRate) / 100;
        $netAmount = $grossAmount - $tdsAmount;

        return [
            'tds_amount' => round($tdsAmount, 2),
            'net_amount' => round($netAmount, 2),
            'tds_rate' => $tdsRate,
            'section_code' => $sectionCode,
            'applicable' => true,
        ];
    }

    /**
     * Record TDS deduction
     *
     * @param User $user
     * @param string $transactionType
     * @param int $transactionId
     * @param float $grossAmount
     * @param float $tdsAmount
     * @param float $tdsRate
     * @param string $sectionCode
     * @return TdsDeduction
     */
    public function recordDeduction(
        User $user,
        string $transactionType,
        int $transactionId,
        float $grossAmount,
        float $tdsAmount,
        float $tdsRate,
        string $sectionCode
    ): TdsDeduction {
        $deductionDate = now();
        $financialYear = TdsDeduction::getFinancialYearFromDate($deductionDate);
        $quarter = TdsDeduction::getQuarterFromDate($deductionDate);

        return TdsDeduction::create([
            'user_id' => $user->id,
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'financial_year' => $financialYear,
            'quarter' => $quarter,
            'gross_amount' => $grossAmount,
            'gross_amount_paise' => bcmul($grossAmount, 100),
            'tds_amount' => $tdsAmount,
            'tds_amount_paise' => bcmul($tdsAmount, 100),
            'tds_rate' => $tdsRate,
            'net_amount' => $grossAmount - $tdsAmount,
            'net_amount_paise' => bcmul($grossAmount - $tdsAmount, 100),
            'section_code' => $sectionCode,
            'pan_number' => $user->pan_number,
            'pan_available' => !empty($user->pan_number),
            'deduction_date' => $deductionDate,
            'status' => 'pending',
        ]);
    }

    /**
     * Get quarterly TDS report
     *
     * @param string $financialYear
     * @param int $quarter
     * @return array
     */
    public function getQuarterlyReport(string $financialYear, int $quarter): array
    {
        $deductions = TdsDeduction::forFinancialYear($financialYear)
            ->forQuarter($quarter)
            ->with('user')
            ->get();

        $summary = [
            'financial_year' => $financialYear,
            'quarter' => $quarter,
            'total_deductees' => $deductions->unique('user_id')->count(),
            'total_deductions' => $deductions->count(),
            'total_tds' => $deductions->sum('tds_amount'),
            'total_gross' => $deductions->sum('gross_amount'),
            'by_section' => [],
            'by_status' => [],
        ];

        // Group by section
        foreach ($deductions->groupBy('section_code') as $section => $items) {
            $summary['by_section'][$section] = [
                'count' => $items->count(),
                'tds_amount' => $items->sum('tds_amount'),
            ];
        }

        // Group by status
        foreach ($deductions->groupBy('status') as $status => $items) {
            $summary['by_status'][$status] = [
                'count' => $items->count(),
                'tds_amount' => $items->sum('tds_amount'),
            ];
        }

        return [
            'summary' => $summary,
            'deductions' => $deductions,
        ];
    }

    /**
     * Generate Form 16A certificate for user
     *
     * @param User $user
     * @param string $financialYear
     * @return string Path to PDF
     */
    public function generateForm16A(User $user, string $financialYear): string
    {
        $deductions = TdsDeduction::where('user_id', $user->id)
            ->forFinancialYear($financialYear)
            ->certified()
            ->get();

        if ($deductions->isEmpty()) {
            throw new \RuntimeException('No TDS deductions found for this financial year');
        }

        $data = [
            'user' => $user,
            'financial_year' => $financialYear,
            'deductions' => $deductions,
            'total_tds' => $deductions->sum('tds_amount'),
            'total_gross' => $deductions->sum('gross_amount'),
            'certificate_number' => $this->generateCertificateNumber($user, $financialYear),
            'generated_at' => now(),
        ];

        // Generate PDF (requires view template)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('tds.form16a', $data);
        $pdf->setPaper('A4', 'portrait');

        // Save to storage
        $filename = "form16a_{$user->id}_{$financialYear}_" . now()->format('YmdHis') . '.pdf';
        $path = "tds/form16a/{$user->id}/{$filename}";
        \Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Get user's TDS summary
     *
     * @param User $user
     * @param string|null $financialYear
     * @return array
     */
    public function getUserSummary(User $user, ?string $financialYear = null): array
    {
        $query = TdsDeduction::where('user_id', $user->id);

        if ($financialYear) {
            $query->forFinancialYear($financialYear);
        } else {
            $financialYear = TdsDeduction::getFinancialYearFromDate(now());
            $query->forFinancialYear($financialYear);
        }

        $deductions = $query->get();

        return [
            'financial_year' => $financialYear,
            'total_tds_deducted' => $deductions->sum('tds_amount'),
            'total_gross_amount' => $deductions->sum('gross_amount'),
            'total_net_amount' => $deductions->sum('net_amount'),
            'deductions_count' => $deductions->count(),
            'by_quarter' => [
                'Q1' => $deductions->where('quarter', 1)->sum('tds_amount'),
                'Q2' => $deductions->where('quarter', 2)->sum('tds_amount'),
                'Q3' => $deductions->where('quarter', 3)->sum('tds_amount'),
                'Q4' => $deductions->where('quarter', 4)->sum('tds_amount'),
            ],
            'by_type' => $deductions->groupBy('transaction_type')->map(function ($items, $type) {
                return [
                    'count' => $items->count(),
                    'tds_amount' => $items->sum('tds_amount'),
                ];
            }),
        ];
    }

    /**
     * Get section code based on transaction type
     */
    protected function getSectionCode(string $transactionType): string
    {
        return match ($transactionType) {
            'withdrawal' => '194',
            'profit_share' => '194A',
            'commission' => '194H',
            'dividend' => '194',
            default => '194',
        };
    }

    /**
     * Get TDS rate for user
     */
    protected function getTdsRate(User $user, string $sectionCode): float
    {
        // If PAN not available, apply 20% TDS
        if (empty($user->pan_number)) {
            return 20.0;
        }

        return self::TDS_RATES[$sectionCode] ?? 10.0;
    }

    /**
     * Generate unique certificate number
     */
    protected function generateCertificateNumber(User $user, string $financialYear): string
    {
        $year = str_replace('-', '', $financialYear);
        $userId = str_pad($user->id, 6, '0', STR_PAD_LEFT);
        $sequence = str_pad(TdsDeduction::where('user_id', $user->id)->count() + 1, 4, '0', STR_PAD_LEFT);

        return "16A/{$year}/{$userId}/{$sequence}";
    }

    /**
     * Bulk mark TDS as deposited
     */
    public function bulkMarkDeposited(array $deductionIds, string $challanNumber, string $bsrCode, Carbon $depositDate): int
    {
        return TdsDeduction::whereIn('id', $deductionIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'deposited',
                'challan_number' => $challanNumber,
                'bsr_code' => $bsrCode,
                'deposit_date' => $depositDate,
            ]);
    }
}
