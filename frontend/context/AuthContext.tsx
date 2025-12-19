// V-FINAL-1730-633 (Created) | V-SECURITY-TOKEN-ENCRYPTION | V-TYPES-FIX | V-AUDIT-FIX-DRY
// V-FIX-LOGIN-REDIRECT (Fixed API endpoint paths - removed double /api/ prefix)

'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '@/lib/api';
import { useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { User } from '@/types';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (credentials: any) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * AuthProvider
 * Manages authentication state and provides login/logout functions.
 * Token is stored in localStorage via secureStorage and attached to requests
 * via the API client interceptor (see lib/api.ts).
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const router = useRouter();
  const queryClient = useQueryClient();

  /**
   * On initial app load, check session via API.
   * Token is retrieved from localStorage via secureStorage (api.ts interceptor).
   */
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        const response = await api.get('/user/profile');
        setUser(response.data.user || response.data);
      } catch (error) {
        // Unauthenticated or session expired
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    initializeAuth();
  }, []);

  /**
   * Note: This login function is NOT used by the login page anymore.
   * The login page directly stores the token and redirects.
   * This is kept for backward compatibility if needed elsewhere.
   */
  const login = async (credentials: any) => {
    setIsLoading(true);
    try {
      const response = await api.post('/login', credentials);
      const userData = response.data.user;

      setUser(userData);

      // Routing logic remains unchanged
      const isAdmin = userData.role === 'admin' || userData.is_admin;
      router.push(isAdmin ? '/admin/dashboard' : '/dashboard');
    } catch (error) {
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * Logout clears localStorage token and server session.
   */
  const logout = async () => {
    try {
      await api.post('/logout');
    } catch (err) {
      console.error('Logout failed', err);
    } finally {
      setUser(null);
      queryClient.clear();
      router.push('/login');
    }
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
};