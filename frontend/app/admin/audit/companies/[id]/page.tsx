/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Company Governance Audit - Complete governance history for a company
 *
 * PURPOSE:
 * - Track company lifecycle transitions
 * - View disclosure submission/approval history
 * - See tier transitions with reasons
 * - Review risk flag history
 * - Audit platform governance actions
 *
 * INVARIANTS:
 * - READ-ONLY - no modifications possible
 * - All governance actions are traceable
 * - Timestamps in UTC with local time
 */

"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  Loader2,
  AlertCircle,
  Building2,
  FileText,
  Shield,
  AlertTriangle,
  Activity,
  Clock,
  TrendingUp,
  Users,
  Download,
} from "lucide-react";
import {
  fetchCompanyGovernanceAudit,
  exportCompanyGovernanceAudit,
  type CompanyGovernanceAudit,
} from "@/lib/auditApi";
import {
  AuditImmutableNotice,
  AuditTimestamp,
  AuditAuthorityLabel,
  AuditActorBadge,
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

function StatBox({
  label,
  value,
  variant = "default",
}: {
  label: string;
  value: string | number;
  variant?: "default" | "warning" | "success";
}) {
  const variantStyles = {
    default: "bg-slate-50 dark:bg-slate-800",
    warning: "bg-amber-50 dark:bg-amber-950/30",
    success: "bg-emerald-50 dark:bg-emerald-950/30",
  };

  return (
    <div className={`${variantStyles[variant]} rounded-lg p-4 text-center`}>
      <span className="block text-2xl font-bold font-mono text-slate-900 dark:text-slate-100">
        {value}
      </span>
      <span className="block text-xs font-mono text-slate-500 uppercase tracking-wide mt-1">
        {label}
      </span>
    </div>
  );
}

export default function CompanyGovernanceAuditPage() {
  const params = useParams();
  const companyId = Number(params.id);

  const [data, setData] = useState<CompanyGovernanceAudit | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    async function loadData() {
      if (!companyId || isNaN(companyId)) {
        setError("Invalid company ID");
        setLoading(false);
        return;
      }

      try {
        const result = await fetchCompanyGovernanceAudit(companyId);
        setData(result);
      } catch (err) {
        console.error("[Audit] Failed to load company:", err);
        setError("Failed to load company governance audit.");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [companyId]);

  const handleExport = async () => {
    if (!companyId) return;

    setExporting(true);
    try {
      const blob = await exportCompanyGovernanceAudit(companyId);
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `company-${companyId}-governance-audit.json`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (err) {
      console.error("[Audit] Export failed:", err);
    } finally {
      setExporting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2">
          <Loader2 className="w-8 h-8 animate-spin text-slate-400" />
          <span className="text-sm font-mono text-slate-500">
            Loading governance audit...
          </span>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="space-y-4">
        <Link
          href="/admin/audit/companies"
          className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Companies
        </Link>
        <div className="flex items-center justify-center min-h-[30vh]">
          <div className="flex flex-col items-center gap-2 text-center">
            <AlertCircle className="w-8 h-8 text-red-500" />
            <p className="text-sm font-mono text-red-600 dark:text-red-400">
              {error || "Company not found"}
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
        href="/admin/audit/companies"
        className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Companies
      </Link>

      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">
            {data.company.name}
          </h1>
          <p className="text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
            Company ID: #{data.company.id} | Governance Audit
          </p>
        </div>
        <button
          onClick={handleExport}
          disabled={exporting}
          className="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 dark:bg-slate-700 text-white rounded-lg hover:bg-slate-800 dark:hover:bg-slate-600 transition-colors disabled:opacity-50"
        >
          {exporting ? (
            <Loader2 className="w-4 h-4 animate-spin" />
          ) : (
            <Download className="w-4 h-4" />
          )}
          Export for Regulator
        </button>
      </div>

      {/* Immutability Notice */}
      <AuditImmutableNotice variant="default" recordType="Governance Data" />

      {/* Current State */}
      <Section
        title="Current Governance State"
        icon={<Shield className="w-4 h-4" />}
      >
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <StatBox
            label="Lifecycle State"
            value={data.current_state.lifecycle_state}
          />
          <StatBox
            label="Disclosure Tier"
            value={`Tier ${data.current_state.disclosure_tier}`}
          />
          <StatBox
            label="Buying"
            value={data.current_state.buying_enabled ? "Enabled" : "Disabled"}
            variant={data.current_state.buying_enabled ? "success" : "warning"}
          />
          <StatBox
            label="Status"
            value={
              data.current_state.is_frozen
                ? "Frozen"
                : data.current_state.is_suspended
                ? "Suspended"
                : "Active"
            }
            variant={
              data.current_state.is_frozen || data.current_state.is_suspended
                ? "warning"
                : "success"
            }
          />
        </div>

        {data.current_state.is_under_investigation && (
          <div className="flex items-center gap-2 p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded">
            <AlertTriangle className="w-4 h-4 text-red-600" />
            <span className="text-sm font-mono text-red-700 dark:text-red-400">
              COMPANY IS UNDER INVESTIGATION
            </span>
          </div>
        )}
      </Section>

      {/* Investor Impact */}
      <Section title="Investor Impact" icon={<Users className="w-4 h-4" />}>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <StatBox
            label="Total Investors"
            value={data.investor_impact.total_investors}
          />
          <StatBox
            label="Total Investments"
            value={data.investor_impact.total_investments}
          />
          <StatBox
            label="Total Invested"
            value={`₹${data.investor_impact.total_invested_amount}`}
          />
        </div>
      </Section>

      {/* Tier Transitions */}
      <Section
        title="Tier Transition History"
        icon={<TrendingUp className="w-4 h-4" />}
      >
        {data.tier_transitions.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No tier transitions recorded.
          </p>
        ) : (
          <div className="space-y-3">
            {data.tier_transitions.map((transition, idx) => (
              <div
                key={idx}
                className="flex items-start gap-4 p-3 bg-slate-50 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700"
              >
                <div className="flex items-center gap-2">
                  <span className="text-lg font-bold font-mono text-slate-400">
                    {transition.from_tier}
                  </span>
                  <span className="text-slate-400">→</span>
                  <span className="text-lg font-bold font-mono text-indigo-600 dark:text-indigo-400">
                    {transition.to_tier}
                  </span>
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <AuditAuthorityLabel authority={transition.authority} />
                    <AuditTimestamp
                      timestamp={transition.transitioned_at}
                      showLocal
                      className="text-xs"
                    />
                  </div>
                  <p className="text-sm text-slate-600 dark:text-slate-400">
                    {transition.reason}
                  </p>
                </div>
              </div>
            ))}
          </div>
        )}
      </Section>

      {/* Disclosure History */}
      <Section
        title="Disclosure History"
        icon={<FileText className="w-4 h-4" />}
      >
        {data.disclosure_history.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No disclosures recorded.
          </p>
        ) : (
          <div className="space-y-3">
            {data.disclosure_history.map((disclosure, idx) => (
              <div
                key={idx}
                className="p-3 bg-slate-50 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700"
              >
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <span className="font-mono font-semibold text-slate-800 dark:text-slate-200">
                      {disclosure.module_name}
                    </span>
                    <span className="text-xs font-mono text-slate-500">
                      v{disclosure.version_number}
                    </span>
                  </div>
                  <span
                    className={`px-2 py-0.5 text-xs font-mono uppercase rounded ${
                      disclosure.status === "approved"
                        ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                        : disclosure.status === "rejected"
                        ? "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                        : "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                    }`}
                  >
                    {disclosure.status}
                  </span>
                </div>
                <div className="flex flex-wrap gap-4 text-xs text-slate-500">
                  <span>
                    Submitted:{" "}
                    <AuditTimestamp
                      timestamp={disclosure.submitted_at}
                      showLocal={false}
                      showTime={false}
                    />
                  </span>
                  {disclosure.reviewed_at && (
                    <span>
                      Reviewed:{" "}
                      <AuditTimestamp
                        timestamp={disclosure.reviewed_at}
                        showLocal={false}
                        showTime={false}
                      />
                    </span>
                  )}
                  {disclosure.reviewed_by && (
                    <span>By: {disclosure.reviewed_by}</span>
                  )}
                </div>
                {disclosure.reason && (
                  <AuditReasonBlock reason={disclosure.reason} className="mt-2" />
                )}
              </div>
            ))}
          </div>
        )}
      </Section>

      {/* Risk Flag History */}
      <Section
        title="Risk Flag History"
        icon={<AlertTriangle className="w-4 h-4" />}
      >
        {data.risk_flag_history.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No risk flags recorded.
          </p>
        ) : (
          <div className="space-y-3">
            {data.risk_flag_history.map((flag, idx) => (
              <div
                key={idx}
                className={`p-3 rounded border ${
                  flag.status === "active"
                    ? "bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800"
                    : "bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <AlertTriangle
                      className={`w-4 h-4 ${
                        flag.status === "active"
                          ? "text-amber-600"
                          : "text-slate-400"
                      }`}
                    />
                    <span className="font-mono font-semibold text-slate-800 dark:text-slate-200">
                      {flag.flag_type}
                    </span>
                    <span
                      className={`text-xs font-mono px-1.5 py-0.5 rounded ${
                        flag.severity === "high"
                          ? "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                          : flag.severity === "medium"
                          ? "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                          : "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400"
                      }`}
                    >
                      {flag.severity}
                    </span>
                  </div>
                  <span
                    className={`px-2 py-0.5 text-xs font-mono uppercase rounded ${
                      flag.status === "active"
                        ? "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                        : "bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                    }`}
                  >
                    {flag.status}
                  </span>
                </div>
                <div className="flex flex-wrap gap-4 text-xs text-slate-500 mb-2">
                  <span>
                    Created:{" "}
                    <AuditTimestamp
                      timestamp={flag.created_at}
                      showLocal={false}
                      showTime={false}
                    />
                  </span>
                  {flag.resolved_at && (
                    <span>
                      Resolved:{" "}
                      <AuditTimestamp
                        timestamp={flag.resolved_at}
                        showLocal={false}
                        showTime={false}
                      />
                    </span>
                  )}
                  <AuditAuthorityLabel authority={flag.authority} />
                </div>
                <AuditReasonBlock reason={flag.reason} />
              </div>
            ))}
          </div>
        )}
      </Section>

      {/* Platform Actions Timeline */}
      <Section
        title="Platform Actions Timeline"
        icon={<Activity className="w-4 h-4" />}
      >
        {data.platform_actions.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No platform actions recorded.
          </p>
        ) : (
          <div className="space-y-4">
            {data.platform_actions.map((event, idx) => (
              <div
                key={event.id || idx}
                className="relative pl-6 pb-4 border-l-2 border-slate-200 dark:border-slate-700 last:pb-0"
              >
                <div className="absolute -left-1.5 top-0 w-3 h-3 rounded-full bg-indigo-500" />
                <div className="space-y-2">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200">
                      {event.event_type}
                    </span>
                    <AuditAuthorityLabel authority={event.authority} />
                  </div>
                  <p className="text-sm text-slate-600 dark:text-slate-400">
                    {event.event_description}
                  </p>
                  <div className="flex items-center gap-4 flex-wrap">
                    <AuditActorBadge
                      actorType={event.actor.type}
                      actorId={event.actor.id}
                      actorName={event.actor.name}
                    />
                    <AuditTimestamp
                      timestamp={event.timestamp}
                      showLocal
                      className="text-xs"
                    />
                  </div>
                  {event.reason && (
                    <AuditReasonBlock reason={event.reason} className="mt-2" />
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </Section>
    </div>
  );
}
