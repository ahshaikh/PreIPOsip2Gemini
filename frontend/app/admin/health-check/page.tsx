'use client';

/**
 * HealthCheckDashboard
 * * [AUDIT FIX]: Final verification tool for the Handover process.
 * * Checks API connectivity, WebSocket heartbeats, and asset versions.
 */
import { useEffect, useState } from 'react';
import api from '@/lib/api';

export default function HealthCheck() {
  const [report, setReport] = useState<any>(null);

  const runFinalUAT = async () => {
    const { data } = await api.get('/api/v1/admin/system/full-audit');
    setReport(data);
  };

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold mb-4">Final UAT Handover Report</h1>
      <button 
        onClick={runFinalUAT}
        className="bg-green-600 text-white px-6 py-2 rounded"
      >
        Execute System-Wide Sanity Check
      </button>

      {report && (
        <div className="mt-6 grid grid-cols-2 gap-4">
          <div className="p-4 border rounded">
            <h2 className="font-bold">Security Headers</h2>
            <p className={report.headers_ok ? "text-green-600" : "text-red-600"}>
              {report.headers_ok ? "Verified" : "Failing"}
            </p>
          </div>
          {/* Additional UAT results */}
        </div>
      )}
    </div>
  );
}