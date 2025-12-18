'use client';

/**
 * InvestorCRMList
 * * [AUDIT FIX]: Real-time filtering by lead score and status.
 */
import { useState, useEffect } from 'react';
import api from '@/lib/api';

export function InvestorCRMList() {
  const [investors, setInvestors] = useState<any[]>([]);
  const [filter, setFilter] = useState('Hot');

  useEffect(() => {
    const fetchInvestors = async () => {
      const { data } = await api.get(`/api/v1/admin/crm/investors?category=${filter}`);
      setInvestors(data);
    };
    fetchInvestors();
  }, [filter]);

  return (
    <div className="space-y-4">
      <div className="flex gap-2">
        {['Hot', 'Warm', 'Cold'].map(cat => (
          <button 
            key={cat}
            onClick={() => setFilter(cat)}
            className={`px-4 py-1 rounded ${filter === cat ? 'bg-blue-600 text-white' : 'bg-gray-100'}`}
          >
            {cat} Leads
          </button>
        ))}
      </div>

      <table className="w-full border-collapse">
        <thead className="bg-gray-50">
          <tr><th>Investor</th><th>Score</th><th>Engagement</th></tr>
        </thead>
        <tbody>
          {investors.map(inv => (
            <tr key={inv.id} className="border-b">
              <td className="p-2">{inv.email}</td>
              <td className="p-2 font-bold">{inv.lead_score}</td>
              <td className="p-2 text-xs">{inv.last_active}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}