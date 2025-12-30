'use client';
//C:\PreIPOsip\frontend\app\(public)\login\page.tsx
// V-PHASE4-1730-111 (Created - Revised) | V-FINAL-1730-633 (Redirect Fixed) | V-FIX-BUILD-SONNER

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { Eye, EyeOff, Loader2, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
// GEMINI FIX: Switched from use-toast to sonner to fix Build Error
import { toast } from 'sonner';
import api from '@/lib/api';
import Navbar from '@/components/shared/Navbar';
import Footer from '@/components/shared/Footer';

// NEW: centralized role extraction helper
import { extractRoleNames } from '@/lib/auth';

export default function LoginPage() {
  const router = useRouter();
  
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  
  const [formData, setFormData] = useState({
    login: '', // Can be email, username, or mobile
    password: '',
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData(prev => ({
      ...prev,
      [e.target.name]: e.target.value
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      // 1. Call Login API
      const response = await api.post('/login', formData);
      
      // 2. Handle Success
      if (response.data.token) {
        // Store Token & User Data
        localStorage.setItem('auth_token', response.data.token);
        // Also set cookie for middleware compatibility if needed
        document.cookie = `auth_token=${response.data.token}; path=/; max-age=86400; SameSite=Strict`;
        
        if (response.data.user) {
            localStorage.setItem('user_data', JSON.stringify(response.data.user));
        }

        toast.success("Welcome back!", {
          description: "Login successful.",
        });

        // -----------------------------------------------------------------------
        // CRITICAL FIX: Role-Based Routing (USING CENTRALIZED HELPER)
        // -----------------------------------------------------------------------
        const userData = response.data.user || response.data || {};
        const roleNames = extractRoleNames(userData);
        console.log('[LOGIN] roleNames:', roleNames, 'userData:', userData);
        
        // Check for Admin Role
        if (roleNames.includes('admin') || roleNames.includes('superadmin')) {
            router.push('/admin/dashboard');
        } 
        // Check for Company Role
        else if (roleNames.includes('company')) {
            router.push('/company/dashboard');
        } 
        // Default to User Dashboard
        else {
            router.push('/dashboard');
        }
        // -----------------------------------------------------------------------

      }
    } catch (error: any) {
      console.error('Login error:', error);
      
      let errorMessage = "Invalid credentials. Please try again.";
      
      if (error.response) {
        // Handle specific backend error messages
        if (error.response.status === 429) {
          errorMessage = "Too many attempts. Please try again later.";
        } else if (error.response.data?.message) {
          errorMessage = error.response.data.message;
        } else if (error.response.data?.two_factor_required) {
           // Handle 2FA flow
           router.push(`/login/2fa?user_id=${error.response.data.user_id}`);
           return; 
        }
      }

      toast.error("Login Failed", {
        description: errorMessage,
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-slate-900 flex flex-col">
      <Navbar />

      {/* Added pt-20 for navbar spacing (h-16 navbar + extra padding) */}
      <main className="flex-grow flex items-center justify-center pt-20 pb-12 px-4 sm:px-6 lg:px-8">
        <Card className="w-full max-w-md shadow-xl border-gray-100 dark:border-slate-700 dark:bg-slate-800">
          <CardHeader className="space-y-1">
            <CardTitle className="text-2xl font-bold text-center text-[#0A2647] dark:text-white">Sign in to your account</CardTitle>
            <CardDescription className="text-center dark:text-gray-300">
              Enter your credentials to access your dashboard
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="login">Email, Username or Mobile</Label>
                <Input 
                  id="login" 
                  name="login"
                  type="text" 
                  placeholder="Enter your registered ID"
                  value={formData.login}
                  onChange={handleChange}
                  required
                  className="border-gray-200 focus:border-[#144272]"
                />
              </div>
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label htmlFor="password">Password</Label>
                  <Link 
                    href="/password/reset" 
                    className="text-sm font-medium text-[#144272] hover:text-[#0A2647]"
                  >
                    Forgot password?
                  </Link>
                </div>
                <div className="relative">
                  <Input 
                    id="password" 
                    name="password"
                    type={showPassword ? "text" : "password"} 
                    placeholder="••••••••"
                    value={formData.password}
                    onChange={handleChange}
                    required
                    className="border-gray-200 focus:border-[#144272] pr-10"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  >
                    {showPassword ? (
                      <EyeOff className="h-4 w-4" />
                    ) : (
                      <Eye className="h-4 w-4" />
                    )}
                  </button>
                </div>
              </div>
              <Button 
                type="submit" 
                className="w-full bg-[#0A2647] hover:bg-[#144272] text-white"
                disabled={isLoading}
              >
                {isLoading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Signing in...
                  </>
                ) : (
                  <>
                    Sign In
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </>
                )}
              </Button>
            </form>
          </CardContent>
          <CardFooter className="flex flex-col space-y-4 text-center">
            <div className="relative w-full">
              <div className="absolute inset-0 flex items-center">
                <span className="w-full border-t border-gray-200" />
              </div>
              <div className="relative flex justify-center text-xs uppercase">
                <span className="bg-white px-2 text-gray-500">Or continue with</span>
              </div>
            </div>
            
            <div className="grid grid-cols-2 gap-4 w-full">
              <Button variant="outline" className="w-full" onClick={() => window.location.href = `${process.env.NEXT_PUBLIC_API_URL}/auth/google`}>
                <svg className="mr-2 h-4 w-4" viewBox="0 0 24 24">
                  <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                  <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                  <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.26z" fill="#FBBC05" />
                  <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                </svg>
                Google
              </Button>
              <Button variant="outline" className="w-full" onClick={() => window.location.href = `${process.env.NEXT_PUBLIC_API_URL}/auth/linkedin`}>
                <svg className="mr-2 h-4 w-4 fill-[#0077b5]" viewBox="0 0 24 24">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.[...]" />
                </svg>
                LinkedIn
              </Button>
            </div>

            <p className="text-sm text-gray-500 mt-4">
              Don't have an account?{' '}
              <Link href="/signup" className="font-semibold text-[#144272] hover:text-[#0A2647]">
                Create one now
              </Link>
            </p>
          </CardFooter>
        </Card>
      </main>
      
      <Footer />
    </div>
  );
}
