<?php
/**
 * V-AUDIT-FIX-2026: Company Tier Changed Event
 *
 * Fired when a company's disclosure tier is promoted.
 * Enables downstream listeners to react to tier changes.
 *
 * GOVERNANCE:
 * - Only fired by CompanyDisclosureTierService (sole authority)
 * - Promotions are monotonic (no downgrades)
 * - Contains full audit context for compliance
 */

namespace App\Events;

use App\Enums\DisclosureTier;
use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyTierChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Company $company;
    public DisclosureTier $fromTier;
    public DisclosureTier $toTier;
    public $actor;
    public string $justification;
    public array $metadata;
    public \DateTimeInterface $promotedAt;

    /**
     * Create a new event instance.
     *
     * @param Company $company The company that was promoted
     * @param DisclosureTier $fromTier The previous tier
     * @param DisclosureTier $toTier The new tier
     * @param mixed $actor The user who performed the promotion (User model or null for system)
     * @param string $justification Reason for promotion
     * @param array $metadata Additional context
     * @param \DateTimeInterface $promotedAt When the promotion occurred
     */
    public function __construct(
        Company $company,
        DisclosureTier $fromTier,
        DisclosureTier $toTier,
        $actor,
        string $justification,
        array $metadata,
        \DateTimeInterface $promotedAt
    ) {
        $this->company = $company;
        $this->fromTier = $fromTier;
        $this->toTier = $toTier;
        $this->actor = $actor;
        $this->justification = $justification;
        $this->metadata = $metadata;
        $this->promotedAt = $promotedAt;
    }

    /**
     * Get audit context for logging.
     */
    public function getAuditContext(): array
    {
        return [
            'event' => 'company_tier_changed',
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'from_tier' => $this->fromTier->value,
            'to_tier' => $this->toTier->value,
            'actor_type' => $this->actor ? get_class($this->actor) : 'system',
            'actor_id' => $this->actor?->id,
            'justification' => $this->justification,
            'promoted_at' => $this->promotedAt->format('Y-m-d H:i:s'),
            'is_now_publicly_visible' => $this->toTier->isPubliclyVisible(),
            'is_now_investable' => $this->toTier->isInvestable(),
        ];
    }
}
