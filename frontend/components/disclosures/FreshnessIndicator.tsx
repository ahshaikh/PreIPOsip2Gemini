/**
 * FreshnessIndicator Component
 *
 * Displays the freshness state of a disclosure artifact.
 *
 * FROZEN VOCABULARY - DO NOT ADD SYNONYMS:
 * - current: Artifact is within expected update window / stable
 * - aging: Artifact is approaching staleness threshold
 * - stale: Artifact has exceeded expected update window
 * - unstable: Artifact has excessive changes in stability window
 *
 * This component renders backend-computed facts.
 * It does NOT infer compliance or readiness.
 */

import { Clock, AlertCircle, AlertTriangle, RefreshCw } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

export type ArtifactFreshnessState = "current" | "aging" | "stale" | "unstable" | null;

interface FreshnessIndicatorProps {
  state: ArtifactFreshnessState;
  signalText?: string;
  variant?: "badge" | "inline" | "chip";
  showIcon?: boolean;
  className?: string;
}

const freshnessConfig: Record<
  NonNullable<ArtifactFreshnessState>,
  {
    label: string;
    icon: typeof Clock;
    badgeVariant: "outline" | "default" | "secondary" | "destructive";
    colors: string;
  }
> = {
  current: {
    label: "Current",
    icon: Clock,
    badgeVariant: "outline",
    colors: "text-green-600 border-green-300 bg-green-50",
  },
  aging: {
    label: "Aging",
    icon: Clock,
    badgeVariant: "outline",
    colors: "text-amber-600 border-amber-300 bg-amber-50",
  },
  stale: {
    label: "Stale",
    icon: AlertCircle,
    badgeVariant: "outline",
    colors: "text-orange-600 border-orange-300 bg-orange-50",
  },
  unstable: {
    label: "Unstable",
    icon: RefreshCw,
    badgeVariant: "outline",
    colors: "text-blue-600 border-blue-300 bg-blue-50",
  },
};

export function FreshnessIndicator({
  state,
  signalText,
  variant = "badge",
  showIcon = true,
  className,
}: FreshnessIndicatorProps) {
  // No indicator for null state or current (current is the happy path, no badge needed)
  if (!state) return null;

  const config = freshnessConfig[state];
  if (!config) return null;

  const Icon = config.icon;

  // Badge variant (default)
  if (variant === "badge") {
    return (
      <Badge variant={config.badgeVariant} className={cn(config.colors, className)}>
        {showIcon && <Icon className="w-3 h-3 mr-1" />}
        {signalText || config.label}
      </Badge>
    );
  }

  // Inline variant (for compact displays)
  if (variant === "inline") {
    return (
      <span className={cn("inline-flex items-center gap-1 text-xs", config.colors, className)}>
        {showIcon && <Icon className="w-3 h-3" />}
        {signalText || config.label}
      </span>
    );
  }

  // Chip variant (for subscriber UI - minimal)
  if (variant === "chip") {
    // Don't show chip for current state
    if (state === "current") return null;

    return (
      <span className={cn("text-xs", config.colors.split(" ")[0], className)}>
        {signalText || config.label}
      </span>
    );
  }

  return null;
}

/**
 * Subscriber-friendly freshness chip
 *
 * Abstracts technical terms into neutral, honest language:
 * - stale -> "Update Pending"
 * - aging -> "Update Expected Soon"
 * - unstable -> "Frequent Changes" (neutral, not falsely positive)
 * - current -> No chip shown
 *
 * GOVERNANCE: unstable means excessive document changes in stability window.
 * "Frequent Changes" is honest without being alarmist. Do NOT use "Recently Updated"
 * which implies a positive signal when it actually indicates governance concern.
 */
interface SubscriberFreshnessChipProps {
  state: ArtifactFreshnessState;
  className?: string;
}

const subscriberLabels: Record<NonNullable<ArtifactFreshnessState>, string | null> = {
  current: null, // No badge for current
  aging: "Update Expected Soon",
  stale: "Update Pending",
  unstable: "Frequent Changes", // Neutral - not falsely positive
};

const subscriberColors: Record<NonNullable<ArtifactFreshnessState>, string> = {
  current: "",
  aging: "text-amber-600",
  stale: "text-orange-600",
  unstable: "text-gray-600", // Gray - neutral, not positive blue
};

export function SubscriberFreshnessChip({ state, className }: SubscriberFreshnessChipProps) {
  if (!state || state === "current") return null;

  const label = subscriberLabels[state];
  if (!label) return null;

  return (
    <span className={cn("text-xs", subscriberColors[state], className)}>
      {label}
    </span>
  );
}
