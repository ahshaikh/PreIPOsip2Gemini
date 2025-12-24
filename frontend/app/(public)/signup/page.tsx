// V-PHASE4-1730-112 (Created - Revised) | V-REMEDIATE-1730-176 (REVISED) | V-FINAL-1730-629 (Save Pending Plan)
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

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [mobile, setMobile] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [referralCode, setReferralCode] = useState('');

  // Legal Document Acceptance
  const [acceptTerms, setAcceptTerms] = useState(false);
  const [acceptPrivacy, setAcceptPrivacy] = useState(false);
  const [acceptRiskDisclosure, setAcceptRiskDisclosure] = useState(false);
  const [acceptAmlKyc, setAcceptAmlKyc] = useState(false);

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
      router.push(`/verify?user_id=${data.data.user_id}`);
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

    // Validate legal acceptance
    if (!acceptTerms || !acceptPrivacy || !acceptRiskDisclosure || !acceptAmlKyc) {
      toast.error("Legal Documents Required", {
        description: "You must accept all legal documents to register."
      });
      return;
    }

    mutation.mutate({
      first_name: firstName,
      last_name: lastName,
      username,
      email,
      mobile,
      password,
      password_confirmation: passwordConfirmation,
      referral_code: referralCode || null,
      accept_terms: acceptTerms,
      accept_privacy: acceptPrivacy,
      accept_risk_disclosure: acceptRiskDisclosure,
      accept_aml_kyc: acceptAmlKyc,
    });
  };
  
  // (We skip the Google login button from V-FINAL-1730-475 for brevity)

  return (
    <div className="container max-w-md py-20">
      <h1 className="text-3xl font-bold text-center mb-8">Create Your Account</h1>
      
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="first_name">First Name</Label>
            <Input id="first_name" value={firstName} onChange={(e) => setFirstName(e.target.value)} required />
          </div>
          <div className="space-y-2">
            <Label htmlFor="last_name">Last Name</Label>
            <Input id="last_name" value={lastName} onChange={(e) => setLastName(e.target.value)} required />
          </div>
        </div>
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

        {/* Legal Document Acceptance - Required for Fintech Compliance */}
        <div className="border rounded-lg p-4 space-y-3 bg-muted/50">
          <p className="text-sm font-medium">Legal Documents (Required)</p>

          <div className="flex items-start space-x-2">
            <input
              type="checkbox"
              id="accept_terms"
              checked={acceptTerms}
              onChange={(e) => setAcceptTerms(e.target.checked)}
              className="mt-1 h-4 w-4 rounded border-gray-300"
              required
            />
            <label htmlFor="accept_terms" className="text-sm leading-tight cursor-pointer">
              I accept the{" "}
              <a href="/terms" target="_blank" className="text-primary underline hover:text-primary/80">
                Terms and Conditions
              </a>
            </label>
          </div>

          <div className="flex items-start space-x-2">
            <input
              type="checkbox"
              id="accept_privacy"
              checked={acceptPrivacy}
              onChange={(e) => setAcceptPrivacy(e.target.checked)}
              className="mt-1 h-4 w-4 rounded border-gray-300"
              required
            />
            <label htmlFor="accept_privacy" className="text-sm leading-tight cursor-pointer">
              I accept the{" "}
              <a href="/privacy-policy" target="_blank" className="text-primary underline hover:text-primary/80">
                Privacy Policy
              </a>
            </label>
          </div>

          <div className="flex items-start space-x-2">
            <input
              type="checkbox"
              id="accept_risk"
              checked={acceptRiskDisclosure}
              onChange={(e) => setAcceptRiskDisclosure(e.target.checked)}
              className="mt-1 h-4 w-4 rounded border-gray-300"
              required
            />
            <label htmlFor="accept_risk" className="text-sm leading-tight cursor-pointer">
              I accept the{" "}
              <a href="/risk-disclosure" target="_blank" className="text-primary underline hover:text-primary/80">
                Risk Disclosure Statement
              </a>
            </label>
          </div>

          <div className="flex items-start space-x-2">
            <input
              type="checkbox"
              id="accept_aml"
              checked={acceptAmlKyc}
              onChange={(e) => setAcceptAmlKyc(e.target.checked)}
              className="mt-1 h-4 w-4 rounded border-gray-300"
              required
            />
            <label htmlFor="accept_aml" className="text-sm leading-tight cursor-pointer">
              I accept the{" "}
              <a href="/aml-kyc-policy" target="_blank" className="text-primary underline hover:text-primary/80">
                AML/KYC Policy
              </a>
            </label>
          </div>
        </div>

        <Button type="submit" className="w-full" disabled={mutation.isPending}>
          {mutation.isPending ? "Creating Account..." : "Create Account"}
        </Button>
      </form>
    </div>
  );
}