/**
 * EPIC 5 Story 5.1 - Platform Relationship Banner
 *
 * PURPOSE:
 * Display the platform's relationship with listed companies to public visitors.
 * This is a non-dismissible, frontend-owned banner that establishes:
 * - The listing is informational only
 * - This is NOT an offer to sell or solicitation to buy
 * - Investment requires registration and verification
 *
 * DEFENSIVE PRINCIPLES:
 * - Non-dismissible (no close button)
 * - Frontend-owned copy (not from backend)
 * - Clear, prominent placement
 * - Distinct visual style from warning disclaimers
 *
 * COMPLIANCE:
 * Per Story 5.1 A3 - Explicit platform statement must be shown
 * Per Story 5.1 A4 - No implication of investment availability
 */

import { Info, Building2 } from "lucide-react";

// ============================================================================
// PLATFORM RELATIONSHIP COPY (Frontend-Owned)
// ============================================================================

/**
 * Platform relationship statements
 * These are owned by the frontend, not fetched from backend
 */
const PLATFORM_COPY = {
  // Main statement
  headline: "Listed for Informational Purposes Only",

  // Detailed disclaimer points
  disclaimers: [
    {
      key: "not_offer",
      text: "This listing does not constitute an offer to sell or a solicitation to buy any security.",
    },
    {
      key: "not_advice",
      text: "Information presented is not investment advice and should not be construed as a recommendation.",
    },
    {
      key: "registration_required",
      text: "Investment opportunities are available only through registered accounts with completed verification.",
    },
    {
      key: "platform_role",
      text: "The platform facilitates information access but does not endorse or guarantee any listing.",
    },
  ],

  // Short version for compact display
  shortStatement:
    "This company is listed for informational purposes only. This is not an offer to sell or buy securities.",

  // Call to action guidance
  ctaGuidance:
    "To access detailed information and investment opportunities, create a free account.",
} as const;

// ============================================================================
// COMPONENT PROPS
// ============================================================================

interface PlatformRelationshipBannerProps {
  /**
   * Display variant:
   * - "prominent": Full banner with all disclaimers (for detail pages)
   * - "compact": Shorter version (for listing pages)
   * - "inline": Single line for tight spaces
   */
  variant?: "prominent" | "compact" | "inline";

  /**
   * Additional CSS classes
   */
  className?: string;
}

// ============================================================================
// MAIN COMPONENT
// ============================================================================

export function PlatformRelationshipBanner({
  variant = "prominent",
  className = "",
}: PlatformRelationshipBannerProps) {
  // Inline variant - minimal text
  if (variant === "inline") {
    return (
      <div
        className={`flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300 ${className}`}
      >
        <Info className="w-4 h-4 flex-shrink-0" />
        <span>{PLATFORM_COPY.shortStatement}</span>
      </div>
    );
  }

  // Compact variant - short banner
  if (variant === "compact") {
    return (
      <div
        className={`bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 ${className}`}
      >
        <div className="flex items-start gap-3">
          <Info className="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
              {PLATFORM_COPY.headline}
            </p>
            <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
              {PLATFORM_COPY.shortStatement}
            </p>
          </div>
        </div>
      </div>
    );
  }

  // Prominent variant - full banner with all disclaimers
  return (
    <div
      className={`bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6 ${className}`}
    >
      <div className="flex items-start gap-4">
        {/* Icon */}
        <div className="bg-blue-100 dark:bg-blue-900/50 rounded-full p-3 flex-shrink-0">
          <Building2 className="w-6 h-6 text-blue-600 dark:text-blue-400" />
        </div>

        {/* Content */}
        <div className="flex-1">
          {/* Headline */}
          <h3 className="text-lg font-bold text-blue-900 dark:text-blue-100 mb-3">
            {PLATFORM_COPY.headline}
          </h3>

          {/* Disclaimer Points */}
          <ul className="space-y-2 text-sm text-blue-800 dark:text-blue-200">
            {PLATFORM_COPY.disclaimers.map((disclaimer) => (
              <li key={disclaimer.key} className="flex items-start gap-2">
                <span className="text-blue-400 dark:text-blue-500 mt-1">•</span>
                <span>{disclaimer.text}</span>
              </li>
            ))}
          </ul>

          {/* CTA Guidance */}
          <div className="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
            <p className="text-sm text-blue-700 dark:text-blue-300">
              {PLATFORM_COPY.ctaGuidance}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

// ============================================================================
// STATIC TEXT EXPORTS (for use elsewhere if needed)
// ============================================================================

export const PlatformRelationshipCopy = PLATFORM_COPY;

export default PlatformRelationshipBanner;
