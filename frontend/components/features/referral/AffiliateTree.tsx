'use client';

/**
 * AffiliateTree
 * * [AUDIT FIX]: Visualizes multi-level referral performance.
 */
import { useEffect, useState } from 'react';
import api from '@/lib/api';

export function AffiliateTree() {
  const [stats, setStats] = useState<any>(null);

  useEffect(() => {
    const fetchStats = async () => {
      const { data } = await api.get('/api/v1/user/referrals/stats');
      setStats(data);
    };
    fetchStats();
  }, []);

  if (!stats) return null;

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div className="p-4 border rounded bg-white">
        <label className="text-xs text-muted-foreground uppercase">Level 1 (Direct)</label>
        <p className="text-xl font-bold">{stats.level1_count} Users</p>
        <p className="text-sm text-green-600">Earnings: ₹{stats.level1_earnings}</p>
      </div>
      
      <div className="p-4 border rounded bg-white">
        <label className="text-xs text-muted-foreground uppercase">Level 2 (Indirect)</label>
        <p className="text-xl font-bold">{stats.level2_count} Users</p>
        <p className="text-sm text-green-600">Earnings: ₹{stats.level2_earnings}</p>
      </div>

      <div className="p-4 border rounded bg-white">
        <label className="text-xs text-muted-foreground uppercase">Pending Payouts</label>
        <p className="text-xl font-bold text-amber-600">₹{stats.total_pending}</p>
      </div>
    </div>
  );
}