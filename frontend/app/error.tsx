'use client';

import { useEffect } from 'react';
import { Button } from "@/components/ui/button";

export default function Error({
  error,
  reset,
}: {
  error: Error;
  reset: () => void;
}) {
  useEffect(() => {
    console.error("Route Error:", error);
  }, [error]);

  return (
    <div className="h-[60vh] flex items-center justify-center">
      <div className="bg-white p-10 rounded-xl shadow-lg text-center max-w-md space-y-4 border">
        <h2 className="text-xl font-semibold">Error loading this page</h2>
        <p className="text-sm text-muted-foreground">
          {error?.message || "An error occurred while rendering the page."}
        </p>

        <div className="flex justify-center gap-2">
          <Button variant="outline" onClick={() => window.location.reload()}>
            Reload Page
          </Button>

          <Button variant="default" onClick={reset}>
            Try Again
          </Button>
        </div>
      </div>
    </div>
  );
}
