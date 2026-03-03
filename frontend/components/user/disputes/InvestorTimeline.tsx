'use client';

/**
 * V-DISPUTE-MGMT-2026: Investor Timeline Component
 *
 * Shows timeline events visible to investors.
 */

import { DisputeTimeline } from '@/lib/api/disputes';

interface InvestorTimelineProps {
  timeline: DisputeTimeline[];
}

export default function InvestorTimeline({ timeline }: InvestorTimelineProps) {
  // Filter to only show investor-visible events
  const visibleEvents = timeline.filter(
    (event) => event.visible_to_investor
  );

  if (visibleEvents.length === 0) {
    return (
      <div className="text-gray-500 text-sm">
        No updates yet. We will notify you when there are updates.
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {visibleEvents.map((event) => (
        <div key={event.id} className="border-l-2 border-blue-200 pl-4 py-2">
          <div className="flex items-start justify-between">
            <div>
              <div className="font-medium text-gray-900">{event.title}</div>
              {event.description && (
                <div className="text-sm text-gray-600 mt-1">
                  {event.description}
                </div>
              )}
            </div>
            <span className="text-xs text-gray-400 whitespace-nowrap ml-4">
              {formatDate(event.created_at)}
            </span>
          </div>
        </div>
      ))}
    </div>
  );
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  const now = new Date();
  const diffDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } else if (diffDays === 1) {
    return 'Yesterday';
  } else if (diffDays < 7) {
    return `${diffDays} days ago`;
  } else {
    return date.toLocaleDateString();
  }
}
