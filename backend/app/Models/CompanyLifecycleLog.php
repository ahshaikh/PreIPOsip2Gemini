<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 2 - MODEL: CompanyLifecycleLog
 *
 * PURPOSE:
 * Audit trail for all company lifecycle state transitions.
 * Immutable record of why and when a company moved between states.
 *
 * USAGE:
 * - Automatically created by CompanyLifecycleService
 * - Provides compliance audit trail
 * - Shows investors why company state changed
 *
 * IMMUTABILITY:
 * - No updates allowed after creation
 * - No soft deletes (permanent retention)
 *
 * @property int $id
 * @property int $company_id
 * @property string $from_state Previous lifecycle state
 * @property string $to_state New lifecycle state
 * @property string $trigger What triggered: tier_approval, admin_action, system
 * @property int|null $triggered_by User who triggered
 * @property string|null $reason Reason for change
 * @property array|null $metadata Additional context
 * @property string|null $ip_address IP of trigger
 * @property string|null $user_agent User agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompanyLifecycleLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_lifecycle_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'from_state',
        'to_state',
        'trigger',
        'triggered_by',
        'reason',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Company whose state changed
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * User who triggered the change
     */
    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to logs by company
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to logs by trigger type
     */
    public function scopeByTrigger($query, string $trigger)
    {
        return $query->where('trigger', $trigger);
    }

    /**
     * Scope to suspensions
     */
    public function scopeSuspensions($query)
    {
        return $query->where('to_state', 'suspended');
    }

    /**
     * Scope to state changes in date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable trigger label
     */
    public function getTriggerLabelAttribute(): string
    {
        return match($this->trigger) {
            'tier_approval' => 'Tier Approval',
            'admin_action' => 'Admin Action',
            'system' => 'System Automated',
            default => ucwords(str_replace('_', ' ', $this->trigger)),
        };
    }

    /**
     * Get human-readable state change description
     */
    public function getStateChangeDescriptionAttribute(): string
    {
        $from = $this->getStateLabel($this->from_state);
        $to = $this->getStateLabel($this->to_state);

        return "{$from} → {$to}";
    }

    /**
     * Get human-readable state label
     */
    protected function getStateLabel(string $state): string
    {
        return match($state) {
            'draft' => 'Draft',
            'live_limited' => 'Live (Limited)',
            'live_investable' => 'Live (Investable)',
            'live_fully_disclosed' => 'Live (Fully Disclosed)',
            'suspended' => 'Suspended',
            default => ucwords(str_replace('_', ' ', $state)),
        };
    }

    /**
     * Check if this was an upgrade (positive transition)
     */
    public function getIsUpgradeAttribute(): bool
    {
        $stateOrder = [
            'draft' => 0,
            'live_limited' => 1,
            'live_investable' => 2,
            'live_fully_disclosed' => 3,
            'suspended' => -1, // Downgrade
        ];

        $fromOrder = $stateOrder[$this->from_state] ?? 0;
        $toOrder = $stateOrder[$this->to_state] ?? 0;

        return $toOrder > $fromOrder;
    }

    /**
     * Check if this was a suspension
     */
    public function getIsSuspensionAttribute(): bool
    {
        return $this->to_state === 'suspended';
    }
}
