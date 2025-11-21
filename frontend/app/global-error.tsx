'use client';

import { useEffect } from 'react';

export default function GlobalError({ error, reset }: { error: Error; reset: () => void }) {
  useEffect(() => {
    console.error('Global Error:', error);
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
