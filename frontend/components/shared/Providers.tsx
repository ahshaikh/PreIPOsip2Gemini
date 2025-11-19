// V-PHASE4-1730-102 (Created) | // V-FINAL-1730-657 (Updated)

'use client';

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React, { useState } from 'react';
import { AuthProvider } from '@/context/AuthContext'; // <-- IMPORT

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Sensible defaults for a fintech app
      staleTime: 1000 * 60 * 5, // Data is considered fresh for 5 minutes
      refetchOnWindowFocus: false,
      retry: 1, // Only retry once on failure
    },
  },
});

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <QueryClientProvider client={queryClient}>
	<AuthProvider>
      	{children}
	</AuthProvider>
    </QueryClientProvider>
  );
}