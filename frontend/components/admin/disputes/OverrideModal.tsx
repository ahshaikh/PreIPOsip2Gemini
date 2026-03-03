'use client';

/**
 * V-DISPUTE-MGMT-2026: Override Modal Component
 *
 * Modal for defensibility override with documented reason.
 */

import { useState } from 'react';
import { overrideDefensibility } from '@/lib/api/disputes';

interface OverrideModalProps {
  disputeId: number;
  onClose: () => void;
  onSuccess: () => void;
}

const OVERRIDE_TYPES = [
  { value: 'integrity_confirmed', label: 'Integrity Confirmed', description: 'Admin confirms data integrity despite system warning' },
  { value: 'external_evidence', label: 'External Evidence', description: 'External evidence supports the position' },
  { value: 'business_decision', label: 'Business Decision', description: 'Business decision with documented reason' },
  { value: 'data_correction_pending', label: 'Data Correction Pending', description: 'Data will be corrected, override temporary' },
];

export default function OverrideModal({ disputeId, onClose, onSuccess }: OverrideModalProps) {
  const [overrideType, setOverrideType] = useState('');
  const [reason, setReason] = useState('');
  const [evidenceReference, setEvidenceReference] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (reason.length < 20) {
      setError('Reason must be at least 20 characters');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      const response = await overrideDefensibility(
        disputeId,
        overrideType,
        reason,
        evidenceReference || undefined
      );
      if (response.success) {
        onSuccess();
      } else {
        setError(response.message || 'Override failed');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Override failed';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <div className="p-6 border-b">
          <h2 className="text-xl font-semibold">Override Defensibility</h2>
          <p className="text-sm text-gray-500 mt-1">
            Document your reason for overriding the defensibility status.
          </p>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {error && (
            <div className="bg-red-50 border border-red-200 rounded p-3 text-red-700 text-sm">
              {error}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Override Type *
            </label>
            <select
              value={overrideType}
              onChange={(e) => setOverrideType(e.target.value)}
              required
              className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select type...</option>
              {OVERRIDE_TYPES.map((type) => (
                <option key={type.value} value={type.value}>
                  {type.label}
                </option>
              ))}
            </select>
            {overrideType && (
              <p className="text-xs text-gray-500 mt-1">
                {OVERRIDE_TYPES.find((t) => t.value === overrideType)?.description}
              </p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Reason * (min 20 characters)
            </label>
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              required
              minLength={20}
              rows={4}
              className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Document the detailed reason for this override..."
            />
            <p className="text-xs text-gray-400 mt-1">
              {reason.length}/20 characters minimum
            </p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Evidence Reference (optional)
            </label>
            <input
              type="text"
              value={evidenceReference}
              onChange={(e) => setEvidenceReference(e.target.value)}
              className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Ticket ID, document reference, etc."
            />
          </div>

          <div className="flex justify-end gap-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-700 border rounded-md hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading || !overrideType || reason.length < 20}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {loading ? 'Submitting...' : 'Submit Override'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
