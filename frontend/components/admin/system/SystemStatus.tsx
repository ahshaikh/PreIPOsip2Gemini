'use client';

/**
 * SystemStatus
 * * [AUDIT FIX]: Provides real-time heartbeat monitoring for Admins.
 */
import { useEffect, useState } from 'react';
import api from '@/lib/api';
import { ShieldCheck, ShieldAlert, Database, Server } from 'lucide-react';

export function SystemStatus() {
  const [status, setStatus] = useState<any>(null);

  useEffect(() => {
    const checkHealth = async () => {
      try {
        const { data } = await api.get('/api/v1/admin/system/health');
        setStatus(data);
      } catch (e) {
        setStatus({ healthy: false });
      }
    };
    checkHealth();
    const interval = setInterval(checkHealth, 30000); // Check every 30s
    return () => clearInterval(interval);
  }, []);

  if (!status) return <div>Checking pulses...</div>;

  return (
    <div className="p-4 border rounded bg-slate-50">
      <div className="flex items-center justify-between mb-4">
        <h3 className="font-bold flex items-center gap-2">
          {status.healthy ? <ShieldCheck className="text-green-500" /> : <ShieldAlert className="text-red-500" />}
          System Health
        </h3>
        <span className={status.healthy ? "text-green-600" : "text-red-600"}>
          {status.healthy ? "Operational" : "Degraded"}
        </span>
      </div>

      <div className="grid grid-cols-2 gap-2 text-xs">
        <div className="flex items-center gap-2 p-2 bg-white rounded border">
          <Database className="h-4 w-4" /> DB: {status.services?.database}
        </div>
        <div className="flex items-center gap-2 p-2 bg-white rounded border">
          <Server className="h-4 w-4" /> Redis: {status.services?.redis}
        </div>
      </div>
    </div>
  );
}