// Dynamic Admin Modals - Lazy loaded to reduce initial bundle size
'use client';

import dynamic from 'next/dynamic';

// Loading fallback for modals
const ModalSkeleton = () => (
  <div className="flex items-center justify-center p-8">
    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
  </div>
);

// Dynamically import heavy modal components
export const DynamicKycVerificationModal = dynamic(
  () => import('./KycVerificationModal').then((mod) => ({ default: mod.KycVerificationModal })),
  {
    loading: () => <ModalSkeleton />,
    ssr: false, // Modals don't need SSR
  }
);

export const DynamicWithdrawalProcessModal = dynamic(
  () => import('./WithdrawalProcessModal').then((mod) => ({ default: mod.WithdrawalProcessModal })),
  {
    loading: () => <ModalSkeleton />,
    ssr: false,
  }
);
