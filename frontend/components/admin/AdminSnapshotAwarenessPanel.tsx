/**
 * EPIC 5 Story 5.4 - Admin Snapshot Awareness Panel
 *
 * PURPOSE:
 * Show frozen investor snapshots and emphasize their immutability.
 * Admin must understand that historical snapshots cannot be altered.
 *
 * STORY 5.4 REQUIREMENTS:
 * - Admin can see frozen investor snapshots
 * - Admin UI clearly marks irreversible actions
 * - Admin can distinguish actions affecting:
 *   - future investments only
 *   - no past snapshots
 * - Past investor snapshots are visibly immutable
 *
 * INVARIANTS:
 * - ❌ No silent historical mutation
 * - ❌ Admin cannot accidentally mutate investor history
 * - ✅ A regulator can reconstruct what an investor saw
 */

import {
  Lock,
  Clock,
  Users,
  FileText,
  Shield,
  AlertTriangle,
  Eye,
  History,
} from "lucide-react";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import Link from "next/link";

// ============================================================================
// TYPES
// ============================================================================

export interface SnapshotSummary {
  id: number;
  created_at: string;
  investor_id: number;
  investor_name?: string;
  investment_amount: number;
  disclosure_version: number;
  is_immutable: boolean;
  locked_at?: string;
}

export interface SnapshotAwareness {
  total_investors: number;
  total_investments: number;
  total_snapshots: number;
  latest_snapshot_at?: string;
  earliest_snapshot_at?: string;
  snapshots_by_version: Array<{
    version: number;
    count: number;
    total_amount: number;
  }>;
  recent_snapshots?: SnapshotSummary[];
}

interface AdminSnapshotAwarenessPanelProps {
  awareness: SnapshotAwareness;
  companyId: number;
  companyName: string;
  showRecentSnapshots?: boolean;
  className?: string;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0,
  }).format(amount);
}

// ============================================================================
// COMPONENT
// ============================================================================

export function AdminSnapshotAwarenessPanel({
  awareness,
  companyId,
  companyName,
  showRecentSnapshots = true,
  className = "",
}: AdminSnapshotAwarenessPanelProps) {
  const hasSnapshots = awareness.total_snapshots > 0;

  return (
    <div className={`space-y-4 ${className}`}>
      {/*
       * STORY 5.4: Immutability Warning Banner
       * Prominent warning that snapshots cannot be altered.
       */}
      <Alert className="border-2 border-blue-300 bg-blue-50 dark:bg-blue-950/30">
        <Lock className="h-5 w-5 text-blue-600" />
        <AlertTitle className="text-blue-900 dark:text-blue-100 font-bold flex items-center gap-2">
          Investor Snapshots Are Permanently Frozen
          <Badge className="bg-blue-600 text-white">IMMUTABLE</Badge>
        </AlertTitle>
        <AlertDescription className="text-blue-800 dark:text-blue-200">
          <p className="mb-2">
            Each investor's purchase is bound to an immutable snapshot of the company state
            at the moment of investment. These historical records:
          </p>
          <ul className="list-disc list-inside space-y-1 text-sm">
            <li>Cannot be altered by platform actions</li>
            <li>Preserve what the investor saw at purchase time</li>
            <li>Enable regulatory reconstruction of investment context</li>
            <li>Protect investor rights in case of disputes</li>
          </ul>
        </AlertDescription>
      </Alert>

      {/*
       * STORY 5.4: Snapshot Summary Card
       */}
      <Card className="border-blue-200 dark:border-blue-800">
        <CardHeader className="pb-3">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Shield className="w-5 h-5 text-blue-600" />
            Snapshot Summary: {companyName}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Metrics Grid */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <MetricCard
              icon={<Users className="w-4 h-4" />}
              label="Investors"
              value={awareness.total_investors}
              color="blue"
            />
            <MetricCard
              icon={<FileText className="w-4 h-4" />}
              label="Investments"
              value={awareness.total_investments}
              color="green"
            />
            <MetricCard
              icon={<Lock className="w-4 h-4" />}
              label="Frozen Snapshots"
              value={awareness.total_snapshots}
              color="purple"
            />
            <MetricCard
              icon={<History className="w-4 h-4" />}
              label="Version Groups"
              value={awareness.snapshots_by_version.length}
              color="amber"
            />
          </div>

          {/* Timeline */}
          {hasSnapshots && (
            <div className="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg">
              <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">
                Snapshot Timeline
              </p>
              <div className="flex justify-between text-sm">
                <div>
                  <span className="text-gray-500">First Snapshot:</span>
                  <p className="font-medium">
                    {awareness.earliest_snapshot_at
                      ? formatDate(awareness.earliest_snapshot_at)
                      : "N/A"}
                  </p>
                </div>
                <div className="text-right">
                  <span className="text-gray-500">Latest Snapshot:</span>
                  <p className="font-medium">
                    {awareness.latest_snapshot_at
                      ? formatDate(awareness.latest_snapshot_at)
                      : "N/A"}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Version Distribution */}
          {awareness.snapshots_by_version.length > 0 && (
            <div>
              <p className="text-sm font-semibold mb-2">Snapshots by Disclosure Version</p>
              <div className="space-y-2">
                {awareness.snapshots_by_version.map((versionData) => (
                  <div
                    key={versionData.version}
                    className="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-950/30 rounded"
                  >
                    <div className="flex items-center gap-2">
                      <Badge variant="outline">v{versionData.version}</Badge>
                      <span className="text-sm">{versionData.count} snapshots</span>
                    </div>
                    <span className="text-sm font-medium text-blue-700 dark:text-blue-300">
                      {formatCurrency(versionData.total_amount)}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* No Snapshots State */}
          {!hasSnapshots && (
            <div className="text-center py-6 text-gray-500">
              <Shield className="w-12 h-12 mx-auto mb-2 opacity-50" />
              <p>No investor snapshots exist yet for this company.</p>
              <p className="text-sm">Snapshots will be created when investors make purchases.</p>
            </div>
          )}

          {/* View Snapshots Link */}
          {hasSnapshots && (
            <div className="flex justify-end">
              <Link href={`/admin/companies/${companyId}/snapshots`}>
                <Button variant="outline" size="sm">
                  <Eye className="w-4 h-4 mr-2" />
                  View All Snapshots
                </Button>
              </Link>
            </div>
          )}
        </CardContent>
      </Card>

      {/*
       * STORY 5.4: Recent Snapshots List (Optional)
       */}
      {showRecentSnapshots && awareness.recent_snapshots && awareness.recent_snapshots.length > 0 && (
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2">
              <Clock className="w-5 h-5" />
              Recent Investor Snapshots
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {awareness.recent_snapshots.slice(0, 5).map((snapshot) => (
                <div
                  key={snapshot.id}
                  className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                >
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                      <Lock className="w-4 h-4 text-blue-600" />
                    </div>
                    <div>
                      <p className="text-sm font-medium">
                        Snapshot #{snapshot.id}
                      </p>
                      <p className="text-xs text-gray-500">
                        {formatDate(snapshot.created_at)}
                      </p>
                    </div>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-semibold">
                      {formatCurrency(snapshot.investment_amount)}
                    </p>
                    <Badge variant="outline" className="text-xs">
                      Disclosure v{snapshot.disclosure_version}
                    </Badge>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/*
       * STORY 5.4: Action Impact Notice
       * Clarifies what admin actions CAN and CANNOT do to snapshots.
       */}
      <Alert className="border-green-200 bg-green-50 dark:bg-green-950/30">
        <Shield className="h-4 w-4 text-green-600" />
        <AlertTitle className="text-green-900 dark:text-green-100">
          Platform Actions Cannot Alter Past Snapshots
        </AlertTitle>
        <AlertDescription className="text-sm text-green-800 dark:text-green-200">
          <p className="mb-2">
            When you take platform actions (suspend, freeze, change visibility):
          </p>
          <ul className="list-disc list-inside space-y-1">
            <li>
              <strong>Future investments:</strong> Will see the new state
            </li>
            <li>
              <strong>Past snapshots:</strong> Remain frozen with original state
            </li>
            <li>
              <strong>Investor holdings:</strong> Unaffected by platform actions
            </li>
          </ul>
        </AlertDescription>
      </Alert>
    </div>
  );
}

// ============================================================================
// HELPER COMPONENT
// ============================================================================

function MetricCard({
  icon,
  label,
  value,
  color,
}: {
  icon: React.ReactNode;
  label: string;
  value: number;
  color: "blue" | "green" | "purple" | "amber";
}) {
  const colorStyles = {
    blue: "bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300",
    green: "bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300",
    purple: "bg-purple-50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300",
    amber: "bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300",
  };

  const valueStyles = {
    blue: "text-blue-900 dark:text-blue-100",
    green: "text-green-900 dark:text-green-100",
    purple: "text-purple-900 dark:text-purple-100",
    amber: "text-amber-900 dark:text-amber-100",
  };

  return (
    <div className={`p-3 rounded-lg border ${colorStyles[color]}`}>
      <div className="flex items-center gap-2 mb-1">
        {icon}
        <span className="text-xs font-semibold uppercase tracking-wide">{label}</span>
      </div>
      <p className={`text-2xl font-bold ${valueStyles[color]}`}>{value}</p>
    </div>
  );
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

export default AdminSnapshotAwarenessPanel;
