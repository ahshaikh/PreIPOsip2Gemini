/**
 * V-DISPUTE-MGMT-2026: Dispute Management API Client
 *
 * Provides typed API functions for dispute operations.
 * Backend is authoritative - frontend uses permission flags from API responses.
 */

import api from '../api';

// Types

/**
 * Admin dispute permissions (computed by backend).
 */
export interface DisputePermissions {
  can_transition: boolean;
  can_escalate: boolean;
  can_resolve: boolean;
  can_override_defensibility: boolean;
  can_refund: boolean;
  can_close: boolean;
  available_transitions: string[];
}

/**
 * Investor dispute permissions (computed by backend).
 *
 * CRITICAL: Frontend MUST use these flags to control UI behavior.
 * Frontend MUST NOT derive permissions from dispute.status.
 */
export interface InvestorDisputePermissions {
  can_add_evidence: boolean;
  can_add_comment: boolean;
}

export interface DisputeTimeline {
  id: number;
  event_type: string;
  actor_role: string;
  title: string;
  description?: string;
  old_status?: string;
  new_status?: string;
  created_at: string;
  visible_to_investor: boolean;
}

export interface DisputeSnapshot {
  id: number;
  integrity_hash: string;
  disputable_snapshot: Record<string, unknown>;
  wallet_snapshot: Record<string, unknown>;
  created_at: string;
}

export interface Dispute {
  id: number;
  title: string;
  description: string;
  type: string;
  status: string;
  severity: string;
  category: string;
  user_id: number;
  company_id?: number;
  disputable_type?: string;
  disputable_id?: number;
  assigned_to_admin_id?: number;
  settlement_action?: string;
  settlement_amount_paise?: number;
  resolution?: string;
  risk_score: number;
  sla_deadline_at?: string;
  escalated_at?: string;
  resolved_at?: string;
  closed_at?: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
  company?: {
    id: number;
    name: string;
  };
  timeline?: DisputeTimeline[];
  snapshot?: DisputeSnapshot;
  /**
   * Investor permissions (present in user dispute detail responses).
   * Backend-computed. Frontend MUST use these flags for UI behavior.
   */
  permissions?: InvestorDisputePermissions;
}

export interface DisputeWithPermissions {
  dispute: Dispute;
  permissions: DisputePermissions;
}

export interface DisputeIntegrity {
  valid: boolean;
  computed_hash?: string;
  stored_hash?: string;
  error?: string;
}

export interface DisputeFilters {
  status?: string;
  type?: string;
  assigned_to?: number;
  unassigned?: boolean;
  severity?: string;
  user_id?: number;
  company_id?: number;
  sla_breached?: boolean;
  active_only?: boolean;
  per_page?: number;
  page?: number;
}

// Admin API Functions

export async function getAdminDisputes(filters: DisputeFilters = {}) {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== '') {
      params.append(key, String(value));
    }
  });

  const response = await api.get(`/admin/dispute-management?${params.toString()}`);
  return response.data;
}

export async function getAdminDisputeDetail(disputeId: number) {
  const response = await api.get(`/admin/dispute-management/${disputeId}`);
  return response.data;
}

export async function transitionDispute(
  disputeId: number,
  targetStatus: string,
  comment?: string
) {
  const response = await api.post(`/admin/dispute-management/${disputeId}/transition`, {
    target_status: targetStatus,
    comment,
  });
  return response.data;
}

export async function resolveDispute(
  disputeId: number,
  outcome: 'approved' | 'rejected',
  resolution: string,
  settlementAction?: string,
  settlementAmount?: number,
  settlementDetails?: Record<string, unknown>
) {
  const response = await api.post(`/admin/dispute-management/${disputeId}/resolve`, {
    outcome,
    resolution,
    settlement_action: settlementAction,
    settlement_amount: settlementAmount,
    settlement_details: settlementDetails,
  });
  return response.data;
}

export async function escalateDispute(disputeId: number, reason: string) {
  const response = await api.post(`/admin/dispute-management/${disputeId}/escalate`, {
    reason,
  });
  return response.data;
}

export async function overrideDefensibility(
  disputeId: number,
  overrideType: string,
  reason: string,
  evidenceReference?: string,
  expiresAt?: string
) {
  const response = await api.post(
    `/admin/dispute-management/${disputeId}/override-defensibility`,
    {
      override_type: overrideType,
      reason,
      evidence_reference: evidenceReference,
      expires_at: expiresAt,
    }
  );
  return response.data;
}

export async function settleDispute(
  disputeId: number,
  action: string,
  amount?: number,
  details?: Record<string, unknown>
) {
  const response = await api.post(`/admin/dispute-management/${disputeId}/settle`, {
    action,
    amount,
    details,
  });
  return response.data;
}

export async function closeDispute(disputeId: number, notes?: string) {
  const response = await api.post(`/admin/dispute-management/${disputeId}/close`, {
    notes,
  });
  return response.data;
}

// User/Investor API Functions

export async function getUserDisputes() {
  const response = await api.get('/user/disputes');
  return response.data;
}

export async function getUserDisputeDetail(disputeId: number) {
  const response = await api.get(`/user/disputes/${disputeId}`);
  return response.data;
}

export async function fileDispute(data: {
  title: string;
  description: string;
  category: string;
  disputable_type?: string;
  disputable_id?: number;
}) {
  const response = await api.post('/user/disputes', data);
  return response.data;
}

export async function addEvidence(
  disputeId: number,
  evidence: Array<{
    type: string;
    value: string;
    description?: string;
  }>,
  description?: string
) {
  const response = await api.post(`/user/disputes/${disputeId}/evidence`, {
    evidence,
    description,
  });
  return response.data;
}

export async function addComment(disputeId: number, comment: string) {
  const response = await api.post(`/user/disputes/${disputeId}/comments`, {
    comment,
  });
  return response.data;
}

// Utility functions

export function getStatusBadgeColor(status: string): string {
  const colors: Record<string, string> = {
    open: 'bg-blue-100 text-blue-800',
    under_review: 'bg-yellow-100 text-yellow-800',
    awaiting_investor: 'bg-purple-100 text-purple-800',
    escalated: 'bg-red-100 text-red-800',
    resolved_approved: 'bg-green-100 text-green-800',
    resolved_rejected: 'bg-gray-100 text-gray-800',
    closed: 'bg-gray-200 text-gray-600',
  };
  return colors[status] || 'bg-gray-100 text-gray-800';
}

export function getTypeBadgeColor(type: string): string {
  const colors: Record<string, string> = {
    confusion: 'bg-blue-50 text-blue-700',
    payment: 'bg-yellow-50 text-yellow-700',
    allocation: 'bg-orange-50 text-orange-700',
    fraud: 'bg-red-50 text-red-700',
  };
  return colors[type] || 'bg-gray-50 text-gray-700';
}

export function formatAmountPaise(paise: number): string {
  return `₹${(paise / 100).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

/**
 * @deprecated Use backend-provided permission flags instead.
 * Frontend MUST NOT derive behavior from status.
 * This function is retained only for display/statistics purposes.
 */
export function isDisputeActive(status: string): boolean {
  return ['open', 'under_review', 'awaiting_investor', 'escalated'].includes(status);
}

/**
 * @deprecated Use backend-provided permission flags instead.
 * Frontend MUST NOT derive behavior from status.
 * This function is retained only for display/statistics purposes.
 */
export function isDisputeResolved(status: string): boolean {
  return ['resolved_approved', 'resolved_rejected'].includes(status);
}
