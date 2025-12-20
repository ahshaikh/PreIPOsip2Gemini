// V-PHASE6-1730-124 (Created) | V-FINAL-1730-238 (NotificationBell Integrated) | V-ENHANCED-ADMIN-NAV
'use client';

import { AdminNav } from '@/components/shared/AdminNav';
import { AdminTopNav } from '@/components/shared/AdminTopNav';
import { Button } from '@/components/ui/button';
import { NotificationBell } from '@/components/shared/NotificationBell';
import api from '@/lib/api';
import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { User } from '@/types';
import { LogOut } from 'lucide-react';

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const checkAuth = async () => {
      const token = localStorage.getItem('auth_token');
      if (!token) {
        router.push('/login');
        return;
      }

      try {
        const response = await api.get('/user/profile');
        const userData = response.data;

        // Check if user is admin or superadmin
        const isAdmin = userData.is_admin ||
                        ['admin', 'superadmin'].includes(userData.role) ||
                        (userData.roles && userData.roles.some((r: any) => ['admin', 'superadmin', 'super-admin'].includes(r.name)));

        if (!isAdmin) {
          // Redirect non-admin users to user dashboard
          router.push('/dashboard');
          return;
        }

        setUser(userData);
      } catch (error) {
        localStorage.removeItem('auth_token');
        router.push('/login');
      } finally {
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
        <div>Loading admin panel...</div>
      </div>
    );
  }

  return (
    <>
      {/* Top Navigation Bar */}
      <AdminTopNav user={user} />

      <div className="container mx-auto py-8 pt-24">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-8">
          <aside className="md:col-span-1">
            {/* --- UPDATED HEADER WITH BELL --- */}
            <div className="mb-4 p-4 rounded-lg bg-muted flex items-center justify-between">
              <div className="overflow-hidden">
                  <h3 className="font-semibold truncate">{user.profile?.first_name || user.username}</h3>
                  <p className="text-sm text-primary">Admin Access</p>
              </div>
              <div className="flex-shrink-0 ml-2">
                  <NotificationBell />
              </div>
            </div>
            {/* ------------------------------- */}

            <AdminNav />

            <Button variant="ghost" onClick={handleLogout} className="w-full justify-start mt-4">
              <LogOut className="mr-3 h-5 w-5" />
              Logout
            </Button>
          </aside>
          <main className="md:col-span-4">
            {children}
          </main>
        </div>
      </div>
    </>
  );
}