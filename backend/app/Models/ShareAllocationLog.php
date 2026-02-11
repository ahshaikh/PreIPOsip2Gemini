<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ShareAllocationLog Model
 * 
 * P0 FIX: Immutable audit trail for share allocation flow.
 * 
 * PURPOSE:
 * Provides forensic traceability proving:
 * 1. Which BulkPurchase lot funded which investment
 * 2. Inventory balance before/after each allocation
 * 3. Link to AdminLedgerEntry proving cash receipt
 * 
 * This enables answering:
 * "Admin bought 100K shares. Where did 99K go?"
 * 
 * IMMUTABILITY:
 * - Records are locked immediately on creation
 * - Cannot be updated after lock
 * - Reversals create new compensating entries
 *
 * @mixin IdeHelperShareAllocationLog
 */
class ShareAllocationLog extends Model
{
    protected $fillable = [
        'bulk_purchase_id',
        'allocatable_type',
        'allocatable_id',
        'value_allocated',
        'units_allocated',
        'inventory_before',
        'inventory_after',
        'admin_ledger_entry_id',
        'company_id',
        'user_id',
        'allocated_by',
        'is_immutable',
        'locked_at',
        'is_reversed',
        'reversed_at',
        'reversal_reason',
        'reversal_log_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'value_allocated' => 'decimal:2',
        'units_allocated' => 'decimal:4',
        'inventory_before' => 'decimal:2',
        'inventory_after' => 'decimal:2',
        'is_immutable' => 'boolean',
        'locked_at' => 'datetime',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot: Enforce immutability
     */
    protected static function booted()
    {
        static::creating(function ($log) {
            // Auto-lock on creation
            $log->is_immutable = true;
            $log->locked_at = now();
        });

        static::updating(function ($log) {
            // Only allow reversal fields to be updated
            $allowedChanges = ['is_reversed', 'reversed_at', 'reversal_reason', 'reversal_log_id'];
            $changedKeys = array_keys($log->getDirty());

            $disallowedChanges = array_diff($changedKeys, $allowedChanges);
            if (!empty($disallowedChanges) && $log->getOriginal('is_immutable')) {
                throw new \RuntimeException(
                    "ShareAllocationLog #{$log->id} is immutable. Cannot modify: " . implode(', ', $disallowedChanges)
                );
            }
        });
    }

    // --- RELATIONSHIPS ---

    /**
     * Source inventory lot
     */
    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class);
    }

    /**
     * Polymorphic: CompanyInvestment or UserInvestment
     */
    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Company the shares belong to
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * User who received the allocation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Admin who approved (if manual)
     */
    public function allocatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    /**
     * Link to cash receipt proof
     */
    public function adminLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(AdminLedgerEntry::class);
    }

    /**
     * Compensating reversal entry
     */
    public function reversalLog(): BelongsTo
    {
        return $this->belongsTo(ShareAllocationLog::class, 'reversal_log_id');
    }

    // --- SCOPES ---

    public function scopeActive($query)
    {
        return $query->where('is_reversed', false);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBulkPurchase($query, int $bulkPurchaseId)
    {
        return $query->where('bulk_purchase_id', $bulkPurchaseId);
    }

    // --- HELPERS ---

    /**
     * Check if this log has been reversed
     */
    public function isReversed(): bool
    {
        return $this->is_reversed === true;
    }

    /**
     * Get the full provenance chain for this allocation
     */
    public function getProvenanceChain(): array
    {
        return [
            'allocation_log_id' => $this->id,
            'created_at' => $this->created_at->toIso8601String(),
            'source' => [
                'type' => 'bulk_purchase',
                'id' => $this->bulk_purchase_id,
                'company_id' => $this->bulkPurchase?->company_id,
                'purchase_date' => $this->bulkPurchase?->purchase_date?->toDateString(),
            ],
            'destination' => [
                'type' => $this->allocatable_type,
                'id' => $this->allocatable_id,
            ],
            'allocation' => [
                'value' => $this->value_allocated,
                'units' => $this->units_allocated,
                'inventory_before' => $this->inventory_before,
                'inventory_after' => $this->inventory_after,
            ],
            'cash_proof' => [
                'admin_ledger_entry_id' => $this->admin_ledger_entry_id,
            ],
            'user' => [
                'id' => $this->user_id,
            ],
            'is_reversed' => $this->is_reversed,
            'reversal_reason' => $this->reversal_reason,
        ];
    }
}
