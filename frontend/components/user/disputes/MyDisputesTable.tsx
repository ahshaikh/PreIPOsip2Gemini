'use client';

/**
 * V-DISPUTE-MGMT-2026: My Disputes Table Component
 *
 * Displays investor's disputes in a simple table.
 */

import { Dispute, getStatusBadgeColor } from '@/lib/api/disputes';

interface MyDisputesTableProps {
  disputes: Dispute[];
  loading: boolean;
  onDisputeClick: (disputeId: number) => void;
}

export default function MyDisputesTable({ disputes, loading, onDisputeClick }: MyDisputesTableProps) {
  if (loading) {
    return (
      <div className="p-8 text-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
        <p className="mt-2 text-gray-500">Loading your disputes...</p>
      </div>
    );
  }

  if (disputes.length === 0) {
    return (
      <div className="p-8 text-center">
        <p className="text-gray-500">You have not filed any disputes yet.</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Dispute
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Filed
            </th>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Action
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {disputes.map((dispute) => (
            <tr
              key={dispute.id}
              className="hover:bg-gray-50"
            >
              <td className="px-6 py-4">
                <div className="text-sm font-medium text-gray-900">
                  {dispute.title}
                </div>
                <div className="text-xs text-gray-500 capitalize">
                  {dispute.category.replace('_', ' ')}
                </div>
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <span className={`px-2 py-1 text-xs font-medium rounded ${getStatusBadgeColor(dispute.status)}`}>
                  {getStatusLabel(dispute.status)}
                </span>
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {new Date(dispute.created_at).toLocaleDateString()}
              </td>
              <td className="px-6 py-4 whitespace-nowrap">
                <button
                  onClick={() => onDisputeClick(dispute.id)}
                  className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  View Details
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    open: 'Open',
    under_review: 'Under Review',
    awaiting_investor: 'Action Needed',
    escalated: 'Escalated',
    resolved_approved: 'Approved',
    resolved_rejected: 'Rejected',
    closed: 'Closed',
  };
  return labels[status] || status;
}
