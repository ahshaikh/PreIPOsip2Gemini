/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Audit Layout - Specialized layout for audit/compliance views
 *
 * CRITICAL CONSTRAINTS:
 * - This is a READ-ONLY zone
 * - No edit, approve, reject, or modify actions
 * - All actions are view-only
 * - Designed for regulatory/compliance review
 *
 * INVARIANTS:
 * - Prominent "READ-ONLY AUDIT MODE" indicator
 * - Navigation to all audit views
 * - No action buttons that modify state
 */

"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Shield,
  FileSearch,
  Users,
  Building2,
  Activity,
  LayoutDashboard,
  AlertTriangle,
} from "lucide-react";

interface AuditNavItem {
  label: string;
  href: string;
  icon: React.ReactNode;
  description: string;
}

const AUDIT_NAV_ITEMS: AuditNavItem[] = [
  {
    label: "Dashboard",
    href: "/admin/audit",
    icon: <LayoutDashboard className="w-4 h-4" />,
    description: "Global audit overview",
  },
  {
    label: "Investments",
    href: "/admin/audit/investments",
    icon: <FileSearch className="w-4 h-4" />,
    description: "Investment reconstructions",
  },
  {
    label: "Investors",
    href: "/admin/audit/investors",
    icon: <Users className="w-4 h-4" />,
    description: "Investor audit timelines",
  },
  {
    label: "Companies",
    href: "/admin/audit/companies",
    icon: <Building2 className="w-4 h-4" />,
    description: "Company governance audits",
  },
  {
    label: "Admin Actions",
    href: "/admin/audit/actions",
    icon: <Activity className="w-4 h-4" />,
    description: "Platform action history",
  },
];

export default function AuditLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();

  return (
    <div className="space-y-6">
      {/* READ-ONLY AUDIT MODE BANNER */}
      <div className="bg-slate-900 dark:bg-slate-950 text-white rounded-lg p-4 border-2 border-slate-700">
        <div className="flex items-start gap-4">
          <div className="flex-shrink-0 p-2 bg-amber-500/20 rounded-lg">
            <Shield className="w-6 h-6 text-amber-400" />
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-1">
              <h2 className="text-lg font-bold font-mono uppercase tracking-wider">
                AUDIT & COMPLIANCE MODE
              </h2>
              <span className="px-2 py-0.5 text-xs font-mono font-bold uppercase bg-emerald-500/20 text-emerald-400 rounded">
                READ-ONLY
              </span>
            </div>
            <p className="text-sm text-slate-300 font-mono">
              This is a regulator-grade audit surface. All data is immutable and
              rendered exactly as captured. No modifications are possible from
              this interface.
            </p>
          </div>
        </div>
      </div>

      {/* AUDIT NAVIGATION */}
      <nav className="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
        <div className="bg-slate-50 dark:bg-slate-800 px-4 py-2 border-b border-slate-200 dark:border-slate-700">
          <span className="text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
            Audit Navigation
          </span>
        </div>
        <div className="flex flex-wrap gap-1 p-2 bg-white dark:bg-slate-900">
          {AUDIT_NAV_ITEMS.map((item) => {
            const isActive =
              pathname === item.href ||
              (item.href !== "/admin/audit" &&
                pathname.startsWith(item.href));

            return (
              <Link
                key={item.href}
                href={item.href}
                className={`
                  flex items-center gap-2 px-4 py-2 rounded-md
                  font-mono text-sm transition-colors
                  ${
                    isActive
                      ? "bg-slate-900 dark:bg-slate-700 text-white"
                      : "text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                  }
                `}
                title={item.description}
              >
                {item.icon}
                <span>{item.label}</span>
              </Link>
            );
          })}
        </div>
      </nav>

      {/* WARNING NOTICE */}
      <div className="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded text-amber-800 dark:text-amber-200">
        <AlertTriangle className="w-4 h-4 flex-shrink-0" />
        <span className="text-xs font-mono">
          All records shown are immutable. Timestamps are displayed in UTC with
          local time in parentheses. Actor identities are traceable.
        </span>
      </div>

      {/* MAIN CONTENT */}
      <div className="min-h-[60vh]">{children}</div>
    </div>
  );
}
