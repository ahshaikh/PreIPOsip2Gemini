<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * CompanyInvestment Model
 *
 * PURPOSE: Direct company investments (outside of SIP subscriptions)
 * This model handles one-time investments in companies via the investor portal
 *
 * DISTINCTION from Investment model:
 * - Investment: SIP-based investments with subscription_id and deal_id
 * - CompanyInvestment: Direct company investments with disclosure snapshots
 *
 * P0 FIX: Now includes per-lot provenance tracking:
 * - bulk_purchase_id: Links to inventory source
 * - admin_ledger_entry_id: Links to cash receipt proof
 * - allocation_status: Tracks allocation state
 * - allocation_logs: Full audit trail via ShareAllocationLog
 */
class CompanyInvestment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'amount',
        'disclosure_snapshot_id',
        'status',
        'invested_at',
        'idempotency_key',
        'notes',
        // P0 FIX: Share traceability fields
        'bulk_purchase_id',
        'admin_ledger_entry_id',
        'allocation_status',
        'allocated_value',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_value' => 'decimal:2',
        'invested_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function disclosureSnapshot(): BelongsTo
    {
        return $this->belongsTo(InvestmentDisclosureSnapshot::class, 'disclosure_snapshot_id');
    }

    /**
     * P0 FIX: Primary inventory lot source
     *
     * Note: For multi-batch allocations, this links to the first/primary batch.
     * Full allocation chain is available via allocationLogs() relationship.
     */
    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class);
    }

    /**
     * P0 FIX: Full allocation audit trail
     *
     * Returns all ShareAllocationLog entries for this investment,
     * providing complete provenance: which batches funded this investment.
     */
    public function allocationLogs(): MorphMany
    {
        return $this->morphMany(ShareAllocationLog::class, 'allocatable');
    }

    /**
     * P0 FIX: Active (non-reversed) allocations only
     */
    public function activeAllocationLogs(): MorphMany
    {
        return $this->morphMany(ShareAllocationLog::class, 'allocatable')
            ->where('is_reversed', false);
    }

    // --- SCOPES ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * P0 FIX: Investments needing allocation
     */
    public function scopeUnallocated($query)
    {
        return $query->where('allocation_status', 'unallocated');
    }

    /**
     * P0 FIX: Fully allocated investments
     */
    public function scopeAllocated($query)
    {
        return $query->where('allocation_status', 'allocated');
    }

    // --- HELPER METHODS ---

    /**
     * Check if investment can be cancelled
     */
    public function canCancel(): bool
    {
        return in_array($this->status, ['pending', 'active']);
    }

    /**
     * P0 FIX: Check if fully allocated
     */
    public function isFullyAllocated(): bool
    {
        return $this->allocation_status === 'allocated';
    }

    /**
     * P0 FIX: Get the provenance chain for this investment
     *
     * Returns full audit trail proving:
     * BulkPurchase → CompanyInvestment → Cash Receipt
     */
    public function getProvenanceChain(): array
    {
        return [
            'investment_id' => $this->id,
            'amount' => $this->amount,
            'allocated_value' => $this->allocated_value,
            'allocation_status' => $this->allocation_status,
            'primary_bulk_purchase_id' => $this->bulk_purchase_id,
            'admin_ledger_entry_id' => $this->admin_ledger_entry_id,
            'allocation_logs' => $this->activeAllocationLogs->map(function ($log) {
                return [
                    'log_id' => $log->id,
                    'bulk_purchase_id' => $log->bulk_purchase_id,
                    'value_allocated' => $log->value_allocated,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];
    }
}
