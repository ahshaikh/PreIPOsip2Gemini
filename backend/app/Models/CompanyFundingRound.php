<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperCompanyFundingRound
 */
class CompanyFundingRound extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'round_name',
        'amount_raised',
        'currency',
        'valuation',
        'round_date',
        'investors',
        'description',
    ];

    protected $casts = [
        'amount_raised' => 'decimal:2',
        'valuation' => 'decimal:2',
        'round_date' => 'date',
        'investors' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Funding round belongs to a Company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope: Order by date descending
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('round_date', 'desc');
    }

    /**
     * Get formatted amount raised
     */
    public function getFormattedAmountAttribute()
    {
        if (!$this->amount_raised) return 'Undisclosed';

        $currency_symbol = $this->currency === 'INR' ? '₹' : '$';
        $amount = $this->amount_raised;

        if ($amount >= 10000000) { // Crores (10 million)
            return $currency_symbol . number_format($amount / 10000000, 2) . ' Cr';
        } elseif ($amount >= 100000) { // Lakhs
            return $currency_symbol . number_format($amount / 100000, 2) . ' L';
        } else {
            return $currency_symbol . number_format($amount, 2);
        }
    }
}
