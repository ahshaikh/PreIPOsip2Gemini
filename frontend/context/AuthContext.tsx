// V-FINAL-1730-633 (Created) | V-SECURITY-TOKEN-ENCRYPTION | V-TYPES-FIX
'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '@/lib/api';
import { secureStorage, migrateToEncryptedStorage } from '@/lib/secureStorage';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import { User } from '@/types';

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (token: string, userData: User) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * This component wraps the entire application.
 * It holds the user's login state (user object, token)
 * and provides it to all other pages.
 */
export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const router = useRouter();
  const queryClient = useQueryClient();

  // On initial app load, check if a token exists in secureStorage
  useEffect(() => {
    // Migrate existing unencrypted tokens on first load
    migrateToEncryptedStorage();

    const token = secureStorage.getItem('auth_token');
    if (token) {
      // Token exists, set it in our API helper
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      // Fetch the user's profile to confirm the token is valid
      fetchProfile();
    } else {
      // No token, user is a guest
      setIsLoading(false);
    }
  }, []);

  const fetchProfile = async () => {
    try {
      // Fetch the user's profile, including KYC and sub status
      const { data } = await api.get('/user/profile');
      setUser(data);
    } catch (error) {
      // Token was invalid (e.g., expired or user deleted)
      // Log them out.
      logout();
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * This is the master "login" function.
   * It is called by the Login page, the 2FA page, or the Social Callback page.
   */
  const login = (token: string, userData: any) => {
    // 1. Store token and user data (encrypted)
    secureStorage.setItem('auth_token', token);
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    setUser(userData);

    // 2. --- THE "PENDING PLAN" LOGIC ---
    // Check if the user had selected a plan *before* they signed up
    const pendingPlan = localStorage.getItem('pending_plan');

    if (pendingPlan) {
      // Yes. Send them to the special /subscribe page
      router.push('/subscribe');
    } else {
      // No. This is a normal login. Send to dashboard based on role.
      const isAdmin = userData.roles && userData.roles.some(r =>
        ['Super Admin', 'Admin', 'KYC Officer', 'Support Agent', 'Finance Manager'].includes(r.name)
      );
      router.push(isAdmin ? '/admin/dashboard' : '/dashboard');
    }
    // ------------------------------------
  };

  /**
   * This is the master "logout" function.
   */
  const logout = () => {
    secureStorage.removeItem('auth_token');
    delete api.defaults.headers.common['Authorization'];
    setUser(null);
    queryClient.clear(); // Clear all cached data
    router.push('/login');
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

/**
 * This is the "hook" that all other pages will use
 * to get the user's data (e.g., `const { user } = useAuth();`)
 */
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};