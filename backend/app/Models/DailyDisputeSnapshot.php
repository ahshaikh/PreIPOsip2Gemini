<?php

// V-DISPUTE-RISK-2026-002 | Daily Dispute Snapshot Model

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DailyDisputeSnapshot - Aggregated dispute/chargeback metrics per day per plan.
 *
 * IMMUTABILITY: Once created, snapshots should not be modified.
 * These are historical records for reporting and trend analysis.
 *
 * @property int $id
 * @property \Carbon\Carbon $snapshot_date
 * @property int|null $plan_id
 * @property int $total_disputes
 * @property int $open_disputes
 * @property int $under_investigation_disputes
 * @property int $resolved_disputes
 * @property int $escalated_disputes
 * @property int $total_chargeback_count
 * @property int $total_chargeback_amount_paise
 * @property int $confirmed_chargeback_count
 * @property int $confirmed_chargeback_amount_paise
 * @property int $low_severity_count
 * @property int $medium_severity_count
 * @property int $high_severity_count
 * @property int $critical_severity_count
 * @property array|null $category_breakdown
 * @property int $blocked_users_count
 * @property int $high_risk_users_count
 */
class DailyDisputeSnapshot extends Model
{
    use HasFactory;

    protected $table = 'daily_dispute_snapshots';

    protected $fillable = [
        'snapshot_date',
        'plan_id',
        'total_disputes',
        'open_disputes',
        'under_investigation_disputes',
        'resolved_disputes',
        'escalated_disputes',
        'total_chargeback_count',
        'total_chargeback_amount_paise',
        'confirmed_chargeback_count',
        'confirmed_chargeback_amount_paise',
        'low_severity_count',
        'medium_severity_count',
        'high_severity_count',
        'critical_severity_count',
        'category_breakdown',
        'blocked_users_count',
        'high_risk_users_count',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'total_disputes' => 'integer',
        'open_disputes' => 'integer',
        'under_investigation_disputes' => 'integer',
        'resolved_disputes' => 'integer',
        'escalated_disputes' => 'integer',
        'total_chargeback_count' => 'integer',
        'total_chargeback_amount_paise' => 'integer',
        'confirmed_chargeback_count' => 'integer',
        'confirmed_chargeback_amount_paise' => 'integer',
        'low_severity_count' => 'integer',
        'medium_severity_count' => 'integer',
        'high_severity_count' => 'integer',
        'critical_severity_count' => 'integer',
        'category_breakdown' => 'array',
        'blocked_users_count' => 'integer',
        'high_risk_users_count' => 'integer',
    ];

    /**
     * Accessor: Total chargeback amount in Rupees (read-only).
     */
    public function getTotalChargebackAmountAttribute(): float
    {
        return $this->total_chargeback_amount_paise / 100;
    }

    /**
     * Accessor: Confirmed chargeback amount in Rupees (read-only).
     */
    public function getConfirmedChargebackAmountAttribute(): float
    {
        return $this->confirmed_chargeback_amount_paise / 100;
    }

    /**
     * Plan relationship (nullable for platform-wide snapshots).
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope: Get snapshots for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get platform-wide snapshots (no plan filter).
     */
    public function scopePlatformWide($query)
    {
        return $query->whereNull('plan_id');
    }

    /**
     * Scope: Get snapshots for a specific plan.
     */
    public function scopeForPlan($query, int $planId)
    {
        return $query->where('plan_id', $planId);
    }
}
