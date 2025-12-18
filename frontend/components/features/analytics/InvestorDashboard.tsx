'use client';

/**
 * InvestorDashboard
 * * [AUDIT FIX]: Uses pre-aggregated data for high-performance rendering.
 */
import { useEffect, useState } from 'react';
import api from '@/lib/api';
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';

export function InvestorDashboard() {
  const [metrics, setMetrics] = useState<any>(null);

  useEffect(() => {
    const fetchMetrics = async () => {
      const { data } = await api.get('/api/v1/user/portfolio/summary');
      setMetrics(data);
    };
    fetchMetrics();
  }, []);

  if (!metrics) return <div>Loading Analytics...</div>;

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="p-6 bg-white rounded shadow">
          <h3 className="text-gray-500 text-sm">Total Invested</h3>
          <p className="text-2xl font-bold">₹{metrics.total_invested_rupees.toLocaleString()}</p>
        </div>
        {/* Additional metric cards */}
      </div>

      <div className="h-[300px] w-full bg-white p-4 rounded shadow">
        <h3 className="mb-4 font-semibold">Portfolio Growth</h3>
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={metrics.growth_data}>
            <XAxis dataKey="month" />
            <YAxis />
            <Tooltip />
            <Line type="monotone" dataKey="value" stroke="#2563eb" strokeWidth={2} />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}