/**
 * EPIC 5 Story 5.4 - Admin Components
 *
 * Components for admin frontend that make platform authority explicit,
 * intentional, and snapshot-safe.
 */

export {
  AdminPlatformAuthorityBanner,
  getActionConfig,
} from "./AdminPlatformAuthorityBanner";
export type { PlatformActionType } from "./AdminPlatformAuthorityBanner";

export {
  AdminInvestorImpactPanel,
  hasSignificantInvestorImpact,
} from "./AdminInvestorImpactPanel";
export type { InvestorMetrics, ActionImpact } from "./AdminInvestorImpactPanel";

export { AdminSnapshotAwarenessPanel } from "./AdminSnapshotAwarenessPanel";
export type {
  SnapshotSummary,
  SnapshotAwareness,
} from "./AdminSnapshotAwarenessPanel";

export {
  AdminActionSimulationPanel,
  createStateComparison,
} from "./AdminActionSimulationPanel";
export type {
  StateComparison,
  BlockedActions,
  SimulationResult,
} from "./AdminActionSimulationPanel";
