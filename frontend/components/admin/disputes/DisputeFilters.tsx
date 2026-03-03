'use client';

/**
 * V-DISPUTE-MGMT-2026: Dispute Filters Component
 */

import { DisputeFilters as FilterType } from '@/lib/api/disputes';

interface DisputeFiltersProps {
  filters: FilterType;
  onFilterChange: (filters: Partial<FilterType>) => void;
}

const STATUS_OPTIONS = [
  { value: '', label: 'All Statuses' },
  { value: 'open', label: 'Open' },
  { value: 'under_review', label: 'Under Review' },
  { value: 'awaiting_investor', label: 'Awaiting Investor' },
  { value: 'escalated', label: 'Escalated' },
  { value: 'resolved_approved', label: 'Resolved (Approved)' },
  { value: 'resolved_rejected', label: 'Resolved (Rejected)' },
  { value: 'closed', label: 'Closed' },
];

const TYPE_OPTIONS = [
  { value: '', label: 'All Types' },
  { value: 'confusion', label: 'Confusion' },
  { value: 'payment', label: 'Payment' },
  { value: 'allocation', label: 'Allocation' },
  { value: 'fraud', label: 'Fraud' },
];

const SEVERITY_OPTIONS = [
  { value: '', label: 'All Severities' },
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
];

export default function DisputeFilters({ filters, onFilterChange }: DisputeFiltersProps) {
  return (
    <div className="flex flex-wrap gap-4">
      <div>
        <label className="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select
          value={filters.status || ''}
          onChange={(e) => onFilterChange({ status: e.target.value || undefined })}
          className="block w-40 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          {STATUS_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>

      <div>
        <label className="block text-xs font-medium text-gray-500 mb-1">Type</label>
        <select
          value={filters.type || ''}
          onChange={(e) => onFilterChange({ type: e.target.value || undefined })}
          className="block w-36 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          {TYPE_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>

      <div>
        <label className="block text-xs font-medium text-gray-500 mb-1">Severity</label>
        <select
          value={filters.severity || ''}
          onChange={(e) => onFilterChange({ severity: e.target.value || undefined })}
          className="block w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
          {SEVERITY_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>

      <div className="flex items-end gap-2">
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={filters.active_only || false}
            onChange={(e) => onFilterChange({ active_only: e.target.checked || undefined })}
            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          Active only
        </label>
      </div>

      <div className="flex items-end gap-2">
        <label className="flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={filters.sla_breached || false}
            onChange={(e) => onFilterChange({ sla_breached: e.target.checked || undefined })}
            className="rounded border-gray-300 text-red-600 focus:ring-red-500"
          />
          SLA Breached
        </label>
      </div>
    </div>
  );
}
