// V-FINAL-1730-282 (Updated with Chat Widget) | V-FINAL-1730-433 (Chat Widget) | V-FINAL-1730-525 (Popup Banner) | V-FINAL-1730-634 (AuthContext Fix)
// C:\PreIPOsip\frontend\app\layout.tsx

import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { Providers } from '@/components/shared/Providers';
import Navbar from '@/components/shared/Navbar';
import Footer from '@/components/shared/Footer';
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { LiveChatWidget } from '@/components/shared/LiveChatWidget';
import { AuthProvider } from '@/context/AuthContext'; // <-- THE IMPORT IS FIXED

const inter = Inter({ subsets: ['latin'], weight: ['300', '400', '500', '600', '700', '800', '900'] });

export const metadata: Metadata = {
  title: 'PreIPO SIP - Invest in Pre-IPOs with Zero Fees',
  description: "India's First 100% FREE Pre-IPO SIP Platform with 10% Bonuses.",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className={inter.className}>
        <Providers>
          <AuthProvider> {/* <-- AuthProvider now wraps everything */}
            <div className="flex flex-col min-h-screen">
              <Navbar />
              <main className="flex-grow">{children}</main>
              <Footer />
            </div>
            <CookieConsent />
            <LiveChatWidget />
            <Toaster richColors />
          </AuthProvider>
        </Providers>
      </body>
    </html>
  );
}