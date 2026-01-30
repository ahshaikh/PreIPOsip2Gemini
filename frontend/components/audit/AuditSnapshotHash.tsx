/**
 * EPIC 6 - Audit & Regulator Views
 *
 * AuditSnapshotHash - Displays copyable snapshot ID/hash
 *
 * INVARIANTS:
 * - Read-only display (copy is allowed, editing is not)
 * - Hash is rendered verbatim, no truncation of stored value
 * - Visual indicator when copied
 * - Monospace font for exact character representation
 */

"use client";

import { useState, useCallback } from "react";
import { Hash, Copy, Check, ExternalLink } from "lucide-react";

interface AuditSnapshotHashProps {
  hash: string;
  label?: string;
  snapshotId?: string | number;
  linkToSnapshot?: string;
  truncate?: boolean;
  className?: string;
}

export function AuditSnapshotHash({
  hash,
  label = "Snapshot Hash",
  snapshotId,
  linkToSnapshot,
  truncate = true,
  className = "",
}: AuditSnapshotHashProps) {
  const [copied, setCopied] = useState(false);

  const handleCopy = useCallback(async () => {
    try {
      await navigator.clipboard.writeText(hash);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error("Failed to copy hash:", err);
    }
  }, [hash]);

  // Display hash - truncate middle if too long and truncate is enabled
  const displayHash = truncate && hash.length > 20
    ? `${hash.slice(0, 10)}...${hash.slice(-10)}`
    : hash;

  return (
    <div
      className={`
        inline-flex flex-col gap-1
        ${className}
      `}
    >
      {/* Label row */}
      <div className="flex items-center gap-1.5">
        <Hash className="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
        <span className="text-xs font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wide">
          {label}
        </span>
        {snapshotId && (
          <span className="text-xs font-mono text-slate-400 dark:text-slate-500">
            #{snapshotId}
          </span>
        )}
      </div>

      {/* Hash display */}
      <div
        className={`
          flex items-center gap-2 px-3 py-2
          bg-slate-100 dark:bg-slate-800
          border border-slate-200 dark:border-slate-700
          rounded font-mono text-sm
        `}
      >
        <code
          className="flex-1 text-slate-700 dark:text-slate-300 select-all"
          title={hash}
        >
          {displayHash}
        </code>

        {/* Copy button */}
        <button
          onClick={handleCopy}
          className={`
            p-1 rounded transition-colors
            ${copied
              ? "text-emerald-600 dark:text-emerald-400"
              : "text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300"
            }
          `}
          title={copied ? "Copied!" : "Copy hash"}
          aria-label={copied ? "Copied to clipboard" : "Copy hash to clipboard"}
        >
          {copied ? (
            <Check className="w-4 h-4" />
          ) : (
            <Copy className="w-4 h-4" />
          )}
        </button>

        {/* Link to snapshot view if provided */}
        {linkToSnapshot && (
          <a
            href={linkToSnapshot}
            className="p-1 text-slate-400 hover:text-indigo-600 dark:text-slate-500 dark:hover:text-indigo-400 transition-colors"
            title="View full snapshot"
            aria-label="View full snapshot details"
          >
            <ExternalLink className="w-4 h-4" />
          </a>
        )}
      </div>

      {/* Full hash on hover/focus for accessibility */}
      {truncate && hash.length > 20 && (
        <span className="text-xs font-mono text-slate-400 dark:text-slate-500 break-all hidden group-hover:block">
          Full: {hash}
        </span>
      )}
    </div>
  );
}

export default AuditSnapshotHash;
