<?php
/**
 * FIX 48: Product Audit Trail
 *
 * Maintains comprehensive audit log of all product changes.
 *
 * Why this matters:
 * - Regulatory compliance (SEBI requires audit trails)
 * - Investor protection (track price changes, status changes)
 * - Fraud prevention (detect unauthorized modifications)
 * - Dispute resolution (show historical product state)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ProductAudit extends Model
{
    protected $fillable = [
        'product_id',
        'action',               // created, updated, activated, deactivated, price_updated
        'changed_fields',       // JSON array of field names that changed
        'old_values',           // JSON object of old field values
        'new_values',           // JSON object of new field values
        'performed_by',         // User ID who made the change
        'performed_by_type',    // User, Admin, System
        'ip_address',
        'user_agent',
        'reason',               // Optional reason for the change
        'metadata',             // Additional context
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Audit records are immutable once created
     */
    protected static function booted()
    {
        static::updating(function () {
            throw new \RuntimeException(
                'Product audit records are immutable. Create a new record instead.'
            );
        });
    }

    // --- RELATIONSHIPS ---

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // --- HELPERS ---

    /**
     * Create audit log for product change
     */
    public static function log(
        Product $product,
        string $action,
        ?array $changedFields = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): self {
        // Auto-detect changed fields if not provided
        if ($changedFields === null && $product->isDirty()) {
            $changedFields = array_keys($product->getDirty());
        }

        // Get old and new values
        if ($oldValues === null && $newValues === null && !empty($changedFields)) {
            $oldValues = [];
            $newValues = [];

            foreach ($changedFields as $field) {
                $oldValues[$field] = $product->getOriginal($field);
                $newValues[$field] = $product->$field;
            }
        }

        return self::create([
            'product_id' => $product->id,
            'action' => $action,
            'changed_fields' => $changedFields ?? [],
            'old_values' => $oldValues ?? [],
            'new_values' => $newValues ?? [],
            'performed_by' => auth()->id(),
            'performed_by_type' => auth()->check() ? get_class(auth()->user()) : 'System',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'route' => request()->path(),
                'method' => request()->method(),
            ],
        ]);
    }

    /**
     * Get field change summary
     */
    public function getChangeSummary(): string
    {
        if (empty($this->changed_fields)) {
            return "No fields changed";
        }

        $changes = [];

        foreach ($this->changed_fields as $field) {
            $oldValue = $this->old_values[$field] ?? 'null';
            $newValue = $this->new_values[$field] ?? 'null';

            $fieldLabel = ucwords(str_replace('_', ' ', $field));
            $changes[] = "{$fieldLabel}: {$oldValue} → {$newValue}";
        }

        return implode(', ', $changes);
    }

    /**
     * Check if specific field was changed in this audit
     */
    public function hasFieldChanged(string $field): bool
    {
        return in_array($field, $this->changed_fields ?? []);
    }

    /**
     * Get value change for specific field
     */
    public function getFieldChange(string $field): ?array
    {
        if (!$this->hasFieldChanged($field)) {
            return null;
        }

        return [
            'old' => $this->old_values[$field] ?? null,
            'new' => $this->new_values[$field] ?? null,
        ];
    }

    /**
     * Check if this is a critical change (status, price, compliance)
     */
    public function isCriticalChange(): bool
    {
        $criticalFields = [
            'status',
            'current_market_price',
            'face_value_per_unit',
            'sebi_approval_number',
            'sebi_approval_date',
        ];

        return !empty(array_intersect($this->changed_fields ?? [], $criticalFields));
    }
}
