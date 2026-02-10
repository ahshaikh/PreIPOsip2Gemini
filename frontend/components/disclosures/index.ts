/**
 * Disclosure Components
 *
 * FROZEN VOCABULARY:
 * - Artifact Freshness: current | aging | stale | unstable
 * - Pillar Vitality: healthy | needs_attention | at_risk
 *
 * NO PROGRESS BARS. NO PERCENTAGES. NO READINESS SCORES.
 */

export {
  FreshnessIndicator,
  SubscriberFreshnessChip,
  type ArtifactFreshnessState,
} from "./FreshnessIndicator";

export {
  VitalityBadge,
  PillarSummary,
  type PillarVitalityState,
} from "./VitalityBadge";

export { CoverageVitalitySummary } from "./CoverageVitalitySummary";
