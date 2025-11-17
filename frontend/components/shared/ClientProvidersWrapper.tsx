'use client';

import { Providers } from '@/components/shared/Providers';
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { LiveChatWidget } from '@/components/shared/LiveChatWidget';
import PopupBanner from '@/components/shared/PopupBanner';

export default function ClientProvidersWrapper({ children }: { children: React.ReactNode }) {
  return (
    <Providers>
  {children}

  {/* FIX: Wrap all floating widgets to prevent stray DOM nodes */}
  <div id="client-widgets">
    <CookieConsent />
    <LiveChatWidget />
    <PopupBanner />
    <Toaster richColors />
  </div>
</Providers>

  );
}
