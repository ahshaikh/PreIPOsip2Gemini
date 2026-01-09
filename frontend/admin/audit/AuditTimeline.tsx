'use client';

/**
 * AuditTimeline
 * [AUDIT FIX]: Provides transparent "Diff" view of system changes.
 */
import { format } from 'date-fns';

// Define the structure of a single audit log entry
interface AuditLog {
  id: string | number;
  action: string;
  created_at: string;
  user?: {
    name: string;
  };
  // Record<string, any> allows for dynamic key-value pairs in the diff view
  old_values?: Record<string, any>;
  new_values?: Record<string, any>;
}

// Define the component props
interface AuditTimelineProps {
  logs: AuditLog[];
}

export function AuditTimeline({ logs }: AuditTimelineProps) {
  return (
    <div className="flow-root">
      <ul className="-mb-8">
        {logs.map((log, idx) => (
          <li key={log.id}>
            <div className="relative pb-8">
              {/* Only show the line if it's not the last item */}
              {idx !== logs.length - 1 && (
                <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true" />
              )}
              
              <div className="relative flex space-x-3">
                <div className="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                  <div>
                    <p className="text-sm text-gray-500">
                      {log.action} by <span className="font-medium text-gray-900">{log.user?.name || 'System'}</span>
                    </p>
                    
                    {/* [AUDIT FIX]: Display the specific field changes */}
                    {log.new_values && (
                      <div className="mt-2 text-xs bg-gray-50 p-2 rounded border border-gray-100">
                        {Object.keys(log.new_values).map(key => (
                          <div key={key} className="py-0.5">
                            <span className="font-bold text-gray-600">{key}</span>: 
                            <span className="text-red-500 line-through mx-1">
                              {log.old_values ? String(log.old_values[key]) : 'N/A'}
                            </span> 
                            <span className="text-gray-400">→</span> 
                            <span className="text-green-600 ml-1 font-medium">
                              {String(log.new_values![key])}
                            </span>
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