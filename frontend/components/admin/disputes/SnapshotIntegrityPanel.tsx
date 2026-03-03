'use client';

/**
 * V-DISPUTE-MGMT-2026: Snapshot Integrity Panel
 *
 * Displays snapshot integrity verification status from backend.
 */

import { DisputeIntegrity } from '@/lib/api/disputes';

interface SnapshotIntegrityPanelProps {
  integrity: DisputeIntegrity;
}

export default function SnapshotIntegrityPanel({ integrity }: SnapshotIntegrityPanelProps) {
  const isValid = integrity.valid;

  return (
    <div className={`rounded-lg shadow p-6 ${isValid ? 'bg-white' : 'bg-red-50 border border-red-200'}`}>
      <h3 className="text-sm font-semibold text-gray-500 uppercase mb-3">
        Snapshot Integrity
      </h3>

      <div className="flex items-center gap-2 mb-4">
        {isValid ? (
          <>
            <div className="w-3 h-3 bg-green-500 rounded-full" />
            <span className="text-green-700 font-medium">Verified</span>
          </>
        ) : (
          <>
            <div className="w-3 h-3 bg-red-500 rounded-full" />
            <span className="text-red-700 font-medium">Integrity Issue</span>
          </>
        )}
      </div>

      {integrity.error && (
        <div className="text-sm text-red-600 mb-3">
          {integrity.error}
        </div>
      )}

      <div className="space-y-2 text-xs">
        {integrity.stored_hash && (
          <div>
            <span className="text-gray-500">Stored Hash:</span>
            <code className="ml-2 bg-gray-100 px-1 rounded text-gray-700 break-all">
              {integrity.stored_hash.substring(0, 16)}...
            </code>
          </div>
        )}
        {integrity.computed_hash && (
          <div>
            <span className="text-gray-500">Computed Hash:</span>
            <code className="ml-2 bg-gray-100 px-1 rounded text-gray-700 break-all">
              {integrity.computed_hash.substring(0, 16)}...
            </code>
          </div>
        )}
      </div>

      {!isValid && (
        <div className="mt-4 p-3 bg-red-100 rounded text-sm text-red-800">
          Resolution may be blocked until integrity is verified or overridden.
        </div>
      )}
    </div>
  );
}
