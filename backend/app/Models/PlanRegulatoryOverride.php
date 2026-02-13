<?php
// V-CONTRACT-HARDENING-002: Regulatory override model
// V-CONTRACT-HARDENING-CORRECTIVE: Added schema validation and single-override enforcement
// Represents an explicit, audited regulatory override for a plan's bonus configuration.
// Overrides are applied during bonus calculation WITHOUT mutating subscription snapshots.

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Services\SchemaAwareOverrideResolver;
use App\Exceptions\OverrideSchemaViolationException;

class PlanRegulatoryOverride extends Model
{
    use HasFactory;

    /**
     * Valid override scopes
     * SCOPE_FULL is BLOCKED by SchemaAwareOverrideResolver
     */
    public const SCOPE_PROGRESSIVE = 'progressive_config';
    public const SCOPE_MILESTONE = 'milestone_config';
    public const SCOPE_CONSISTENCY = 'consistency_config';
    public const SCOPE_WELCOME = 'welcome_bonus_config';
    public const SCOPE_REFERRAL = 'referral_tiers';
    public const SCOPE_MULTIPLIER_CAP = 'multiplier_cap';
    public const SCOPE_GLOBAL_RATE = 'global_rate_adjust';
    public const SCOPE_FULL = 'full_config'; // BLOCKED - kept for backwards compatibility errors

    /**
     * Scopes that are permitted for override creation
     * SCOPE_FULL is explicitly excluded
     */
    public const PERMITTED_SCOPES = [
        self::SCOPE_PROGRESSIVE,
        self::SCOPE_MILESTONE,
        self::SCOPE_CONSISTENCY,
        self::SCOPE_WELCOME,
        self::SCOPE_MULTIPLIER_CAP,
        self::SCOPE_GLOBAL_RATE,
    ];

    protected $fillable = [
        'plan_id',
        'override_scope',
        'override_payload',
        'reason',
        'regulatory_reference',
        'approved_by_admin_id',
        'effective_from',
        'expires_at',
        'revoked_at',
        'revoked_by_admin_id',
        'revocation_reason',
    ];

    protected $casts = [
        'override_payload' => 'json',
        'effective_from' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * The admin user who approved this override.
     * Note: Admins are users with admin roles (Spatie permissions)
     */
    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_admin_id');
    }

    /**
     * The admin user who revoked this override.
     */
    public function revokedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_admin_id');
    }

    public function bonusTransactions()
    {
        return $this->hasMany(BonusTransaction::class, 'override_id');
    }

    // --- SCOPES ---

    /**
     * Scope to active (non-revoked, currently effective) overrides
     */
    public function scopeActive(Builder $query): void
    {
        $now = now();
        $query->whereNull('revoked_at')
              ->where('effective_from', '<=', $now)
              ->where(function ($q) use ($now) {
                  $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
              });
    }

    /**
     * Scope to overrides for a specific scope type
     */
    public function scopeForScope(Builder $query, string $scope): void
    {
        $query->where('override_scope', $scope);
    }

    /**
     * Scope to overrides for a specific plan
     */
    public function scopeForPlan(Builder $query, int $planId): void
    {
        $query->where('plan_id', $planId);
    }

    // --- BUSINESS LOGIC ---

    /**
     * Check if this override is currently active (effective and not revoked/expired)
     */
    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        $now = now();

        if ($this->effective_from > $now) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at <= $now) {
            return false;
        }

        return true;
    }

    /**
     * V-CONTRACT-HARDENING-CORRECTIVE: Check if another active override exists for same plan+scope
     *
     * @return bool True if a conflicting active override exists
     */
    public function hasConflictingOverride(): bool
    {
        $query = static::forPlan($this->plan_id)
            ->forScope($this->override_scope)
            ->whereNull('revoked_at');

        // Exclude self if this is an existing record
        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Revoke this override with audit trail
     *
     * @param User $admin The admin user revoking the override
     * @param string $reason The reason for revocation
     */
    public function revoke($admin, string $reason): void
    {
        if ($this->revoked_at !== null) {
            throw new \DomainException("Override is already revoked.");
        }

        $this->update([
            'revoked_at' => now(),
            'revoked_by_admin_id' => $admin->id,
            'revocation_reason' => $reason,
        ]);
    }

    /**
     * Get a human-readable description of this override for audit logs
     */
    public function getAuditDescription(): string
    {
        return sprintf(
            "Regulatory Override [%s] for Plan #%d: %s (Ref: %s)",
            $this->override_scope,
            $this->plan_id,
            $this->reason,
            $this->regulatory_reference
        );
    }

    // --- BOOT ---

    protected static function booted()
    {
        // V-CONTRACT-HARDENING-CORRECTIVE: Validate on create
        static::creating(function ($override) {
            // Required audit fields
            if (empty($override->regulatory_reference)) {
                throw new \InvalidArgumentException("Regulatory reference is required for all overrides.");
            }
            if (empty($override->reason)) {
                throw new \InvalidArgumentException("Reason is required for all overrides.");
            }
            if (empty($override->approved_by_admin_id)) {
                throw new \InvalidArgumentException("Admin approval is required for all overrides.");
            }

            // V-CONTRACT-HARDENING-FINAL: Validate scope is permitted
            if (!in_array($override->override_scope, self::PERMITTED_SCOPES)) {
                throw new OverrideSchemaViolationException(
                    "Scope '{$override->override_scope}' is not permitted. " .
                    "Permitted scopes: " . implode(', ', self::PERMITTED_SCOPES),
                    $override->override_scope,
                    $override->override_payload
                );
            }

            // V-CONTRACT-HARDENING-CORRECTIVE: Schema-validate the payload
            $resolver = app(SchemaAwareOverrideResolver::class);
            $resolver->validatePayload($override->override_scope, $override->override_payload);

            // V-CONTRACT-HARDENING-CORRECTIVE: Check for conflicting active override
            // The DB constraint will also catch this, but we provide a better error message
            if ($override->hasConflictingOverride()) {
                throw new \DomainException(
                    "An active override already exists for Plan #{$override->plan_id} scope '{$override->override_scope}'. " .
                    "Revoke the existing override before creating a new one."
                );
            }
        });

        // V-CONTRACT-HARDENING-CORRECTIVE: Validate on update (if payload changes)
        static::updating(function ($override) {
            if ($override->isDirty('override_payload')) {
                $resolver = app(SchemaAwareOverrideResolver::class);
                $resolver->validatePayload($override->override_scope, $override->override_payload);
            }
        });
    }
}
