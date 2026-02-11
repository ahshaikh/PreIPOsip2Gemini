<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * P0 REMEDIATION - Gate 4: Investment Snapshot Immutability
 * 
 * PURPOSE:
 * Enforces ABSOLUTE immutability of investment snapshots.
 * Once created, snapshots CANNOT be modified or deleted.
 * 
 * CRITICAL GUARANTEE:
 * Investor disputes can reconstruct exactly what was seen at purchase time.
 * 
 * ENFORCEMENT:
 * - booted() hooks prevent UPDATE and DELETE operations
 * - No exceptions, not even in console mode
 * - Snapshots are permanent audit records
 *
 * @mixin IdeHelperInvestmentDisclosureSnapshot
 */
class InvestmentDisclosureSnapshot extends Model
{
    /**
     * Table name
     */
    protected $table = 'investment_disclosure_snapshots';

    /**
     * Guarded attributes (allow mass assignment except id)
     */
    protected $guarded = ['id'];

    /**
     * Attribute casting
     */
    protected $casts = [
        'snapshot_timestamp' => 'datetime',
        'disclosure_snapshot' => 'array',
        'metrics_snapshot' => 'array',
        'risk_flags_snapshot' => 'array',
        'valuation_context_snapshot' => 'array',
        'governance_snapshot' => 'array',
        'disclosure_versions_map' => 'array',
        'public_page_view_snapshot' => 'array',
        'acknowledgements_snapshot' => 'array',
        'acknowledgements_granted' => 'array',
        'viewed_documents' => 'array',
        'was_under_review' => 'boolean',
        'buying_enabled_at_snapshot' => 'boolean',
        'is_immutable' => 'boolean',
        'locked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * CRITICAL: Enforce absolute immutability
     *
     * P0 REMEDIATION - Protocol-1 Audit Requirement
     *
     * ENFORCEMENT RULES:
     * 1. NO updates allowed (not even in console)
     * 2. NO deletes allowed (not even in console)
     * 3. Snapshots are permanent audit records
     *
     * RATIONALE:
     * Investment snapshots must be preserved forever to:
     * - Defend investor disputes
     * - Prove regulatory compliance
     * - Reconstruct exactly what investor saw
     * - Maintain audit trail integrity
     */
    protected static function booted()
    {
        // PREVENT ALL UPDATES
        // Returns false to abort the update operation
        static::updating(function () {
            \Log::warning('[IMMUTABILITY VIOLATION] Attempted to update investment snapshot', [
                'model' => 'InvestmentDisclosureSnapshot',
                'stack' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
            return false;
        });

        // PREVENT ALL DELETES
        // Returns false to abort the delete operation
        // No exceptions - snapshots are forever
        static::deleting(function () {
            \Log::warning('[IMMUTABILITY VIOLATION] Attempted to delete investment snapshot', [
                'model' => 'InvestmentDisclosureSnapshot',
                'stack' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
            return false;
        });
    }

    /**
     * Relationships
     */

    public function investment()
    {
        return $this->belongsTo(\App\Models\CompanyInvestment::class, 'investment_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    /**
     * Scopes
     */

    public function scopeForInvestor($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForInvestment($query, int $investmentId)
    {
        return $query->where('investment_id', $investmentId);
    }

    public function scopeImmutable($query)
    {
        return $query->where('is_immutable', true);
    }

    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_at');
    }
}
