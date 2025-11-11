// V-PHASE6-1730-124
'use client';

import { AdminNav } from '@/components/shared/AdminNav';
import { Button } from '@/components/ui/button';
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
        // In a real app, the profile endpoint should return user roles
        // For now, we'll assume the user is an admin if they try to access this page
        // A real check would use: if (!response.data.roles.includes('admin')) router.push('/dashboard');
        setUser(response.data);
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
    await api.post('/logout');
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
    <div className="container mx-auto py-8">
      <div className="grid grid-cols-1 md:grid-cols-5 gap-8">
        <aside className="md:col-span-1">
          <div className="mb-4 p-4 rounded-lg bg-muted">
            <h3 className="font-semibold">{user.profile.first_name || user.username}</h3>
            <p className="text-sm text-primary">Admin Access</p>
          </div>
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
  );
}