/**
 * EPIC 6 - Audit & Regulator Views
 *
 * AuditAuthorityLabel - Displays the authority source for a decision
 *
 * AUTHORITY TYPES:
 * - Issuer: Company-provided information
 * - Platform: Platform governance decision
 * - Investor: Investor action/decision
 * - System: Automated system action
 *
 * INVARIANTS:
 * - Read-only, no interactions
 * - Every decision must render authority
 * - Styling communicates "evidence", not UI
 */

import { Building2, Shield, User, Cpu } from "lucide-react";

export type AuditAuthority = "issuer" | "platform" | "investor" | "system";

interface AuditAuthorityLabelProps {
  authority: AuditAuthority;
  className?: string;
}

const AUTHORITY_CONFIG: Record<AuditAuthority, {
  label: string;
  icon: React.ReactNode;
  bgColor: string;
  textColor: string;
  borderColor: string;
}> = {
  issuer: {
    label: "ISSUER",
    icon: <Building2 className="w-3.5 h-3.5" />,
    bgColor: "bg-slate-100 dark:bg-slate-800",
    textColor: "text-slate-700 dark:text-slate-300",
    borderColor: "border-slate-300 dark:border-slate-600",
  },
  platform: {
    label: "PLATFORM",
    icon: <Shield className="w-3.5 h-3.5" />,
    bgColor: "bg-indigo-50 dark:bg-indigo-950/50",
    textColor: "text-indigo-700 dark:text-indigo-300",
    borderColor: "border-indigo-300 dark:border-indigo-700",
  },
  investor: {
    label: "INVESTOR",
    icon: <User className="w-3.5 h-3.5" />,
    bgColor: "bg-emerald-50 dark:bg-emerald-950/50",
    textColor: "text-emerald-700 dark:text-emerald-300",
    borderColor: "border-emerald-300 dark:border-emerald-700",
  },
  system: {
    label: "SYSTEM",
    icon: <Cpu className="w-3.5 h-3.5" />,
    bgColor: "bg-gray-100 dark:bg-gray-800",
    textColor: "text-gray-700 dark:text-gray-300",
    borderColor: "border-gray-300 dark:border-gray-600",
  },
};

export function AuditAuthorityLabel({
  authority,
  className = "",
}: AuditAuthorityLabelProps) {
  const config = AUTHORITY_CONFIG[authority];

  return (
    <span
      className={`
        inline-flex items-center gap-1.5 px-2.5 py-1
        text-xs font-mono font-semibold uppercase tracking-wider
        border rounded
        ${config.bgColor} ${config.textColor} ${config.borderColor}
        ${className}
      `}
    >
      {config.icon}
      {config.label}
    </span>
  );
}

export default AuditAuthorityLabel;
