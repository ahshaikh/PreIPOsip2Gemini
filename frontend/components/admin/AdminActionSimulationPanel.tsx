/**
 * EPIC 5 Story 5.4 - Admin Action Simulation Panel
 *
 * PURPOSE:
 * Pre-action simulation preview showing:
 * - What WILL change
 * - What WILL NOT change
 * - Which snapshots remain untouched
 *
 * STORY 5.4 REQUIREMENTS:
 * - Admin must acknowledge irreversible or high-impact actions
 * - Acknowledgement is recorded for audit
 * - Admin understands consequences before acting
 *
 * INVARIANTS:
 * - ❌ No silent historical mutation
 * - ✅ Every user action is explainable in audit
 */

import { useState } from "react";
import {
  ArrowRight,
  AlertTriangle,
  CheckCircle2,
  XCircle,
  Lock,
  Shield,
  Users,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

// ============================================================================
// TYPES
// ============================================================================

export interface StateComparison {
  field: string;
  label: string;
  current: string | boolean;
  proposed: string | boolean;
  is_changing: boolean;
  change_severity: "critical" | "warning" | "info" | "none";
}

export interface BlockedActions {
  issuer_actions: string[];
  investor_actions: string[];
}

export interface SimulationResult {
  state_changes: StateComparison[];
  blocked_actions: BlockedActions;
  warnings: string[];
  unaffected_items: string[];
  requires_acknowledgement: boolean;
  acknowledgement_text?: string;
  is_reversible: boolean;
}

interface AdminActionSimulationPanelProps {
  actionType: string;
  actionLabel: string;
  simulation: SimulationResult;
  onConfirm: (reason: string) => void;
  onCancel: () => void;
  isSubmitting?: boolean;
  minReasonLength?: number;
  className?: string;
}

// ============================================================================
// COMPONENT
// ============================================================================

export function AdminActionSimulationPanel({
  actionType,
  actionLabel,
  simulation,
  onConfirm,
  onCancel,
  isSubmitting = false,
  minReasonLength = 20,
  className = "",
}: AdminActionSimulationPanelProps) {
  const [reason, setReason] = useState("");
  const [acknowledged, setAcknowledged] = useState(false);

  const hasChanges = simulation.state_changes.some((s) => s.is_changing);
  const hasCriticalChanges = simulation.state_changes.some(
    (s) => s.is_changing && s.change_severity === "critical"
  );
  const isReasonValid = reason.trim().length >= minReasonLength;
  const canConfirm =
    isReasonValid &&
    (!simulation.requires_acknowledgement || acknowledged) &&
    !isSubmitting;

  return (
    <div className={`space-y-4 ${className}`}>
      {/*
       * STORY 5.4: Action Header
       */}
      <div className="flex items-center gap-3 p-4 bg-slate-100 dark:bg-slate-800 rounded-lg">
        <Shield className="w-6 h-6 text-purple-600" />
        <div>
          <p className="text-sm text-gray-500">Platform Action</p>
          <p className="text-lg font-bold">{actionLabel}</p>
        </div>
        <Badge className="ml-auto" variant={hasCriticalChanges ? "destructive" : "secondary"}>
          {hasCriticalChanges ? "HIGH IMPACT" : "STANDARD"}
        </Badge>
      </div>

      {/*
       * STORY 5.4: State Comparison - Side by Side
       */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-lg">State Transition Preview</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {simulation.state_changes.map((change) => (
              <StateTransitionRow key={change.field} change={change} />
            ))}
          </div>

          {!hasChanges && (
            <div className="text-center py-4 text-gray-500">
              <CheckCircle2 className="w-8 h-8 mx-auto mb-2 text-green-500" />
              <p>No state changes will occur from this action.</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/*
       * STORY 5.4: Blocked Actions Preview
       */}
      {(simulation.blocked_actions.issuer_actions.length > 0 ||
        simulation.blocked_actions.investor_actions.length > 0) && (
        <Card className="border-amber-200 dark:border-amber-800">
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2 text-amber-800 dark:text-amber-200">
              <Lock className="w-5 h-5" />
              Actions That Will Be Blocked
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {simulation.blocked_actions.issuer_actions.length > 0 && (
              <div>
                <p className="text-sm font-semibold text-amber-700 dark:text-amber-300 mb-2">
                  Issuer Actions Blocked:
                </p>
                <ul className="space-y-1">
                  {simulation.blocked_actions.issuer_actions.map((action, i) => (
                    <li key={i} className="flex items-center gap-2 text-sm">
                      <XCircle className="w-4 h-4 text-amber-600" />
                      {action}
                    </li>
                  ))}
                </ul>
              </div>
            )}

            {simulation.blocked_actions.investor_actions.length > 0 && (
              <div>
                <p className="text-sm font-semibold text-amber-700 dark:text-amber-300 mb-2">
                  Investor Actions Blocked:
                </p>
                <ul className="space-y-1">
                  {simulation.blocked_actions.investor_actions.map((action, i) => (
                    <li key={i} className="flex items-center gap-2 text-sm">
                      <XCircle className="w-4 h-4 text-amber-600" />
                      {action}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/*
       * STORY 5.4: Unaffected Items
       */}
      {simulation.unaffected_items.length > 0 && (
        <Card className="border-green-200 dark:border-green-800">
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2 text-green-800 dark:text-green-200">
              <Shield className="w-5 h-5" />
              What Will NOT Change
            </CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-2">
              {simulation.unaffected_items.map((item, i) => (
                <li key={i} className="flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
                  <CheckCircle2 className="w-4 h-4" />
                  {item}
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>
      )}

      {/*
       * STORY 5.4: Warnings
       */}
      {simulation.warnings.length > 0 && (
        <Alert variant="destructive">
          <AlertTriangle className="h-5 w-5" />
          <AlertTitle>Warnings</AlertTitle>
          <AlertDescription>
            <ul className="list-disc list-inside space-y-1">
              {simulation.warnings.map((warning, i) => (
                <li key={i}>{warning}</li>
              ))}
            </ul>
          </AlertDescription>
        </Alert>
      )}

      {/*
       * STORY 5.4: Reversibility Notice
       */}
      <Alert className={simulation.is_reversible ? "border-blue-200 bg-blue-50 dark:bg-blue-950/30" : "border-red-200 bg-red-50 dark:bg-red-950/30"}>
        <AlertTriangle className={`h-4 w-4 ${simulation.is_reversible ? "text-blue-600" : "text-red-600"}`} />
        <AlertDescription className={simulation.is_reversible ? "text-blue-800 dark:text-blue-200" : "text-red-800 dark:text-red-200"}>
          {simulation.is_reversible
            ? "This action can be reversed by a subsequent platform action."
            : "This action may have irreversible consequences. Some effects cannot be undone."}
        </AlertDescription>
      </Alert>

      {/*
       * STORY 5.4: Acknowledgement Checkbox (Non-Blocking but Required)
       */}
      {simulation.requires_acknowledgement && (
        <Card className="border-purple-200 dark:border-purple-800">
          <CardContent className="pt-6">
            <div className="flex items-start space-x-3 p-4 bg-purple-50 dark:bg-purple-950/30 rounded-lg">
              <Checkbox
                id="action-acknowledgement"
                checked={acknowledged}
                onCheckedChange={(checked) => setAcknowledged(!!checked)}
              />
              <div className="space-y-1">
                <Label
                  htmlFor="action-acknowledgement"
                  className="text-sm font-medium cursor-pointer leading-relaxed"
                >
                  {simulation.acknowledgement_text ||
                    "I acknowledge that I understand the impact of this action and have reviewed all warnings."}
                </Label>
                <p className="text-xs text-gray-500 dark:text-gray-400">
                  This acknowledgement is recorded for audit purposes.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/*
       * STORY 5.4: Reason Input (Required for Audit Trail)
       */}
      <Card>
        <CardHeader className="pb-3">
          <CardTitle className="text-lg">Reason for Action</CardTitle>
          <p className="text-sm text-gray-500">
            Required for audit trail (minimum {minReasonLength} characters)
          </p>
        </CardHeader>
        <CardContent>
          <Textarea
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="Provide a detailed reason for this platform action..."
            className="min-h-[100px]"
          />
          <div className="flex justify-between items-center mt-2 text-xs">
            <span className={reason.length >= minReasonLength ? "text-green-600" : "text-gray-400"}>
              {reason.length} / {minReasonLength} minimum characters
            </span>
            {!isReasonValid && reason.length > 0 && (
              <span className="text-amber-600">
                Please provide more detail
              </span>
            )}
          </div>
        </CardContent>
      </Card>

      {/*
       * STORY 5.4: Action Buttons
       */}
      <div className="flex justify-end gap-3 pt-4 border-t">
        <Button variant="outline" onClick={onCancel} disabled={isSubmitting}>
          Cancel
        </Button>
        <Button
          onClick={() => onConfirm(reason)}
          disabled={!canConfirm}
          variant={hasCriticalChanges ? "destructive" : "default"}
        >
          {isSubmitting ? (
            <>Processing...</>
          ) : (
            <>
              Confirm {actionLabel}
              {hasCriticalChanges && <AlertTriangle className="w-4 h-4 ml-2" />}
            </>
          )}
        </Button>
      </div>
    </div>
  );
}

// ============================================================================
// HELPER COMPONENT
// ============================================================================

function StateTransitionRow({ change }: { change: StateComparison }) {
  const formatValue = (value: string | boolean): string => {
    if (typeof value === "boolean") {
      return value ? "Yes" : "No";
    }
    return value || "—";
  };

  const severityStyles = {
    critical: "border-red-300 bg-red-50 dark:bg-red-950/30",
    warning: "border-amber-300 bg-amber-50 dark:bg-amber-950/30",
    info: "border-blue-300 bg-blue-50 dark:bg-blue-950/30",
    none: "border-gray-200 bg-gray-50 dark:bg-gray-900/30",
  };

  return (
    <div
      className={`flex items-center justify-between p-3 rounded-lg border ${
        change.is_changing ? severityStyles[change.change_severity] : severityStyles.none
      }`}
    >
      <span className="text-sm font-medium w-1/3">{change.label}</span>

      <div className="flex items-center gap-3 w-2/3 justify-end">
        <span className={`text-sm px-2 py-1 rounded ${change.is_changing ? "bg-white/50" : ""}`}>
          {formatValue(change.current)}
        </span>

        {change.is_changing ? (
          <>
            <ArrowRight className="w-4 h-4 text-gray-400" />
            <span
              className={`text-sm px-2 py-1 rounded font-semibold ${
                change.change_severity === "critical"
                  ? "text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/50"
                  : change.change_severity === "warning"
                  ? "text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/50"
                  : "text-blue-700 bg-blue-100 dark:text-blue-300 dark:bg-blue-900/50"
              }`}
            >
              {formatValue(change.proposed)}
            </span>
          </>
        ) : (
          <Badge variant="outline" className="text-xs">
            No Change
          </Badge>
        )}
      </div>
    </div>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

/**
 * Create a state comparison object
 */
export function createStateComparison(
  field: string,
  label: string,
  current: string | boolean,
  proposed: string | boolean,
  severity: "critical" | "warning" | "info" = "info"
): StateComparison {
  return {
    field,
    label,
    current,
    proposed,
    is_changing: current !== proposed,
    change_severity: current !== proposed ? severity : "none",
  };
}

export default AdminActionSimulationPanel;
