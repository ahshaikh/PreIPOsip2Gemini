'use client';
// V-FINAL-1730-476 (Created)


import { useEffect } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { toast } from 'sonner';

export default function SocialCallbackPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const queryClient = useQueryClient();

  useEffect(() => {
    const token = searchParams.get('token');
    
    if (token) {
      // 1. Save the token
      localStorage.setItem('auth_token', token);
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      toast.success("Login Successful!", { description: "Welcome back." });
      
      // 2. Invalidate all queries to force refetch user data
      queryClient.invalidateQueries();
      
      // 3. Redirect to the dashboard
      // We can't know if they are admin or not without another fetch,
      // so we send to /dashboard and let the layout handle the redirect.
      router.push('/dashboard');

    } else {
      // Handle error
      toast.error("Login Failed", { description: "Invalid login token." });
      router.push('/login');
    }

  }, [router, searchParams, queryClient]);

  return (
    <div className="container py-20 text-center">
      <p>Please wait, logging you in...</p>
    </div>
  );
}