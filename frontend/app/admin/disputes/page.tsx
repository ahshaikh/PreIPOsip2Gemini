'use client';

/**
 * V-DISPUTE-MGMT-2026: Admin Dispute Dashboard
 *
 * Lists all disputes with filtering and permission-aware actions.
 * Permission flags come from the backend - frontend does NOT derive from status.
 */

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { getAdminDisputes, DisputeFilters as DisputeFiltersType, DisputeWithPermissions } from '@/lib/api/disputes';
import DisputeTable from '@/components/admin/disputes/DisputeTable';
import DisputeFiltersComponent from '@/components/admin/disputes/DisputeFilters';
import DisputeSummaryBar from '@/components/admin/disputes/DisputeSummaryBar';

export default function AdminDisputesPage() {
  const router = useRouter();
  const [disputes, setDisputes] = useState<DisputeWithPermissions[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filters, setFilters] = useState<DisputeFiltersType>({
    per_page: 15,
    page: 1,
  });
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  });

  useEffect(() => {
    fetchDisputes();
  }, [filters]);

  const fetchDisputes = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await getAdminDisputes(filters);
      if (response.success) {
        setDisputes(response.data);
        setMeta(response.meta);
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

  const handleFilterChange = (newFilters: Partial<DisputeFiltersType>) => {
    setFilters(prev => ({ ...prev, ...newFilters, page: 1 }));
  };

  const handlePageChange = (page: number) => {
    setFilters(prev => ({ ...prev, page }));
  };

  const handleDisputeClick = (disputeId: number) => {
    router.push(`/admin/disputes/${disputeId}`);
  };

  return (
    <div className="container mx-auto px-4 py-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Dispute Management</h1>
        <p className="text-gray-600 mt-1">
          Review and manage investor disputes. Actions are permission-controlled.
        </p>
      </div>

      <DisputeSummaryBar disputes={disputes} />

      <div className="mt-6 bg-white rounded-lg shadow">
        <div className="p-4 border-b">
          <DisputeFiltersComponent
            filters={filters}
            onFilterChange={handleFilterChange}
          />
        </div>

        {error && (
          <div className="p-4 bg-red-50 border-b border-red-200">
            <p className="text-red-700">{error}</p>
          </div>
        )}

        <DisputeTable
          disputes={disputes}
          loading={loading}
          onDisputeClick={handleDisputeClick}
        />

        {/* Pagination */}
        {meta.last_page > 1 && (
          <div className="px-4 py-3 border-t flex items-center justify-between">
            <div className="text-sm text-gray-700">
              Showing page {meta.current_page} of {meta.last_page} ({meta.total} total)
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => handlePageChange(meta.current_page - 1)}
                disabled={meta.current_page <= 1}
                className="px-3 py-1 border rounded text-sm disabled:opacity-50"
              >
                Previous
              </button>
              <button
                onClick={() => handlePageChange(meta.current_page + 1)}
                disabled={meta.current_page >= meta.last_page}
                className="px-3 py-1 border rounded text-sm disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
