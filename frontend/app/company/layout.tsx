'use client';

import { useEffect } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import CompanyNav from '@/components/shared/CompanyNav';
import CompanyTopNav from '@/components/shared/CompanyTopNav';

export default function CompanyLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();

  // Public routes that don't require authentication
  const publicRoutes = ['/company/login', '/company/register'];

  useEffect(() => {
    const token = localStorage.getItem('company_token');
    const isPublicRoute = publicRoutes.includes(pathname);

    if (!token && !isPublicRoute) {
      router.push('/company/login');
    } else if (token && isPublicRoute) {
      router.push('/company/dashboard');
    }
  }, [pathname, router]);

  // If it's a public route, render without navigation
  if (publicRoutes.includes(pathname)) {
    return <>{children}</>;
  }

  // Authenticated layout with navigation
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <CompanyNav />
      <div className="ml-64">
        <CompanyTopNav />
        <main className="pt-16">
          <div className="p-6">{children}</div>
        </main>
      </div>
    </div>
  );
}
