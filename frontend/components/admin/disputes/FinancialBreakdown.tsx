'use client';

/**
 * V-DISPUTE-MGMT-2026: Financial Breakdown Component
 *
 * Displays financial details related to the dispute.
 */

import { Dispute, formatAmountPaise } from '@/lib/api/disputes';

interface FinancialBreakdownProps {
  dispute: Dispute;
}

export default function FinancialBreakdown({ dispute }: FinancialBreakdownProps) {
  const hasSettlement = dispute.settlement_action && dispute.settlement_amount_paise;

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h2 className="text-lg font-semibold mb-4">Financial Details</h2>

      <div className="grid grid-cols-2 gap-4">
        <div>
          <div className="text-sm text-gray-500">Disputable Type</div>
          <div className="font-medium">
            {dispute.disputable_type?.split('\\').pop() || 'N/A'}
          </div>
        </div>

        <div>
          <div className="text-sm text-gray-500">Disputable ID</div>
          <div className="font-medium">
            {dispute.disputable_id || 'N/A'}
          </div>
        </div>

        <div>
          <div className="text-sm text-gray-500">Risk Score</div>
          <div className="font-medium">
            <span className={`px-2 py-0.5 rounded text-sm ${getRiskColor(dispute.risk_score)}`}>
              {dispute.risk_score}/5
            </span>
          </div>
        </div>

        <div>
          <div className="text-sm text-gray-500">Category</div>
          <div className="font-medium capitalize">
            {dispute.category.replace('_', ' ')}
          </div>
        </div>
      </div>

      {hasSettlement && (
        <div className="mt-6 pt-4 border-t">
          <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
            Settlement
          </h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <div className="text-sm text-gray-500">Action</div>
              <div className="font-medium capitalize">
                {dispute.settlement_action?.replace('_', ' ')}
              </div>
            </div>
            <div>
              <div className="text-sm text-gray-500">Amount</div>
              <div className="font-medium text-green-600">
                {formatAmountPaise(dispute.settlement_amount_paise!)}
              </div>
            </div>
          </div>
        </div>
      )}

      {dispute.resolution && (
        <div className="mt-6 pt-4 border-t">
          <h3 className="text-sm font-semibold text-gray-500 uppercase mb-2">
            Resolution Notes
          </h3>
          <p className="text-gray-700 text-sm whitespace-pre-wrap">
            {dispute.resolution}
          </p>
        </div>
      )}
    </div>
  );
}

function getRiskColor(score: number): string {
  if (score >= 4) return 'bg-red-100 text-red-800';
  if (score >= 3) return 'bg-orange-100 text-orange-800';
  if (score >= 2) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
}
