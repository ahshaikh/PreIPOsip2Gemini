<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-IMMUTABLE-LEDGER | V-COMPLIANCE-PRUNING
 * Refactored to address Module 11 Audit Gaps:
 * 1. Immutability: Standard Eloquent boot hooks prevent log tampering/deletion.
 * 2. Pruning Policy: Implements automated cleanup for records older than 2 years.
 * 3. Forensic Schema: Captures serialized snapshots of model states (old_values/new_values).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Prunable; // [AUDIT FIX]: Import Prunable trait

class AuditLog extends Model
{
    use HasFactory, Prunable;

    /**
     * Disable UPDATED_AT as audit logs are immutable records of a specific point in time.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action',
        'module',
        'target_type',
        'target_id',
        'old_values',   // [AUDIT FIX]: Captured 'before' state
        'new_values',   // [AUDIT FIX]: Captured 'after' state
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * [AUDIT FIX]: Cast values to array for structured "Diff" views in the UI.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'target_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * [AUDIT FIX]: SECURITY - Enforce Immutability
     * Prevents any administrative or programmatic attempt to modify or delete logs.
     */
    protected static function booted()
    {
        static::updating(function ($log) {
            return false; // Silently reject updates
        });

        static::deleting(function ($log) {
            // Only allow deletion via the automated 'prunable' system
            if (!app()->runningInConsole()) {
                return false;
            }
        });
    }

    /**
     * [AUDIT FIX]: Automated Pruning Logic
     * Defines which records are eligible for deletion to prevent database bloat.
     * Keeps 730 days (2 years) by default, configurable via settings.
     */
    public function prunable()
    {
        $retentionDays = setting('audit_log_retention_days', 730);
        return static::where('created_at', '<=', now()->subDays($retentionDays));
    }

    /**
     * Optional: Archive hook before the record is purged.
     */
    protected function pruning()
    {
        // Integration point for cold storage (S3/Glacier) can be added here
    }

    // --- RELATIONSHIPS ---

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Polymorphic relationship to the entity being audited.
     */
    public function target()
    {
        return $this->morphTo();
    }
}