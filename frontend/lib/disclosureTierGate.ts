/**
 * EPIC 5 Story 5.1 - Disclosure Tier Gate
 *
 * PURPOSE:
 * Frontend enforcement of disclosure tier visibility rules.
 * Only companies at tier_2_live or higher should be displayed on public pages.
 *
 * RULE:
 * Public visibility requires disclosure_tier >= tier_2_live
 *
 * TIERS (from DisclosureTier enum):
 * - tier_0_pending    → NOT public (no disclosures yet)
 * - tier_1_upcoming   → NOT public (under review)
 * - tier_2_live       → PUBLIC (approved, investable)
 * - tier_3_featured   → PUBLIC (premium visibility)
 *
 * DEFENSIVE PRINCIPLES:
 * - Default to NOT visible if tier is missing or unknown
 * - Log breaches when non-public companies appear in public API
 * - This is UI-level enforcement only (backend is authoritative but frozen)
 */

import { logTierVisibilityBreach } from './backendBreachLogger';

// ============================================================================
// TIER CONSTANTS
// ============================================================================

/**
 * Disclosure tier values matching backend DisclosureTier enum
 */
export const DisclosureTiers = {
  TIER_0_PENDING: 'tier_0_pending',
  TIER_1_UPCOMING: 'tier_1_upcoming',
  TIER_2_LIVE: 'tier_2_live',
  TIER_3_FEATURED: 'tier_3_featured',
} as const;

export type DisclosureTier = typeof DisclosureTiers[keyof typeof DisclosureTiers];

/**
 * Tiers that are publicly visible (per EPIC 1-4 rules)
 */
export const PUBLIC_VISIBLE_TIERS: ReadonlySet<string> = new Set([
  DisclosureTiers.TIER_2_LIVE,
  DisclosureTiers.TIER_3_FEATURED,
]);

/**
 * Tier rank for comparison operations
 */
const TIER_RANKS: Record<string, number> = {
  [DisclosureTiers.TIER_0_PENDING]: 0,
  [DisclosureTiers.TIER_1_UPCOMING]: 1,
  [DisclosureTiers.TIER_2_LIVE]: 2,
  [DisclosureTiers.TIER_3_FEATURED]: 3,
};

// ============================================================================
// VISIBILITY CHECK FUNCTIONS
// ============================================================================

/**
 * Check if a disclosure tier is publicly visible
 *
 * @param tier - The disclosure tier value
 * @returns true if tier is tier_2_live or tier_3_featured
 */
export function isPubliclyVisibleTier(tier: string | undefined | null): boolean {
  if (!tier) return false;
  return PUBLIC_VISIBLE_TIERS.has(tier);
}

/**
 * Check if a company should show 404 on public pages
 *
 * @param company - Company object with disclosure_tier field
 * @returns true if company should NOT be shown (404)
 */
export function shouldShow404(company: any): boolean {
  if (!company) return true;

  const tier = company.disclosure_tier;

  // If tier is missing, default to NOT showing (defensive)
  if (!tier) return true;

  // If tier is not publicly visible, show 404
  return !isPubliclyVisibleTier(tier);
}

/**
 * Check if a company is publicly visible and log breach if not
 *
 * @param company - Company object with disclosure_tier field
 * @returns true if company should be displayed publicly
 */
export function checkPublicVisibility(company: any): boolean {
  if (!company) return false;

  const tier = company.disclosure_tier;
  const isVisible = isPubliclyVisibleTier(tier);

  // If company has a non-public tier but appears in public API, log breach
  if (!isVisible && tier) {
    logTierVisibilityBreach(company.slug || 'unknown', tier, {
      companyId: company.id,
    });
  }

  return isVisible;
}

/**
 * Get the tier rank (for comparison operations)
 *
 * @param tier - The disclosure tier value
 * @returns Numeric rank (0-3), or -1 if invalid
 */
export function getTierRank(tier: string | undefined | null): number {
  if (!tier) return -1;
  return TIER_RANKS[tier] ?? -1;
}

/**
 * Check if tier A is at or above tier B
 *
 * @param tierA - First tier to compare
 * @param tierB - Minimum required tier
 * @returns true if tierA >= tierB
 */
export function isAtOrAboveTier(
  tierA: string | undefined | null,
  tierB: string
): boolean {
  const rankA = getTierRank(tierA);
  const rankB = getTierRank(tierB);
  return rankA >= rankB;
}

/**
 * Get human-readable tier label (for internal use only, not public display)
 *
 * @param tier - The disclosure tier value
 * @returns Human-readable label
 */
export function getTierLabel(tier: string | undefined | null): string {
  switch (tier) {
    case DisclosureTiers.TIER_0_PENDING:
      return 'Pending';
    case DisclosureTiers.TIER_1_UPCOMING:
      return 'Upcoming';
    case DisclosureTiers.TIER_2_LIVE:
      return 'Live';
    case DisclosureTiers.TIER_3_FEATURED:
      return 'Featured';
    default:
      return 'Unknown';
  }
}

// ============================================================================
// LIST FILTERING
// ============================================================================

/**
 * Filter a list of companies to only include publicly visible ones
 *
 * @param companies - Array of company objects
 * @returns Filtered array with only tier_2_live+ companies
 */
export function filterPubliclyVisible<T extends { disclosure_tier?: string }>(
  companies: T[]
): T[] {
  if (!Array.isArray(companies)) return [];

  return companies.filter(company => {
    const isVisible = isPubliclyVisibleTier(company.disclosure_tier);

    // Log breach if non-public company was in the list
    if (!isVisible && company.disclosure_tier) {
      logTierVisibilityBreach(
        (company as any).slug || 'unknown',
        company.disclosure_tier,
        { companyId: (company as any).id }
      );
    }

    return isVisible;
  });
}

/**
 * Count how many companies in a list are NOT publicly visible
 * (for breach detection statistics)
 *
 * @param companies - Array of company objects
 * @returns Count of non-public companies
 */
export function countNonPublicCompanies<T extends { disclosure_tier?: string }>(
  companies: T[]
): number {
  if (!Array.isArray(companies)) return 0;

  return companies.filter(
    company => !isPubliclyVisibleTier(company.disclosure_tier)
  ).length;
}
