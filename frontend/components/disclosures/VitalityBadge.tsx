/**
 * VitalityBadge Component
 *
 * Displays the vitality state of a disclosure pillar (category).
 *
 * FROZEN VOCABULARY - DO NOT ADD SYNONYMS:
 * - healthy: All artifacts in pillar are current
 * - needs_attention: Any artifact is aging OR exactly 1 is stale/unstable
 * - at_risk: 2+ artifacts are stale OR 2+ are unstable
 *
 * This component renders backend-computed facts.
 * It does NOT infer compliance or readiness.
 */

import { CheckCircle2, AlertCircle, AlertTriangle } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

export type PillarVitalityState = "healthy" | "needs_attention" | "at_risk" | null;

interface VitalityBadgeProps {
  state: PillarVitalityState;
  showLabel?: boolean;
  showIcon?: boolean;
  size?: "sm" | "md" | "lg";
  className?: string;
}

const vitalityConfig: Record<
  NonNullable<PillarVitalityState>,
  {
    label: string;
    icon: typeof CheckCircle2;
    colors: string;
  }
> = {
  healthy: {
    label: "Healthy",
    icon: CheckCircle2,
    colors: "text-green-600 border-green-300 bg-green-50",
  },
  needs_attention: {
    label: "Needs Attention",
    icon: AlertCircle,
    colors: "text-amber-600 border-amber-300 bg-amber-50",
  },
  at_risk: {
    label: "At Risk",
    icon: AlertTriangle,
    colors: "text-red-600 border-red-300 bg-red-50",
  },
};

const sizeClasses = {
  sm: "text-xs px-2 py-0.5",
  md: "text-sm px-2.5 py-1",
  lg: "text-base px-3 py-1.5",
};

const iconSizes = {
  sm: "w-3 h-3",
  md: "w-4 h-4",
  lg: "w-5 h-5",
};

export function VitalityBadge({
  state,
  showLabel = true,
  showIcon = true,
  size = "md",
  className,
}: VitalityBadgeProps) {
  if (!state) return null;

  const config = vitalityConfig[state];
  if (!config) return null;

  const Icon = config.icon;

  return (
    <Badge
      variant="outline"
      className={cn(config.colors, sizeClasses[size], "font-medium", className)}
    >
      {showIcon && <Icon className={cn(iconSizes[size], showLabel ? "mr-1" : "")} />}
      {showLabel && config.label}
    </Badge>
  );
}

/**
 * Pillar Summary Component
 *
 * Shows pillar name with vitality badge and summary text.
 */
interface PillarSummaryProps {
  pillarLabel: string;
  vitalityState: PillarVitalityState;
  signalText?: string;
  className?: string;
}

export function PillarSummary({
  pillarLabel,
  vitalityState,
  signalText,
  className,
}: PillarSummaryProps) {
  return (
    <div className={cn("flex items-center justify-between py-2", className)}>
      <div className="flex items-center gap-2">
        <span className="font-medium">{pillarLabel}</span>
        <VitalityBadge state={vitalityState} size="sm" />
      </div>
      {signalText && (
        <span className="text-sm text-gray-600">{signalText}</span>
      )}
    </div>
  );
}
