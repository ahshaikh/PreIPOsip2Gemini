'use client';

/**
 * AuditTimeline
 * * [AUDIT FIX]: Provides transparent "Diff" view of system changes.
 */
import { format } from 'date-fns';

export function AuditTimeline({ logs }) {
  return (
    <div className="flow-root">
      <ul className="-mb-8">
        {logs.map((log, idx) => (
          <li key={log.id}>
            <div className="relative pb-8">
              <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" />
              <div className="relative flex space-x-3">
                <div className="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                  <div>
                    <p className="text-sm text-gray-500">
                      {log.action} by <span className="font-medium text-gray-900">{log.user?.name}</span>
                    </p>
                    {/* [AUDIT FIX]: Display the specific field changes */}
                    {log.new_values && (
                      <div className="mt-2 text-xs bg-gray-50 p-2 rounded">
                        {Object.keys(log.new_values).map(key => (
                          <div key={key}>
                            <span className="font-bold">{key}</span>: 
                            <span className="text-red-500 line-through mx-1">{log.old_values[key]}</span> → 
                            <span className="text-green-600">{log.new_values[key]}</span>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                  <div className="whitespace-nowrap text-right text-sm text-gray-500">
                    {format(new Date(log.created_at), 'MMM d, h:mm a')}
                  </div>
                </div>
              </div>
            </div>
          </li>
        ))}
      </ul>
    </div>
  );
}