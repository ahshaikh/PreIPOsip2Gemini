'use client';

/**
 * V-DISPUTE-MGMT-2026: Timeline View Component
 *
 * Displays dispute timeline events in chronological order.
 */

import { DisputeTimeline } from '@/lib/api/disputes';

interface TimelineViewProps {
  timeline: DisputeTimeline[];
}

const EVENT_ICONS: Record<string, string> = {
  created: '📝',
  status_change: '🔄',
  comment: '💬',
  evidence_added: '📎',
  assigned: '👤',
  escalated: '⚠️',
  settlement: '💰',
  sla_warning: '⏰',
  sla_breach: '🚨',
  auto_escalation: '🔺',
};

const ROLE_COLORS: Record<string, string> = {
  admin: 'text-blue-600',
  investor: 'text-purple-600',
  system: 'text-gray-500',
};

export default function TimelineView({ timeline }: TimelineViewProps) {
  if (timeline.length === 0) {
    return (
      <div className="text-gray-500 text-sm">No timeline events yet.</div>
    );
  }

  return (
    <div className="relative">
      <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200" />

      <div className="space-y-4">
        {timeline.map((event) => (
          <div key={event.id} className="relative pl-10">
            <div className="absolute left-2 w-5 h-5 bg-white border-2 border-gray-300 rounded-full flex items-center justify-center text-xs">
              {EVENT_ICONS[event.event_type] || '•'}
            </div>

            <div className="bg-gray-50 rounded-lg p-3">
              <div className="flex items-start justify-between">
                <div>
                  <div className="font-medium text-gray-900">{event.title}</div>
                  {event.description && (
                    <div className="text-sm text-gray-600 mt-1">
                      {event.description}
                    </div>
                  )}
                </div>
                <span className={`text-xs ${ROLE_COLORS[event.actor_role] || 'text-gray-500'}`}>
                  {event.actor_role}
                </span>
              </div>

              {event.old_status && event.new_status && (
                <div className="mt-2 text-xs text-gray-500">
                  <span className="bg-gray-200 px-1 rounded">{event.old_status}</span>
                  {' → '}
                  <span className="bg-gray-200 px-1 rounded">{event.new_status}</span>
                </div>
              )}

              <div className="mt-2 text-xs text-gray-400">
                {new Date(event.created_at).toLocaleString()}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
