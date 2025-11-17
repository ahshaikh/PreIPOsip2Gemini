// V-FINAL-1730-282 (Updated with Chat Widget) | V-FINAL-1730-433 (Chat Widget) | V-FINAL-1730-525 (Popup Banner)
// C:\PreIPOsip\frontend\app\layout.tsx

// FIXED ROOT LAYOUT (SERVER COMPONENT)
// DO NOT use "use client" here.

import '@/app/globals.css';
import { Inter } from 'next/font/google';

// Client wrappers
import ClientProviders from '@/components/shared/ClientProvidersWrapper';
import Navbar from '@/components/shared/Navbar';
import Footer from '@/components/shared/Footer';

const inter = Inter({ subsets: ['latin'] });

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <head />
      <body className={inter.className}>
        
        {/* Client-only stuff handled in wrapper */}
        <ClientProviders>
          <div className="flex flex-col min-h-screen">
            <Navbar />
            <main className="flex-grow">{children}</main>
            <Footer />
          </div>
        </ClientProviders>

      </body>
    </html>
  );
}
