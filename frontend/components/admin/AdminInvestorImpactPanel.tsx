/**
 * EPIC 5 Story 5.4 - Admin Investor Impact Panel
 *
 * PURPOSE:
 * Show investor count, buy impact, and re-acknowledgement requirements
 * BEFORE admin takes any action. Admin must understand consequences.
 *
 * STORY 5.4 REQUIREMENTS:
 * - Before approvals/rejections/freezes, admin sees:
 *   - Number of affected investors
 *   - Buy impact (pause/resume)
 *   - Re-acknowledgement requirements
 * - Admin understands consequences before acting
 *
 * INVARIANTS:
 * - ❌ No silent historical mutation
 * - ✅ Compliance depends on what the user saw
 */

import {
  Users,
  Wallet,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Clock,
  ShieldAlert,
  RefreshCw,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

// ============================================================================
// TYPES
// ============================================================================

export interface InvestorMetrics {
  total_investors: number;
  active_investments: number;
  pending_investments: number;
  total_invested_amount: number;
  snapshot_count: number;
}

export interface ActionImpact {
  // What will be affected
  will_block_new_investments: boolean;
  will_affect_discovery: boolean;
  will_require_reacknowledgement: boolean;
  will_trigger_material_change: boolean;

  // What will NOT be affected
  existing_holdings_affected: boolean;
  historical_snapshots_affected: boolean;

  // Counts
  affected_investor_count: number;
  affected_subscription_count: number;

  // Summary messages
  impact_summary: string;
  warnings: string[];
}

interface AdminInvestorImpactPanelProps {
  metrics: InvestorMetrics;
  impact: ActionImpact;
  actionType: string;
  className?: string;
}

// ============================================================================
// COMPONENT
// ============================================================================

export function AdminInvestorImpactPanel({
  metrics,
  impact,
  actionType,
  className = "",
}: AdminInvestorImpactPanelProps) {
  const hasSignificantImpact =
    impact.will_block_new_investments ||
    impact.will_require_reacknowledgement ||
    impact.will_trigger_material_change;

  return (
    <div className={`space-y-4 ${className}`}>
      {/*
       * STORY 5.4: Investor Metrics Summary
       * Shows current state before any action is taken.
       */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Users className="w-5 h-5" />
            Current Investor State
          </CardTitle>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Review these metrics before taking action
          </p>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {/* Total Investors */}
            <div className="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800">
              <div className="flex items-center gap-2 mb-1">
                <Users className="w-4 h-4 text-blue-600" />
                <span className="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                  Investors
                </span>
              </div>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {metrics.total_investors}
              </p>
            </div>

            {/* Active Investments */}
            <div className="p-3 bg-green-50 dark:bg-green-950/30 rounded-lg border border-green-200 dark:border-green-800">
              <div className="flex items-center gap-2 mb-1">
                <Wallet className="w-4 h-4 text-green-600" />
                <span className="text-xs font-semibold uppercase tracking-wide text-green-700 dark:text-green-300">
                  Active
                </span>
              </div>
              <p className="text-2xl font-bold text-green-900 dark:text-green-100">
                {metrics.active_investments}
              </p>
            </div>

            {/* Pending */}
            <div className="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-lg border border-amber-200 dark:border-amber-800">
              <div className="flex items-center gap-2 mb-1">
                <Clock className="w-4 h-4 text-amber-600" />
                <span className="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                  Pending
                </span>
              </div>
              <p className="text-2xl font-bold text-amber-900 dark:text-amber-100">
                {metrics.pending_investments}
              </p>
            </div>

            {/* Snapshots */}
            <div className="p-3 bg-purple-50 dark:bg-purple-950/30 rounded-lg border border-purple-200 dark:border-purple-800">
              <div className="flex items-center gap-2 mb-1">
                <ShieldAlert className="w-4 h-4 text-purple-600" />
                <span className="text-xs font-semibold uppercase tracking-wide text-purple-700 dark:text-purple-300">
                  Snapshots
                </span>
              </div>
              <p className="text-2xl font-bold text-purple-900 dark:text-purple-100">
                {metrics.snapshot_count}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/*
       * STORY 5.4: Action Impact Assessment
       * What WILL and WILL NOT be affected.
       */}
      <Card className={hasSignificantImpact ? "border-amber-300 dark:border-amber-700" : ""}>
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-lg">
            <AlertTriangle className={`w-5 h-5 ${hasSignificantImpact ? "text-amber-600" : "text-gray-600"}`} />
            Impact Assessment: {actionType}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Impact Summary */}
          {impact.impact_summary && (
            <Alert className={hasSignificantImpact ? "border-amber-300 bg-amber-50 dark:bg-amber-950/30" : ""}>
              <AlertDescription className="text-sm">
                {impact.impact_summary}
              </AlertDescription>
            </Alert>
          )}

          {/* What WILL Change */}
          <div className="space-y-2">
            <p className="text-sm font-semibold text-red-700 dark:text-red-300">
              What WILL Change:
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
              <ImpactItem
                label="New Investments"
                affected={impact.will_block_new_investments}
                affectedText="BLOCKED"
                unaffectedText="Allowed"
              />
              <ImpactItem
                label="Discovery"
                affected={impact.will_affect_discovery}
                affectedText="CHANGED"
                unaffectedText="Unchanged"
              />
              <ImpactItem
                label="Re-Acknowledgement"
                affected={impact.will_require_reacknowledgement}
                affectedText="REQUIRED"
                unaffectedText="Not Required"
              />
              <ImpactItem
                label="Material Change"
                affected={impact.will_trigger_material_change}
                affectedText="TRIGGERED"
                unaffectedText="Not Triggered"
              />
            </div>
          </div>

          {/* Affected Counts */}
          {(impact.affected_investor_count > 0 || impact.affected_subscription_count > 0) && (
            <div className="p-3 bg-red-50 dark:bg-red-950/30 rounded-lg border border-red-200 dark:border-red-800">
              <p className="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                Affected by this action:
              </p>
              <div className="flex gap-4 text-sm">
                <span>
                  <strong>{impact.affected_investor_count}</strong> investors
                </span>
                <span>
                  <strong>{impact.affected_subscription_count}</strong> subscriptions
                </span>
              </div>
            </div>
          )}

          {/* What WILL NOT Change */}
          <div className="space-y-2">
            <p className="text-sm font-semibold text-green-700 dark:text-green-300">
              What WILL NOT Change:
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
              <ImpactItem
                label="Existing Holdings"
                affected={impact.existing_holdings_affected}
                affectedText="AFFECTED"
                unaffectedText="UNAFFECTED"
                invertColors
              />
              <ImpactItem
                label="Historical Snapshots"
                affected={impact.historical_snapshots_affected}
                affectedText="MUTATED"
                unaffectedText="FROZEN & IMMUTABLE"
                invertColors
              />
            </div>
          </div>

          {/* Warnings */}
          {impact.warnings.length > 0 && (
            <Alert variant="destructive">
              <AlertTriangle className="h-4 w-4" />
              <AlertTitle>Warnings</AlertTitle>
              <AlertDescription>
                <ul className="list-disc list-inside space-y-1">
                  {impact.warnings.map((warning, i) => (
                    <li key={i}>{warning}</li>
                  ))}
                </ul>
              </AlertDescription>
            </Alert>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

// ============================================================================
// HELPER COMPONENT
// ============================================================================

function ImpactItem({
  label,
  affected,
  affectedText,
  unaffectedText,
  invertColors = false,
}: {
  label: string;
  affected: boolean;
  affectedText: string;
  unaffectedText: string;
  invertColors?: boolean;
}) {
  // When invertColors is true, "affected" is bad and "unaffected" is good
  // When invertColors is false, we show the status as-is
  const showAsNegative = invertColors ? affected : affected;

  return (
    <div className="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900/50 rounded">
      <span className="text-sm text-gray-600 dark:text-gray-400">{label}</span>
      <Badge
        className={
          invertColors
            ? affected
              ? "bg-red-600 text-white"
              : "bg-green-600 text-white"
            : affected
            ? "bg-amber-600 text-white"
            : "bg-green-600 text-white"
        }
      >
        {affected ? (
          invertColors ? <XCircle className="w-3 h-3 mr-1" /> : <AlertTriangle className="w-3 h-3 mr-1" />
        ) : (
          <CheckCircle2 className="w-3 h-3 mr-1" />
        )}
        {affected ? affectedText : unaffectedText}
      </Badge>
    </div>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

/**
 * Check if action has significant investor impact
 */
export function hasSignificantInvestorImpact(impact: ActionImpact): boolean {
  return (
    impact.will_block_new_investments ||
    impact.will_require_reacknowledgement ||
    impact.will_trigger_material_change ||
    impact.affected_investor_count > 0
  );
}

export default AdminInvestorImpactPanel;
