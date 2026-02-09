/**
 * PHASE 5 - Company/Issuer Frontend: API Functions
 *
 * PURPOSE:
 * - Fetch issuer's own company data with platform context
 * - Submit/edit disclosures with platform state awareness
 * - Respond to clarifications
 *
 * DEFENSIVE PRINCIPLES:
 * - Platform state supremacy - cannot override platform restrictions
 * - Review-state-driven editability
 * - Cannot close/extend clarification timelines
 * - PHASE SEPARATION: Issuer has ZERO visibility into investor metrics
 */

import companyApi from './companyApi';

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
  platform_overrides: Array<{
    type: string;
    severity: string;
    message: string;
    reason?: string;
    blocks_editing?: boolean;
    blocks_submission?: boolean;
    adds_review_delay?: boolean;
    affects_go_live?: boolean;
  }>;

  // Disclosure Requirements (contract-complete with backend taxonomy)
  // Backend provides ALL requirements including not-started ones
  disclosures: Array<{
    id: number | null; // null when status is "not_started"
    module_id: number;
    module_code: string;
    module_name: string;
    // BACKEND-PROVIDED TAXONOMY (no frontend inference)
    category: 'governance' | 'financial' | 'legal' | 'operational';
    tier: number; // Minimum tier requirement (1-3)
    is_required: boolean;
    required_for_tier: number; // Same as tier
    // STATUS & PERMISSIONS
    status: string; // Backend normalizes to "not_started" when no thread exists
    completion_percentage: number;
    can_edit: boolean;
    can_submit: boolean;
    // REJECTION INFO
    rejection_reason?: string;
    corrective_guidance?: string;
    // VERSION INFO
    version_number: number;
    submitted_at?: string;
    approved_at?: string;
  }>;

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
 * Backend endpoint: GET /api/company/disclosures
 * Returns contract-complete structure with:
 * - All disclosure requirements (including not_started)
 * - Backend-provided category and tier taxonomy
 * - Platform context and effective permissions
 */
export async function fetchIssuerCompany(): Promise<IssuerCompanyData> {
  const response = await companyApi.get('/disclosures');
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
  const response = await companyApi.get(`/disclosures/${disclosureId}`);
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
  const response = await companyApi.post(`/disclosures/${disclosureId}/submit`, { data });
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
  const response = await companyApi.post(`/disclosures/${disclosureId}/submit`);
  return response.data;
}

/**
 * Fetch disclosure thread with timeline
 *
 * Backend endpoint: GET /company/disclosures/{id}
 * Returns disclosure details with complete timeline of events
 */
export async function fetchDisclosureThread(
  disclosureId: number
): Promise<any> {
  const response = await companyApi.get(`/disclosures/${disclosureId}`);
  return response.data;
}

/**
 * Submit response to disclosure thread
 *
 * Backend endpoint: POST /company/disclosures/{id}/respond
 * Adds a response entry to the disclosure timeline
 */
export async function submitDisclosureResponse(
  disclosureId: number,
  data: {
    message: string;
    documents?: File[];
  }
): Promise<{ success: boolean; message: string }> {
  const formData = new FormData();
  formData.append('message', data.message);

  if (data.documents && data.documents.length > 0) {
    data.documents.forEach((file, index) => {
      formData.append(`documents[${index}]`, file);
    });
  }

  const response = await companyApi.post(`/disclosures/${disclosureId}/respond`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
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
  const response = await companyApi.post(`/clarifications/${clarificationId}/answer`, {
    answer,
  });
  return response.data;
}

/**
 * AUDIT GAP 2 FIX: Removed getInvestorSnapshotAwareness()
 *
 * REASON: Phase separation violation. Issuer must have ZERO visibility
 * into investor metrics, even aggregate data like version distribution.
 *
 * This function allowed issuers to infer investor behavior patterns
 * from snapshot version distribution, which could be used to game
 * disclosure timing.
 *
 * If platform oversight is needed, this function should be moved to
 * admin-only API, not issuer API.
 */
