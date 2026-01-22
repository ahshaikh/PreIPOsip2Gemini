// V-PHASE4-1730-101 (Created - Revised) | V-FINAL-1730-634 (AuthContext Fix) | V-FINAL-1730-658 (Providers Integration) | V-FIX-BANNER-MOUNT | V-COMPLIANCE-GUARD-ADDED
'use client';

import { Inter } from 'next/font/google';
import "./globals.css";
import { usePathname } from 'next/navigation';
import Script from 'next/script';
import Navbar from "@/components/shared/Navbar";
import Footer from "@/components/shared/Footer";
import ScrollToTop from "@/components/shared/ScrollToTop";
import { Toaster } from '@/components/ui/sonner';
import { CookieConsent } from '@/components/shared/CookieConsent';
import { Providers } from "@/components/shared/Providers";
import PopupBanner from "@/components/shared/PopupBanner"; // Imported
import ComplianceGuard from "@/components/shared/ComplianceGuard"; // Imported Guard

const inter = Inter({ subsets: ['latin'], weight: ['300', '400', '500', '600', '700', '800', '900'] });

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  // Don't show public navbar/footer on dashboard, admin, or company pages
  // FIX: Added /company to exclusion list so company portal has its own nav (CompanyNav + CompanyTopNav)
  // FIX: Exclude ALL user-specific routes to prevent double nav (public Navbar + UserTopNav)
  // Note: /plan (singular) is user route, /plans (plural) is public
  // CRITICAL FIX: Added /deals - this is a user route under (user) layout, not a public page
  const userRoutes = [
    '/profile', '/Profile', '/wallet', '/subscription', '/subscriptions',
    '/investments', '/portfolio', '/referrals', '/support', '/lucky-draws',
    '/settings', '/transactions', '/bonuses', '/kyc', '/compliance',
    '/materials', '/notifications', '/promote', '/reports', '/profit-sharing', '/offers',
    '/deals' // CRITICAL FIX: Was missing, caused public Navbar + Footer to render alongside UserTopNav
  ];
  const isPublicPage = !pathname?.startsWith('/dashboard') &&
                       !pathname?.startsWith('/admin') &&
                       !pathname?.startsWith('/company') &&
                       !userRoutes.some(route => pathname?.startsWith(route));
  // PROTOCOL 1 FIX: Exclude routes that don't need compliance checks
  // - Company routes: Have their own auth system (company_token, not auth_token)
  // - Login/Signup: User not authenticated yet, causes unnecessary 401 errors
  // ComplianceGuard calls /user/compliance/status which requires auth_token
  const isCompanyRoute = pathname?.startsWith('/company');
  const isAuthPage = pathname === '/login' || pathname === '/signup';
  const skipComplianceGuard = isCompanyRoute || isAuthPage;

  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <Script
          src="https://checkout.razorpay.com/v1/checkout.js"
          strategy="lazyOnload"
        />
      </head>
      <body className={inter.className}>
        <Providers>
          {/* V-FIX-BANNER-MOUNT: Added the component here so it actually renders */}
          <PopupBanner />

          {/* Wrapper for Global Compliance Checks */}
          {/* PROTOCOL 1 FIX: Skip ComplianceGuard for company/auth routes */}
          {skipComplianceGuard ? (
            <div className="flex flex-col min-h-screen">
              {isPublicPage && <Navbar />}
              <main className="flex-grow">{children}</main>
              {isPublicPage && <Footer />}
              <ScrollToTop />
            </div>
          ) : (
            <ComplianceGuard>
              <div className="flex flex-col min-h-screen">
                {isPublicPage && <Navbar />}
                <main className="flex-grow">{children}</main>
                {isPublicPage && <Footer />}
                <ScrollToTop />
              </div>
            </ComplianceGuard>
          )}

          <CookieConsent />
          <Toaster richColors />
        </Providers>
      </body>
    </html>
  );
}