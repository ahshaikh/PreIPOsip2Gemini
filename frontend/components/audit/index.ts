/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Core audit primitives for regulator-grade read-only views.
 *
 * INVARIANTS:
 * - All components are READ-ONLY
 * - No edit, delete, or modify actions
 * - Data is rendered exactly as stored
 * - Designed for regulatory/compliance review
 */

// Authority and Actor identification
export { AuditAuthorityLabel } from "./AuditAuthorityLabel";
export type { AuditAuthority } from "./AuditAuthorityLabel";

export { AuditActorBadge } from "./AuditActorBadge";
export type { ActorType } from "./AuditActorBadge";

// Decision and reason capture
export { AuditReasonBlock } from "./AuditReasonBlock";

// Immutability indicators
export { AuditImmutableNotice } from "./AuditImmutableNotice";

// Snapshot identification
export { AuditSnapshotHash } from "./AuditSnapshotHash";

// Timestamp rendering (UTC + local)
export { AuditTimestamp } from "./AuditTimestamp";
