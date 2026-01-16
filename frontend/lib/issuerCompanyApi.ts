/**
 * PHASE 5 - Company/Issuer Frontend: API Functions
 *
 * PURPOSE:
 * - Fetch issuer's own company data with platform context
 * - Submit/edit disclosures with platform state awareness
 * - Respond to clarifications
 * - View investor snapshot awareness (aggregate only)
 *
 * DEFENSIVE PRINCIPLES:
 * - Platform state supremacy - cannot override platform restrictions
 * - Review-state-driven editability
 * - Investor impact awareness (read-only, aggregate)
 * - Cannot close/extend clarification timelines
 */

import api from './api';

export interface IssuerCompanyData {
  id: number;
  name: string;
  slug: string;

  // Platform Context (Read-only, supremacy over issuer)
  platform_context: {
    lifecycle_state: string;
    is_suspended: boolean;
    is_frozen: boolean;
    is_under_investigation: boolean;
    buying_enabled: boolean;
    buying_pause_reason?: string;
    tier_status: {
      tier_1_approved: boolean;
      tier_2_approved: boolean;
      tier_3_approved: boolean;
    };
  };

  // Effective Permissions (Platform-controlled)
  effective_permissions: {
    can_edit_disclosures: boolean;
    can_submit_disclosures: boolean;
    can_answer_clarifications: boolean;
  };

  // Platform Overrides (Messages explaining restrictions)
  platform_overrides: string[];

  // Disclosures with edit state
  disclosures: Array<{
    id: number;
    module_id: number;
    module_name: string;
    status: string;
    can_edit: boolean;
    can_submit: boolean;
    rejection_reason?: string;
    corrective_guidance?: string;
    data: any;
  }>;

  // Investor Snapshot Awareness (Aggregate only, NO personal data)
  investor_snapshot_awareness?: {
    total_investors: number;
    version_distribution: Array<{
      version_number: number;
      investor_count: number;
      percentage: number;
    }>;
    privacy_note: string;
  };

  // Clarifications (with deadlines)
  clarifications?: Array<{
    id: number;
    question: string;
    status: string;
    issuer_response_due_at?: string;
    issuer_response_overdue: boolean;
    is_escalated: boolean;
    is_expired: boolean;
  }>;
}

export interface DisclosureEditPermissions {
  can_edit: boolean;
  can_submit: boolean;
  blockers: string[];
  platform_restrictions: string[];
}

/**
 * Fetch issuer's own company data with platform context
 *
 * Backend endpoint: GET /issuer/company
 * Uses CompanyDisclosureService.getDashboardSummary() with platform context injection
 */
export async function fetchIssuerCompany(): Promise<IssuerCompanyData> {
  const response = await api.get('/issuer/company');
  return response.data.data;
}

/**
 * Check if issuer can edit disclosure
 *
 * Backend endpoint: GET /issuer/disclosures/{id}/edit-permissions
 * Checks platform state, review state, freeze status
 */
export async function checkDisclosureEditPermissions(
  disclosureId: number
): Promise<DisclosureEditPermissions> {
  const response = await api.get(`/issuer/disclosures/${disclosureId}/edit-permissions`);
  return response.data.data;
}

/**
 * Update disclosure (if permitted)
 *
 * Backend endpoint: PUT /issuer/disclosures/{id}
 * Validates platform state before allowing edit
 */
export async function updateDisclosure(
  disclosureId: number,
  data: any
): Promise<{ success: boolean; message: string }> {
  const response = await api.put(`/issuer/disclosures/${disclosureId}`, { data });
  return response.data;
}

/**
 * Submit disclosure for review
 *
 * Backend endpoint: POST /issuer/disclosures/{id}/submit
 * Validates platform state, marks as under_review
 */
export async function submitDisclosureForReview(
  disclosureId: number
): Promise<{ success: boolean; message: string }> {
  const response = await api.post(`/issuer/disclosures/${disclosureId}/submit`);
  return response.data;
}

/**
 * Answer clarification
 *
 * Backend endpoint: POST /issuer/clarifications/{id}/answer
 * Validates disclosure state, checks deadlines
 * Uses CompanyDisclosureService.answerClarification() with Phase 2 guards
 */
export async function answerClarification(
  clarificationId: number,
  answer: string
): Promise<{ success: boolean; message: string }> {
  const response = await api.post(`/issuer/clarifications/${clarificationId}/answer`, {
    answer,
  });
  return response.data;
}

/**
 * Get investor snapshot awareness for disclosure
 *
 * Backend endpoint: GET /issuer/disclosures/{id}/investor-awareness
 * Uses IssuerSnapshotAwarenessService (Phase 3)
 * Returns aggregate only, NO investor personal data
 */
export async function getInvestorSnapshotAwareness(
  disclosureId: number
): Promise<{
  total_investors: number;
  version_distribution: Array<{
    version_number: number;
    investor_count: number;
    percentage: number;
  }>;
  current_version: number;
  privacy_note: string;
}> {
  const response = await api.get(`/issuer/disclosures/${disclosureId}/investor-awareness`);
  return response.data.data;
}
