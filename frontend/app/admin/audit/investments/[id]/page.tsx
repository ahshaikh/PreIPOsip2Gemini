/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Investment Reconstruction View - THE ANCHOR VIEW
 *
 * PURPOSE:
 * Reconstruct EXACTLY what was shown to an investor at purchase time.
 * This is the primary audit view for regulatory compliance.
 *
 * WHAT THIS VIEW ANSWERS:
 * - What company information did the investor see?
 * - What disclosures were shown?
 * - What risk flags were visible?
 * - What acknowledgements did they make?
 * - What were the financial terms?
 * - When did this happen?
 * - Is this record immutable?
 *
 * INVARIANTS:
 * - 100% READ-ONLY - no modifications possible
 * - Data rendered verbatim from snapshot
 * - Timestamps in UTC with local time
 * - Snapshot hash visible and copyable
 * - Immutability notice prominently displayed
 */

"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  Loader2,
  AlertCircle,
  CheckCircle,
  XCircle,
  Building2,
  User,
  Wallet,
  FileText,
  AlertTriangle,
  Shield,
  Clock,
  Download,
} from "lucide-react";
import {
  fetchInvestmentReconstruction,
  exportInvestmentReconstruction,
  type InvestmentReconstruction,
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
  mono = true,
}: {
  label: string;
  value: React.ReactNode;
  mono?: boolean;
}) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-4 py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
      <span className="text-xs font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wide sm:w-40 flex-shrink-0">
        {label}
      </span>
      <span
        className={`text-sm text-slate-800 dark:text-slate-200 ${
          mono ? "font-mono" : ""
        }`}
      >
        {value}
      </span>
    </div>
  );
}

export default function InvestmentReconstructionPage() {
  const params = useParams();
  const investmentId = Number(params.id);

  const [data, setData] = useState<InvestmentReconstruction | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    async function loadData() {
      if (!investmentId || isNaN(investmentId)) {
        setError("Invalid investment ID");
        setLoading(false);
        return;
      }

      try {
        const result = await fetchInvestmentReconstruction(investmentId);
        setData(result);
      } catch (err) {
        console.error("[Audit] Failed to load investment:", err);
        setError("Failed to load investment reconstruction. Please try again.");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [investmentId]);

  const handleExport = async () => {
    if (!investmentId) return;

    setExporting(true);
    try {
      const blob = await exportInvestmentReconstruction(investmentId);
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `investment-${investmentId}-reconstruction.json`;
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
            Reconstructing investment record...
          </span>
        </div>
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="space-y-4">
        <Link
          href="/admin/audit/investments"
          className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Investments
        </Link>
        <div className="flex items-center justify-center min-h-[30vh]">
          <div className="flex flex-col items-center gap-2 text-center">
            <AlertCircle className="w-8 h-8 text-red-500" />
            <p className="text-sm font-mono text-red-600 dark:text-red-400">
              {error || "Investment not found"}
            </p>
          </div>
        </div>
      </div>
    );
  }

  const { investment, snapshot, journey_validation, wallet_transaction, timeline } = data;

  return (
    <div className="space-y-6">
      {/* Back link */}
      <Link
        href="/admin/audit/investments"
        className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
      >
        <ArrowLeft className="w-4 h-4" />
        Back to Investments
      </Link>

      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">
            Investment #{investment.id}
          </h1>
          <p className="text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
            Complete reconstruction of investor purchase experience
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
      <AuditImmutableNotice variant="investment" />

      {/* Snapshot Hash */}
      <div className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
        <AuditSnapshotHash
          hash={snapshot.snapshot_hash}
          snapshotId={snapshot.id}
          label="Investment Snapshot Hash"
        />
        <div className="mt-3">
          <AuditTimestamp
            timestamp={snapshot.captured_at}
            label="Captured At"
            variant="block"
          />
        </div>
      </div>

      {/* Investment Summary */}
      <Section title="Investment Details" icon={<FileText className="w-4 h-4" />}>
        <div className="space-y-0">
          <DataRow label="Investment ID" value={investment.id} />
          <DataRow label="Investor ID" value={`#${investment.investor_id}`} />
          <DataRow label="Company ID" value={`#${investment.company_id}`} />
          <DataRow
            label="Amount"
            value={`₹${investment.amount} (${investment.quantity} units)`}
          />
          <DataRow
            label="Status"
            value={
              <span
                className={`inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-mono uppercase ${
                  investment.status === "completed"
                    ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                    : "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                }`}
              >
                {investment.status}
              </span>
            }
          />
          <DataRow
            label="Created"
            value={
              <AuditTimestamp timestamp={investment.created_at} showLocal />
            }
          />
          {investment.completed_at && (
            <DataRow
              label="Completed"
              value={
                <AuditTimestamp timestamp={investment.completed_at} showLocal />
              }
            />
          )}
        </div>
      </Section>

      {/* Company State at Purchase */}
      <Section
        title="Company State at Purchase"
        icon={<Building2 className="w-4 h-4" />}
      >
        <div className="space-y-4">
          <div className="space-y-0">
            <DataRow
              label="Company Name"
              value={snapshot.company_state.name}
              mono={false}
            />
            <DataRow label="Company ID" value={snapshot.company_state.id} />
            <DataRow
              label="Lifecycle State"
              value={snapshot.company_state.lifecycle_state}
            />
            <DataRow
              label="Disclosure Tier"
              value={`Tier ${snapshot.company_state.disclosure_tier}`}
            />
            <DataRow
              label="Buying Enabled"
              value={
                snapshot.company_state.platform_context.buying_enabled ? (
                  <span className="inline-flex items-center gap-1 text-emerald-600">
                    <CheckCircle className="w-4 h-4" /> Yes
                  </span>
                ) : (
                  <span className="inline-flex items-center gap-1 text-red-600">
                    <XCircle className="w-4 h-4" /> No
                  </span>
                )
              }
            />
            <DataRow
              label="Suspended"
              value={
                snapshot.company_state.platform_context.is_suspended
                  ? "Yes"
                  : "No"
              }
            />
            <DataRow
              label="Frozen"
              value={
                snapshot.company_state.platform_context.is_frozen ? "Yes" : "No"
              }
            />
          </div>

          {/* Risk Flags at Purchase */}
          {snapshot.company_state.risk_flags.length > 0 && (
            <div className="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
              <h3 className="text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-3">
                Risk Flags Shown to Investor
              </h3>
              <div className="space-y-2">
                {snapshot.company_state.risk_flags.map((flag, idx) => (
                  <div
                    key={idx}
                    className="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded"
                  >
                    <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div>
                      <span className="text-sm font-mono font-semibold text-amber-800 dark:text-amber-200">
                        {flag.flag_type}
                      </span>
                      <span
                        className={`ml-2 text-xs font-mono px-1.5 py-0.5 rounded ${
                          flag.severity === "high"
                            ? "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                            : flag.severity === "medium"
                            ? "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                            : "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400"
                        }`}
                      >
                        {flag.severity}
                      </span>
                      <p className="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        {flag.description}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </Section>

      {/* Disclosures Shown */}
      <Section
        title="Disclosures Shown to Investor"
        icon={<FileText className="w-4 h-4" />}
      >
        {snapshot.disclosures_shown.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No disclosures were shown at purchase time.
          </p>
        ) : (
          <div className="space-y-3">
            {snapshot.disclosures_shown.map((disclosure, idx) => (
              <div
                key={idx}
                className="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700"
              >
                <div className="flex items-center gap-3">
                  <FileText className="w-4 h-4 text-slate-400" />
                  <div>
                    <span className="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200">
                      {disclosure.module_name}
                    </span>
                    <span className="ml-2 text-xs font-mono text-slate-500">
                      v{disclosure.version_number}
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <span className="px-2 py-0.5 text-xs font-mono bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded uppercase">
                    {disclosure.status}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </Section>

      {/* Acknowledgements */}
      <Section
        title="Investor Acknowledgements"
        icon={<Shield className="w-4 h-4" />}
      >
        {snapshot.acknowledgements.length === 0 ? (
          <p className="text-sm font-mono text-amber-600 italic">
            NO ACKNOWLEDGEMENTS RECORDED
          </p>
        ) : (
          <div className="space-y-3">
            {snapshot.acknowledgements.map((ack, idx) => (
              <div
                key={idx}
                className="flex items-start gap-3 p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded"
              >
                <CheckCircle className="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" />
                <div>
                  <span className="text-sm font-mono font-semibold text-emerald-800 dark:text-emerald-200">
                    {ack.acknowledgement_type}
                  </span>
                  <div className="mt-1">
                    <AuditTimestamp
                      timestamp={ack.acknowledged_at}
                      showLocal
                      className="text-xs text-emerald-700 dark:text-emerald-400"
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </Section>

      {/* Financial Terms */}
      <Section
        title="Financial Terms at Purchase"
        icon={<Wallet className="w-4 h-4" />}
      >
        <div className="space-y-0">
          <DataRow
            label="Price per Unit"
            value={`${snapshot.financial_terms.currency} ${snapshot.financial_terms.price_per_unit}`}
          />
          <DataRow label="Quantity" value={snapshot.financial_terms.quantity} />
          <DataRow
            label="Total Amount"
            value={`${snapshot.financial_terms.currency} ${snapshot.financial_terms.total_amount}`}
          />
        </div>

        {wallet_transaction && (
          <div className="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <h3 className="text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-3">
              Wallet Transaction
            </h3>
            <div className="space-y-0">
              <DataRow label="Transaction ID" value={wallet_transaction.id} />
              <DataRow label="Type" value={wallet_transaction.type} />
              <DataRow
                label="Amount"
                value={`₹${wallet_transaction.amount}`}
              />
              <DataRow
                label="Balance Before"
                value={`₹${wallet_transaction.balance_before}`}
              />
              <DataRow
                label="Balance After"
                value={`₹${wallet_transaction.balance_after}`}
              />
              <DataRow
                label="Created"
                value={
                  <AuditTimestamp
                    timestamp={wallet_transaction.created_at}
                    showLocal
                  />
                }
              />
            </div>
          </div>
        )}
      </Section>

      {/* Journey Validation */}
      <Section
        title="Journey Validation (at Purchase)"
        icon={<Shield className="w-4 h-4" />}
      >
        <div className="space-y-4">
          <div className="flex items-center gap-2">
            {journey_validation.passed ? (
              <>
                <CheckCircle className="w-5 h-5 text-emerald-600" />
                <span className="text-sm font-mono font-semibold text-emerald-700 dark:text-emerald-400">
                  ALL CHECKS PASSED
                </span>
              </>
            ) : (
              <>
                <XCircle className="w-5 h-5 text-red-600" />
                <span className="text-sm font-mono font-semibold text-red-700 dark:text-red-400">
                  VALIDATION FAILED
                </span>
              </>
            )}
          </div>

          <div className="space-y-2">
            {journey_validation.checks_performed.map((check, idx) => (
              <div
                key={idx}
                className={`flex items-center gap-2 p-2 rounded ${
                  check.result === "passed"
                    ? "bg-emerald-50 dark:bg-emerald-950/30"
                    : "bg-red-50 dark:bg-red-950/30"
                }`}
              >
                {check.result === "passed" ? (
                  <CheckCircle className="w-4 h-4 text-emerald-600" />
                ) : (
                  <XCircle className="w-4 h-4 text-red-600" />
                )}
                <span className="text-sm font-mono text-slate-700 dark:text-slate-300">
                  {check.check_name}
                </span>
                {check.reason && (
                  <span className="text-xs font-mono text-slate-500 ml-auto">
                    {check.reason}
                  </span>
                )}
              </div>
            ))}
          </div>

          <div className="pt-2">
            <AuditTimestamp
              timestamp={journey_validation.validated_at}
              label="Validated At"
              variant="block"
            />
          </div>
        </div>
      </Section>

      {/* Timeline */}
      <Section title="Event Timeline" icon={<Clock className="w-4 h-4" />}>
        {timeline.length === 0 ? (
          <p className="text-sm font-mono text-slate-500 italic">
            No timeline events recorded.
          </p>
        ) : (
          <div className="space-y-4">
            {timeline.map((event, idx) => (
              <div
                key={event.id || idx}
                className="relative pl-6 pb-4 border-l-2 border-slate-200 dark:border-slate-700 last:pb-0"
              >
                <div className="absolute -left-1.5 top-0 w-3 h-3 rounded-full bg-slate-300 dark:bg-slate-600" />
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
