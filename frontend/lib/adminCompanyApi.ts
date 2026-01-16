/**
 * PHASE 5 - Admin/Platform Frontend: API Functions
 *
 * PURPOSE:
 * - Manage company visibility controls (public/subscriber independent)
 * - Edit platform context (risk flags, tier approvals, governance state)
 * - Lifecycle transitions
 * - Suspension/freeze controls
 * - Material change flagging
 *
 * DEFENSIVE PRINCIPLES:
 * - Visibility changes show impact preview
 * - All actions are audited
 * - Platform authority explicitly marked
 * - Snapshot awareness enforced
 */

import api from './api';

export interface AdminCompanyDetail {
  id: number;
  name: string;
  slug: string;

  // Visibility Controls (CRITICAL - Independent toggles)
  is_visible_public: boolean;
  is_visible_subscribers: boolean;

  // Platform Context (Admin-editable)
  platform_context: {
    lifecycle_state: string;
    is_suspended: boolean;
    is_frozen: boolean;
    is_under_investigation: boolean;
    buying_enabled: boolean;
    buying_pause_reason?: string;
    suspension_reason?: string;
    tier_status: {
      tier_1_approved: boolean;
      tier_1_approved_at?: string;
      tier_2_approved: boolean;
      tier_2_approved_at?: string;
      tier_3_approved: boolean;
      tier_3_approved_at?: string;
    };
  };

  // Risk Flags (Admin-managed)
  risk_flags: Array<{
    id: number;
    flag_type: string;
    severity: string;
    description: string;
    is_visible_to_investors: boolean;
    status: string;
  }>;

  // Disclosures (Admin review)
  disclosures: Array<{
    id: number;
    module_name: string;
    status: string;
    version_number: number;
    submitted_at?: string;
  }>;

  // Investor Snapshots (Read-only)
  investor_snapshots: {
    total_investors: number;
    total_investments: number;
    snapshot_count: number;
    latest_snapshot_at?: string;
  };

  // Audit Trail Summary
  last_modified_by?: string;
  last_modified_at?: string;
}

export interface VisibilityChangeImpact {
  current_state: {
    is_visible_public: boolean;
    is_visible_subscribers: boolean;
  };
  proposed_change: {
    is_visible_public?: boolean;
    is_visible_subscribers?: boolean;
  };
  impact: {
    will_block_new_investments: boolean;
    will_remove_from_public_discovery: boolean;
    will_remove_from_subscriber_discovery: boolean;
    affected_existing_investors: number;
    affected_existing_investors_note: string;
  };
}

/**
 * Fetch company detail for admin management
 *
 * Backend endpoint: GET /admin/companies/{id}
 */
export async function fetchAdminCompanyDetail(companyId: number): Promise<AdminCompanyDetail> {
  const response = await api.get(`/admin/companies/${companyId}`);
  return response.data.data;
}

/**
 * Preview visibility change impact (before applying)
 *
 * Backend endpoint: POST /admin/companies/{id}/preview-visibility-change
 */
export async function previewVisibilityChange(
  companyId: number,
  changes: {
    is_visible_public?: boolean;
    is_visible_subscribers?: boolean;
  }
): Promise<VisibilityChangeImpact> {
  const response = await api.post(`/admin/companies/${companyId}/preview-visibility-change`, changes);
  return response.data.data;
}

/**
 * Update company visibility (CRITICAL CONTROL)
 *
 * Backend endpoint: PUT /admin/companies/{id}/visibility
 * Requires explicit confirmation
 * Logs to audit trail with actor_type: admin_override
 */
export async function updateCompanyVisibility(
  companyId: number,
  visibility: {
    is_visible_public: boolean;
    is_visible_subscribers: boolean;
  },
  reason: string
): Promise<{ success: boolean; message: string }> {
  const response = await api.put(`/admin/companies/${companyId}/visibility`, {
    ...visibility,
    reason,
  });
  return response.data;
}

/**
 * Update platform context (governance controls)
 *
 * Backend endpoint: PUT /admin/companies/{id}/platform-context
 */
export async function updatePlatformContext(
  companyId: number,
  context: {
    lifecycle_state?: string;
    is_suspended?: boolean;
    is_frozen?: boolean;
    is_under_investigation?: boolean;
    buying_enabled?: boolean;
    buying_pause_reason?: string;
    suspension_reason?: string;
  },
  reason: string
): Promise<{ success: boolean; message: string }> {
  const response = await api.put(`/admin/companies/${companyId}/platform-context`, {
    ...context,
    reason,
  });
  return response.data;
}

/**
 * Approve tier
 *
 * Backend endpoint: POST /admin/companies/{id}/approve-tier
 * Uses Tier2RiskControlService for Tier 2 (dual approval, 48-hour delay)
 */
export async function approveTier(
  companyId: number,
  tier: number,
  reason: string
): Promise<{ success: boolean; message: string }> {
  const response = await api.post(`/admin/companies/${companyId}/approve-tier`, {
    tier,
    reason,
  });
  return response.data;
}

/**
 * Add risk flag
 *
 * Backend endpoint: POST /admin/companies/{id}/risk-flags
 */
export async function addRiskFlag(
  companyId: number,
  flag: {
    flag_type: string;
    severity: string;
    description: string;
    is_visible_to_investors: boolean;
  }
): Promise<{ success: boolean; message: string }> {
  const response = await api.post(`/admin/companies/${companyId}/risk-flags`, flag);
  return response.data;
}

/**
 * Remove risk flag
 *
 * Backend endpoint: DELETE /admin/companies/{id}/risk-flags/{flagId}
 */
export async function removeRiskFlag(
  companyId: number,
  flagId: number,
  reason: string
): Promise<{ success: boolean; message: string }> {
  const response = await api.delete(`/admin/companies/${companyId}/risk-flags/${flagId}`, {
    data: { reason },
  });
  return response.data;
}

/**
 * Trigger material change detection
 *
 * Backend endpoint: POST /admin/companies/{id}/detect-material-changes
 * Uses MaterialChangeBuyImpactService
 */
export async function detectMaterialChanges(
  companyId: number
): Promise<{
  has_material_changes: boolean;
  changes: Array<{ field: string; severity: string; description: string }>;
  buy_impact: {
    buying_paused: boolean;
    requires_acknowledgement: boolean;
  };
}> {
  const response = await api.post(`/admin/companies/${companyId}/detect-material-changes`);
  return response.data.data;
}

/**
 * Get audit trail for company
 *
 * Backend endpoint: GET /admin/companies/{id}/audit-trail
 */
export async function getCompanyAuditTrail(
  companyId: number,
  limit: number = 50
): Promise<
  Array<{
    id: number;
    action: string;
    actor_type: string;
    admin_user_id?: number;
    admin_user_name?: string;
    changes: any;
    reason?: string;
    created_at: string;
    ip_address?: string;
  }>
> {
  const response = await api.get(`/admin/companies/${companyId}/audit-trail`, {
    params: { limit },
  });
  return response.data.data;
}
