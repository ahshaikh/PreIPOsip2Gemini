/**
 * CoverageVitalitySummary Component
 *
 * Replaces progress bars with Coverage + Vitality display.
 *
 * NO PROGRESS BARS. NO PERCENTAGES. NO READINESS SCORES.
 *
 * This component displays:
 * - Per-pillar vitality state (healthy|needs_attention|at_risk)
 * - Per-pillar freshness breakdown
 * - Coverage facts (present|draft|partial|missing)
 *
 * All data comes from backend. Frontend renders facts, never infers compliance.
 */

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Shield, TrendingUp, FileText, Building2 } from "lucide-react";
import { VitalityBadge, PillarVitalityState } from "./VitalityBadge";
import { cn } from "@/lib/utils";

// Pillar configuration (matches backend DisclosurePillar enum)
const PILLAR_CONFIG = {
  governance: {
    label: "Governance",
    icon: Shield,
    color: "text-blue-600",
  },
  financial: {
    label: "Financial",
    icon: TrendingUp,
    color: "text-green-600",
  },
  legal: {
    label: "Legal & Risk",
    icon: FileText,
    color: "text-purple-600",
  },
  operational: {
    label: "Operational",
    icon: Building2,
    color: "text-orange-600",
  },
};

interface FreshnessBreakdown {
  current: number;
  aging: number;
  stale: number;
  unstable: number;
}

interface CoverageFacts {
  present: number;
  draft: number;
  partial: number;
  missing: number;
  // NOTE: total_required intentionally excluded - prevents percentage derivation
}

interface PillarData {
  label: string;
  vitality: {
    state: PillarVitalityState;
    total_artifacts: number;
    freshness_breakdown: FreshnessBreakdown;
    drivers: Array<{
      module_code: string;
      module_name: string;
      freshness_state: string;
      signal_text: string;
    }>;
    pillar_signal_text: string;
  };
  coverage: CoverageFacts;
}

interface FreshnessSummary {
  pillars: Record<string, PillarData>;
  overall_vitality: PillarVitalityState;
  coverage_summary: CoverageFacts;
  last_computed: string;
}

interface CoverageVitalitySummaryProps {
  freshnessSummary: FreshnessSummary | null;
  currentTier: number;
  className?: string;
}

export function CoverageVitalitySummary({
  freshnessSummary,
  currentTier,
  className,
}: CoverageVitalitySummaryProps) {
  if (!freshnessSummary) {
    return (
      <Card className={className}>
        <CardHeader>
          <CardTitle>Disclosure Health</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-gray-500">
            Freshness data not available. Disclosures will be evaluated once approved.
          </p>
        </CardContent>
      </Card>
    );
  }

  const { pillars, overall_vitality, coverage_summary } = freshnessSummary;

  return (
    <Card className={className}>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle>Disclosure Health</CardTitle>
            <p className="text-sm text-gray-600 mt-1">
              Coverage and freshness status for Tier {currentTier}
            </p>
          </div>
          <VitalityBadge state={overall_vitality} size="lg" />
        </div>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Pillar Vitality Grid */}
        <div className="space-y-3">
          <h4 className="text-sm font-medium text-gray-700">Pillar Status</h4>
          <div className="grid gap-3">
            {Object.entries(pillars).map(([pillarKey, pillarData]) => {
              const config = PILLAR_CONFIG[pillarKey as keyof typeof PILLAR_CONFIG];
              if (!config) return null;

              const Icon = config.icon;
              const { vitality } = pillarData;

              return (
                <div
                  key={pillarKey}
                  className="flex items-center justify-between p-3 border rounded-lg bg-gray-50/50"
                >
                  <div className="flex items-center gap-3">
                    <Icon className={cn("w-5 h-5", config.color)} />
                    <span className="font-medium">{config.label}</span>
                    <VitalityBadge state={vitality.state} size="sm" />
                  </div>
                  <span className="text-sm text-gray-600">
                    {vitality.pillar_signal_text}
                  </span>
                </div>
              );
            })}
          </div>
        </div>

        {/* Coverage Summary */}
        <div className="space-y-3">
          <h4 className="text-sm font-medium text-gray-700">Coverage (Tier {currentTier})</h4>
          <div className="flex items-center gap-4 text-sm">
            <span className="text-green-600">
              {coverage_summary.present} present
            </span>
            <span className="text-gray-400">·</span>
            <span className="text-amber-600">
              {coverage_summary.draft} draft
            </span>
            {coverage_summary.partial > 0 && (
              <>
                <span className="text-gray-400">·</span>
                <span className="text-orange-600">
                  {coverage_summary.partial} partial
                </span>
              </>
            )}
            {coverage_summary.missing > 0 && (
              <>
                <span className="text-gray-400">·</span>
                <span className="text-red-600">
                  {coverage_summary.missing} missing
                </span>
              </>
            )}
          </div>
        </div>

        {/* Attention Drivers (if any) */}
        {Object.values(pillars).some(p => p.vitality.drivers.length > 0) && (
          <div className="space-y-3">
            <h4 className="text-sm font-medium text-gray-700">Attention Required</h4>
            <div className="space-y-2">
              {Object.entries(pillars).map(([pillarKey, pillarData]) => {
                const { vitality } = pillarData;
                if (vitality.drivers.length === 0) return null;

                return vitality.drivers.map((driver, idx) => (
                  <div
                    key={`${pillarKey}-${idx}`}
                    className={cn(
                      "p-2 rounded text-sm",
                      driver.freshness_state === "stale"
                        ? "bg-orange-50 text-orange-800"
                        : driver.freshness_state === "unstable"
                        ? "bg-blue-50 text-blue-800"
                        : "bg-amber-50 text-amber-800"
                    )}
                  >
                    <span className="font-medium">{driver.module_name}</span>
                    <span className="mx-2">·</span>
                    <span>{driver.signal_text}</span>
                  </div>
                ));
              })}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
