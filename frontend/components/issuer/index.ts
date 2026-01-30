/**
 * EPIC 5 Story 5.3 - Issuer Components
 *
 * Components for company/issuer frontend that enforce platform governance
 * and transform the issuer UI from "form editor" to constrained interface.
 */

export { PlatformStatusBanner, hasPlatformRestriction, getRestrictionStatus } from "./PlatformStatusBanner";
export type { PlatformContext, EffectivePermissions } from "./PlatformStatusBanner";

export { IssuerInvestorImpactPanel, hasSignificantImpact, getImpactSeverity } from "./IssuerInvestorImpactPanel";
export type { InvestorSnapshotAwareness, VersionDistribution, ChangeImpact } from "./IssuerInvestorImpactPanel";

export { IssuerDisclosureEditor, isEditable, isLocked, getStatusConfig as getDisclosureStatusConfig } from "./IssuerDisclosureEditor";
export type { DisclosureStatus, DisclosureData, FieldSchema, ModuleSchema } from "./IssuerDisclosureEditor";

export { ClarificationResponsePanel, getStatusConfig as getClarificationStatusConfig, formatDate, getDaysRemaining } from "./ClarificationResponsePanel";
export type { ClarificationStatus, ClarificationData } from "./ClarificationResponsePanel";
