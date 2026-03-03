'use client';

/**
 * V-DISPUTE-MGMT-2026: Dispute Table Component
 *
 * Displays disputes in a table with permission badges.
 */

import { DisputeWithPermissions, getStatusBadgeColor, getTypeBadgeColor } from '@/lib/api/disputes';

interface DisputeTableProps {
  disputes: DisputeWithPermissions[];
  loading: boolean;
  onDisputeClick: (disputeId: number) => void;
}

export default function DisputeTable({ disputes, loading, onDisputeClick }: DisputeTableProps) {
  if (loading) {
    return (
      <div className="p-8 text-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
        <p className="mt-2 text-gray-500">Loading disputes...</p>
      </div>
    );
  }

  if (disputes.length === 0) {
    return (
      <div className="p-8 text-center">
        <p className="text-gray-500">No disputes found matching your filters.</p>
      </div>
    );
  }

  return (
    <table className="min-w-full divide-y divide-gray-200">
      <thead className="bg-gray-50">
        <tr>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            ID
          </th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Title
          </th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Type
          </th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Status
          </th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            User
          </th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Actions
          </th>
        </tr>
      </thead>
      <tbody className="bg-white divide-y divide-gray-200">
        {disputes.map(({ dispute, permissions }) => (
          <tr
            key={dispute.id}
            onClick={() => onDisputeClick(dispute.id)}
            className="hover:bg-gray-50 cursor-pointer"
          >
            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              #{dispute.id}
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <div className="text-sm font-medium text-gray-900 truncate max-w-xs">
                {dispute.title}
              </div>
              <div className="text-xs text-gray-500">
                {new Date(dispute.created_at).toLocaleDateString()}
              </div>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <span className={`px-2 py-1 text-xs font-medium rounded ${getTypeBadgeColor(dispute.type)}`}>
                {dispute.type}
              </span>
            </td>
            <td className="px-6 py-4 whitespace-nowrap">
              <span className={`px-2 py-1 text-xs font-medium rounded ${getStatusBadgeColor(dispute.status)}`}>
                {dispute.status.replace('_', ' ')}
              </span>
            </td>
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {dispute.user?.name || `User #${dispute.user_id}`}
            </td>
            <td className="px-6 py-4 whitespace-nowrap text-sm">
              <div className="flex gap-1">
                {permissions.can_transition && (
                  <span className="w-2 h-2 bg-blue-500 rounded-full" title="Can transition" />
                )}
                {permissions.can_escalate && (
                  <span className="w-2 h-2 bg-red-500 rounded-full" title="Can escalate" />
                )}
                {permissions.can_resolve && (
                  <span className="w-2 h-2 bg-green-500 rounded-full" title="Can resolve" />
                )}
              </div>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
