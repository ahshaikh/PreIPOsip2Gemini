/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Admin Action Detail - Complete audit of a single admin action
 *
 * PURPOSE:
 * - View complete details of any admin/platform action
 * - See who performed the action and when
 * - Review the mandatory reason provided
 * - Examine before/after state changes
 * - Understand impact at time of action
 *
 * INVARIANTS:
 * - READ-ONLY - no modifications possible
 * - All admin actions are immutable once performed
 * - Actor identity is always traceable
 * - Reason is mandatory and rendered verbatim
 */

"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  Loader2,
  AlertCircle,
  Activity,
  User,
  Target,
  Clock,
  Shield,
  ArrowRight,
  Users,
  FileText,
} from "lucide-react";
import {
  fetchAdminActionDetail,
  type AdminActionAudit,
} from "@/lib/auditApi";
import {
  AuditImmutableNotice,
  AuditSnapshotHash,
  AuditTimestamp,
  AuditActorBadge,
  AuditAuthorityLabel,
  AuditReasonBlock,
} from "@/components/audit";

interface SectionProps {
  title: string;
  icon: React.ReactNode;
  children: React.ReactNode;
}

function Section({ title, icon, children }: SectionProps) {
  return (
    <div className="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
      <div className="flex items-center gap-2 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
        <span className="text-slate-500 dark:text-slate-400">{icon}</span>
        <h2 className="text-sm font-mono font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
          {title}
        </h2>
      </div>
      <div className="p-4 bg-white dark:bg-slate-900">{children}</div>
    </div>
  );
}

function DataRow({
  label,
  value,
}: {
  label: string;
  value: React.ReactNode;
}) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-4 py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
      <span className="text-xs font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wide sm:w-40 flex-shrink-0">
        {label}
      </span>
      <span className="text-sm font-mono text-slate-800 dark:text-slate-200">
        {value}
      </span>
    </div>
  );
}

function StateChangeViewer({
  before,
  after,
  changedFields,
}: {
  before: Record<string, unknown>;
  after: Record<string, unknown>;
  changedFields: string[];
}) {
  return (
    <div className="space-y-3">
      {changedFields.map((field) => (
        <div
          key={field}
          className="p-3 bg-slate-50 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700"
        >
          <span className="block text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">
            {field}
          </span>
          <div className="flex items-center gap-4">
            <div className="flex-1 p-2 bg-red-50 dark:bg-red-950/30 rounded border border-red-200 dark:border-red-800">
              <span className="block text-xs font-mono text-red-600 dark:text-red-400 uppercase mb-1">
                Before
              </span>
              <code className="text-sm font-mono text-red-800 dark:text-red-300">
                {JSON.stringify(before[field], null, 2)}
              </code>
            </div>
            <ArrowRight className="w-4 h-4 text-slate-400 flex-shrink-0" />
            <div className="flex-1 p-2 bg-emerald-50 dark:bg-emerald-950/30 rounded border border-emerald-200 dark:border-emerald-800">
              <span className="block text-xs font-mono text-emerald-600 dark:text-emerald-400 uppercase mb-1">
                After
              </span>
              <code className="text-sm font-mono text-emerald-800 dark:text-emerald-300">
                {JSON.stringify(after[field], null, 2)}
              </code>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
}

export default function AdminActionDetailPage() {
  const params = useParams();
  const actionId = Number(params.id);

  const [data, setData] = useState<AdminActionAudit | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function loadData() {
      if (!actionId || isNaN(actionId)) {
        setError("Invalid action ID");
        setLoading(false);
        return;
      }

      try {
        const result = await fetchAdminActionDetail(actionId);
        setData(result);
      } catch (err) {
        console.error("[Audit] Failed to load action:", err);
        setError("Failed to load admin action detail.");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [actionId]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2">
          <Loader2 className="w-8 h-8 animate-spin text-slate-400" />
          <span className="text-sm font-mono text-slate-500">
            Loading action details...
          </span>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="space-y-4">
        <Link
          href="/admin/audit/actions"
          className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Actions
        </Link>
        <div className="flex items-center justify-center min-h-[30vh]">
          <div className="flex flex-col items-center gap-2 text-center">
            <AlertCircle className="w-8 h-8 text-red-500" />
            <p className="text-sm font-mono text-red-600 dark:text-red-400">
              {error || "Action not found"}
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Back link */}
      <Link
        href="/admin/audit/actions"
        className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Actions
      </Link>

      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">
          Admin Action #{data.id}
        </h1>
        <p className="text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
          {data.action_type}: {data.action_description}
        </p>
      </div>

      {/* Immutability Notice */}
      <AuditImmutableNotice variant="approval" recordType="Admin Action" />

      {/* Snapshot Hash if available */}
      {data.snapshot_hash && (
        <div className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
          <AuditSnapshotHash
            hash={data.snapshot_hash}
            label="Action Snapshot Hash"
          />
        </div>
      )}

      {/* Action Summary */}
      <Section title="Action Details" icon={<Activity className="w-4 h-4" />}>
        <div className="space-y-0">
          <DataRow label="Action ID" value={`#${data.id}`} />
          <DataRow label="Action Type" value={data.action_type} />
          <DataRow label="Description" value={data.action_description} />
          <DataRow
            label="Performed At"
            value={
              <AuditTimestamp timestamp={data.performed_at} showLocal variant="block" />
            }
          />
        </div>
      </Section>

      {/* Who Performed */}
      <Section title="Performed By" icon={<User className="w-4 h-4" />}>
        <div className="space-y-4">
          <AuditActorBadge
            actorType="admin"
            actorId={data.actor.admin_id}
            actorName={data.actor.admin_name}
          />
          <div className="space-y-0 pt-2 border-t border-slate-200 dark:border-slate-700">
            <DataRow label="Admin ID" value={`#${data.actor.admin_id}`} />
            <DataRow label="Admin Name" value={data.actor.admin_name} />
            <DataRow label="Admin Email" value={data.actor.admin_email} />
          </div>
        </div>
      </Section>

      {/* Target */}
      <Section title="Action Target" icon={<Target className="w-4 h-4" />}>
        <div className="space-y-0">
          <DataRow label="Target Type" value={data.target.type} />
          <DataRow label="Target ID" value={`#${data.target.id}`} />
          {data.target.name && (
            <DataRow label="Target Name" value={data.target.name} />
          )}
        </div>
        <div className="mt-4">
          {data.target.type === "company" && (
            <Link
              href={`/admin/audit/companies/${data.target.id}`}
              className="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
            >
              <FileText className="w-4 h-4" />
              View Company Governance Audit
            </Link>
          )}
          {data.target.type === "investor" && (
            <Link
              href={`/admin/audit/investors/${data.target.id}`}
              className="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
            >
              <Users className="w-4 h-4" />
              View Investor Audit Timeline
            </Link>
          )}
          {data.target.type === "investment" && (
            <Link
              href={`/admin/audit/investments/${data.target.id}`}
              className="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
            >
              <FileText className="w-4 h-4" />
              View Investment Reconstruction
            </Link>
          )}
        </div>
      </Section>

      {/* Decision & Reason */}
      <Section title="Decision & Authority" icon={<Shield className="w-4 h-4" />}>
        <div className="space-y-4">
          <div className="flex items-center gap-4">
            <AuditAuthorityLabel authority={data.decision.authority} />
            {data.decision.is_mandatory_reason && (
              <span className="px-2 py-0.5 text-xs font-mono bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded uppercase">
                Mandatory Reason Required
              </span>
            )}
          </div>
          <AuditReasonBlock reason={data.decision.reason} />
        </div>
      </Section>

      {/* State Change */}
      {data.state_change && data.state_change.changed_fields.length > 0 && (
        <Section
          title="State Changes"
          icon={<ArrowRight className="w-4 h-4" />}
        >
          <StateChangeViewer
            before={data.state_change.before}
            after={data.state_change.after}
            changedFields={data.state_change.changed_fields}
          />
        </Section>
      )}

      {/* Impact Captured */}
      {data.impact_captured && (
        <Section title="Impact at Action Time" icon={<Users className="w-4 h-4" />}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 text-center">
              <span className="block text-2xl font-bold font-mono text-slate-900 dark:text-slate-100">
                {data.impact_captured.affected_investors}
              </span>
              <span className="block text-xs font-mono text-slate-500 uppercase tracking-wide mt-1">
                Affected Investors
              </span>
            </div>
            <div className="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 text-center">
              <span className="block text-2xl font-bold font-mono text-slate-900 dark:text-slate-100">
                {data.impact_captured.affected_investments}
              </span>
              <span className="block text-xs font-mono text-slate-500 uppercase tracking-wide mt-1">
                Affected Investments
              </span>
            </div>
          </div>
          {data.impact_captured.blocked_actions &&
            data.impact_captured.blocked_actions.length > 0 && (
              <div className="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                <h3 className="text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">
                  Actions Blocked by This Change
                </h3>
                <ul className="space-y-1">
                  {data.impact_captured.blocked_actions.map((action, idx) => (
                    <li
                      key={idx}
                      className="flex items-center gap-2 text-sm font-mono text-slate-600 dark:text-slate-400"
                    >
                      <span className="w-1.5 h-1.5 rounded-full bg-red-500" />
                      {action}
                    </li>
                  ))}
                </ul>
              </div>
            )}
        </Section>
      )}
    </div>
  );
}
