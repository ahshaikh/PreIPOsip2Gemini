/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Global Audit Dashboard - Entry point for audit/compliance review
 *
 * PURPOSE:
 * - High-level overview of platform audit data
 * - Quick access to key audit views
 * - Summary statistics for regulatory reporting
 *
 * INVARIANTS:
 * - READ-ONLY - no actions modify state
 * - All data fetched from backend
 * - Statistics are real-time (not cached)
 */

"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import {
  FileSearch,
  Users,
  Building2,
  Activity,
  TrendingUp,
  Clock,
  Database,
  AlertCircle,
  ExternalLink,
  Loader2,
} from "lucide-react";
import {
  fetchAuditDashboardStats,
  type AuditDashboardStats,
} from "@/lib/auditApi";
import { AuditTimestamp, AuditImmutableNotice } from "@/components/audit";

interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon: React.ReactNode;
  trend?: "up" | "down" | "neutral";
}

function StatCard({ title, value, subtitle, icon, trend }: StatCardProps) {
  return (
    <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
      <div className="flex items-start justify-between mb-2">
        <span className="text-xs font-mono font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {title}
        </span>
        <span className="text-slate-400 dark:text-slate-500">{icon}</span>
      </div>
      <div className="flex items-baseline gap-2">
        <span className="text-2xl font-bold font-mono text-slate-900 dark:text-slate-100">
          {value}
        </span>
        {trend && (
          <TrendingUp
            className={`w-4 h-4 ${
              trend === "up"
                ? "text-emerald-500"
                : trend === "down"
                ? "text-red-500 rotate-180"
                : "text-slate-400"
            }`}
          />
        )}
      </div>
      {subtitle && (
        <p className="text-xs font-mono text-slate-500 dark:text-slate-400 mt-1">
          {subtitle}
        </p>
      )}
    </div>
  );
}

interface QuickAccessCardProps {
  title: string;
  description: string;
  href: string;
  icon: React.ReactNode;
  count?: number;
}

function QuickAccessCard({
  title,
  description,
  href,
  icon,
  count,
}: QuickAccessCardProps) {
  return (
    <Link
      href={href}
      className="block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-4 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors group"
    >
      <div className="flex items-start gap-3">
        <div className="flex-shrink-0 p-2 bg-slate-100 dark:bg-slate-800 rounded-lg group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/30 transition-colors">
          {icon}
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center justify-between">
            <h3 className="font-semibold text-slate-900 dark:text-slate-100">
              {title}
            </h3>
            <ExternalLink className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" />
          </div>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {description}
          </p>
          {count !== undefined && (
            <span className="inline-block mt-2 px-2 py-0.5 text-xs font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded">
              {count.toLocaleString()} records
            </span>
          )}
        </div>
      </div>
    </Link>
  );
}

export default function AuditDashboardPage() {
  const [stats, setStats] = useState<AuditDashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function loadStats() {
      try {
        const data = await fetchAuditDashboardStats();
        setStats(data);
      } catch (err) {
        console.error("[Audit Dashboard] Failed to load stats:", err);
        setError("Failed to load audit statistics. Please try again.");
      } finally {
        setLoading(false);
      }
    }

    loadStats();
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2">
          <Loader2 className="w-8 h-8 animate-spin text-slate-400" />
          <span className="text-sm font-mono text-slate-500">
            Loading audit data...
          </span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2 text-center">
          <AlertCircle className="w-8 h-8 text-red-500" />
          <p className="text-sm font-mono text-red-600 dark:text-red-400">
            {error}
          </p>
        </div>
      </div>
    );
  }

  // Use mock data if API returns null (for development)
  const displayStats: AuditDashboardStats = stats || {
    investments: {
      total_count: 0,
      total_amount: "0.00",
      today_count: 0,
      today_amount: "0.00",
    },
    governance: {
      companies_active: 0,
      companies_suspended: 0,
      companies_frozen: 0,
      pending_disclosures: 0,
    },
    admin_actions: {
      today_count: 0,
      week_count: 0,
      month_count: 0,
    },
    snapshots: {
      total_count: 0,
      oldest_snapshot_date: new Date().toISOString(),
      newest_snapshot_date: new Date().toISOString(),
    },
  };

  return (
    <div className="space-y-8">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">
          Audit Dashboard
        </h1>
        <p className="text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
          Regulator-grade overview of platform activity and governance
        </p>
      </div>

      {/* Immutable Notice */}
      <AuditImmutableNotice
        variant="default"
        recordType="Platform Audit Data"
      />

      {/* Statistics Grid */}
      <section>
        <h2 className="text-sm font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-4">
          Platform Statistics
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            title="Total Investments"
            value={displayStats.investments.total_count.toLocaleString()}
            subtitle={`₹${displayStats.investments.total_amount} total value`}
            icon={<FileSearch className="w-5 h-5" />}
          />
          <StatCard
            title="Active Companies"
            value={displayStats.governance.companies_active}
            subtitle={`${displayStats.governance.companies_suspended} suspended, ${displayStats.governance.companies_frozen} frozen`}
            icon={<Building2 className="w-5 h-5" />}
          />
          <StatCard
            title="Admin Actions (Month)"
            value={displayStats.admin_actions.month_count}
            subtitle={`${displayStats.admin_actions.today_count} today, ${displayStats.admin_actions.week_count} this week`}
            icon={<Activity className="w-5 h-5" />}
          />
          <StatCard
            title="Total Snapshots"
            value={displayStats.snapshots.total_count.toLocaleString()}
            subtitle="Immutable investment records"
            icon={<Database className="w-5 h-5" />}
          />
        </div>
      </section>

      {/* Snapshot Timeline */}
      {displayStats.snapshots.total_count > 0 && (
        <section className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
          <h2 className="text-sm font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-3">
            Snapshot Timeline
          </h2>
          <div className="flex flex-col md:flex-row md:items-center gap-4 md:gap-8">
            <div className="flex items-center gap-2">
              <Clock className="w-4 h-4 text-slate-400" />
              <span className="text-xs font-mono text-slate-500 uppercase">
                Oldest:
              </span>
              <AuditTimestamp
                timestamp={displayStats.snapshots.oldest_snapshot_date}
                showTime={false}
                className="text-slate-700 dark:text-slate-300"
              />
            </div>
            <div className="hidden md:block h-px flex-1 bg-gradient-to-r from-slate-300 via-slate-400 to-slate-300 dark:from-slate-600 dark:via-slate-500 dark:to-slate-600" />
            <div className="flex items-center gap-2">
              <Clock className="w-4 h-4 text-slate-400" />
              <span className="text-xs font-mono text-slate-500 uppercase">
                Newest:
              </span>
              <AuditTimestamp
                timestamp={displayStats.snapshots.newest_snapshot_date}
                showTime={false}
                className="text-slate-700 dark:text-slate-300"
              />
            </div>
          </div>
        </section>
      )}

      {/* Quick Access */}
      <section>
        <h2 className="text-sm font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-4">
          Audit Views
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <QuickAccessCard
            title="Investment Reconstructions"
            description="Reconstruct exactly what was shown to investors at purchase time. The anchor view for regulatory compliance."
            href="/admin/audit/investments"
            icon={<FileSearch className="w-5 h-5 text-indigo-600" />}
            count={displayStats.investments.total_count}
          />
          <QuickAccessCard
            title="Investor Audit Timelines"
            description="View complete investor activity history including investments, wallet transactions, and acknowledgements."
            href="/admin/audit/investors"
            icon={<Users className="w-5 h-5 text-emerald-600" />}
          />
          <QuickAccessCard
            title="Company Governance Audits"
            description="Track company lifecycle, disclosure history, tier transitions, and platform governance actions."
            href="/admin/audit/companies"
            icon={<Building2 className="w-5 h-5 text-amber-600" />}
            count={displayStats.governance.companies_active}
          />
          <QuickAccessCard
            title="Admin Action History"
            description="Complete log of all platform admin actions with actor identity, reason, and state changes."
            href="/admin/audit/actions"
            icon={<Activity className="w-5 h-5 text-purple-600" />}
            count={displayStats.admin_actions.month_count}
          />
        </div>
      </section>

      {/* Governance Alerts */}
      {(displayStats.governance.companies_suspended > 0 ||
        displayStats.governance.companies_frozen > 0 ||
        displayStats.governance.pending_disclosures > 0) && (
        <section className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
          <h2 className="text-sm font-mono font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-200 mb-3">
            Governance Alerts
          </h2>
          <div className="space-y-2">
            {displayStats.governance.companies_suspended > 0 && (
              <div className="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                <AlertCircle className="w-4 h-4" />
                <span className="text-sm font-mono">
                  {displayStats.governance.companies_suspended} companies
                  currently suspended
                </span>
              </div>
            )}
            {displayStats.governance.companies_frozen > 0 && (
              <div className="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                <AlertCircle className="w-4 h-4" />
                <span className="text-sm font-mono">
                  {displayStats.governance.companies_frozen} companies
                  currently frozen
                </span>
              </div>
            )}
            {displayStats.governance.pending_disclosures > 0 && (
              <div className="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                <AlertCircle className="w-4 h-4" />
                <span className="text-sm font-mono">
                  {displayStats.governance.pending_disclosures} disclosures
                  pending review
                </span>
              </div>
            )}
          </div>
        </section>
      )}
    </div>
  );
}
