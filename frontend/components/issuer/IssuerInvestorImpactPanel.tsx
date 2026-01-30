/**
 * EPIC 5 Story 5.3 - Issuer Investor Impact Panel
 *
 * PURPOSE:
 * Display read-only information about investor impact to issuers.
 * Allows issuer to understand how changes affect investors without
 * giving them any control over investor-facing settings.
 *
 * STORY 5.3 RULES:
 * - Issuer MUST see which disclosure version investors currently see
 * - Issuer MUST see whether edits will:
 *   - affect only future investors
 *   - trigger material change workflow
 *   - pause buying
 * - Issuer MUST NOT control any of these settings (READ-ONLY)
 *
 * ISSUER ACKNOWLEDGEMENT:
 * - Issuer must acknowledge investor impact before submitting changes
 * - Acknowledgement is informational, not consent
 * - Stored for audit trail
 *
 * INVARIANT:
 * - All investor impact information is read-only
 * - Issuer cannot modify tier, warnings, risk flags, or freeze/suspend logic
 */

import { useState } from "react";
import { AlertTriangle, Users, Eye, Clock, FileText, CheckCircle2, Info } from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";

// ============================================================================
// TYPES
// ============================================================================

/**
 * Version distribution showing which investors see which version
 */
export interface VersionDistribution {
  version: number;
  investor_count: number;
  percentage: number;
  is_current: boolean;
}

/**
 * Investor snapshot awareness data (aggregate only)
 */
export interface InvestorSnapshotAwareness {
  total_investors: number;
  version_distribution: VersionDistribution[];
  current_approved_version?: number;
  last_investor_snapshot_at?: string;
}

/**
 * Change impact assessment
 */
export interface ChangeImpact {
  affects_future_investors_only: boolean;
  triggers_material_change: boolean;
  pauses_buying: boolean;
  effective_timing: "immediate" | "after_approval" | "after_next_investor_action";
  material_change_reason?: string;
}

interface IssuerInvestorImpactPanelProps {
  investorAwareness?: InvestorSnapshotAwareness;
  pendingChangeImpact?: ChangeImpact;
  disclosureModuleName?: string;
  onAcknowledgementChange?: (acknowledged: boolean) => void;
  requireAcknowledgement?: boolean;
  className?: string;
}

// ============================================================================
// COMPONENT
// ============================================================================

export function IssuerInvestorImpactPanel({
  investorAwareness,
  pendingChangeImpact,
  disclosureModuleName,
  onAcknowledgementChange,
  requireAcknowledgement = false,
  className = "",
}: IssuerInvestorImpactPanelProps) {
  const [acknowledged, setAcknowledged] = useState(false);

  const handleAcknowledgementChange = (checked: boolean) => {
    setAcknowledged(checked);
    onAcknowledgementChange?.(checked);
  };

  return (
    <div className={`space-y-4 ${className}`}>
      {/*
       * STORY 5.3: Investor Version Visibility (READ-ONLY)
       * Shows aggregate investor metrics without exposing personal data.
       * Issuer can see what version investors have but cannot change it.
       */}
      <Card className="border-blue-200 dark:border-blue-800">
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Eye className="w-5 h-5 text-blue-600" />
            Current Investor View
            <Badge variant="outline" className="ml-auto text-xs">
              READ-ONLY
            </Badge>
          </CardTitle>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            What investors currently see (you cannot change this directly)
          </p>
        </CardHeader>
        <CardContent className="space-y-4">
          {investorAwareness ? (
            <>
              {/* Total Investors */}
              <div className="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg">
                <div className="flex items-center gap-2">
                  <Users className="w-4 h-4 text-blue-600" />
                  <span className="text-sm font-medium">Total Investors</span>
                </div>
                <span className="text-lg font-bold text-blue-700 dark:text-blue-300">
                  {investorAwareness.total_investors}
                </span>
              </div>

              {/* Current Approved Version */}
              {investorAwareness.current_approved_version && (
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-400">
                    Current Approved Version
                  </span>
                  <Badge className="bg-green-600">
                    v{investorAwareness.current_approved_version}
                  </Badge>
                </div>
              )}

              {/* Version Distribution */}
              {investorAwareness.version_distribution.length > 0 && (
                <div className="pt-3 border-t border-blue-200 dark:border-blue-800">
                  <p className="text-sm font-semibold mb-2">Version Distribution</p>
                  <div className="space-y-2">
                    {investorAwareness.version_distribution.map((dist) => (
                      <div
                        key={dist.version}
                        className="flex justify-between items-center text-sm"
                      >
                        <span className="flex items-center gap-2">
                          <FileText className="w-3 h-3" />
                          Version {dist.version}
                          {dist.is_current && (
                            <Badge variant="outline" className="text-xs">Current</Badge>
                          )}
                        </span>
                        <span className="text-gray-600 dark:text-gray-400">
                          {dist.investor_count} investors ({dist.percentage}%)
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Privacy Notice */}
              <Alert className="border-gray-200 bg-gray-50 dark:bg-gray-900/30">
                <Info className="h-4 w-4 text-gray-500" />
                <AlertDescription className="text-xs text-gray-600 dark:text-gray-400">
                  For investor privacy, you can only see aggregate metrics.
                  Individual investor details are not available.
                </AlertDescription>
              </Alert>
            </>
          ) : (
            <p className="text-sm text-gray-500 italic text-center py-4">
              No investor data available yet
            </p>
          )}
        </CardContent>
      </Card>

      {/*
       * STORY 5.3: Change Impact Assessment (READ-ONLY)
       * Shows how pending changes will affect investors.
       * Issuer sees but does not control these outcomes.
       */}
      {pendingChangeImpact && (
        <Card className={`border-2 ${
          pendingChangeImpact.triggers_material_change || pendingChangeImpact.pauses_buying
            ? "border-amber-300 dark:border-amber-700"
            : "border-green-300 dark:border-green-700"
        }`}>
          <CardHeader className="pb-3">
            <CardTitle className="flex items-center gap-2 text-lg">
              <AlertTriangle className={`w-5 h-5 ${
                pendingChangeImpact.triggers_material_change || pendingChangeImpact.pauses_buying
                  ? "text-amber-600"
                  : "text-green-600"
              }`} />
              Change Impact Assessment
              {disclosureModuleName && (
                <Badge variant="outline" className="ml-2">{disclosureModuleName}</Badge>
              )}
            </CardTitle>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              How your changes will affect investors (platform-determined)
            </p>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Impact Summary Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              {/* Affects Future Only */}
              <div className={`p-3 rounded-lg border ${
                pendingChangeImpact.affects_future_investors_only
                  ? "bg-green-50 border-green-200 dark:bg-green-950/30 dark:border-green-800"
                  : "bg-amber-50 border-amber-200 dark:bg-amber-950/30 dark:border-amber-800"
              }`}>
                <p className="text-xs font-semibold uppercase tracking-wide mb-1">
                  Investor Scope
                </p>
                <p className={`text-sm font-medium ${
                  pendingChangeImpact.affects_future_investors_only
                    ? "text-green-700 dark:text-green-300"
                    : "text-amber-700 dark:text-amber-300"
                }`}>
                  {pendingChangeImpact.affects_future_investors_only
                    ? "Future investors only"
                    : "All investors affected"
                  }
                </p>
              </div>

              {/* Material Change */}
              <div className={`p-3 rounded-lg border ${
                pendingChangeImpact.triggers_material_change
                  ? "bg-amber-50 border-amber-200 dark:bg-amber-950/30 dark:border-amber-800"
                  : "bg-green-50 border-green-200 dark:bg-green-950/30 dark:border-green-800"
              }`}>
                <p className="text-xs font-semibold uppercase tracking-wide mb-1">
                  Material Change
                </p>
                <p className={`text-sm font-medium ${
                  pendingChangeImpact.triggers_material_change
                    ? "text-amber-700 dark:text-amber-300"
                    : "text-green-700 dark:text-green-300"
                }`}>
                  {pendingChangeImpact.triggers_material_change ? "Triggered" : "Not triggered"}
                </p>
              </div>

              {/* Buying Status */}
              <div className={`p-3 rounded-lg border ${
                pendingChangeImpact.pauses_buying
                  ? "bg-red-50 border-red-200 dark:bg-red-950/30 dark:border-red-800"
                  : "bg-green-50 border-green-200 dark:bg-green-950/30 dark:border-green-800"
              }`}>
                <p className="text-xs font-semibold uppercase tracking-wide mb-1">
                  Buying Status
                </p>
                <p className={`text-sm font-medium ${
                  pendingChangeImpact.pauses_buying
                    ? "text-red-700 dark:text-red-300"
                    : "text-green-700 dark:text-green-300"
                }`}>
                  {pendingChangeImpact.pauses_buying ? "Will be paused" : "Continues"}
                </p>
              </div>
            </div>

            {/* Effective Timing */}
            <div className="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
              <Clock className="w-5 h-5 text-slate-600" />
              <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-400">
                  Effective Timing
                </p>
                <p className="text-sm font-medium">
                  {pendingChangeImpact.effective_timing === "immediate" && "Immediately upon approval"}
                  {pendingChangeImpact.effective_timing === "after_approval" && "After platform approval"}
                  {pendingChangeImpact.effective_timing === "after_next_investor_action" && "When investor next interacts"}
                </p>
              </div>
            </div>

            {/* Material Change Reason */}
            {pendingChangeImpact.triggers_material_change && pendingChangeImpact.material_change_reason && (
              <Alert className="border-amber-300 bg-amber-50 dark:bg-amber-950/30">
                <AlertTriangle className="h-4 w-4 text-amber-600" />
                <AlertTitle className="text-amber-900 dark:text-amber-100">
                  Material Change Reason
                </AlertTitle>
                <AlertDescription className="text-sm text-amber-800 dark:text-amber-200">
                  {pendingChangeImpact.material_change_reason}
                </AlertDescription>
              </Alert>
            )}

            {/* Cannot Control Notice */}
            <Alert className="border-gray-200 bg-gray-50 dark:bg-gray-900/30">
              <Info className="h-4 w-4 text-gray-500" />
              <AlertDescription className="text-xs text-gray-600 dark:text-gray-400">
                These outcomes are determined by platform policy. You cannot modify
                tier changes, platform warnings, risk flags, or freeze/suspend logic.
              </AlertDescription>
            </Alert>
          </CardContent>
        </Card>
      )}

      {/*
       * STORY 5.3: Issuer Acknowledgement (Non-Blocking)
       * - Acknowledgement is informational, not consent
       * - Stored for audit trail
       * - Does not block submission (non-blocking requirement)
       */}
      {requireAcknowledgement && pendingChangeImpact && (
        <Card className="border-indigo-200 dark:border-indigo-800">
          <CardContent className="pt-6">
            <div className="flex items-start space-x-3 p-4 bg-indigo-50 dark:bg-indigo-950/30 rounded-lg">
              <Checkbox
                id="investor-impact-ack"
                checked={acknowledged}
                onCheckedChange={(checked) => handleAcknowledgementChange(!!checked)}
              />
              <div className="space-y-1">
                <Label
                  htmlFor="investor-impact-ack"
                  className="text-sm font-medium cursor-pointer leading-relaxed"
                >
                  I acknowledge that I have reviewed the investor impact assessment above
                </Label>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  This acknowledgement is recorded for audit purposes. It confirms you have
                  seen this information but does not constitute consent or approval.
                </p>
              </div>
            </div>

            {acknowledged && (
              <div className="flex items-center gap-2 mt-3 text-sm text-green-600 dark:text-green-400">
                <CheckCircle2 className="w-4 h-4" />
                <span>Acknowledgement recorded</span>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

/**
 * Check if change has significant investor impact
 */
export function hasSignificantImpact(impact: ChangeImpact): boolean {
  return impact.triggers_material_change || impact.pauses_buying || !impact.affects_future_investors_only;
}

/**
 * Get impact severity level
 */
export function getImpactSeverity(impact: ChangeImpact): "low" | "medium" | "high" {
  if (impact.pauses_buying) return "high";
  if (impact.triggers_material_change) return "medium";
  return "low";
}

export default IssuerInvestorImpactPanel;
