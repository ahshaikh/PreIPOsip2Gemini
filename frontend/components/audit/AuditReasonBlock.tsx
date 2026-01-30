/**
 * EPIC 6 - Audit & Regulator Views
 *
 * AuditReasonBlock - Displays the mandatory reason for an action
 *
 * INVARIANTS:
 * - Read-only, no interactions
 * - Reason is rendered verbatim (no formatting changes)
 * - Every decision must have a reason
 * - Missing reason is explicitly shown as "NO REASON RECORDED"
 */

import { FileText, AlertTriangle } from "lucide-react";

interface AuditReasonBlockProps {
  reason?: string | null;
  recordedAt?: string;
  className?: string;
}

export function AuditReasonBlock({
  reason,
  recordedAt,
  className = "",
}: AuditReasonBlockProps) {
  const hasReason = reason && reason.trim().length > 0;

  return (
    <div
      className={`
        border rounded-lg overflow-hidden
        ${hasReason
          ? "border-slate-200 dark:border-slate-700"
          : "border-amber-300 dark:border-amber-700"
        }
        ${className}
      `}
    >
      {/* Header */}
      <div
        className={`
          flex items-center gap-2 px-4 py-2
          ${hasReason
            ? "bg-slate-50 dark:bg-slate-800"
            : "bg-amber-50 dark:bg-amber-950/50"
          }
          border-b
          ${hasReason
            ? "border-slate-200 dark:border-slate-700"
            : "border-amber-300 dark:border-amber-700"
          }
        `}
      >
        {hasReason ? (
          <FileText className="w-4 h-4 text-slate-500 dark:text-slate-400" />
        ) : (
          <AlertTriangle className="w-4 h-4 text-amber-600 dark:text-amber-400" />
        )}
        <span className="text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
          Recorded Reason
        </span>
        {recordedAt && (
          <span className="ml-auto text-xs font-mono text-slate-400 dark:text-slate-500">
            {recordedAt}
          </span>
        )}
      </div>

      {/* Content */}
      <div className="px-4 py-3 bg-white dark:bg-slate-900">
        {hasReason ? (
          <p className="text-sm font-mono text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
            {reason}
          </p>
        ) : (
          <p className="text-sm font-mono text-amber-700 dark:text-amber-400 italic">
            NO REASON RECORDED
          </p>
        )}
      </div>
    </div>
  );
}

export default AuditReasonBlock;
