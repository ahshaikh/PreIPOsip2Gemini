'use client';

/**
 * V-DISPUTE-MGMT-2026: Investor Disputes List Page
 *
 * Lists all disputes filed by the current investor.
 */

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { getUserDisputes, Dispute } from '@/lib/api/disputes';
import MyDisputesTable from '@/components/user/disputes/MyDisputesTable';

export default function UserDisputesPage() {
  const router = useRouter();
  const [disputes, setDisputes] = useState<Dispute[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchDisputes();
  }, []);

  const fetchDisputes = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await getUserDisputes();
      if (response.success) {
        setDisputes(response.data);
      } else {
        setError(response.message || 'Failed to fetch disputes');
      }
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to fetch disputes';
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  const handleDisputeClick = (disputeId: number) => {
    router.push(`/disputes/${disputeId}`);
  };

  const handleNewDispute = () => {
    router.push('/disputes/new');
  };

  return (
    <div className="container mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">My Disputes</h1>
          <p className="text-gray-600 mt-1">
            View and track your submitted disputes.
          </p>
        </div>
        <button
          onClick={handleNewDispute}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          File New Dispute
        </button>
      </div>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-red-700">{error}</p>
        </div>
      )}

      <div className="bg-white rounded-lg shadow">
        <MyDisputesTable
          disputes={disputes}
          loading={loading}
          onDisputeClick={handleDisputeClick}
        />
      </div>
    </div>
  );
}
