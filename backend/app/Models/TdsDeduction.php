<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasMonetaryFields;

/**
 * FIX 13 (P3): TDS Deduction Model
 * 
 * Tracks Tax Deducted at Source for regulatory compliance
 *
 * @mixin IdeHelperTdsDeduction
 */
class TdsDeduction extends Model
{
    use HasFactory, SoftDeletes, HasMonetaryFields;

    protected $fillable = [
        'user_id',
        'transaction_type',
        'transaction_id',
        'financial_year',
        'quarter',
        'gross_amount_paise',
        'gross_amount',
        'tds_amount_paise',
        'tds_amount',
        'tds_rate',
        'net_amount_paise',
        'net_amount',
        'section_code',
        'pan_number',
        'pan_available',
        'deduction_date',
        'deposit_date',
        'challan_number',
        'bsr_code',
        'certificate_number',
        'certificate_date',
        'certificate_path',
        'status',
        'metadata',
        'remarks',
    ];

    protected $casts = [
        'gross_amount_paise' => 'integer',
        'gross_amount' => 'decimal:2',
        'tds_amount_paise' => 'integer',
        'tds_amount' => 'decimal:2',
        'tds_rate' => 'decimal:2',
        'net_amount_paise' => 'integer',
        'net_amount' => 'decimal:2',
        'pan_available' => 'boolean',
        'deduction_date' => 'date',
        'deposit_date' => 'date',
        'certificate_date' => 'date',
        'metadata' => 'array',
    ];

    protected $monetaryFields = ['gross_amount', 'tds_amount', 'net_amount'];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForFinancialYear($query, string $financialYear)
    {
        return $query->where('financial_year', $financialYear);
    }

    public function scopeForQuarter($query, int $quarter)
    {
        return $query->where('quarter', $quarter);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDeposited($query)
    {
        return $query->where('status', 'deposited');
    }

    public function scopeCertified($query)
    {
        return $query->where('status', 'certified');
    }

    /**
     * Get quarter from date
     */
    public static function getQuarterFromDate(\DateTime $date): int
    {
        $month = (int) $date->format('n');

        // Financial year: April-March
        // Q1: Apr-Jun, Q2: Jul-Sep, Q3: Oct-Dec, Q4: Jan-Mar
        if ($month >= 4 && $month <= 6) return 1;
        if ($month >= 7 && $month <= 9) return 2;
        if ($month >= 10 && $month <= 12) return 3;
        return 4; // Jan-Mar
    }

    /**
     * Get financial year from date
     */
    public static function getFinancialYearFromDate(\DateTime $date): string
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 4) {
            // Apr-Dec: FY is current year to next year
            return $year . '-' . substr($year + 1, 2);
        } else {
            // Jan-Mar: FY is previous year to current year
            return ($year - 1) . '-' . substr($year, 2);
        }
    }

    /**
     * Mark TDS as deposited with government
     */
    public function markDeposited(string $challanNumber, string $bsrCode, \DateTime $depositDate): void
    {
        $this->update([
            'status' => 'deposited',
            'challan_number' => $challanNumber,
            'bsr_code' => $bsrCode,
            'deposit_date' => $depositDate,
        ]);
    }

    /**
     * Mark TDS certificate issued
     */
    public function markCertified(string $certificateNumber, \DateTime $certificateDate, ?string $certificatePath = null): void
    {
        $this->update([
            'status' => 'certified',
            'certificate_number' => $certificateNumber,
            'certificate_date' => $certificateDate,
            'certificate_path' => $certificatePath,
        ]);
    }
}
