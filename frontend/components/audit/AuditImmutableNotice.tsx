/**
 * EPIC 6 - Audit & Regulator Views
 *
 * AuditImmutableNotice - Displays "This record cannot be altered" notice
 *
 * INVARIANTS:
 * - Read-only, no interactions
 * - Always visible on immutable records
 * - Styling communicates finality and permanence
 * - No dismiss/hide capability
 */

import { Lock, ShieldCheck } from "lucide-react";

type NoticeVariant = "default" | "investment" | "snapshot" | "approval";

interface AuditImmutableNoticeProps {
  variant?: NoticeVariant;
  recordType?: string;
  className?: string;
}

const VARIANT_CONFIG: Record<NoticeVariant, {
  title: string;
  description: string;
  icon: "lock" | "shield";
}> = {
  default: {
    title: "IMMUTABLE RECORD",
    description: "This record cannot be altered. All data is preserved exactly as captured.",
    icon: "lock",
  },
  investment: {
    title: "INVESTMENT SNAPSHOT LOCKED",
    description: "This investment record is permanently bound to the snapshot captured at purchase time. No modifications are possible.",
    icon: "shield",
  },
  snapshot: {
    title: "FROZEN SNAPSHOT",
    description: "This snapshot is cryptographically sealed. The data shown reflects the exact state at capture time.",
    icon: "shield",
  },
  approval: {
    title: "APPROVAL FINALIZED",
    description: "This approval decision is final and permanently recorded. The action and reason cannot be changed.",
    icon: "lock",
  },
};

export function AuditImmutableNotice({
  variant = "default",
  recordType,
  className = "",
}: AuditImmutableNoticeProps) {
  const config = VARIANT_CONFIG[variant];

  const Icon = config.icon === "shield" ? ShieldCheck : Lock;

  return (
    <div
      className={`
        flex items-start gap-3 p-4
        bg-slate-50 dark:bg-slate-900
        border-2 border-slate-300 dark:border-slate-600
        rounded-lg
        ${className}
      `}
    >
      <div className="flex-shrink-0 p-2 bg-slate-200 dark:bg-slate-700 rounded">
        <Icon className="w-5 h-5 text-slate-600 dark:text-slate-300" />
      </div>
      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2 mb-1">
          <span className="text-xs font-mono font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
            {config.title}
          </span>
          {recordType && (
            <span className="text-xs font-mono text-slate-500 dark:text-slate-400">
              ({recordType})
            </span>
          )}
        </div>
        <p className="text-sm font-mono text-slate-600 dark:text-slate-400 leading-relaxed">
          {config.description}
        </p>
      </div>
    </div>
  );
}

export default AuditImmutableNotice;
