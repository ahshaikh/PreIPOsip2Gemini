// V-PHASE4-1730-101 (Created - Revised) | V-FINAL-1730-634 (AuthContext Fix) | V-FINAL-1730-658 (Providers Integration)
'use client';

import { Inter } from 'next/font/google';
import "./globals.css";
import { usePathname } from 'next/navigation';
import Navbar from "@/components/shared/Navbar";
import Footer from "@/components/shared/Footer";
import ScrollToTop from "@/components/shared/ScrollToTop";
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { LiveChatWidget } from '@/components/shared/LiveChatWidget';
import { Providers } from "@/components/shared/Providers";

const inter = Inter({ subsets: ['latin'], weight: ['300', '400', '500', '600', '700', '800', '900'] });

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  // Don't show public navbar/footer on dashboard or admin pages
  const isPublicPage = !pathname?.startsWith('/dashboard') &&
                       !pathname?.startsWith('/admin') &&
                       !pathname?.match(/^\/(profile|Profile|wallet|subscriptions|subscription|investments|portfolio|referrals|support|lucky-draws|settings|transactions|bonuses|kyc|compliance|materials|notifications|promote|reports|profit-sharing|offers)/);

  return (
    <html lang="en" suppressHydrationWarning>
      <body className={inter.className}>
        <Providers>
          <div className="flex flex-col min-h-screen">
            {isPublicPage && <Navbar />}
            <main className="flex-grow">{children}</main>
            {isPublicPage && <Footer />}
            <ScrollToTop />
          </div>
          <CookieConsent />
          <LiveChatWidget />
          <Toaster richColors />
        </Providers>
      </body>
    </html>
  );
}
