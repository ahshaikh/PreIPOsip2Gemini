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
   *
   * V-FIX-PUBLIC-PAGE-REDIRECT: Only check auth if token exists
   * This prevents unnecessary 401 errors on public pages
   */
  useEffect(() => {
    const initializeAuth = async () => {
      // Only check auth if token exists
      const token = localStorage.getItem('auth_token');

      if (!token) {
        console.log('[AUTH] No token found, skipping auth check');
        setUser(null);
        setIsLoading(false);
        return;
      }

      try {
        console.log('[AUTH] Token found, verifying with backend...');
        const response = await api.get('/user/profile');
        setUser(response.data.user || response.data);
      } catch (error) {
        // Unauthenticated or session expired
        console.log('[AUTH] Auth check failed, clearing token');
        setUser(null);
        localStorage.removeItem('auth_token');
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
      const token = response.data.token;

      // Store token
      if (token) {
        localStorage.setItem('auth_token', token);
      }

      setUser(userData);

      // -------------------------------------------------------------------
      // GEMINI FIX: Role-Based Redirect Logic
      // -------------------------------------------------------------------
      // We safely extract role names from the 'roles' relationship array.
      // We also check 'role_name' accessor if available.
      // This prevents Admins from being sent to the User Dashboard (which causes 500 errors).
      // -------------------------------------------------------------------
      
      const userRoles = userData.roles ? userData.roles.map((r: any) => r.name) : [];
      const userRoleName = userData.role_name || '';

      if (userRoles.includes('admin') || userRoles.includes('super_admin') || userRoleName === 'admin') {
        router.push('/admin/dashboard');
      } else if (userRoles.includes('company') || userRoleName === 'company') {
        router.push('/company/dashboard');
      } else {
        router.push('/dashboard');
      }
      
      // OLD LOGIC REMOVED:
      // const isAdmin = ['admin', 'superadmin'].includes(userData.role) || userData.is_admin;
      // router.push(isAdmin ? '/admin/dashboard' : '/dashboard');

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
      localStorage.removeItem('auth_token');
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