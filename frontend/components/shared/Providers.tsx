// V-PHASE4-1730-102
'use client';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React, { useState } from 'react';

export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(() => new QueryClient());

  return (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );
}