<?php

// V-DISPUTE-RISK-2026-005: Dispute Model
// V-DISPUTE-MGMT-2026: Enhanced with polymorphic attachments and state machine

namespace App\Models;

use App\Enums\DisputeStatus;
use App\Enums\DisputeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dispute Model - Tracks disputes between investors, companies, and platform.
 *
 * V-DISPUTE-MGMT-2026 Enhancements:
 * - Polymorphic attachment to Payment, Investment, Withdrawal, BonusTransaction, Allocation
 * - 7-state machine: open, under_review, awaiting_investor, escalated, resolved_approved, resolved_rejected, closed
 * - Type classification with risk levels (confusion, payment, allocation, fraud)
 * - Immutable snapshot capture at filing time
 * - Append-only timeline for complete audit trail
 *
 * Used by:
 * - BuyEnablementGuardService: To block investments for companies with active disputes
 * - RiskScoringService: To factor open disputes into user risk score
 * - DisputeStateMachine: Enforces valid state transitions
 * - Admin dispute management system
 *
 * @property int $id
 * @property string|null $disputable_type
 * @property int|null $disputable_id
 * @property string $type
 * @property int|null $company_id
 * @property int|null $user_id
 * @property int|null $raised_by_user_id
 * @property string $status
 * @property string $severity
 * @property string $category
 * @property string $title
 * @property string $description
 * @property array|null $evidence
 * @property string|null $resolution
 * @property string|null $settlement_action
 * @property int|null $settlement_amount_paise
 * @property array|null $settlement_details
 * @property string|null $admin_notes
 * @property int|null $assigned_to_admin_id
 * @property \Carbon\Carbon|null $opened_at
 * @property \Carbon\Carbon|null $investigation_started_at
 * @property \Carbon\Carbon|null $resolved_at
 * @property \Carbon\Carbon|null $closed_at
 * @property \Carbon\Carbon|null $sla_deadline_at
 * @property \Carbon\Carbon|null $escalation_deadline_at
 * @property \Carbon\Carbon|null $escalated_at
 * @property int $risk_score
 * @property bool $blocks_investment
 * @property bool $requires_platform_freeze
 */
class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'disputes';

    // Legacy status constants (for backward compatibility)
    public const STATUS_OPEN = 'open';
    public const STATUS_UNDER_INVESTIGATION = 'under_investigation';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_AWAITING_INVESTOR = 'awaiting_investor';
    public const STATUS_ESCALATED = 'escalated';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_RESOLVED_APPROVED = 'resolved_approved';
    public const STATUS_RESOLVED_REJECTED = 'resolved_rejected';
    public const STATUS_CLOSED = 'closed';

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

    // Settlement action constants
    public const SETTLEMENT_REFUND = 'refund';
    public const SETTLEMENT_CREDIT = 'credit';
    public const SETTLEMENT_ALLOCATION_CORRECTION = 'allocation_correction';
    public const SETTLEMENT_NONE = 'none';

    protected $fillable = [
        'disputable_type',
        'disputable_id',
        'type',
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
        'settlement_action',
        'settlement_amount_paise',
        'settlement_details',
        'admin_notes',
        'assigned_to_admin_id',
        'opened_at',
        'investigation_started_at',
        'resolved_at',
        'closed_at',
        'sla_deadline_at',
        'escalation_deadline_at',
        'escalated_at',
        'risk_score',
        'blocks_investment',
        'requires_platform_freeze',
    ];

    protected $casts = [
        'evidence' => 'array',
        'settlement_details' => 'array',
        'opened_at' => 'datetime',
        'investigation_started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_deadline_at' => 'datetime',
        'escalation_deadline_at' => 'datetime',
        'escalated_at' => 'datetime',
        'blocks_investment' => 'boolean',
        'requires_platform_freeze' => 'boolean',
        'risk_score' => 'integer',
    ];

    // Relationships

    /**
     * Polymorphic relationship to the disputed entity.
     * Can be: Payment, Investment, Withdrawal, BonusTransaction, Allocation
     */
    public function disputable(): MorphTo
    {
        return $this->morphTo();
    }

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

    /**
     * Relationship: Immutable snapshot captured at filing time.
     */
    public function snapshot(): HasOne
    {
        return $this->hasOne(DisputeSnapshot::class);
    }

    /**
     * Relationship: Append-only timeline of all dispute events.
     */
    public function timeline(): HasMany
    {
        return $this->hasMany(DisputeTimeline::class)->orderBy('created_at', 'asc');
    }

    /**
     * Relationship: Timeline entries visible to investor.
     */
    public function investorTimeline(): HasMany
    {
        return $this->hasMany(DisputeTimeline::class)
            ->where('visible_to_investor', true)
            ->where('is_internal_note', false)
            ->orderBy('created_at', 'asc');
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
            self::STATUS_UNDER_REVIEW,
            self::STATUS_AWAITING_INVESTOR,
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
            self::STATUS_UNDER_REVIEW,
            self::STATUS_AWAITING_INVESTOR,
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
            self::STATUS_UNDER_REVIEW,
            self::STATUS_AWAITING_INVESTOR,
            self::STATUS_ESCALATED,
            self::STATUS_RESOLVED,
            self::STATUS_RESOLVED_APPROVED,
            self::STATUS_RESOLVED_REJECTED,
            self::STATUS_CLOSED,
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

    // V-DISPUTE-MGMT-2026: Enum-based helpers

    /**
     * Get the DisputeStatus enum for current status.
     */
    public function getStatusEnum(): ?DisputeStatus
    {
        return DisputeStatus::tryFrom($this->status);
    }

    /**
     * Get the DisputeType enum for current type.
     */
    public function getTypeEnum(): ?DisputeType
    {
        return DisputeType::tryFrom($this->type);
    }

    /**
     * Check if transition to target status is allowed by state machine.
     */
    public function canTransitionTo(DisputeStatus $targetStatus): bool
    {
        $currentStatus = $this->getStatusEnum();
        if (!$currentStatus) {
            return false;
        }
        return $currentStatus->canTransitionTo($targetStatus);
    }

    /**
     * Check if this dispute requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        $type = $this->getTypeEnum();
        if ($type && $type->requiresImmediateEscalation()) {
            return true;
        }
        return $this->risk_score >= 4 || $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Check if SLA deadline has been breached.
     */
    public function isSlaBreached(): bool
    {
        return $this->sla_deadline_at && $this->sla_deadline_at->isPast() && $this->isActive();
    }

    /**
     * Check if auto-escalation deadline has been breached.
     */
    public function shouldAutoEscalate(): bool
    {
        if (!$this->isActive() || $this->status === self::STATUS_ESCALATED) {
            return false;
        }
        return $this->escalation_deadline_at && $this->escalation_deadline_at->isPast();
    }

    /**
     * Get settlement action options.
     */
    public static function getSettlementActions(): array
    {
        return [
            self::SETTLEMENT_REFUND,
            self::SETTLEMENT_CREDIT,
            self::SETTLEMENT_ALLOCATION_CORRECTION,
            self::SETTLEMENT_NONE,
        ];
    }

    /**
     * Get disputable type short name (for display).
     */
    public function getDisputableTypeName(): ?string
    {
        if (!$this->disputable_type) {
            return null;
        }
        return class_basename($this->disputable_type);
    }

    /**
     * Check if dispute has an associated snapshot.
     */
    public function hasSnapshot(): bool
    {
        return $this->snapshot()->exists();
    }

    /**
     * Check if snapshot integrity is valid.
     */
    public function isSnapshotValid(): bool
    {
        $snapshot = $this->snapshot;
        return $snapshot && $snapshot->verifyIntegrity();
    }

    /**
     * Scope: Disputes past their escalation deadline.
     */
    public function scopeReadyForAutoEscalation($query)
    {
        return $query->active()
            ->where('status', '!=', self::STATUS_ESCALATED)
            ->whereNotNull('escalation_deadline_at')
            ->where('escalation_deadline_at', '<', now());
    }

    /**
     * Scope: Disputes past their SLA deadline.
     */
    public function scopeSlaBreach($query)
    {
        return $query->active()
            ->whereNotNull('sla_deadline_at')
            ->where('sla_deadline_at', '<', now());
    }

    /**
     * Scope: By dispute type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: By disputable entity.
     */
    public function scopeForDisputable($query, Model $disputable)
    {
        return $query->where('disputable_type', get_class($disputable))
            ->where('disputable_id', $disputable->getKey());
    }

    /**
     * Scope: High risk disputes (allocation or fraud types).
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('type', ['allocation', 'fraud']);
    }
}
