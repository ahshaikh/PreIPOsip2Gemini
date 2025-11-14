// V-FINAL-1730-282 (Updated with Chat Widget) | V-FINAL-1730-433 (Chat Widget)
import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { Providers } from '@/components/shared/Providers';
import { Navbar } from '@/components/shared/Navbar';
import { Footer } from '@/components/shared/Footer';
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { LiveChatWidget } from '@/components/shared/LiveChatWidget'; // <-- 1. IMPORT

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
          <div className="flex flex-col min-h-screen">
            <Navbar />
            <main className="flex-grow">{children}</main>
            <Footer />
          </div>
          <CookieConsent />
          <LiveChatWidget /> {/* <-- 2. ADD WIDGET HERE */}
          <Toaster richColors />
        </Providers>
      </body>
    </html>
  );
}