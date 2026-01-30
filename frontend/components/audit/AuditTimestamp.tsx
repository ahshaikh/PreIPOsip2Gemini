/**
 * EPIC 6 - Audit & Regulator Views
 *
 * AuditTimestamp - Displays timestamp with UTC + local rendering
 *
 * INVARIANTS:
 * - Read-only, no interactions
 * - UTC is ALWAYS shown (primary authority)
 * - Local time is supplementary (shown in parentheses or tooltip)
 * - ISO 8601 format for unambiguous representation
 * - No relative times ("2 hours ago") - only absolute
 */

"use client";

import { Clock } from "lucide-react";

interface AuditTimestampProps {
  timestamp: string; // ISO 8601 format expected
  label?: string;
  showLocal?: boolean;
  showDate?: boolean;
  showTime?: boolean;
  variant?: "inline" | "block";
  className?: string;
}

export function AuditTimestamp({
  timestamp,
  label,
  showLocal = true,
  showDate = true,
  showTime = true,
  variant = "inline",
  className = "",
}: AuditTimestampProps) {
  // Parse the timestamp
  const date = new Date(timestamp);

  // Check for invalid date
  if (isNaN(date.getTime())) {
    return (
      <span className={`font-mono text-sm text-red-600 dark:text-red-400 ${className}`}>
        INVALID TIMESTAMP
      </span>
    );
  }

  // Format UTC components
  const utcDate = date.toISOString().split("T")[0]; // YYYY-MM-DD
  const utcTime = date.toISOString().split("T")[1].replace("Z", ""); // HH:MM:SS.sss
  const utcTimeShort = utcTime.split(".")[0]; // HH:MM:SS

  // Format local components
  const localDate = date.toLocaleDateString("en-CA"); // YYYY-MM-DD format
  const localTime = date.toLocaleTimeString("en-GB", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false
  });

  // Get timezone abbreviation
  const tzAbbr = Intl.DateTimeFormat("en", { timeZoneName: "short" })
    .formatToParts(date)
    .find(part => part.type === "timeZoneName")?.value || "Local";

  // Build UTC display string
  let utcDisplay = "";
  if (showDate && showTime) {
    utcDisplay = `${utcDate} ${utcTimeShort} UTC`;
  } else if (showDate) {
    utcDisplay = `${utcDate} UTC`;
  } else if (showTime) {
    utcDisplay = `${utcTimeShort} UTC`;
  }

  // Build local display string
  let localDisplay = "";
  if (showDate && showTime) {
    localDisplay = `${localDate} ${localTime} ${tzAbbr}`;
  } else if (showDate) {
    localDisplay = `${localDate} ${tzAbbr}`;
  } else if (showTime) {
    localDisplay = `${localTime} ${tzAbbr}`;
  }

  if (variant === "block") {
    return (
      <div className={`flex flex-col gap-1 ${className}`}>
        {label && (
          <div className="flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
            <span className="text-xs font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wide">
              {label}
            </span>
          </div>
        )}
        <div className="flex flex-col gap-0.5 pl-5">
          <span className="font-mono text-sm text-slate-800 dark:text-slate-200">
            {utcDisplay}
          </span>
          {showLocal && (
            <span className="font-mono text-xs text-slate-500 dark:text-slate-400">
              ({localDisplay})
            </span>
          )}
        </div>
      </div>
    );
  }

  // Inline variant
  return (
    <span
      className={`
        inline-flex items-center gap-1.5
        font-mono text-sm text-slate-700 dark:text-slate-300
        ${className}
      `}
      title={`UTC: ${utcDisplay}\nLocal: ${localDisplay}`}
    >
      {label && (
        <>
          <Clock className="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
          <span className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide mr-1">
            {label}:
          </span>
        </>
      )}
      <span>{utcDisplay}</span>
      {showLocal && (
        <span className="text-slate-500 dark:text-slate-400">
          ({localDisplay})
        </span>
      )}
    </span>
  );
}

export default AuditTimestamp;
