/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Investor Audit Timeline - Complete investor activity history
 *
 * PURPOSE:
 * - View complete investor activity history
 * - Track investments, wallet transactions, acknowledgements
 * - Anonymized investor ID for privacy
 *
 * INVARIANTS:
 * - READ-ONLY - no modifications possible
 * - Investor ID is anonymized
 * - All timestamps in UTC with local time
 */

"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  Loader2,
  AlertCircle,
  User,
  Wallet,
  FileSearch,
  CheckCircle,
  Clock,
} from "lucide-react";
import {
  fetchInvestorAuditTimeline,
  type InvestorAuditSummary,
} from "@/lib/auditApi";
import {
  AuditImmutableNotice,
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

function StatBox({
  label,
  value,
  subtext,
}: {
  label: string;
  value: string | number;
  subtext?: string;
}) {
  return (
    <div className="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 text-center">
      <span className="block text-2xl font-bold font-mono text-slate-900 dark:text-slate-100">
        {value}
      </span>
      <span className="block text-xs font-mono text-slate-500 uppercase tracking-wide mt-1">
        {label}
      </span>
      {subtext && (
        <span className="block text-xs text-slate-400 mt-0.5">{subtext}</span>
      )}
    </div>
  );
}

export default function InvestorAuditTimelinePage() {
  const params = useParams();
  const investorId = Number(params.id);

  const [data, setData] = useState<InvestorAuditSummary | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function loadData() {
      if (!investorId || isNaN(investorId)) {
        setError("Invalid investor ID");
        setLoading(false);
        return;
      }

      try {
        const result = await fetchInvestorAuditTimeline(investorId);
        setData(result);
      } catch (err) {
        console.error("[Audit] Failed to load investor:", err);
        setError("Failed to load investor audit timeline.");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [investorId]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2">
          <Loader2 className="w-8 h-8 animate-spin text-slate-400" />
          <span className="text-sm font-mono text-slate-500">
            Loading investor audit data...
          </span>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="space-y-4">
        <Link
          href="/admin/audit/investors"
          className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Investors
        </Link>
        <div className="flex items-center justify-center min-h-[30vh]">
          <div className="flex flex-col items-center gap-2 text-center">
            <AlertCircle className="w-8 h-8 text-red-500" />
            <p className="text-sm font-mono text-red-600 dark:text-red-400">
              {error || "Investor not found"}
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
        href="/admin/audit/investors"
        className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Investors
      </Link>

      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">
          Investor Audit Timeline
        </h1>
        <p className="text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
          Anonymized ID: {data.anonymized_id}
        </p>
      </div>

      {/* Immutability Notice */}
      <AuditImmutableNotice variant="default" recordType="Investor Data" />

      {/* Summary Stats */}
      <Section title="Investor Summary" icon={<User className="w-4 h-4" />}>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <StatBox
            label="Total Investments"
            value={data.investments.total_count}
          />
          <StatBox
            label="Total Invested"
            value={`₹${data.investments.total_amount}`}
          />
          <StatBox
            label="Companies"
            value={data.investments.companies_invested}
          />
          <StatBox
            label="KYC Status"
            value={data.kyc_status.is_verified ? "Verified" : "Pending"}
            subtext={
              data.kyc_status.verified_at
                ? `Verified ${new Date(
                    data.kyc_status.verified_at
                  ).toLocaleDateString()}`
                : undefined
            }
          />
        </div>
      </Section>

      {/* Wallet Summary */}
      <Section title="Wallet Summary" icon={<Wallet className="w-4 h-4" />}>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <StatBox
            label="Total Deposited"
            value={`₹${data.wallet.total_deposited}`}
          />
          <StatBox
            label="Total Withdrawn"
            value={`₹${data.wallet.total_withdrawn}`}
          />
          <StatBox
            label="Current Balance"
            value={`₹${data.wallet.current_balance}`}
          />
        </div>
      </Section>

      {/* Activity Timeline */}
      <Section title="Activity Timeline" icon={<Clock className="w-4 h-4" />}>
        {data.timeline.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No activity events recorded.
          </p>
        ) : (
          <div className="space-y-4">
            {data.timeline.map((event, idx) => (
              <div
                key={event.id || idx}
                className="relative pl-6 pb-4 border-l-2 border-slate-200 dark:border-slate-700 last:pb-0"
              >
                <div
                  className={`absolute -left-1.5 top-0 w-3 h-3 rounded-full ${
                    event.event_type.includes("investment")
                      ? "bg-emerald-500"
                      : event.event_type.includes("wallet")
                      ? "bg-indigo-500"
                      : "bg-slate-400"
                  }`}
                />
                <div className="space-y-2">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200">
                      {event.event_type}
                    </span>
                    <AuditAuthorityLabel authority={event.authority} />
                    {event.is_immutable && (
                      <span className="px-1.5 py-0.5 text-xs font-mono bg-slate-100 dark:bg-slate-800 text-slate-500 rounded">
                        IMMUTABLE
                      </span>
                    )}
                  </div>
                  <p className="text-sm text-slate-600 dark:text-slate-400">
                    {event.event_description}
                  </p>
                  <AuditTimestamp
                    timestamp={event.timestamp}
                    showLocal
                    className="text-xs"
                  />
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
