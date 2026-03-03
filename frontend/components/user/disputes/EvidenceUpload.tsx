'use client';

/**
 * V-DISPUTE-MGMT-2026: Evidence Upload Component
 *
 * Allows investors to add evidence to their disputes.
 */

import { useState } from 'react';
import { addEvidence } from '@/lib/api/disputes';

interface EvidenceUploadProps {
  disputeId: number;
  onSuccess: () => void;
}

const EVIDENCE_TYPES = [
  { value: 'text', label: 'Text Description' },
  { value: 'transaction_reference', label: 'Transaction Reference' },
  { value: 'link', label: 'External Link' },
];

export default function EvidenceUpload({ disputeId, onSuccess }: EvidenceUploadProps) {
  const [evidenceType, setEvidenceType] = useState('text');
  const [evidenceValue, setEvidenceValue] = useState('');
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!evidenceValue.trim()) {
      setError('Please provide the evidence value');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      const response = await addEvidence(disputeId, [
        {
          type: evidenceType,
          value: evidenceValue.trim(),
          description: description.trim() || undefined,
        },
      ]);

      if (response.success) {
        setEvidenceValue('');
        setDescription('');
        onSuccess();
      } else {
        setError(response.message || 'Failed to add evidence');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to add evidence';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
        Add Evidence
      </h3>
      <p className="text-sm text-gray-600 mb-4">
        Your response has been requested. Please provide additional information.
      </p>

      <form onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="bg-red-50 border border-red-200 rounded p-2 text-red-700 text-sm">
            {error}
          </div>
        )}

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Evidence Type
          </label>
          <select
            value={evidenceType}
            onChange={(e) => setEvidenceType(e.target.value)}
            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
          >
            {EVIDENCE_TYPES.map((type) => (
              <option key={type.value} value={type.value}>
                {type.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            {evidenceType === 'link' ? 'URL' : 'Value'} *
          </label>
          <input
            type={evidenceType === 'link' ? 'url' : 'text'}
            value={evidenceValue}
            onChange={(e) => setEvidenceValue(e.target.value)}
            required
            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder={
              evidenceType === 'transaction_reference'
                ? 'e.g., TXN123456'
                : evidenceType === 'link'
                ? 'https://...'
                : 'Enter details...'
            }
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Description (optional)
          </label>
          <textarea
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            rows={2}
            className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder="Explain what this evidence shows..."
          />
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 text-sm"
        >
          {loading ? 'Submitting...' : 'Submit Evidence'}
        </button>
      </form>
    </div>
  );
}
