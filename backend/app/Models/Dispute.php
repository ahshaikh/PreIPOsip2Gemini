<?php

// V-DISPUTE-RISK-2026-005: Dispute Model

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dispute Model - Tracks disputes between investors, companies, and platform.
 *
 * Used by:
 * - BuyEnablementGuardService: To block investments for companies with active disputes
 * - RiskScoringService: To factor open disputes into user risk score
 * - Admin dispute management system
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $user_id
 * @property int|null $raised_by_user_id
 * @property string $status
 * @property string $severity
 * @property string $category
 * @property string $title
 * @property string $description
 * @property array|null $evidence
 * @property string|null $resolution
 * @property string|null $admin_notes
 * @property int|null $assigned_to_admin_id
 * @property \Carbon\Carbon|null $opened_at
 * @property \Carbon\Carbon|null $investigation_started_at
 * @property \Carbon\Carbon|null $resolved_at
 * @property \Carbon\Carbon|null $closed_at
 * @property bool $blocks_investment
 * @property bool $requires_platform_freeze
 */
class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'disputes';

    // Status constants
    public const STATUS_OPEN = 'open';
    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ESCALATED = 'escalated';

    // Severity constants
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    // Category constants
    public const CATEGORY_FINANCIAL_DISCLOSURE = 'financial_disclosure';
    public const CATEGORY_INVESTMENT_PROCESSING = 'investment_processing';
    public const CATEGORY_KYC_VERIFICATION = 'kyc_verification';
    public const CATEGORY_FUND_TRANSFER = 'fund_transfer';
    public const CATEGORY_PLATFORM_SERVICE = 'platform_service';
    public const CATEGORY_COMPANY_CONDUCT = 'company_conduct';
    public const CATEGORY_INVESTOR_CONDUCT = 'investor_conduct';
    public const CATEGORY_OTHER = 'other';

    protected $fillable = [
        'company_id',
        'user_id',
        'raised_by_user_id',
        'status',
        'severity',
        'category',
        'title',
        'description',
        'evidence',
        'resolution',
        'admin_notes',
        'assigned_to_admin_id',
        'opened_at',
        'investigation_started_at',
        'resolved_at',
        'closed_at',
        'blocks_investment',
        'requires_platform_freeze',
    ];

    protected $casts = [
        'evidence' => 'array',
        'opened_at' => 'datetime',
        'investigation_started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'blocks_investment' => 'boolean',
        'requires_platform_freeze' => 'boolean',
    ];

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_user_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_admin_id');
    }

    // Scopes

    /**
     * Scope: Open disputes only.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope: Active disputes (not resolved or closed).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_OPEN,
            self::STATUS_UNDER_INVESTIGATION,
            self::STATUS_ESCALATED,
        ]);
    }

    /**
     * Scope: Disputes that block investment.
     */
    public function scopeBlockingInvestment($query)
    {
        return $query->active()->where('blocks_investment', true);
    }

    /**
     * Scope: For a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: By severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Critical or high severity.
     */
    public function scopeCriticalOrHigh($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH]);
    }

    // Helper methods

    /**
     * Check if dispute is active (not resolved/closed).
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN,
            self::STATUS_UNDER_INVESTIGATION,
            self::STATUS_ESCALATED,
        ]);
    }

    /**
     * Check if dispute is critical or high severity.
     */
    public function isCriticalOrHigh(): bool
    {
        return in_array($this->severity, [self::SEVERITY_CRITICAL, self::SEVERITY_HIGH]);
    }

    /**
     * Check if dispute blocks investments.
     */
    public function blocksInvestments(): bool
    {
        return $this->isActive() && $this->blocks_investment;
    }

    /**
     * Get all valid statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_UNDER_INVESTIGATION,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
            self::STATUS_ESCALATED,
        ];
    }

    /**
     * Get all valid severities.
     */
    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_LOW,
            self::SEVERITY_MEDIUM,
            self::SEVERITY_HIGH,
            self::SEVERITY_CRITICAL,
        ];
    }

    /**
     * Get all valid categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_FINANCIAL_DISCLOSURE,
            self::CATEGORY_INVESTMENT_PROCESSING,
            self::CATEGORY_KYC_VERIFICATION,
            self::CATEGORY_FUND_TRANSFER,
            self::CATEGORY_PLATFORM_SERVICE,
            self::CATEGORY_COMPANY_CONDUCT,
            self::CATEGORY_INVESTOR_CONDUCT,
            self::CATEGORY_OTHER,
        ];
    }
}
