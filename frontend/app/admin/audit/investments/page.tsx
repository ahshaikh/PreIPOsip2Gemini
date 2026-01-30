/**
 * EPIC 6 - Audit & Regulator Views
 *
 * Investments List - Navigation to individual investment reconstructions
 *
 * INVARIANTS:
 * - READ-ONLY list view
 * - Links to reconstruction pages
 * - Paginated for large datasets
 */

"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Loader2, AlertCircle, FileSearch, ExternalLink } from "lucide-react";
import {
  listAuditInvestments,
  type AuditListItem,
  type PaginatedResponse,
} from "@/lib/auditApi";
import { AuditTimestamp } from "@/components/audit";

export default function AuditInvestmentsListPage() {
  const [data, setData] = useState<PaginatedResponse<AuditListItem> | null>(
    null
  );
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  useEffect(() => {
    async function loadData() {
      setLoading(true);
      try {
        const result = await listAuditInvestments({ page, per_page: 20 });
        setData(result);
      } catch (err) {
        console.error("[Audit] Failed to load investments:", err);
        setError("Failed to load investments list.");
      } finally {
        setLoading(false);
      }
    }

    loadData();
  }, [page]);

  if (loading && !data) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2">
          <Loader2 className="w-8 h-8 animate-spin text-slate-400" />
          <span className="text-sm font-mono text-slate-500">
            Loading investments...
          </span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <div className="flex flex-col items-center gap-2 text-center">
          <AlertCircle className="w-8 h-8 text-red-500" />
          <p className="text-sm font-mono text-red-600 dark:text-red-400">
            {error}
          </p>
        </div>
      </div>
    );
  }

  const investments = data?.data || [];
  const meta = data?.meta;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">
          Investment Reconstructions
        </h1>
        <p className="text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
          Select an investment to view complete reconstruction of what was shown
          to investor at purchase time.
        </p>
      </div>

      {/* List */}
      {investments.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-12 bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
          <FileSearch className="w-12 h-12 text-slate-300 dark:text-slate-600 mb-4" />
          <p className="text-sm font-mono text-slate-500">
            No investments found.
          </p>
        </div>
      ) : (
        <div className="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                <th className="text-left px-4 py-3 text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                  Investment ID
                </th>
                <th className="text-left px-4 py-3 text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                  Details
                </th>
                <th className="text-left px-4 py-3 text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                  Timestamp
                </th>
                <th className="text-left px-4 py-3 text-xs font-mono font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                  Status
                </th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              {investments.map((item) => (
                <tr
                  key={item.id}
                  className="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                >
                  <td className="px-4 py-3">
                    <span className="font-mono text-sm font-semibold text-slate-800 dark:text-slate-200">
                      #{item.id}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm text-slate-700 dark:text-slate-300">
                      {item.title}
                    </span>
                    {item.subtitle && (
                      <span className="block text-xs text-slate-500">
                        {item.subtitle}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <AuditTimestamp
                      timestamp={item.timestamp}
                      showTime
                      showLocal={false}
                      className="text-xs"
                    />
                  </td>
                  <td className="px-4 py-3">
                    {item.status && (
                      <span
                        className={`inline-block px-2 py-0.5 text-xs font-mono uppercase rounded ${
                          item.status === "completed"
                            ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                            : "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                        }`}
                      >
                        {item.status}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Link
                      href={`/admin/audit/investments/${item.id}`}
                      className="inline-flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                      View
                      <ExternalLink className="w-3 h-3" />
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-between pt-4">
          <span className="text-sm font-mono text-slate-500">
            Page {meta.current_page} of {meta.last_page} ({meta.total} total)
          </span>
          <div className="flex gap-2">
            <button
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={page === 1}
              className="px-3 py-1 text-sm font-mono border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={page === meta.last_page}
              className="px-3 py-1 text-sm font-mono border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
