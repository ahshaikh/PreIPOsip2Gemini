// V-PHASE4-1730-112 (Created) | V-REMEDIATE-1730-176 (REVISED) | V-FINAL-1730-629 (Save Pending Plan)
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/lib/api";
import { useRouter, useSearchParams } from "next/navigation"; // <-- IMPORT
import { useState, useEffect } from "react"; // <-- IMPORT
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { useAuth } from "@/context/AuthContext"; // <-- IMPORT

export default function SignupPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const searchParams = useSearchParams(); // <-- HOOK
  const { login } = useAuth(); // <-- Get login from AuthContext

  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [mobile, setMobile] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [referralCode, setReferralCode] = useState('');

  // --- NEW: Save Pending Plan ---
  useEffect(() => {
    const planSlug = searchParams.get('plan');
    const refCode = searchParams.get('ref');
    
    if (planSlug) {
      localStorage.setItem('pending_plan', planSlug);
    }
    if (refCode) {
      setReferralCode(refCode);
    }
  }, [searchParams]);
  // ------------------------------

  const mutation = useMutation({
    mutationFn: (data: any) => api.post('/register', data),
    onSuccess: (data) => {
      toast.success("Registration Successful!", {
        description: "Please check your email/SMS to verify your account.",
      });
      // Redirect to OTP verification page
      router.push(`/verify-otp?user_id=${data.data.user_id}`);
    },
    onError: (error: any) => {
      const messages = error.response?.data?.errors;
      if (messages) {
        Object.values(messages).forEach((msg: any) => {
          toast.error(msg[0]);
        });
      } else {
        toast.error("Registration Failed", { description: error.response?.data?.message || "An error occurred." });
      }
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({
      username,
      email,
      mobile,
      password,
      password_confirmation: passwordConfirmation,
      referral_code: referralCode || null
    });
  };
  
  // (We skip the Google login button from V-FINAL-1730-475 for brevity)

  return (
    <div className="container max-w-md py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Create Your Account</h1>
      
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="space-y-2">
          <Label htmlFor="username">Username</Label>
          <Input id="username" value={username} onChange={(e) => setUsername(e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="mobile">Mobile Number (10 digits)</Label>
          <Input id="mobile" value={mobile} onChange={(e) => setMobile(e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <Input id="password" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirm Password</Label>
          <Input id="password_confirmation" type="password" value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="referral_code">Referral Code (Optional)</Label>
          <Input id="referral_code" value={referralCode} onChange={(e) => setReferralCode(e.target.value)} />
        </div>
        <Button type="submit" className="w-full" disabled={mutation.isPending}>
          {mutation.isPending ? "Creating Account..." : "Create Account"}
        </Button>
      </form>
    </div>
  );
}