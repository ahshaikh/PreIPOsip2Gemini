/**
 * EPIC 6 - Audit & Regulator Views
 *
 * AuditActorBadge - Displays the identity of who performed an action
 *
 * ACTOR TYPES:
 * - Admin: Platform administrator (shows name/ID)
 * - System: Automated process
 * - Investor: Anonymized investor ID
 * - Issuer: Company representative
 *
 * INVARIANTS:
 * - Read-only, no interactions
 * - Actor identity must be traceable
 * - Investor IDs are anonymized for privacy
 */

import { UserCircle, Cpu, Building2, Shield } from "lucide-react";

export type ActorType = "admin" | "system" | "investor" | "issuer";

interface AuditActorBadgeProps {
  actorType: ActorType;
  actorId?: string | number;
  actorName?: string;
  className?: string;
}

export function AuditActorBadge({
  actorType,
  actorId,
  actorName,
  className = "",
}: AuditActorBadgeProps) {
  const getIcon = () => {
    switch (actorType) {
      case "admin":
        return <Shield className="w-4 h-4" />;
      case "system":
        return <Cpu className="w-4 h-4" />;
      case "investor":
        return <UserCircle className="w-4 h-4" />;
      case "issuer":
        return <Building2 className="w-4 h-4" />;
    }
  };

  const getLabel = () => {
    switch (actorType) {
      case "admin":
        return actorName || `Admin #${actorId}`;
      case "system":
        return "Automated System";
      case "investor":
        return `Investor #${actorId}`;
      case "issuer":
        return actorName || `Issuer #${actorId}`;
    }
  };

  const getTypeLabel = () => {
    switch (actorType) {
      case "admin":
        return "Platform Admin";
      case "system":
        return "System Process";
      case "investor":
        return "Investor Action";
      case "issuer":
        return "Issuer Action";
    }
  };

  return (
    <div
      className={`
        inline-flex items-center gap-2 px-3 py-2
        bg-slate-50 dark:bg-slate-900
        border border-slate-200 dark:border-slate-700
        rounded font-mono text-sm
        ${className}
      `}
    >
      <span className="text-slate-500 dark:text-slate-400">
        {getIcon()}
      </span>
      <div className="flex flex-col">
        <span className="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wide">
          {getTypeLabel()}
        </span>
        <span className="text-slate-800 dark:text-slate-200 font-medium">
          {getLabel()}
        </span>
      </div>
    </div>
  );
}

export default AuditActorBadge;
