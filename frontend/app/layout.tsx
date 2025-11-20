// V-PHASE4-1730-101 (Created) | V-FINAL-1730-634 (AuthContext Fix) | V-FINAL-1730-658 (Providers Integration)

import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import "./globals.css";
import Navbar from "@/components/shared/Navbar";
import Footer from "@/components/shared/Footer";
import ScrollToTop from "@/components/shared/ScrollToTop";
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { LiveChatWidget } from '@/components/shared/LiveChatWidget';
import { Providers } from "@/components/shared/Providers";
import { AuthProvider } from "@/context/AuthContext";

const inter = Inter({ subsets: ['latin'], weight: ['300', '400', '500', '600', '700', '800', '900'] });

export const metadata: Metadata = {
  title: 'PreIPO SIP - Invest in Pre-IPOs with Zero Fees',
  description: "India's First 100% FREE Pre-IPO SIP Platform with 10% Bonuses.",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className={inter.className}>
        <Providers> {/* <-- 2. WRAP EVERYTHING HERE */}
          <div className="flex flex-col min-h-screen">
          
            <Navbar />
            <main className="flex-grow">{children}</main>
            <Footer />
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
