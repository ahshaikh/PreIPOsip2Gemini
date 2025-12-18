// V-FINAL-1730-633 (Created) | V-SECURITY-TOKEN-ENCRYPTION | V-TYPES-FIX | V-AUDIT-FIX-DRY | V-COOKIE-AUTH-MIGRATION

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
 * * [AUDIT FIX]: Removed all references to 'secureStorage' and 'localStorage' 
 * for token handling. The browser now manages authentication state via 
 * HttpOnly cookies.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const router = useRouter();
  const queryClient = useQueryClient();

  /**
   * On initial app load, check session via API.
   * Browser automatically includes the HttpOnly 'auth_token' cookie.
   */
  useEffect(() => {
    const initializeAuth = async () => {
      try {
        const response = await api.get('/api/user/profile');
        setUser(response.data.user);
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
   * The login function now delegates cookie handling to the browser.
   */
  const login = async (credentials: any) => {
    setIsLoading(true);
    try {
      const response = await api.post('/api/login', credentials);
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
   * Logout clears state and calls the backend to expire the cookie.
   */
  const logout = async () => {
    try {
      await api.post('/api/logout');
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