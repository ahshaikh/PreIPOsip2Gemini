'use client';

/**
 * V-DISPUTE-MGMT-2026: Dispute Summary Bar Component
 *
 * Shows quick statistics about current disputes.
 */

import { DisputeWithPermissions, isDisputeActive } from '@/lib/api/disputes';

interface DisputeSummaryBarProps {
  disputes: DisputeWithPermissions[];
}

export default function DisputeSummaryBar({ disputes }: DisputeSummaryBarProps) {
  const activeCount = disputes.filter((d) => isDisputeActive(d.dispute.status)).length;
  const escalatedCount = disputes.filter((d) => d.dispute.status === 'escalated').length;
  const actionableCount = disputes.filter((d) => d.permissions.can_resolve).length;
  const closableCount = disputes.filter((d) => d.permissions.can_close).length;

  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div className="bg-white rounded-lg shadow p-4">
        <div className="text-2xl font-bold text-blue-600">{activeCount}</div>
        <div className="text-sm text-gray-500">Active Disputes</div>
      </div>
      <div className="bg-white rounded-lg shadow p-4">
        <div className="text-2xl font-bold text-red-600">{escalatedCount}</div>
        <div className="text-sm text-gray-500">Escalated</div>
      </div>
      <div className="bg-white rounded-lg shadow p-4">
        <div className="text-2xl font-bold text-green-600">{actionableCount}</div>
        <div className="text-sm text-gray-500">Ready to Resolve</div>
      </div>
      <div className="bg-white rounded-lg shadow p-4">
        <div className="text-2xl font-bold text-gray-600">{closableCount}</div>
        <div className="text-sm text-gray-500">Ready to Close</div>
      </div>
    </div>
  );
}
