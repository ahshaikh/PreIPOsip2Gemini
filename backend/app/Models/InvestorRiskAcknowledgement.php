<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Investor Risk Acknowledgement Model
 *
 * Tracks investor risk acknowledgements with full audit trail.
 * Required before any investment can proceed.
 *
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property string $acknowledgement_type
 * @property \Carbon\Carbon $acknowledged_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $session_id
 * @property int|null $investment_id
 * @property string|null $acknowledgement_text_shown
 * @property int|null $platform_context_snapshot_id
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $is_expired
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InvestorRiskAcknowledgement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'investor_risk_acknowledgements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'acknowledgement_type',
        'acknowledged_at',
        'ip_address',
        'user_agent',
        'session_id',
        'investment_id',
        'acknowledgement_text_shown',
        'platform_context_snapshot_id',
        'expires_at',
        'is_expired',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'acknowledged_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_expired' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Acknowledgement types
     */
    public const TYPE_ILLIQUIDITY = 'illiquidity';
    public const TYPE_NO_GUARANTEE = 'no_guarantee';
    public const TYPE_PLATFORM_NON_ADVISORY = 'platform_non_advisory';
    public const TYPE_MATERIAL_CHANGES = 'material_changes';

    /**
     * Get the user (investor) who made the acknowledgement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company this acknowledgement is for.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the investment this acknowledgement is linked to (if any).
     */
    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }

    /**
     * Get the platform context snapshot (if captured).
     */
    public function platformContextSnapshot(): BelongsTo
    {
        return $this->belongsTo(PlatformContextSnapshot::class, 'platform_context_snapshot_id');
    }

    /**
     * Check if acknowledgement is still valid (not expired).
     */
    public function isValid(): bool
    {
        if ($this->is_expired) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Mark acknowledgement as expired.
     */
    public function markAsExpired(): bool
    {
        return $this->update(['is_expired' => true]);
    }

    /**
     * Scope: Get valid (non-expired) acknowledgements.
     */
    public function scopeValid($query)
    {
        return $query->where('is_expired', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: Get acknowledgements for a specific user and company.
     */
    public function scopeForUserAndCompany($query, int $userId, int $companyId)
    {
        return $query->where('user_id', $userId)
            ->where('company_id', $companyId);
    }

    /**
     * Scope: Get acknowledgements of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('acknowledgement_type', $type);
    }
}
