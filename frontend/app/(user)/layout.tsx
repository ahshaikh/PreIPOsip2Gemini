// V-PHASE5-1730-115 (Created) | V-FINAL-1730-237 (NotificationBell Integrated) | V-ENHANCED-USER-NAV
// V-FIX-LOGIN-REDIRECT (Fixed storage consistency - using plain localStorage)
'use client';

import { DashboardNav } from '@/components/shared/DashboardNav';
import { UserTopNav } from '@/components/shared/UserTopNav';
import { Button } from '@/components/ui/button';
import api from '@/lib/api';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { User } from '@/types';
import { LogOut } from 'lucide-react';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // CRITICAL: Only run on client side after hydration
    if (typeof window === 'undefined') {
      console.log('[DASHBOARD LAYOUT] Running on server, skipping auth check');
      return;
    }

    const checkAuth = async () => {
      console.log('[DASHBOARD LAYOUT] Checking authentication...');
      const token = localStorage.getItem('auth_token');
      console.log('[DASHBOARD LAYOUT] Token found:', token ? `${token.substring(0, 20)}...` : 'NONE');

      if (!token) {
        console.log('[DASHBOARD LAYOUT] No token, redirecting to login');
        router.push('/login');
        return;
      }

      try {
        console.log('[DASHBOARD LAYOUT] Fetching user profile...');
        const response = await api.get('/user/profile');
        console.log('[DASHBOARD LAYOUT] Profile response:', response.data);
        const userData = response.data.user || response.data;
        console.log('[DASHBOARD LAYOUT] Setting user:', userData);

        // V-FIX-SUPERADMIN-REDIRECT: Check if user is admin/superadmin
        // If so, redirect them to admin dashboard instead
        const isAdmin = ['admin', 'superadmin'].includes(userData.role) || userData.is_admin;
        if (isAdmin) {
          console.log('[DASHBOARD LAYOUT] Admin user detected, redirecting to admin dashboard');
          console.log('[DASHBOARD LAYOUT] User role:', userData.role, '| is_admin:', userData.is_admin);
          router.push('/admin/dashboard');
          return;
        }

        setUser(userData);
      } catch (error) {
        console.error('[DASHBOARD LAYOUT] Auth check failed:', error);
        localStorage.removeItem('auth_token');
        router.push('/login');
      } finally {
        console.log('[DASHBOARD LAYOUT] Auth check complete');
        setIsLoading(false);
      }
    };
    checkAuth();
  }, [router]);

  const handleLogout = async () => {
    try {
        await api.post('/logout');
    } catch (e) {
        // Ignore logout errors
    }
    localStorage.removeItem('auth_token');
    router.push('/login');
  };

  if (isLoading || !user) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div>Loading dashboard...</div>
      </div>
    );
  }

  return (
    <>
      {/* Top Navigation Bar */}
      <UserTopNav user={user} />

      <div className="container mx-auto py-8 pt-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <aside className="md:col-span-1">
            <div className="mb-4 p-4 rounded-lg bg-muted">
              <h3 className="font-semibold truncate">{user.profile?.first_name || user.username}</h3>
              <p className="text-sm text-muted-foreground truncate">{user.email}</p>
            </div>

            <DashboardNav />

            <Button variant="ghost" onClick={handleLogout} className="w-full justify-start mt-4">
              <LogOut className="mr-3 h-5 w-5" />
              Logout
            </Button>
          </aside>
          <main className="md:col-span-3">
            {children}
          </main>
        </div>
      </div>
    </>
  );
}