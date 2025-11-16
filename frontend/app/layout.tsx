// V-FINAL-1730-282 (Updated with Chat Widget) | V-FINAL-1730-433 (Chat Widget) | V-FINAL-1730-525 (Popup Banner)
'use client';

import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { Providers } from '@/components/shared/Providers';
import { Navbar } from '@/components/shared/Navbar';
import { Footer } from '@/components/shared/Footer';
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { LiveChatWidget } from '@/components/shared/LiveChatWidget';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';

const inter = Inter({ subsets: ['latin'], weight: ['300', '400', '500', '600', '700', '800', '900'] });

// We remove metadata export, as it needs to be dynamic
// export const metadata: Metadata = { ... };

/**
 * FSD-FRONT-007: Popup Banner
 * This component fetches banners and displays the first popup.
 */
function PopupBanner() {
  const [isOpen, setIsOpen] = useState(false);
  
  const { data: settings } = useQuery({
    queryKey: ['globalSettings'],
    queryFn: async () => (await api.get('/global-settings')).data,
    staleTime: 60000,
  });

  const popup = settings?.banners?.find((b: any) => b.type === 'popup' && b.is_active);

  useEffect(() => {
    if (popup) {
      const hasSeen = sessionStorage.getItem(`popup_${popup.id}`);
      if (!hasSeen) {
        setIsOpen(true);
        sessionStorage.setItem(`popup_${popup.id}`, 'true');
      }
    }
  }, [popup]);

  if (!popup) return null;

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{popup.title}</DialogTitle>
          <DialogDescription>
            <div dangerouslySetInnerHTML={{ __html: popup.content }} />
          </DialogDescription>
        </DialogHeader>
      </DialogContent>
    </Dialog>
  );
}


export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <head>
          {/* Dynamic theme/SEO will be injected here */}
      </head>
      <body className={inter.className}>
        <Providers>
          <div className="flex flex-col min-h-screen">
            <Navbar />
            <main className="flex-grow">{children}</main>
            <Footer />
          </div>
          <CookieConsent />
          <LiveChatWidget />
          <PopupBanner /> {/* <-- NEW */}
          <Toaster richColors />
        </Providers>
      </body>
    </html>
  );
}