/**
 * EPIC 5 Story 5.3 - Issuer Platform Status Banner
 *
 * PURPOSE:
 * Display platform governance state to company issuers with clear explanations.
 * Enforces platform state supremacy - if company is frozen/suspended/investigated,
 * edits are disabled immediately with clear reasons.
 *
 * STORY 5.3 RULES:
 * - Platform has frozen company → disable edits, show explanation
 * - Platform has suspended buying → disable edits, show explanation
 * - Platform has opened investigation → disable edits, show explanation
 * - NEVER "fail later" on submit - show blocks upfront
 *
 * INVARIANT:
 * - Issuer UI cannot bypass platform authority
 * - All locks are visible before submit
 */

import { AlertTriangle, ShieldAlert, Lock, AlertCircle, Info, CheckCircle2 } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

// ============================================================================
// TYPES
// ============================================================================

/**
 * Platform context from backend API
 */
export interface PlatformContext {
  lifecycle_state: string;
  is_suspended: boolean;
  is_frozen: boolean;
  is_under_investigation: boolean;
  buying_enabled: boolean;
  tier_status: Record<string, boolean>;
  suspension_reason?: string;
  freeze_reason?: string;
  investigation_reason?: string;
}

/**
 * Effective permissions from backend
 */
export interface EffectivePermissions {
  can_edit_disclosures: boolean;
  can_submit_disclosures: boolean;
  can_answer_clarifications: boolean;
  edit_blocked_reason?: string;
  submit_blocked_reason?: string;
}

interface PlatformStatusBannerProps {
  platformContext: PlatformContext;
  effectivePermissions: EffectivePermissions;
  platformOverrides?: string[];
  variant?: "full" | "compact" | "critical-only";
  className?: string;
}

// ============================================================================
// COMPONENT
// ============================================================================

export function PlatformStatusBanner({
  platformContext,
  effectivePermissions,
  platformOverrides = [],
  variant = "full",
  className = "",
}: PlatformStatusBannerProps) {
  const {
    is_suspended,
    is_frozen,
    is_under_investigation,
    lifecycle_state,
    buying_enabled,
    suspension_reason,
    freeze_reason,
    investigation_reason,
  } = platformContext;

  const hasCriticalRestriction = is_suspended || is_frozen || is_under_investigation;
  const hasAnyRestriction = hasCriticalRestriction || !effectivePermissions.can_edit_disclosures;

  // Critical-only variant: only show if there are critical restrictions
  if (variant === "critical-only" && !hasCriticalRestriction) {
    return null;
  }

  // Compact variant for inline display
  if (variant === "compact") {
    if (!hasAnyRestriction) {
      return (
        <div className={`flex items-center gap-2 text-sm text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-950/30 px-4 py-2 rounded-lg ${className}`}>
          <CheckCircle2 className="w-4 h-4 flex-shrink-0" />
          <span>Platform status: Active - Edits permitted</span>
        </div>
      );
    }

    return (
      <div className={`flex items-center gap-2 text-sm text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/30 px-4 py-2 rounded-lg ${className}`}>
        <ShieldAlert className="w-4 h-4 flex-shrink-0" />
        <span>
          Platform restriction active: {is_suspended ? "Suspended" : is_frozen ? "Frozen" : is_under_investigation ? "Under Investigation" : "Edits Disabled"}
        </span>
      </div>
    );
  }

  // Full variant
  return (
    <div className={`space-y-4 ${className}`}>
      {/*
       * STORY 5.3: Critical Platform Restrictions
       * These alerts MUST be shown before any edit UI.
       * Issuer cannot proceed if any of these are active.
       */}

      {/* Suspension Alert */}
      {is_suspended && (
        <Alert variant="destructive" className="border-2">
          <ShieldAlert className="h-5 w-5" />
          <AlertTitle className="font-bold">Company Suspended by Platform</AlertTitle>
          <AlertDescription>
            <p className="mb-2">
              Your company has been suspended by the platform. All disclosure edits and submissions are blocked until the suspension is lifted.
            </p>
            {suspension_reason && (
              <div className="mt-3 p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                <p className="font-semibold text-sm">Reason:</p>
                <p className="text-sm">{suspension_reason}</p>
              </div>
            )}
            <p className="text-sm mt-3 text-red-700 dark:text-red-300">
              Contact platform support for assistance with resolving this suspension.
            </p>
          </AlertDescription>
        </Alert>
      )}

      {/* Frozen Alert */}
      {is_frozen && (
        <Alert variant="destructive" className="border-2 border-orange-500 bg-orange-50 dark:bg-orange-950/30">
          <Lock className="h-5 w-5 text-orange-600" />
          <AlertTitle className="font-bold text-orange-900 dark:text-orange-100">Disclosures Frozen</AlertTitle>
          <AlertDescription className="text-orange-800 dark:text-orange-200">
            <p className="mb-2">
              Your company's disclosures have been frozen by the platform. You cannot edit or submit disclosures until the freeze is lifted.
            </p>
            {freeze_reason && (
              <div className="mt-3 p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                <p className="font-semibold text-sm">Reason:</p>
                <p className="text-sm">{freeze_reason}</p>
              </div>
            )}
          </AlertDescription>
        </Alert>
      )}

      {/* Investigation Alert */}
      {is_under_investigation && (
        <Alert variant="destructive" className="border-2 border-amber-500 bg-amber-50 dark:bg-amber-950/30">
          <AlertTriangle className="h-5 w-5 text-amber-600" />
          <AlertTitle className="font-bold text-amber-900 dark:text-amber-100">Under Platform Investigation</AlertTitle>
          <AlertDescription className="text-amber-800 dark:text-amber-200">
            <p className="mb-2">
              Your company is currently under platform investigation. Disclosure editing may be restricted pending the outcome.
            </p>
            {investigation_reason && (
              <div className="mt-3 p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                <p className="font-semibold text-sm">Investigation Context:</p>
                <p className="text-sm">{investigation_reason}</p>
              </div>
            )}
          </AlertDescription>
        </Alert>
      )}

      {/* Platform Overrides */}
      {platformOverrides.length > 0 && (
        <Alert className="border-purple-300 bg-purple-50 dark:bg-purple-950/30">
          <Info className="h-5 w-5 text-purple-600" />
          <AlertTitle className="font-bold text-purple-900 dark:text-purple-100">Platform Override Active</AlertTitle>
          <AlertDescription className="text-purple-800 dark:text-purple-200">
            <ul className="list-disc list-inside space-y-1 mt-2">
              {platformOverrides.map((override, i) => (
                <li key={i}>{override}</li>
              ))}
            </ul>
          </AlertDescription>
        </Alert>
      )}

      {/* Platform Status Card - Always Show */}
      <Card className={hasCriticalRestriction ? "border-red-200 dark:border-red-800" : ""}>
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-lg">
            <ShieldAlert className="w-5 h-5" />
            Platform Governance Status
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Lifecycle State */}
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600 dark:text-gray-400">Lifecycle State</span>
            <Badge variant="outline" className="capitalize">
              {lifecycle_state.replace(/_/g, " ")}
            </Badge>
          </div>

          {/* Buying Status */}
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600 dark:text-gray-400">Investor Buying</span>
            <Badge className={buying_enabled ? "bg-green-600" : "bg-red-600"}>
              {buying_enabled ? "Enabled" : "Paused"}
            </Badge>
          </div>

          {/* Edit Permission */}
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600 dark:text-gray-400">Disclosure Editing</span>
            <Badge className={effectivePermissions.can_edit_disclosures ? "bg-green-600" : "bg-red-600"}>
              {effectivePermissions.can_edit_disclosures ? "Permitted" : "Blocked"}
            </Badge>
          </div>

          {/* Submit Permission */}
          <div className="flex justify-between items-center">
            <span className="text-sm text-gray-600 dark:text-gray-400">Submission</span>
            <Badge className={effectivePermissions.can_submit_disclosures ? "bg-green-600" : "bg-red-600"}>
              {effectivePermissions.can_submit_disclosures ? "Permitted" : "Blocked"}
            </Badge>
          </div>

          {/* Tier Approvals */}
          {Object.keys(platformContext.tier_status).length > 0 && (
            <div className="pt-3 border-t border-gray-200 dark:border-gray-700">
              <p className="text-sm font-semibold mb-2">Tier Approvals</p>
              <div className="space-y-2">
                {Object.entries(platformContext.tier_status).map(([tier, approved]) => {
                  const tierName = tier.replace(/_approved$/, "").replace(/_/g, " ").toUpperCase();
                  return (
                    <div key={tier} className="flex justify-between items-center text-sm">
                      <span className="text-gray-600 dark:text-gray-400">{tierName}</span>
                      <Badge variant={approved ? "default" : "secondary"} className={approved ? "bg-green-600" : ""}>
                        {approved ? "Approved" : "Pending"}
                      </Badge>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* Block Reasons */}
          {(effectivePermissions.edit_blocked_reason || effectivePermissions.submit_blocked_reason) && (
            <div className="pt-3 border-t border-red-200 dark:border-red-800">
              <p className="text-sm font-semibold text-red-700 dark:text-red-300 mb-2">
                Block Reasons
              </p>
              {effectivePermissions.edit_blocked_reason && (
                <p className="text-sm text-red-600 dark:text-red-400">
                  Edit: {effectivePermissions.edit_blocked_reason}
                </p>
              )}
              {effectivePermissions.submit_blocked_reason && (
                <p className="text-sm text-red-600 dark:text-red-400">
                  Submit: {effectivePermissions.submit_blocked_reason}
                </p>
              )}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

/**
 * Check if platform has any critical restrictions
 */
export function hasPlatformRestriction(platformContext: PlatformContext): boolean {
  return platformContext.is_suspended || platformContext.is_frozen || platformContext.is_under_investigation;
}

/**
 * Get human-readable restriction status
 */
export function getRestrictionStatus(platformContext: PlatformContext): string {
  if (platformContext.is_suspended) return "Suspended";
  if (platformContext.is_frozen) return "Frozen";
  if (platformContext.is_under_investigation) return "Under Investigation";
  return "Active";
}

export default PlatformStatusBanner;
