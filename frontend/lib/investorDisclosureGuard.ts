/**
 * EPIC 5 Story 5.2 - Investor Disclosure Guard
 *
 * PURPOSE:
 * Frontend projection layer for investor-visible disclosures.
 * Ensures only approved disclosures are rendered to investors.
 *
 * DEFENSIVE PRINCIPLES:
 * - Show ONLY status: 'approved' disclosures
 * - Non-approved disclosures get placeholder text
 * - Backend is source of truth, this is defense-in-depth
 *
 * DISPLAY LAYERS (Story 5.2 Separation):
 * - Disclosures: Issuer-provided, approved content
 * - Risk Indicators: Platform-generated, non-editable
 * - Material Change Notices: Time-bound alerts with timestamps
 *
 * INVARIANTS:
 * - Frontend NEVER infers visibility
 * - Frontend NEVER computes tier gates
 * - All state comes from backend API response
 */

// ============================================================================
// TYPES
// ============================================================================

/**
 * Disclosure status values from backend
 */
export type DisclosureStatus =
  | 'draft'
  | 'submitted'
  | 'under_review'
  | 'clarification_required'
  | 'resubmitted'
  | 'approved'
  | 'rejected';

/**
 * Disclosure object from backend API
 */
export interface Disclosure {
  module_name: string;
  module_code?: string;
  status: DisclosureStatus;
  data?: any;
  version?: number;
  approved_at?: string;
  message?: string;
}

/**
 * Risk indicator from platform (not issuer)
 */
export interface RiskIndicator {
  flag_type: string;
  severity: 'critical' | 'high' | 'medium' | 'low';
  category?: string;
  title?: string;
  description: string;
  detected_at?: string;
  is_material?: boolean;
}

/**
 * Material change notice (time-bound alert)
 */
export interface MaterialChangeNotice {
  type: string;
  severity: string;
  description: string;
  detected_at: string;
  requires_acknowledgement: boolean;
}

// ============================================================================
// DISCLOSURE VISIBILITY CONSTANTS
// ============================================================================

/**
 * Statuses that are visible to investors
 * Only 'approved' disclosures should show their content
 */
const INVESTOR_VISIBLE_STATUSES: ReadonlySet<DisclosureStatus> = new Set([
  'approved',
]);

/**
 * Statuses that show placeholder (in progress)
 */
const IN_PROGRESS_STATUSES: ReadonlySet<DisclosureStatus> = new Set([
  'submitted',
  'under_review',
  'clarification_required',
  'resubmitted',
]);

/**
 * Statuses that are completely hidden
 */
const HIDDEN_STATUSES: ReadonlySet<DisclosureStatus> = new Set([
  'draft',
  'rejected',
]);

// ============================================================================
// DISCLOSURE GUARD FUNCTIONS
// ============================================================================

/**
 * Check if a disclosure is visible to investors
 *
 * @param disclosure - Disclosure object from API
 * @returns true if disclosure content should be shown
 */
export function isDisclosureVisible(disclosure: Disclosure): boolean {
  return INVESTOR_VISIBLE_STATUSES.has(disclosure.status);
}

/**
 * Check if a disclosure is in progress (show placeholder)
 *
 * @param disclosure - Disclosure object from API
 * @returns true if disclosure is being reviewed
 */
export function isDisclosureInProgress(disclosure: Disclosure): boolean {
  return IN_PROGRESS_STATUSES.has(disclosure.status);
}

/**
 * Check if a disclosure should be hidden entirely
 *
 * @param disclosure - Disclosure object from API
 * @returns true if disclosure should not appear at all
 */
export function isDisclosureHidden(disclosure: Disclosure): boolean {
  return HIDDEN_STATUSES.has(disclosure.status);
}

/**
 * Filter disclosures to only investor-visible ones
 *
 * @param disclosures - Array of disclosures from API
 * @returns Only approved disclosures
 */
export function filterApprovedDisclosures(disclosures: Disclosure[]): Disclosure[] {
  if (!Array.isArray(disclosures)) return [];
  return disclosures.filter(d => isDisclosureVisible(d));
}

/**
 * Filter disclosures for display (approved + in-progress with placeholder)
 *
 * @param disclosures - Array of disclosures from API
 * @returns Disclosures that should appear in UI (approved or in-progress)
 */
export function filterDisplayableDisclosures(disclosures: Disclosure[]): Disclosure[] {
  if (!Array.isArray(disclosures)) return [];
  return disclosures.filter(d => !isDisclosureHidden(d));
}

/**
 * Get placeholder text for non-visible disclosures
 *
 * @param status - Disclosure status
 * @returns User-friendly placeholder message
 */
export function getDisclosurePlaceholder(status: DisclosureStatus): string {
  switch (status) {
    case 'submitted':
      return 'This disclosure has been submitted and is awaiting platform review.';
    case 'under_review':
      return 'This disclosure is currently under platform review.';
    case 'clarification_required':
      return 'This disclosure requires clarification from the company.';
    case 'resubmitted':
      return 'This disclosure has been resubmitted and is awaiting review.';
    case 'draft':
      return 'This disclosure has not been submitted yet.';
    case 'rejected':
      return 'This disclosure did not meet platform requirements.';
    default:
      return 'This disclosure is not yet available.';
  }
}

/**
 * Get status badge configuration for display
 *
 * @param status - Disclosure status
 * @returns Badge configuration with label and color
 */
export function getDisclosureStatusBadge(status: DisclosureStatus): {
  label: string;
  variant: 'default' | 'secondary' | 'outline' | 'destructive';
  colorClass: string;
} {
  switch (status) {
    case 'approved':
      return {
        label: 'Approved',
        variant: 'default',
        colorClass: 'text-green-600 border-green-600 bg-green-50',
      };
    case 'under_review':
    case 'submitted':
    case 'resubmitted':
      return {
        label: 'Under Review',
        variant: 'outline',
        colorClass: 'text-amber-600 border-amber-600 bg-amber-50',
      };
    case 'clarification_required':
      return {
        label: 'Pending Clarification',
        variant: 'outline',
        colorClass: 'text-orange-600 border-orange-600 bg-orange-50',
      };
    case 'rejected':
      return {
        label: 'Not Available',
        variant: 'outline',
        colorClass: 'text-gray-500 border-gray-500 bg-gray-50',
      };
    default:
      return {
        label: 'Pending',
        variant: 'outline',
        colorClass: 'text-gray-500 border-gray-500 bg-gray-50',
      };
  }
}

// ============================================================================
// DISPLAY LAYER HELPERS
// ============================================================================

/**
 * Categorize items into display layers per Story 5.2
 *
 * DISPLAY LAYERS:
 * 1. Disclosures - Issuer-provided, approved content
 * 2. Risk Indicators - Platform-generated, non-editable
 * 3. Material Changes - Time-bound alerts with timestamps
 */
export interface DisplayLayers {
  disclosures: Disclosure[];
  riskIndicators: RiskIndicator[];
  materialChanges: MaterialChangeNotice[];
}

/**
 * Separate company data into distinct display layers
 *
 * @param company - Company data from API
 * @returns Separated display layers
 */
export function separateDisplayLayers(company: any): DisplayLayers {
  return {
    // Layer 1: Issuer-provided approved disclosures only
    disclosures: filterApprovedDisclosures(company.disclosures || []),

    // Layer 2: Platform-generated risk indicators
    riskIndicators: (company.risk_flags || company.platform_risk_flags || []) as RiskIndicator[],

    // Layer 3: Time-bound material change alerts
    materialChanges: (company.material_change_warnings || company.material_changes || []) as MaterialChangeNotice[],
  };
}

/**
 * Check if a company has any material changes requiring acknowledgement
 *
 * @param materialChanges - Array of material change notices
 * @returns true if any change requires acknowledgement
 */
export function hasPendingMaterialAcknowledgement(
  materialChanges: MaterialChangeNotice[]
): boolean {
  if (!Array.isArray(materialChanges)) return false;
  return materialChanges.some(change => change.requires_acknowledgement);
}

/**
 * Format risk severity for display
 *
 * @param severity - Risk severity level
 * @returns Display configuration
 */
export function formatRiskSeverity(severity: RiskIndicator['severity']): {
  label: string;
  colorClass: string;
  icon: 'critical' | 'warning' | 'info';
} {
  switch (severity) {
    case 'critical':
      return {
        label: 'Critical',
        colorClass: 'text-red-600 bg-red-50 border-red-200',
        icon: 'critical',
      };
    case 'high':
      return {
        label: 'High',
        colorClass: 'text-orange-600 bg-orange-50 border-orange-200',
        icon: 'warning',
      };
    case 'medium':
      return {
        label: 'Medium',
        colorClass: 'text-amber-600 bg-amber-50 border-amber-200',
        icon: 'warning',
      };
    case 'low':
      return {
        label: 'Low',
        colorClass: 'text-blue-600 bg-blue-50 border-blue-200',
        icon: 'info',
      };
    default:
      return {
        label: 'Unknown',
        colorClass: 'text-gray-600 bg-gray-50 border-gray-200',
        icon: 'info',
      };
  }
}

// ============================================================================
// SNAPSHOT MESSAGING
// ============================================================================

/**
 * Snapshot guarantee copy for investment review
 */
export const SNAPSHOT_GUARANTEE = {
  title: 'Investment Record Guarantee',
  description: 'Your investment decision will be recorded with an immutable snapshot of:',
  items: [
    'All company disclosures visible at this moment',
    'Platform context and risk assessment',
    'Your acknowledged risks and consents',
    'Exact timestamp of your decision',
  ],
  footer: 'This snapshot cannot be altered and serves as your audit trail for regulatory purposes.',
} as const;

/**
 * Get snapshot guarantee message
 */
export function getSnapshotGuaranteeMessage(): typeof SNAPSHOT_GUARANTEE {
  return SNAPSHOT_GUARANTEE;
}
