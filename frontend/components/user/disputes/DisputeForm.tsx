'use client';

/**
 * V-DISPUTE-MGMT-2026: Dispute Form Component
 *
 * Form for filing a new dispute.
 */

import { useState } from 'react';
import { fileDispute } from '@/lib/api/disputes';

interface DisputeFormProps {
  onSuccess: (disputeId: number) => void;
  onCancel: () => void;
}

const CATEGORIES = [
  { value: 'fund_transfer', label: 'Fund Transfer Issues' },
  { value: 'investment_processing', label: 'Investment Processing' },
  { value: 'financial_disclosure', label: 'Financial Disclosure' },
  { value: 'platform_service', label: 'Platform Service' },
  { value: 'other', label: 'Other' },
];

export default function DisputeForm({ onSuccess, onCancel }: DisputeFormProps) {
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [category, setCategory] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!title.trim() || !description.trim() || !category) {
      setError('Please fill in all required fields');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      const response = await fileDispute({
        title: title.trim(),
        description: description.trim(),
        category,
      });

      if (response.success) {
        onSuccess(response.data.id);
      } else {
        setError(response.message || 'Failed to file dispute');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to file dispute';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
      {error && (
        <div className="bg-red-50 border border-red-200 rounded p-3 text-red-700 text-sm">
          {error}
        </div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Category *
        </label>
        <select
          value={category}
          onChange={(e) => setCategory(e.target.value)}
          required
          className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">Select a category...</option>
          {CATEGORIES.map((cat) => (
            <option key={cat.value} value={cat.value}>
              {cat.label}
            </option>
          ))}
        </select>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Title *
        </label>
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          required
          maxLength={200}
          className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="Brief summary of the issue"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">
          Description *
        </label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          required
          rows={6}
          className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="Please describe the issue in detail. Include any relevant dates, amounts, or reference numbers."
        />
        <p className="text-xs text-gray-400 mt-1">
          Be as specific as possible to help us investigate your dispute.
        </p>
      </div>

      <div className="flex justify-end gap-3 pt-4 border-t">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 text-gray-700 border rounded-md hover:bg-gray-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={loading}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {loading ? 'Submitting...' : 'Submit Dispute'}
        </button>
      </div>
    </form>
  );
}
