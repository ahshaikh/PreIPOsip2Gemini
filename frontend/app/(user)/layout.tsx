// V-PHASE5-1730-115 (Created) | V-FINAL-1730-237 (NotificationBell Integrated) | V-ENHANCED-USER-NAV
// V-FIX-LOGIN-REDIRECT (Fixed storage consistency - using plain localStorage)
// V-FIX-AVATAR-DISPLAY: Converted to React Query to share userProfile state across components
'use client';

import { DashboardNav } from '@/components/shared/DashboardNav';
import { UserTopNav } from '@/components/shared/UserTopNav';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import api from '@/lib/api';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { User as UserIcon, LogOut } from 'lucide-react';

// NEW: use shared role helper
import { extractRoleNames } from '@/lib/auth';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();

  // FIX: Use React Query with the SAME query key as Profile page
  // This ensures avatar updates are reflected everywhere
  const { data: user, isLoading, isError } = useQuery({
    queryKey: ['userProfile'], // Same key as Profile page
    queryFn: async () => {
      const response = await api.get('/user/profile');
      return response.data;
    },
    retry: false,
    staleTime: 0, // Always refetch to get latest avatar
  });

  useEffect(() => {
    // CRITICAL: Only run on client side after hydration
    if (typeof window === 'undefined') {
      console.log('[DASHBOARD LAYOUT] Running on server, skipping auth check');
      return;
    }

    const token = localStorage.getItem('auth_token');

    if (!token) {
      console.log('[DASHBOARD LAYOUT] No token, redirecting to login');
      router.push('/login');
      return;
    }

    // Check for authentication errors
    if (isError) {
      console.error('[DASHBOARD LAYOUT] Auth check failed');
      localStorage.removeItem('auth_token');
      router.push('/login');
      return;
    }

    // V-FIX-SUPERADMIN-REDIRECT: Check if user is admin/superadmin
    if (user) {
      const roleNames = extractRoleNames(user);
      const isAdmin = roleNames.includes('admin') || roleNames.includes('superadmin');

      if (isAdmin) {
        console.log('[DASHBOARD LAYOUT] Admin user detected, redirecting to admin dashboard');
        console.log('[DASHBOARD LAYOUT] User role(s):', roleNames, '| is_admin:', user.is_admin);
        router.push('/admin/dashboard');
        return;
      }
    }
  }, [user, isError, router]);

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
            <div className="mb-4 p-4 rounded-lg bg-muted flex items-center gap-3">
              {/* FIX: Added avatar to sidebar menu header */}
              <Avatar className="h-12 w-12">
                <AvatarImage src={user.profile?.avatar_url} alt={user.username} />
                <AvatarFallback>
                  <UserIcon className="h-6 w-6 text-muted-foreground" />
                </AvatarFallback>
              </Avatar>
              <div className="flex-1 min-w-0">
                <h3 className="font-semibold truncate">{user.profile?.first_name || user.username}</h3>
                <p className="text-sm text-muted-foreground truncate">{user.email}</p>
              </div>
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
