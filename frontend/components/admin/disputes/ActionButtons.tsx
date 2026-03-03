'use client';

/**
 * V-DISPUTE-MGMT-2026: Action Buttons Component
 *
 * Renders action buttons based on backend permission flags.
 * CRITICAL: Frontend does NOT derive permissions from status.
 */

import { useState } from 'react';
import { Dispute, DisputePermissions } from '@/lib/api/disputes';

interface ActionButtonsProps {
  permissions: DisputePermissions;
  dispute: Dispute;
  loading: boolean;
  onTransition: (status: string, comment?: string) => void;
  onEscalate: (reason: string) => void;
  onClose: (notes?: string) => void;
  onResolve: () => void;
  onOverride: () => void;
}

export default function ActionButtons({
  permissions,
  dispute,
  loading,
  onTransition,
  onEscalate,
  onClose,
  onResolve,
  onOverride,
}: ActionButtonsProps) {
  const [showTransitionMenu, setShowTransitionMenu] = useState(false);
  const [showEscalateDialog, setShowEscalateDialog] = useState(false);
  const [showCloseDialog, setShowCloseDialog] = useState(false);
  const [escalateReason, setEscalateReason] = useState('');
  const [closeNotes, setCloseNotes] = useState('');

  const handleEscalate = () => {
    if (escalateReason.trim()) {
      onEscalate(escalateReason);
      setShowEscalateDialog(false);
      setEscalateReason('');
    }
  };

  const handleClose = () => {
    onClose(closeNotes || undefined);
    setShowCloseDialog(false);
    setCloseNotes('');
  };

  return (
    <div className="flex flex-wrap gap-2">
      {/* Transition Button */}
      {permissions.can_transition && permissions.available_transitions.length > 0 && (
        <div className="relative">
          <button
            onClick={() => setShowTransitionMenu(!showTransitionMenu)}
            disabled={loading}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            Transition
          </button>
          {showTransitionMenu && (
            <div className="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border">
              {permissions.available_transitions.map((status) => (
                <button
                  key={status}
                  onClick={() => {
                    onTransition(status);
                    setShowTransitionMenu(false);
                  }}
                  className="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 capitalize"
                >
                  {status.replace('_', ' ')}
                </button>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Escalate Button */}
      {permissions.can_escalate && (
        <>
          <button
            onClick={() => setShowEscalateDialog(true)}
            disabled={loading}
            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
          >
            Escalate
          </button>
          {showEscalateDialog && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 className="text-lg font-semibold mb-4">Escalate Dispute</h3>
                <textarea
                  value={escalateReason}
                  onChange={(e) => setEscalateReason(e.target.value)}
                  placeholder="Enter reason for escalation..."
                  rows={3}
                  className="w-full rounded-md border-gray-300 mb-4"
                />
                <div className="flex justify-end gap-2">
                  <button
                    onClick={() => setShowEscalateDialog(false)}
                    className="px-4 py-2 border rounded-md"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleEscalate}
                    disabled={!escalateReason.trim()}
                    className="px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50"
                  >
                    Escalate
                  </button>
                </div>
              </div>
            </div>
          )}
        </>
      )}

      {/* Resolve Button */}
      {permissions.can_resolve && (
        <button
          onClick={onResolve}
          disabled={loading}
          className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
        >
          Resolve
        </button>
      )}

      {/* Close Button */}
      {permissions.can_close && (
        <>
          <button
            onClick={() => setShowCloseDialog(true)}
            disabled={loading}
            className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 disabled:opacity-50"
          >
            Close
          </button>
          {showCloseDialog && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 className="text-lg font-semibold mb-4">Close Dispute</h3>
                <textarea
                  value={closeNotes}
                  onChange={(e) => setCloseNotes(e.target.value)}
                  placeholder="Optional closing notes..."
                  rows={3}
                  className="w-full rounded-md border-gray-300 mb-4"
                />
                <div className="flex justify-end gap-2">
                  <button
                    onClick={() => setShowCloseDialog(false)}
                    className="px-4 py-2 border rounded-md"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleClose}
                    className="px-4 py-2 bg-gray-600 text-white rounded-md"
                  >
                    Close Dispute
                  </button>
                </div>
              </div>
            </div>
          )}
        </>
      )}

      {/* Override Button */}
      {permissions.can_override_defensibility && (
        <button
          onClick={onOverride}
          disabled={loading}
          className="px-4 py-2 border border-yellow-500 text-yellow-700 rounded-md hover:bg-yellow-50 disabled:opacity-50"
        >
          Override
        </button>
      )}
    </div>
  );
}
