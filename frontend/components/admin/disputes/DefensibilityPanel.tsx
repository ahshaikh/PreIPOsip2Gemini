'use client';

/**
 * V-DISPUTE-MGMT-2026: Defensibility Panel
 *
 * Shows defensibility status and recommended settlement action.
 */

import { Dispute, DisputeIntegrity, formatAmountPaise } from '@/lib/api/disputes';

interface DefensibilityPanelProps {
  dispute: Dispute;
  integrity: DisputeIntegrity;
  recommendedSettlement?: {
    action: string;
    reason: string;
    suggested_amount?: number;
    note?: string;
  };
}

export default function DefensibilityPanel({
  dispute,
  integrity,
  recommendedSettlement,
}: DefensibilityPanelProps) {
  const isDefensible = integrity.valid && !dispute.settlement_action;

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
        Defensibility
      </h3>

      <div className="flex items-center gap-2 mb-4">
        {isDefensible ? (
          <>
            <div className="w-3 h-3 bg-green-500 rounded-full" />
            <span className="text-green-700 font-medium">Defensible</span>
          </>
        ) : (
          <>
            <div className="w-3 h-3 bg-yellow-500 rounded-full" />
            <span className="text-yellow-700 font-medium">Review Required</span>
          </>
        )}
      </div>

      {recommendedSettlement && (
        <div className="border-t pt-4 mt-4">
          <h4 className="text-sm font-medium text-gray-700 mb-2">
            Recommended Action
          </h4>
          <div className="bg-blue-50 rounded p-3">
            <div className="font-medium text-blue-900 capitalize">
              {recommendedSettlement.action.replace('_', ' ')}
            </div>
            <div className="text-sm text-blue-700 mt-1">
              {recommendedSettlement.reason}
            </div>
            {recommendedSettlement.suggested_amount && (
              <div className="text-sm text-blue-800 mt-2 font-medium">
                Suggested: {formatAmountPaise(recommendedSettlement.suggested_amount)}
              </div>
            )}
            {recommendedSettlement.note && (
              <div className="text-xs text-blue-600 mt-2 italic">
                {recommendedSettlement.note}
              </div>
            )}
          </div>
        </div>
      )}

      {dispute.settlement_action && (
        <div className="border-t pt-4 mt-4">
          <h4 className="text-sm font-medium text-gray-700 mb-2">
            Settlement Applied
          </h4>
          <div className="bg-green-50 rounded p-3">
            <div className="font-medium text-green-900 capitalize">
              {dispute.settlement_action.replace('_', ' ')}
            </div>
            {dispute.settlement_amount_paise && (
              <div className="text-sm text-green-700 mt-1">
                Amount: {formatAmountPaise(dispute.settlement_amount_paise)}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
