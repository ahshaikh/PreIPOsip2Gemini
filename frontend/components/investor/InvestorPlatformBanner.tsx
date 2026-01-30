/**
 * EPIC 5 Story 5.2 - Investor Platform Banner
 *
 * PURPOSE:
 * Display platform relationship and acknowledgement requirements to logged-in investors.
 * This is a non-dismissible banner that establishes:
 * - Platform provides information for investment decisions
 * - All investments require explicit acknowledgement
 * - Platform is non-advisory
 *
 * DEFENSIVE PRINCIPLES:
 * - Non-dismissible (no close button)
 * - Frontend-owned copy (not from backend)
 * - Clear, prominent placement
 * - Different from public banner (investor-specific messaging)
 *
 * STORY 5.2 INVARIANTS:
 * - Frontend NEVER infers eligibility
 * - Frontend NEVER computes disclosure tier gates
 * - All state comes from backend API response
 */

import { AlertCircle, Shield, FileCheck, Scale } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";

// ============================================================================
// INVESTOR PLATFORM COPY (Frontend-Owned)
// ============================================================================

/**
 * Investor platform relationship statements
 * These are owned by the frontend, not fetched from backend
 */
const INVESTOR_COPY = {
  // Main headline
  headline: "Investment Decision Support",

  // Key points for investors
  keyPoints: [
    {
      key: "decision_support",
      icon: "info",
      text: "Information provided here supports your independent investment decision.",
    },
    {
      key: "explicit_consent",
      icon: "check",
      text: "All investments require explicit acknowledgement of risks before proceeding.",
    },
    {
      key: "non_advisory",
      icon: "scale",
      text: "The platform is non-advisory. We facilitate, we do not recommend.",
    },
    {
      key: "audit_trail",
      icon: "shield",
      text: "Your investment decisions are recorded with immutable snapshots for your protection.",
    },
  ],

  // Short version
  shortStatement:
    "Information provided supports your investment decision. All investments require explicit risk acknowledgement.",

  // Disclosure layer explanation
  layerExplanation: {
    disclosures: "Company-provided information reviewed by platform",
    riskIndicators: "Platform-assessed risk factors",
    materialChanges: "Recent changes requiring your attention",
  },
} as const;

// ============================================================================
// COMPONENT PROPS
// ============================================================================

interface InvestorPlatformBannerProps {
  /**
   * Display variant:
   * - "full": Complete banner with all key points (for deals listing)
   * - "compact": Shorter version (for detail pages)
   * - "minimal": Single line for tight spaces
   */
  variant?: "full" | "compact" | "minimal";

  /**
   * Show explanation of display layers (disclosures/risk/changes)
   */
  showLayerExplanation?: boolean;

  /**
   * Additional CSS classes
   */
  className?: string;
}

// ============================================================================
// ICON MAPPING
// ============================================================================

function getIcon(iconType: string) {
  switch (iconType) {
    case "info":
      return <AlertCircle className="w-4 h-4" />;
    case "check":
      return <FileCheck className="w-4 h-4" />;
    case "scale":
      return <Scale className="w-4 h-4" />;
    case "shield":
      return <Shield className="w-4 h-4" />;
    default:
      return <AlertCircle className="w-4 h-4" />;
  }
}

// ============================================================================
// MAIN COMPONENT
// ============================================================================

export function InvestorPlatformBanner({
  variant = "full",
  showLayerExplanation = false,
  className = "",
}: InvestorPlatformBannerProps) {
  // Minimal variant - single line
  if (variant === "minimal") {
    return (
      <div
        className={`flex items-center gap-2 text-sm text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/30 px-4 py-2 rounded-lg ${className}`}
      >
        <Shield className="w-4 h-4 flex-shrink-0" />
        <span>{INVESTOR_COPY.shortStatement}</span>
      </div>
    );
  }

  // Compact variant - alert style
  if (variant === "compact") {
    return (
      <Alert
        className={`border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/30 ${className}`}
      >
        <Shield className="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
        <AlertTitle className="text-indigo-900 dark:text-indigo-100 font-semibold">
          {INVESTOR_COPY.headline}
        </AlertTitle>
        <AlertDescription className="text-sm text-indigo-700 dark:text-indigo-300">
          {INVESTOR_COPY.shortStatement}
        </AlertDescription>
      </Alert>
    );
  }

  // Full variant - complete banner with all points
  return (
    <div
      className={`bg-gradient-to-r from-indigo-50 to-violet-50 dark:from-indigo-950/40 dark:to-violet-950/40 border border-indigo-200 dark:border-indigo-800 rounded-xl p-6 ${className}`}
    >
      {/* Header */}
      <div className="flex items-center gap-3 mb-4">
        <div className="bg-indigo-100 dark:bg-indigo-900/50 rounded-full p-2">
          <Shield className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
        </div>
        <h3 className="text-lg font-bold text-indigo-900 dark:text-indigo-100">
          {INVESTOR_COPY.headline}
        </h3>
      </div>

      {/* Key Points */}
      <div className="grid sm:grid-cols-2 gap-3 mb-4">
        {INVESTOR_COPY.keyPoints.map((point) => (
          <div
            key={point.key}
            className="flex items-start gap-2 text-sm text-indigo-800 dark:text-indigo-200"
          >
            <span className="text-indigo-500 dark:text-indigo-400 mt-0.5">
              {getIcon(point.icon)}
            </span>
            <span>{point.text}</span>
          </div>
        ))}
      </div>

      {/* Display Layer Explanation (optional) */}
      {showLayerExplanation && (
        <div className="mt-4 pt-4 border-t border-indigo-200 dark:border-indigo-700">
          <p className="text-xs font-semibold text-indigo-900 dark:text-indigo-100 mb-2">
            Information is organized into three layers:
          </p>
          <div className="grid sm:grid-cols-3 gap-2 text-xs text-indigo-700 dark:text-indigo-300">
            <div className="bg-white/50 dark:bg-black/20 rounded px-2 py-1">
              <span className="font-medium">Disclosures:</span>{" "}
              {INVESTOR_COPY.layerExplanation.disclosures}
            </div>
            <div className="bg-white/50 dark:bg-black/20 rounded px-2 py-1">
              <span className="font-medium">Risk Indicators:</span>{" "}
              {INVESTOR_COPY.layerExplanation.riskIndicators}
            </div>
            <div className="bg-white/50 dark:bg-black/20 rounded px-2 py-1">
              <span className="font-medium">Material Changes:</span>{" "}
              {INVESTOR_COPY.layerExplanation.materialChanges}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

// ============================================================================
// STATIC EXPORTS
// ============================================================================

export const InvestorPlatformCopy = INVESTOR_COPY;

export default InvestorPlatformBanner;
