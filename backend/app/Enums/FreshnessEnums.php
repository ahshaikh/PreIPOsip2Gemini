<?php

namespace App\Enums;

/**
 * FRESHNESS VOCABULARY - FROZEN
 *
 * These enums are locked. Do not add synonyms.
 * No "warning", "critical", "degraded", etc.
 * If vocabulary drifts, philosophy drifts.
 */

/**
 * Artifact Freshness State
 *
 * Describes the temporal health of a single disclosure artifact.
 *
 * FROZEN VALUES - DO NOT MODIFY:
 * - current: Within expected update window / stable
 * - aging: Approaching staleness threshold
 * - stale: Exceeded expected update window
 * - unstable: Excessive changes in stability window
 */
enum ArtifactFreshness: string
{
    case CURRENT = 'current';
    case AGING = 'aging';
    case STALE = 'stale';
    case UNSTABLE = 'unstable';

    public function label(): string
    {
        return match ($this) {
            self::CURRENT => 'Current',
            self::AGING => 'Aging',
            self::STALE => 'Stale',
            self::UNSTABLE => 'Unstable',
        };
    }

    public function isHealthy(): bool
    {
        return $this === self::CURRENT;
    }

    public function requiresAttention(): bool
    {
        return in_array($this, [self::AGING, self::STALE, self::UNSTABLE]);
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::STALE, self::UNSTABLE]);
    }
}

/**
 * Pillar Vitality State
 *
 * Describes the aggregate health of all disclosures in a pillar (category).
 *
 * FROZEN VALUES - DO NOT MODIFY:
 * - healthy: All artifacts current
 * - needs_attention: Any aging OR 1 stale/unstable
 * - at_risk: 2+ stale OR 2+ unstable
 */
enum PillarVitality: string
{
    case HEALTHY = 'healthy';
    case NEEDS_ATTENTION = 'needs_attention';
    case AT_RISK = 'at_risk';

    public function label(): string
    {
        return match ($this) {
            self::HEALTHY => 'Healthy',
            self::NEEDS_ATTENTION => 'Needs Attention',
            self::AT_RISK => 'At Risk',
        };
    }

    public function isHealthy(): bool
    {
        return $this === self::HEALTHY;
    }
}

/**
 * Document Type
 *
 * Determines freshness calculation behavior.
 *
 * FROZEN VALUES - DO NOT MODIFY:
 * - update_required: Expected to change regularly (financials, cap table)
 * - version_controlled: Expected to be stable (articles, bylaws)
 */
enum DocumentType: string
{
    case UPDATE_REQUIRED = 'update_required';
    case VERSION_CONTROLLED = 'version_controlled';

    public function label(): string
    {
        return match ($this) {
            self::UPDATE_REQUIRED => 'Update Required',
            self::VERSION_CONTROLLED => 'Version Controlled',
        };
    }
}

/**
 * Disclosure Pillar
 *
 * Categories for disclosure grouping.
 *
 * FROZEN VALUES - DO NOT MODIFY:
 */
enum DisclosurePillar: string
{
    case GOVERNANCE = 'governance';
    case FINANCIAL = 'financial';
    case LEGAL = 'legal';
    case OPERATIONAL = 'operational';

    public function label(): string
    {
        return match ($this) {
            self::GOVERNANCE => 'Governance',
            self::FINANCIAL => 'Financial',
            self::LEGAL => 'Legal & Risk',
            self::OPERATIONAL => 'Operational',
        };
    }

    public static function all(): array
    {
        return [
            self::GOVERNANCE,
            self::FINANCIAL,
            self::LEGAL,
            self::OPERATIONAL,
        ];
    }
}
