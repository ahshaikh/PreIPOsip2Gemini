<?php
/**
 * STORY 3.1: Disclosure Tier Enum
 *
 * GOVERNANCE INVARIANT:
 * - Every company has exactly one disclosure tier
 * - Tiers are MONOTONICALLY PROGRESSIVE: tier_0 → tier_1 → tier_2 → tier_3
 * - No downgrade is permitted
 * - No tier skipping is permitted
 * - Public visibility requires tier_2_live or higher
 *
 * TIER DEFINITIONS:
 * - tier_0_pending: Newly registered, no disclosures submitted
 * - tier_1_upcoming: Disclosures under review, not investable
 * - tier_2_live: Approved for public investment
 * - tier_3_featured: Premium visibility, editorial selection
 */

namespace App\Enums;

enum DisclosureTier: string
{
    case TIER_0_PENDING = 'tier_0_pending';
    case TIER_1_UPCOMING = 'tier_1_upcoming';
    case TIER_2_LIVE = 'tier_2_live';
    case TIER_3_FEATURED = 'tier_3_featured';

    /**
     * Get the numeric rank of this tier (for comparison).
     */
    public function rank(): int
    {
        return match ($this) {
            self::TIER_0_PENDING => 0,
            self::TIER_1_UPCOMING => 1,
            self::TIER_2_LIVE => 2,
            self::TIER_3_FEATURED => 3,
        };
    }

    /**
     * Get the human-readable label for this tier.
     */
    public function label(): string
    {
        return match ($this) {
            self::TIER_0_PENDING => 'Pending',
            self::TIER_1_UPCOMING => 'Upcoming',
            self::TIER_2_LIVE => 'Live',
            self::TIER_3_FEATURED => 'Featured',
        };
    }

    /**
     * Get the description for this tier.
     */
    public function description(): string
    {
        return match ($this) {
            self::TIER_0_PENDING => 'Company registered, disclosures not yet submitted',
            self::TIER_1_UPCOMING => 'Disclosures under review, not yet investable',
            self::TIER_2_LIVE => 'Approved for public investment',
            self::TIER_3_FEATURED => 'Featured company with premium visibility',
        };
    }

    /**
     * Check if this tier allows public visibility.
     *
     * INVARIANT: Only tier_2_live and tier_3_featured are publicly visible.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->rank() >= self::TIER_2_LIVE->rank();
    }

    /**
     * Check if this tier allows investment.
     *
     * INVARIANT: Investment requires tier_2_live or higher.
     */
    public function isInvestable(): bool
    {
        return $this->rank() >= self::TIER_2_LIVE->rank();
    }

    /**
     * Get the next tier in progression (or null if at maximum).
     */
    public function nextTier(): ?self
    {
        return match ($this) {
            self::TIER_0_PENDING => self::TIER_1_UPCOMING,
            self::TIER_1_UPCOMING => self::TIER_2_LIVE,
            self::TIER_2_LIVE => self::TIER_3_FEATURED,
            self::TIER_3_FEATURED => null,
        };
    }

    /**
     * Check if promotion to target tier is valid.
     *
     * RULES:
     * 1. Cannot promote to same tier
     * 2. Cannot skip tiers
     * 3. Cannot downgrade
     */
    public function canPromoteTo(self $target): bool
    {
        // Must be exactly one tier higher
        return $target->rank() === $this->rank() + 1;
    }

    /**
     * Get all tiers that are publicly visible.
     *
     * @return array<self>
     */
    public static function publiclyVisibleTiers(): array
    {
        return [
            self::TIER_2_LIVE,
            self::TIER_3_FEATURED,
        ];
    }

    /**
     * Get the minimum tier for public visibility.
     */
    public static function minimumPublicTier(): self
    {
        return self::TIER_2_LIVE;
    }

    /**
     * Get all tier values as strings.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all tiers as options for forms/dropdowns.
     *
     * @return array<array{value: string, label: string, description: string, publicly_visible: bool}>
     */
    public static function options(): array
    {
        return array_map(fn(self $tier) => [
            'value' => $tier->value,
            'label' => $tier->label(),
            'description' => $tier->description(),
            'publicly_visible' => $tier->isPubliclyVisible(),
            'rank' => $tier->rank(),
        ], self::cases());
    }
}
