<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-ASYNC-LOGGING | V-IMMUTABLE-PII-MASK
 * * ARCHITECTURAL FIX: 
 * Moves from synchronous DB writes to a "Protection First" observer.
 * Ensures that PII is masked *before* hitting the audit table.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class AuditLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id', 'actor_type', 'actor_id', 'actor_name', 'actor_email',
        'action', 'module', 'target_type', 'target_id', 'target_name',
        'old_values', 'new_values', 'description', 'ip_address', 'user_agent',
        'request_method', 'request_url', 'session_id',
        'risk_level', 'requires_review', 'metadata'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'requires_review' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * [SECURITY FIX]: Immutable & Masked Hooks
     */
    protected static function booted()
    {
        static::creating(function ($log) {
            // Mask sensitive fields in JSON snapshots before they are saved
            $log->old_values = self::maskSensitiveData($log->old_values);
            $log->new_values = self::maskSensitiveData($log->new_values);
        });

        static::updating(fn() => false);
        static::deleting(fn() => app()->runningInConsole() ? true : false);
    }

    /**
     * [ANTI-PATTERN FIX]: Centralized PII Masking
     */
    protected static function maskSensitiveData(?array $data): ?array
    {
        if (!$data) return null;

        $piiFields = ['pan_number', 'aadhaar_number', 'phone', 'account_no', 'bank_details'];
        
        foreach ($piiFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '********' . substr($data[$field], -4);
            }
        }

        return $data;
    }

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(setting('audit_log_retention', 730)));
    }

    // --- SCOPES ---

    public function scopeByActor($query, string $actorType, int $actorId)
    {
        return $query->where('actor_type', $actorType)
                     ->where('actor_id', $actorId);
    }

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopeRequiringReview($query)
    {
        return $query->where('requires_review', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // --- HELPERS ---

    /**
     * Get changes summary (what fields changed).
     */
    public function getChangesSummary(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if this log involves sensitive data.
     */
    public function involvesSensitiveData(): bool
    {
        $sensitiveModules = ['users', 'kyc', 'payments', 'subscriptions'];
        return in_array($this->module, $sensitiveModules);
    }

    /**
     * Get human-readable action description.
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'verified' => 'Verified',
            'suspended' => 'Suspended',
            'reactivated' => 'Reactivated',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }
}