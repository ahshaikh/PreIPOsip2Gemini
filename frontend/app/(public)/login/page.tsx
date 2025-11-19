'use client';

// V-PHASE4-1730-111 (Created) | V-FINAL-1730-475 (Social Login UI)

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/lib/api";
import { useRouter, useSearchParams } from "next/navigation";
import { useState, useEffect } from "react";
import { useMutation } from "@tanstack/react-query";
import { useAuth } from "@/context/AuthContext";

// Google Icon
const GoogleIcon = () => (
  <svg className="mr-2 h-4 w-4" viewBox="0 0 24 24">
    <path
      fill="#4285F4"
      d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
    />
    <path
      fill="#34A853"
      d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
    />
    <path
      fill="#FBBC05"
      d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"
    />
    <path
      fill="#EA4335"
      d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
    />
  </svg>
);

export default function LoginPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isGoogleLoading, setIsGoogleLoading] = useState(false);

  // 2FA State
  const [is2fa, setIs2fa] = useState(false);
  const [userId, setUserId] = useState(null);
  const [otp, setOtp] = useState('');

// Get login function from context
  const { login: handleAuthSuccess } = useAuth();

  // Handle errors from social callback
  useEffect(() => {
    if (searchParams.get('error') === 'google_failed') {
      toast.error("Google Login Failed", { description: "Please try again or use your password." });
    }
  }, [searchParams]);

  // --- Login Mutation (Step 1) ---
  const loginMutation = useMutation({
    mutationFn: () => api.post('/login', { login, password }),
    onSuccess: (response) => {
      const data = response.data;
      if (data.two_factor_required) {
        // --- 2FA Challenge ---
        setIs2fa(true);
        setUserId(data.user_id);
        toast.info("2FA Code Required", { description: "Please enter your authenticator code." });
      } else {
        // --- Standard Login Success ---
        handleAuthSuccess(data.token, data.user);
      }
    },
    onError: (error: any) => {
      toast.error("Login Failed", { description: error.response?.data?.message || "An error occurred." });
    },
    onSettled: () => setIsLoading(false)
  });

  // --- 2FA Verify Mutation (Step 2) ---
  const twoFaMutation = useMutation({
    mutationFn: () => api.post('/login/2fa', { user_id: userId, code: otp }),
    onSuccess: (response) => {
      handleAuthSuccess(response.data.token, response.data.user);
    },
    onError: (error: any) => {
      toast.error("2FA Failed", { description: error.response?.data?.message || "Invalid code." });
    },
    onSettled: () => setIsLoading(false)
  });

  // --- Social Login Mutation ---
  const googleMutation = useMutation({
    mutationFn: () => api.get('/auth/google/redirect'),
    onSuccess: (data) => {
      // Redirect user to Google's auth page
      window.location.href = data.data.redirect_url;
    },
    onError: () => {
      toast.error("Google Login Failed", { description: "Could not connect to Google." });
      setIsGoogleLoading(false);
    }
  });



  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    if (is2fa) {
      twoFaMutation.mutate();
    } else {
      loginMutation.mutate();
    }
  };

  const handleGoogleLogin = () => {
    setIsGoogleLoading(true);
    googleMutation.mutate();
  };

  // --- 2FA Form ---
  if (is2fa) {
    return (
      <div className="container max-w-sm py-20">
        <h1 className="text-3xl font-bold text-center mb-4">Enter 2FA Code</h1>
        <p className="text-muted-foreground text-center mb-8">
          Enter the 6-digit code from your authenticator app.
        </p>
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="space-y-2">
            <Label htmlFor="otp">6-Digit Code</Label>
            <Input 
              id="otp" 
              type="text" 
              inputMode="numeric"
              maxLength={6}
              required 
              value={otp}
              onChange={(e) => setOtp(e.target.value)}
            />
          </div>
          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? "Verifying..." : "Verify & Login"}
          </Button>
          <Button variant="link" className="w-full" onClick={() => setIs2fa(false)}>Back to password</Button>
        </form>
      </div>
    );
  }

  // --- Standard Login Form ---
  return (
    <div className="container max-w-sm py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Login to your Account</h1>
      
      {/* Social Login Button */}
      <Button variant="outline" className="w-full" onClick={handleGoogleLogin} disabled={isGoogleLoading}>
        <GoogleIcon /> {isGoogleLoading ? "Redirecting..." : "Login with Google"}
      </Button>

      <div className="flex items-center my-6">
        <div className="flex-grow border-t border-gray-300"></div>
        <span className="mx-4 text-sm text-gray-500">OR</span>
        <div className="flex-grow border-t border-gray-300"></div>
      </div>
      
      {/* Manual Login Form */}
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="space-y-2">
          <Label htmlFor="login">Email, Username, or Mobile</Label>
          <Input 
            id="login" 
            type="text" 
            placeholder="Your login" 
            required 
            value={login}
            onChange={(e) => setLogin(e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <Input 
            id="password" 
            type="password" 
            required 
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </div>
        <Button type="submit" className="w-full" disabled={isLoading}>
          {isLoading ? "Logging in..." : "Login"}
        </Button>
      </form>
    </div>
  );
}