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
        'admin_id', 'action', 'module', 'target_type', 'target_id',
        'old_values', 'new_values', 'description', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
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
}