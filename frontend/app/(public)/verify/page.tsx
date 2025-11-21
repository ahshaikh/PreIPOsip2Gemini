// V-PHASE4-1730-113 (Created - Revised)
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";

export default function VerifyPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  // const { toast } = useToast(); // <-- REMOVED
  
  const userId = searchParams.get('user_id');
  
  const [emailOtp, setEmailOtp] = useState('');
  const [mobileOtp, setMobileOtp] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleVerify = async (type: 'email' | 'mobile') => {
    setIsLoading(true);
    const otp = type === 'email' ? emailOtp : mobileOtp;

    try {
      await api.post('/verify-otp', {
        user_id: userId,
        type: type,
        otp: otp
      });
      toast.success(`${type === 'email' ? 'Email' : 'Mobile'} Verified!`, { // <-- REVISED
        description: `Your ${type} has been successfully verified.`,
      });
      if (type === 'email') setEmailOtp('VERIFIED');
      if (type === 'mobile') setMobileOtp('VERIFIED');
      
      if ((type === 'email' && mobileOtp === 'VERIFIED') || (type === 'mobile' && emailOtp === 'VERIFIED')) {
        toast.success("Account Activated!", { description: "Please log in." }); // <-- REVISED
        router.push('/login');
      }
    } catch (error: any) {
      toast.error("Verification Failed", { // <-- REVISED
        description: error.response?.data?.message || "Invalid or expired OTP.",
      });
    } finally {
      setIsLoading(false);
    }
  };

  if (!userId) {
    return <div className="container py-20">Invalid verification link.</div>;
  }

  return (
    <div className="container max-w-sm py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Verify Your Account</h1>
      <p className="text-center text-muted-foreground mb-6">
        We've sent OTPs to your email and mobile. Please enter them below.
      </p>
      
      <div className="space-y-6">
        {/* Email OTP */}
        <div className="space-y-2">
          <Label htmlFor="email_otp">Email OTP</Label>
          <div className="flex gap-2">
            <Input 
              id="email_otp" 
              value={emailOtp}
              onChange={(e) => setEmailOtp(e.target.value)}
              disabled={isLoading || emailOtp === 'VERIFIED'}
            />
            <Button 
              onClick={() => handleVerify('email')} 
              disabled={isLoading || emailOtp === 'VERIFIED'}
            >
              {emailOtp === 'VERIFIED' ? 'Verified' : 'Verify'}
            </Button>
          </div>
        </div>
        
        {/* Mobile OTP */}
        <div className="space-y-2">
          <Label htmlFor="mobile_otp">Mobile OTP</Label>
          <div className="flex gap-2">
            <Input 
              id="mobile_otp" 
              value={mobileOtp}
              onChange={(e) => setMobileOtp(e.target.value)}
              disabled={isLoading || mobileOtp === 'VERIFIED'}
            />
            <Button 
              onClick={() => handleVerify('mobile')} 
              disabled={isLoading || mobileOtp === 'VERIFIED'}
            >
              {mobileOtp === 'VERIFIED' ? 'Verified' : 'Verify'}
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}