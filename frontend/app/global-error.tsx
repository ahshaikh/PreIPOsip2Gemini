'use client';

import { useEffect } from 'react';

export default function GlobalError({ error, reset }: { error: Error; reset: () => void }) {
  useEffect(() => {
    // Only log errors in development mode
    if (process.env.NODE_ENV === 'development') {
      console.error('Global Error:', error);
    }

    // Send to Sentry in production
    if (process.env.NODE_ENV === 'production' && process.env.NEXT_PUBLIC_SENTRY_DSN) {
      import('@sentry/nextjs').then((Sentry) => {
        Sentry.captureException(error);
      });
    }
  }, [error]);

  return (
    <html>
      <body className="h-screen w-screen flex items-center justify-center bg-red-50 text-red-800">
        <div className="space-y-4 text-center">
          <h2 className="text-2xl font-bold">Something went wrong.</h2>
          <p>{error.message}</p>

          <button
            onClick={reset}
            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
          >
            Try again
          </button>
        </div>
      </body>
    </html>
  );
}
