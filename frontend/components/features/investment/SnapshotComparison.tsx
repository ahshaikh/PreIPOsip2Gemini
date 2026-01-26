'use client';

/**
 * P0 FIX (GAP 36): Snapshot Comparison UI
 *
 * PURPOSE:
 * Allow investors to compare platform state "then vs now".
 * Shows what the platform looked like when they invested vs current state.
 *
 * FEATURES:
 * - Side-by-side comparison
 * - Change highlights
 * - Risk flag diff
 * - Visual indicators for improvements/degradations
 */

import { useState, useEffect } from 'react';
import {
  ArrowRight,
  ArrowUp,
  ArrowDown,
  Minus,
  AlertTriangle,
  CheckCircle,
  Clock,
  Shield,
  TrendingUp,
  TrendingDown,
  RefreshCw,
  Info
} from 'lucide-react';
import api from '@/lib/api';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { SnapshotComparison, RiskFlag } from '@/types/api';

interface SnapshotComparisonProps {
  investmentId: number;
  compactMode?: boolean;
}

interface ComparisonRowProps {
  label: string;
  thenValue: string | number | boolean;
  nowValue: string | number | boolean;
  changeType: 'improvement' | 'degradation' | 'neutral' | 'unchanged';
  tooltip?: string;
}

/**
 * Determine change type for visual styling
 */
function getChangeType(
  field: string,
  thenVal: any,
  nowVal: any
): 'improvement' | 'degradation' | 'neutral' | 'unchanged' {
  if (thenVal === nowVal) return 'unchanged';

  switch (field) {
    case 'compliance_score':
      return nowVal > thenVal ? 'improvement' : 'degradation';
    case 'risk_level':
      const riskOrder = ['low', 'medium', 'high', 'critical'];
      const thenIdx = riskOrder.indexOf(thenVal);
      const nowIdx = riskOrder.indexOf(nowVal);
      return nowIdx < thenIdx ? 'improvement' : 'degradation';
    case 'buying_enabled':
      return nowVal === true ? 'improvement' : 'degradation';
    case 'lifecycle_state':
      // Pre-IPO progression is generally neutral unless blocked
      if (nowVal === 'blocked' || nowVal === 'suspended') return 'degradation';
      return 'neutral';
    default:
      return 'neutral';
  }
}

/**
 * Change indicator styles
 */
function getChangeStyles(changeType: string) {
  switch (changeType) {
    case 'improvement':
      return {
        bg: 'bg-green-50 dark:bg-green-950/30',
        text: 'text-green-700 dark:text-green-300',
        icon: ArrowUp,
        label: 'Improved'
      };
    case 'degradation':
      return {
        bg: 'bg-red-50 dark:bg-red-950/30',
        text: 'text-red-700 dark:text-red-300',
        icon: ArrowDown,
        label: 'Changed'
      };
    case 'neutral':
      return {
        bg: 'bg-blue-50 dark:bg-blue-950/30',
        text: 'text-blue-700 dark:text-blue-300',
        icon: ArrowRight,
        label: 'Updated'
      };
    case 'unchanged':
    default:
      return {
        bg: 'bg-gray-50 dark:bg-gray-950/30',
        text: 'text-gray-600 dark:text-gray-400',
        icon: Minus,
        label: 'No Change'
      };
  }
}

/**
 * Comparison Row Component
 */
function ComparisonRow({ label, thenValue, nowValue, changeType, tooltip }: ComparisonRowProps) {
  const styles = getChangeStyles(changeType);
  const ChangeIcon = styles.icon;
  const hasChanged = changeType !== 'unchanged';

  const formatValue = (val: any) => {
    if (typeof val === 'boolean') return val ? 'Yes' : 'No';
    if (typeof val === 'number') return val.toString();
    return val;
  };

  return (
    <div className={`rounded-lg p-3 ${hasChanged ? styles.bg : 'bg-muted/30'}`}>
      <div className="flex items-center justify-between mb-2">
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">{label}</span>
          {tooltip && (
            <Info className="h-3 w-3 text-muted-foreground cursor-help" title={tooltip} />
          )}
        </div>
        {hasChanged && (
          <Badge variant={changeType === 'improvement' ? 'success' : changeType === 'degradation' ? 'destructive' : 'secondary'} className="text-xs">
            <ChangeIcon className="h-3 w-3 mr-1" />
            {styles.label}
          </Badge>
        )}
      </div>
      <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
        {/* Then Value */}
        <div className="text-center">
          <p className="text-xs text-muted-foreground mb-1">At Investment</p>
          <p className={`font-mono text-sm ${hasChanged ? 'text-muted-foreground' : ''}`}>
            {formatValue(thenValue)}
          </p>
        </div>

        {/* Arrow */}
        <div className="flex justify-center">
          <ArrowRight className={`h-4 w-4 ${styles.text}`} />
        </div>

        {/* Now Value */}
        <div className="text-center">
          <p className="text-xs text-muted-foreground mb-1">Current</p>
          <p className={`font-mono text-sm font-medium ${hasChanged ? styles.text : ''}`}>
            {formatValue(nowValue)}
          </p>
        </div>
      </div>
    </div>
  );
}

/**
 * Risk Flag Diff Component
 */
function RiskFlagDiff({
  newFlags,
  removedFlags
}: {
  newFlags: RiskFlag[];
  removedFlags: RiskFlag[];
}) {
  if (newFlags.length === 0 && removedFlags.length === 0) {
    return (
      <div className="text-center py-4 text-muted-foreground">
        <Shield className="h-8 w-8 mx-auto mb-2 text-green-500" />
        <p className="text-sm">No changes to risk flags</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* New Risk Flags */}
      {newFlags.length > 0 && (
        <div>
          <h4 className="text-sm font-medium text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
            <AlertTriangle className="h-4 w-4" />
            New Risk Flags ({newFlags.length})
          </h4>
          <div className="space-y-2">
            {newFlags.map((flag) => (
              <div
                key={flag.id}
                className="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg p-3"
              >
                <div className="flex items-center justify-between">
                  <span className="font-medium text-red-800 dark:text-red-200">{flag.name}</span>
                  <Badge variant="destructive" className="text-xs">
                    {flag.severity}
                  </Badge>
                </div>
                <p className="text-xs text-red-700 dark:text-red-300 mt-1">
                  {flag.rationale}
                </p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Removed Risk Flags */}
      {removedFlags.length > 0 && (
        <div>
          <h4 className="text-sm font-medium text-green-600 dark:text-green-400 mb-2 flex items-center gap-2">
            <CheckCircle className="h-4 w-4" />
            Resolved Risk Flags ({removedFlags.length})
          </h4>
          <div className="space-y-2">
            {removedFlags.map((flag) => (
              <div
                key={flag.id}
                className="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg p-3"
              >
                <div className="flex items-center justify-between">
                  <span className="font-medium text-green-800 dark:text-green-200 line-through">
                    {flag.name}
                  </span>
                  <Badge variant="success" className="text-xs">
                    Resolved
                  </Badge>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/**
 * Summary Change Indicator
 */
function ChangeSummary({ comparison }: { comparison: SnapshotComparison }) {
  const { changes } = comparison;
  const totalChanges =
    (changes.lifecycle_state_changed ? 1 : 0) +
    (changes.buying_status_changed ? 1 : 0) +
    (changes.risk_level_changed ? 1 : 0) +
    changes.new_risk_flags.length +
    changes.removed_risk_flags.length;

  const hasImprovements =
    changes.compliance_score_delta > 0 || changes.removed_risk_flags.length > 0;
  const hasDegradations =
    changes.compliance_score_delta < 0 || changes.new_risk_flags.length > 0;

  if (totalChanges === 0 && changes.compliance_score_delta === 0) {
    return (
      <div className="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div className="flex items-center gap-3">
          <CheckCircle className="h-6 w-6 text-green-600" />
          <div>
            <p className="font-medium text-green-800 dark:text-green-200">
              No Significant Changes
            </p>
            <p className="text-sm text-green-700 dark:text-green-300">
              Platform state is essentially the same as when you invested.
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 gap-4">
      {/* Improvements */}
      <div className="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div className="flex items-center gap-2 mb-2">
          <TrendingUp className="h-5 w-5 text-green-600" />
          <span className="font-medium text-green-800 dark:text-green-200">Improvements</span>
        </div>
        <ul className="text-sm text-green-700 dark:text-green-300 space-y-1">
          {changes.compliance_score_delta > 0 && (
            <li>+{changes.compliance_score_delta} compliance score</li>
          )}
          {changes.removed_risk_flags.length > 0 && (
            <li>{changes.removed_risk_flags.length} risk flag(s) resolved</li>
          )}
          {!hasImprovements && <li className="text-muted-foreground">None</li>}
        </ul>
      </div>

      {/* Degradations */}
      <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div className="flex items-center gap-2 mb-2">
          <TrendingDown className="h-5 w-5 text-amber-600" />
          <span className="font-medium text-amber-800 dark:text-amber-200">Changes to Note</span>
        </div>
        <ul className="text-sm text-amber-700 dark:text-amber-300 space-y-1">
          {changes.compliance_score_delta < 0 && (
            <li>{changes.compliance_score_delta} compliance score</li>
          )}
          {changes.new_risk_flags.length > 0 && (
            <li>{changes.new_risk_flags.length} new risk flag(s)</li>
          )}
          {changes.risk_level_changed && <li>Risk level changed</li>}
          {!hasDegradations && <li className="text-muted-foreground">None</li>}
        </ul>
      </div>
    </div>
  );
}

/**
 * Main Snapshot Comparison Component
 * GAP 36 FIX: Allows investors to compare "then vs now"
 */
export function SnapshotComparisonUI({
  investmentId,
  compactMode = false
}: SnapshotComparisonProps) {
  const [comparison, setComparison] = useState<SnapshotComparison | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchComparison = async () => {
      try {
        setLoading(true);
        setError(null);
        const { data } = await api.get(`/investments/${investmentId}/snapshot-comparison`);
        setComparison(data.data);
      } catch (err: any) {
        setError(err.message || 'Failed to load comparison');
      } finally {
        setLoading(false);
      }
    };

    fetchComparison();
  }, [investmentId]);

  if (loading) {
    return (
      <Card>
        <CardHeader>
          <Skeleton className="h-6 w-48" />
          <Skeleton className="h-4 w-64 mt-2" />
        </CardHeader>
        <CardContent className="space-y-4">
          <Skeleton className="h-20 w-full" />
          <Skeleton className="h-20 w-full" />
          <Skeleton className="h-20 w-full" />
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return (
      <Card>
        <CardContent className="py-8">
          <div className="text-center text-muted-foreground">
            <AlertTriangle className="h-8 w-8 mx-auto mb-2 text-amber-500" />
            <p>{error}</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!comparison) {
    return null;
  }

  const { then: thenState, now: nowState, changes } = comparison;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <RefreshCw className="h-5 w-5" />
          Platform State Comparison
        </CardTitle>
        <CardDescription>
          Compare the platform state when you invested vs. current state for{' '}
          <strong>{comparison.company_name}</strong>
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Timeline Header */}
        <div className="grid grid-cols-2 gap-4 text-center">
          <div className="bg-muted/50 rounded-lg p-3">
            <Clock className="h-5 w-5 mx-auto mb-1 text-muted-foreground" />
            <p className="text-xs text-muted-foreground">Investment Date</p>
            <p className="font-medium">{new Date(comparison.investment_date).toLocaleDateString()}</p>
          </div>
          <div className="bg-primary/10 rounded-lg p-3">
            <Clock className="h-5 w-5 mx-auto mb-1 text-primary" />
            <p className="text-xs text-muted-foreground">Current Snapshot</p>
            <p className="font-medium">{new Date(nowState.snapshot_date).toLocaleDateString()}</p>
          </div>
        </div>

        {/* Change Summary */}
        <ChangeSummary comparison={comparison} />

        {/* Detailed Comparison */}
        {!compactMode && (
          <>
            <div className="space-y-3">
              <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                Platform State Details
              </h3>

              <ComparisonRow
                label="Lifecycle State"
                thenValue={thenState.lifecycle_state}
                nowValue={nowState.lifecycle_state}
                changeType={getChangeType('lifecycle_state', thenState.lifecycle_state, nowState.lifecycle_state)}
                tooltip="The current phase of the investment opportunity"
              />

              <ComparisonRow
                label="Buying Enabled"
                thenValue={thenState.buying_enabled}
                nowValue={nowState.buying_enabled}
                changeType={getChangeType('buying_enabled', thenState.buying_enabled, nowState.buying_enabled)}
                tooltip="Whether new investments are currently being accepted"
              />

              <ComparisonRow
                label="Risk Level"
                thenValue={thenState.risk_level}
                nowValue={nowState.risk_level}
                changeType={getChangeType('risk_level', thenState.risk_level, nowState.risk_level)}
                tooltip="Overall assessed risk level for this investment"
              />

              <ComparisonRow
                label="Compliance Score"
                thenValue={thenState.compliance_score}
                nowValue={nowState.compliance_score}
                changeType={getChangeType('compliance_score', thenState.compliance_score, nowState.compliance_score)}
                tooltip="Regulatory compliance score (0-100)"
              />
            </div>

            {/* Risk Flag Changes */}
            <div>
              <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
                Risk Flag Changes
              </h3>
              <RiskFlagDiff
                newFlags={changes.new_risk_flags}
                removedFlags={changes.removed_risk_flags}
              />
            </div>
          </>
        )}

        {/* Footer Note */}
        <div className="text-xs text-muted-foreground bg-muted/30 rounded-lg p-3">
          <Info className="h-4 w-4 inline mr-1" />
          This comparison shows verified platform state snapshots. Snapshot #{thenState.snapshot_id} was
          captured at your investment time and is immutable for audit purposes.
        </div>
      </CardContent>
    </Card>
  );
}

/**
 * Compact inline comparison indicator
 */
export function SnapshotChangeIndicator({
  investmentId,
  onViewDetails
}: {
  investmentId: number;
  onViewDetails?: () => void;
}) {
  const [hasChanges, setHasChanges] = useState<boolean | null>(null);
  const [changeCount, setChangeCount] = useState(0);

  useEffect(() => {
    const checkChanges = async () => {
      try {
        const { data } = await api.get(`/investments/${investmentId}/snapshot-comparison`);
        const comparison = data.data as SnapshotComparison;
        const totalChanges =
          (comparison.changes.lifecycle_state_changed ? 1 : 0) +
          (comparison.changes.buying_status_changed ? 1 : 0) +
          (comparison.changes.risk_level_changed ? 1 : 0) +
          comparison.changes.new_risk_flags.length;
        setChangeCount(totalChanges);
        setHasChanges(totalChanges > 0);
      } catch {
        setHasChanges(null);
      }
    };

    checkChanges();
  }, [investmentId]);

  if (hasChanges === null) {
    return null;
  }

  if (!hasChanges) {
    return (
      <span className="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
        <CheckCircle className="h-3 w-3" />
        No changes
      </span>
    );
  }

  return (
    <button
      onClick={onViewDetails}
      className="text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1 hover:underline"
    >
      <AlertTriangle className="h-3 w-3" />
      {changeCount} change{changeCount !== 1 ? 's' : ''} since investment
    </button>
  );
}
