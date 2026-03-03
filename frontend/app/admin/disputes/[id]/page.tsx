'use client';

/**
 * V-DISPUTE-MGMT-2026: Admin Dispute Detail Page
 *
 * Shows full dispute context with permission-aware action buttons.
 * Backend computes permissions - frontend renders based on flags.
 */

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import {
  getAdminDisputeDetail,
  transitionDispute,
  escalateDispute,
  closeDispute,
  Dispute,
  DisputePermissions,
  DisputeIntegrity,
} from '@/lib/api/disputes';
import TimelineView from '@/components/admin/disputes/TimelineView';
import SnapshotIntegrityPanel from '@/components/admin/disputes/SnapshotIntegrityPanel';
import DefensibilityPanel from '@/components/admin/disputes/DefensibilityPanel';
import FinancialBreakdown from '@/components/admin/disputes/FinancialBreakdown';
import ActionButtons from '@/components/admin/disputes/ActionButtons';
import OverrideModal from '@/components/admin/disputes/OverrideModal';

interface DisputeDetail {
  dispute: Dispute;
  permissions: DisputePermissions;
  integrity: DisputeIntegrity;
  recommended_settlement?: {
    action: string;
    reason: string;
    suggested_amount?: number;
  };
  available_transitions: string[];
}

export default function AdminDisputeDetailPage() {
  const params = useParams();
  const router = useRouter();
  const disputeId = Number(params.id);

  const [detail, setDetail] = useState<DisputeDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [showOverrideModal, setShowOverrideModal] = useState(false);

  useEffect(() => {
    if (disputeId) {
      fetchDisputeDetail();
    }
  }, [disputeId]);

  const fetchDisputeDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await getAdminDisputeDetail(disputeId);
      if (response.success) {
        setDetail(response.data);
      } else {
        setError(response.message || 'Failed to fetch dispute details');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch dispute';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleTransition = async (targetStatus: string, comment?: string) => {
    try {
      setActionLoading(true);
      const response = await transitionDispute(disputeId, targetStatus, comment);
      if (response.success) {
        await fetchDisputeDetail();
      } else {
        setError(response.message);
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Transition failed';
      setError(errorMessage);
    } finally {
      setActionLoading(false);
    }
  };

  const handleEscalate = async (reason: string) => {
    try {
      setActionLoading(true);
      const response = await escalateDispute(disputeId, reason);
      if (response.success) {
        await fetchDisputeDetail();
      } else {
        setError(response.message);
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Escalation failed';
      setError(errorMessage);
    } finally {
      setActionLoading(false);
    }
  };

  const handleClose = async (notes?: string) => {
    try {
      setActionLoading(true);
      const response = await closeDispute(disputeId, notes);
      if (response.success) {
        await fetchDisputeDetail();
      } else {
        setError(response.message);
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Close failed';
      setError(errorMessage);
    } finally {
      setActionLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="container mx-auto px-4 py-6">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="h-64 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  if (error || !detail) {
    return (
      <div className="container mx-auto px-4 py-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-700">{error || 'Dispute not found'}</p>
          <button
            onClick={() => router.push('/admin/disputes')}
            className="mt-2 text-red-600 underline"
          >
            Back to disputes
          </button>
        </div>
      </div>
    );
  }

  const { dispute, permissions, integrity, recommended_settlement } = detail;

  return (
    <div className="container mx-auto px-4 py-6">
      {/* Header */}
      <div className="mb-6 flex items-start justify-between">
        <div>
          <button
            onClick={() => router.push('/admin/disputes')}
            className="text-sm text-gray-500 hover:text-gray-700 mb-2"
          >
            &larr; Back to disputes
          </button>
          <h1 className="text-2xl font-bold text-gray-900">
            Dispute #{dispute.id}: {dispute.title}
          </h1>
          <div className="flex gap-2 mt-2">
            <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(dispute.status)}`}>
              {dispute.status.replace('_', ' ').toUpperCase()}
            </span>
            <span className={`px-2 py-1 rounded text-xs font-medium ${getTypeColor(dispute.type)}`}>
              {dispute.type.toUpperCase()}
            </span>
            <span className="px-2 py-1 bg-gray-100 rounded text-xs font-medium text-gray-700">
              {dispute.severity.toUpperCase()}
            </span>
          </div>
        </div>

        <ActionButtons
          permissions={permissions}
          dispute={dispute}
          loading={actionLoading}
          onTransition={handleTransition}
          onEscalate={handleEscalate}
          onClose={handleClose}
          onResolve={() => router.push(`/admin/disputes/${disputeId}/resolve`)}
          onOverride={() => setShowOverrideModal(true)}
        />
      </div>

      {/* Error Banner */}
      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-700">{error}</p>
        </div>
      )}

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Details */}
        <div className="lg:col-span-2 space-y-6">
          {/* Description */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">Description</h2>
            <p className="text-gray-700 whitespace-pre-wrap">{dispute.description}</p>
          </div>

          {/* Financial Breakdown */}
          <FinancialBreakdown dispute={dispute} />

          {/* Timeline */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">Timeline</h2>
            <TimelineView timeline={dispute.timeline || []} />
          </div>
        </div>

        {/* Right Column - Panels */}
        <div className="space-y-6">
          {/* Snapshot Integrity */}
          <SnapshotIntegrityPanel integrity={integrity} />

          {/* Defensibility */}
          <DefensibilityPanel
            dispute={dispute}
            integrity={integrity}
            recommendedSettlement={recommended_settlement}
          />

          {/* User Info */}
          {dispute.user && (
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
                Investor
              </h3>
              <div className="space-y-2">
                <p className="font-medium">{dispute.user.name}</p>
                <p className="text-sm text-gray-600">{dispute.user.email}</p>
                <p className="text-sm text-gray-600">ID: {dispute.user.id}</p>
              </div>
            </div>
          )}

          {/* Company Info */}
          {dispute.company && (
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
                Company
              </h3>
              <div className="space-y-2">
                <p className="font-medium">{dispute.company.name}</p>
                <p className="text-sm text-gray-600">ID: {dispute.company.id}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Override Modal */}
      {showOverrideModal && (
        <OverrideModal
          disputeId={disputeId}
          onClose={() => setShowOverrideModal(false)}
          onSuccess={() => {
            setShowOverrideModal(false);
            fetchDisputeDetail();
          }}
        />
      )}
    </div>
  );
}

function getStatusColor(status: string): string {
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

function getTypeColor(type: string): string {
  const colors: Record<string, string> = {
    confusion: 'bg-blue-50 text-blue-700',
    payment: 'bg-yellow-50 text-yellow-700',
    allocation: 'bg-orange-50 text-orange-700',
    fraud: 'bg-red-50 text-red-700',
  };
  return colors[type] || 'bg-gray-50 text-gray-700';
}
