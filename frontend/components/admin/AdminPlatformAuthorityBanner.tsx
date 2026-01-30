/**
 * EPIC 5 Story 5.4 - Admin Platform Authority Banner
 *
 * PURPOSE:
 * Explicitly label all admin actions as PLATFORM decisions (not issuer data).
 * Makes platform authority visible, not implied.
 *
 * STORY 5.4 REQUIREMENTS:
 * - Admin actions are explicitly labeled as platform decisions
 * - No admin action is visually conflated with issuer data
 * - Platform judgments are clearly labeled as such
 *
 * INVARIANTS:
 * - ❌ No ambiguous authority (platform vs issuer)
 * - ✅ Backend authority must be visible, not implied
 */

import { ShieldAlert, Building2, Lock, AlertTriangle, ExternalLink } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import Link from "next/link";

// ============================================================================
// TYPES
// ============================================================================

export type PlatformActionType =
  | "visibility"
  | "suspension"
  | "freeze"
  | "buying"
  | "tier_approval"
  | "risk_flag"
  | "general";

interface AdminPlatformAuthorityBannerProps {
  /**
   * Type of platform action being taken
   */
  actionType: PlatformActionType;

  /**
   * Company ID for audit trail link
   */
  companyId?: number;

  /**
   * Name of the admin taking action (for display)
   */
  adminName?: string;

  /**
   * Additional context message
   */
  contextMessage?: string;

  /**
   * Variant: full (with explanation) or compact (badge only)
   */
  variant?: "full" | "compact" | "inline";

  className?: string;
}

// ============================================================================
// ACTION CONFIGURATIONS
// ============================================================================

const ACTION_CONFIG: Record<PlatformActionType, {
  title: string;
  description: string;
  icon: React.ReactNode;
  severity: "critical" | "warning" | "info";
}> = {
  visibility: {
    title: "Platform Visibility Control",
    description: "Controls whether this company appears on public or subscriber views. This is a platform decision that affects investor discovery.",
    icon: <Building2 className="w-5 h-5" />,
    severity: "warning",
  },
  suspension: {
    title: "Platform Suspension Authority",
    description: "Suspending a company blocks all issuer activities and investor purchases. This is an irreversible platform enforcement action.",
    icon: <ShieldAlert className="w-5 h-5" />,
    severity: "critical",
  },
  freeze: {
    title: "Platform Disclosure Freeze",
    description: "Freezing disclosures prevents the issuer from editing or submitting any information. Existing investor data remains accessible.",
    icon: <Lock className="w-5 h-5" />,
    severity: "critical",
  },
  buying: {
    title: "Platform Buying Control",
    description: "Controls whether new investments can be made. Existing investments and holdings are unaffected.",
    icon: <AlertTriangle className="w-5 h-5" />,
    severity: "warning",
  },
  tier_approval: {
    title: "Platform Tier Approval",
    description: "Tier approvals determine disclosure visibility levels. This is a platform governance decision that affects investor access.",
    icon: <ShieldAlert className="w-5 h-5" />,
    severity: "info",
  },
  risk_flag: {
    title: "Platform Risk Assessment",
    description: "Risk flags are platform-generated assessments visible to investors. Issuers cannot edit or remove these flags.",
    icon: <AlertTriangle className="w-5 h-5" />,
    severity: "warning",
  },
  general: {
    title: "Platform Authority Action",
    description: "This action is taken under platform governance authority. All changes are logged to the audit trail.",
    icon: <ShieldAlert className="w-5 h-5" />,
    severity: "info",
  },
};

// ============================================================================
// COMPONENT
// ============================================================================

export function AdminPlatformAuthorityBanner({
  actionType,
  companyId,
  adminName,
  contextMessage,
  variant = "full",
  className = "",
}: AdminPlatformAuthorityBannerProps) {
  const config = ACTION_CONFIG[actionType];

  const severityStyles = {
    critical: "border-red-300 bg-red-50 dark:bg-red-950/30 text-red-900 dark:text-red-100",
    warning: "border-orange-300 bg-orange-50 dark:bg-orange-950/30 text-orange-900 dark:text-orange-100",
    info: "border-blue-300 bg-blue-50 dark:bg-blue-950/30 text-blue-900 dark:text-blue-100",
  };

  const iconStyles = {
    critical: "text-red-600",
    warning: "text-orange-600",
    info: "text-blue-600",
  };

  // Inline variant - just a badge
  if (variant === "inline") {
    return (
      <Badge
        className={`${
          config.severity === "critical"
            ? "bg-red-600"
            : config.severity === "warning"
            ? "bg-orange-600"
            : "bg-blue-600"
        } text-white ${className}`}
      >
        {config.icon}
        <span className="ml-1">PLATFORM AUTHORITY</span>
      </Badge>
    );
  }

  // Compact variant - small alert
  if (variant === "compact") {
    return (
      <div
        className={`flex items-center gap-3 px-4 py-2 rounded-lg border ${severityStyles[config.severity]} ${className}`}
      >
        <span className={iconStyles[config.severity]}>{config.icon}</span>
        <span className="text-sm font-semibold">{config.title}</span>
        <Badge variant="outline" className="ml-auto text-xs">
          PLATFORM DECISION
        </Badge>
      </div>
    );
  }

  // Full variant - complete explanation
  return (
    <Alert className={`${severityStyles[config.severity]} border-2 ${className}`}>
      <div className={iconStyles[config.severity]}>{config.icon}</div>
      <AlertTitle className="flex items-center gap-2 font-bold">
        {config.title}
        <Badge variant="outline" className="ml-2 text-xs font-normal">
          PLATFORM DECISION
        </Badge>
      </AlertTitle>
      <AlertDescription className="space-y-3">
        <p>{config.description}</p>

        {contextMessage && (
          <p className="font-medium">{contextMessage}</p>
        )}

        <div className="flex items-center justify-between pt-2 border-t border-current/20">
          <div className="text-sm">
            {adminName && (
              <span>
                Acting Admin: <strong>{adminName}</strong>
              </span>
            )}
          </div>

          {companyId && (
            <Link href={`/admin/companies/${companyId}/audit-trail`}>
              <Button variant="outline" size="sm" className="text-xs">
                View Audit Trail
                <ExternalLink className="w-3 h-3 ml-1" />
              </Button>
            </Link>
          )}
        </div>

        <p className="text-xs opacity-75">
          All platform authority actions are immutably logged for regulatory compliance and audit purposes.
        </p>
      </AlertDescription>
    </Alert>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

/**
 * Get action configuration for a given type
 */
export function getActionConfig(actionType: PlatformActionType) {
  return ACTION_CONFIG[actionType];
}

export default AdminPlatformAuthorityBanner;
