'use client';

/**
 * V-DISPUTE-MGMT-2026: Investor Dispute Detail Page
 *
 * Shows dispute details with investor-visible timeline.
 *
 * PERMISSION DISCIPLINE: This component uses backend-provided permission flags
 * (permissions.can_add_evidence, permissions.can_add_comment) to control UI behavior.
 * It MUST NOT derive behavior from dispute.status.
 */

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { getUserDisputeDetail, addComment, Dispute, InvestorDisputePermissions } from '@/lib/api/disputes';
import InvestorTimeline from '@/components/user/disputes/InvestorTimeline';
import EvidenceUpload from '@/components/user/disputes/EvidenceUpload';

export default function UserDisputeDetailPage() {
  const params = useParams();
  const router = useRouter();
  const disputeId = Number(params.id);

  const [dispute, setDispute] = useState<Dispute | null>(null);
  const [permissions, setPermissions] = useState<InvestorDisputePermissions | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (disputeId) {
      fetchDisputeDetail();
    }
  }, [disputeId]);

  const fetchDisputeDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await getUserDisputeDetail(disputeId);
      // Handle both wrapped { success, data } and direct response structures
      const data = response.success !== undefined ? response.data : response;
      // Extract dispute and permissions from response
      const disputeData = data.dispute || data;
      const permissionsData = data.permissions || disputeData.permissions;
      setDispute(disputeData);
      setPermissions(permissionsData || null);
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch dispute';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleAddComment = async () => {
    if (!comment.trim()) return;

    try {
      setSubmitting(true);
      const response = await addComment(disputeId, comment);
      if (response.success) {
        setComment('');
        await fetchDisputeDetail();
      } else {
        setError(response.message);
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to add comment';
      setError(errorMessage);
    } finally {
      setSubmitting(false);
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

  if (error || !dispute) {
    return (
      <div className="container mx-auto px-4 py-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-700">{error || 'Dispute not found'}</p>
          <button
            onClick={() => router.push('/disputes')}
            className="mt-2 text-red-600 underline"
          >
            Back to my disputes
          </button>
        </div>
      </div>
    );
  }

  // PERMISSION DISCIPLINE: Use backend-provided permission flags
  // DO NOT derive UI behavior from dispute.status
  const canAddComment = permissions?.can_add_comment ?? false;
  const canAddEvidence = permissions?.can_add_evidence ?? false;

  return (
    <div className="container mx-auto px-4 py-6">
      {/* Header */}
      <div className="mb-6">
        <button
          onClick={() => router.push('/disputes')}
          className="text-sm text-gray-500 hover:text-gray-700 mb-2"
        >
          &larr; Back to my disputes
        </button>
        <h1 className="text-2xl font-bold text-gray-900">
          {dispute.title}
        </h1>
        <div className="flex gap-2 mt-2">
          <span className={`px-2 py-1 rounded text-xs font-medium ${getStatusColor(dispute.status)}`}>
            {getStatusLabel(dispute.status)}
          </span>
          <span className="px-2 py-1 bg-gray-100 rounded text-xs font-medium text-gray-700">
            #{dispute.id}
          </span>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Description */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">Description</h2>
            <p className="text-gray-700 whitespace-pre-wrap">{dispute.description}</p>
          </div>

          {/* Timeline */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold mb-4">Updates</h2>
            <InvestorTimeline timeline={dispute.timeline || []} />
          </div>

          {/* Add Comment - controlled by backend permission flag */}
          {canAddComment && (
            <div className="bg-white rounded-lg shadow p-6">
              <h2 className="text-lg font-semibold mb-4">Add Comment</h2>
              <textarea
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                placeholder="Add a comment or question..."
                rows={3}
                className="w-full rounded-md border-gray-300 mb-4"
              />
              <button
                onClick={handleAddComment}
                disabled={submitting || !comment.trim()}
                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                {submitting ? 'Sending...' : 'Send Comment'}
              </button>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Status Card */}
          <div className="bg-white rounded-lg shadow p-6">
            <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
              Status
            </h3>
            <div className={`text-lg font-medium ${getStatusTextColor(dispute.status)}`}>
              {getStatusLabel(dispute.status)}
            </div>
            <p className="text-sm text-gray-500 mt-2">
              Filed on {new Date(dispute.created_at).toLocaleDateString()}
            </p>
            {dispute.resolved_at && (
              <p className="text-sm text-gray-500 mt-1">
                Resolved on {new Date(dispute.resolved_at).toLocaleDateString()}
              </p>
            )}
          </div>

          {/* Evidence Upload - controlled by backend permission flag */}
          {canAddEvidence && (
            <EvidenceUpload
              disputeId={disputeId}
              onSuccess={fetchDisputeDetail}
            />
          )}

          {/* Resolution */}
          {dispute.resolution && (
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
                Resolution
              </h3>
              <p className="text-gray-700 text-sm whitespace-pre-wrap">
                {dispute.resolution}
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    open: 'bg-blue-100 text-blue-800',
    under_review: 'bg-yellow-100 text-yellow-800',
    awaiting_investor: 'bg-purple-100 text-purple-800',
    escalated: 'bg-orange-100 text-orange-800',
    resolved_approved: 'bg-green-100 text-green-800',
    resolved_rejected: 'bg-gray-100 text-gray-800',
    closed: 'bg-gray-200 text-gray-600',
  };
  return colors[status] || 'bg-gray-100 text-gray-800';
}

function getStatusTextColor(status: string): string {
  const colors: Record<string, string> = {
    open: 'text-blue-600',
    under_review: 'text-yellow-600',
    awaiting_investor: 'text-purple-600',
    escalated: 'text-orange-600',
    resolved_approved: 'text-green-600',
    resolved_rejected: 'text-gray-600',
    closed: 'text-gray-500',
  };
  return colors[status] || 'text-gray-600';
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    open: 'Open',
    under_review: 'Under Review',
    awaiting_investor: 'Awaiting Your Response',
    escalated: 'Escalated',
    resolved_approved: 'Resolved - Approved',
    resolved_rejected: 'Resolved - Rejected',
    closed: 'Closed',
  };
  return labels[status] || status;
}
