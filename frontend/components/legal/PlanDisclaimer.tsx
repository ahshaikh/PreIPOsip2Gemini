'use client';

/**
 * PlanDisclaimer
 * * [AUDIT FIX]: Fetches dynamic disclaimer from the backend.
 * * Ensures investors see the specific risks associated with the asset.
 */
import { useEffect, useState } from 'react';
import api from '@/lib/api';

export function PlanDisclaimer({ planId }) {
  const [disclaimer, setDisclaimer] = useState('');

  useEffect(() => {
    const fetchDisclaimer = async () => {
      const { data } = await api.get(`/api/v1/plans/${planId}/disclaimer`);
      setDisclaimer(data.text);
    };
    fetchDisclaimer();
  }, [planId]);

  return (
    <div className="bg-amber-50 border-l-4 border-amber-400 p-4 mt-6">
      <p className="text-xs text-amber-800 italic">
        <strong>Important:</strong> {disclaimer}
      </p>
    </div>
  );
}