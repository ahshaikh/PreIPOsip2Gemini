// V-FINAL-1730-669 | V-COMPLIANCE-SINGLE-CHECKBOX
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/lib/api";
import { useRouter, useSearchParams } from "next/navigation";
import { useState, useEffect } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useAuth } from "@/context/AuthContext";
import { Checkbox } from "@/components/ui/checkbox";
import { LegalAcceptanceModal } from "@/components/shared/LegalAcceptanceModal";

export default function SignupPage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    mobile: '',
    password: '',
    password_confirmation: '',
    referral_code: '',
  });

  const [isAccepted, setIsAccepted] = useState(false);
  const [activeDoc, setActiveDoc] = useState<string | null>(null); // To open specific doc in modal

  // 1. Fetch Active Legal Documents (To get versions for the backend)
  const { data: legalDocuments } = useQuery({
    queryKey: ['publicLegalDocuments'],
    queryFn: async () => {
      const res = await api.get('/legal/documents'); 
      return Array.isArray(res.data) ? res.data : res.data.data || []; 
    },
  });

  // Handle URL params
  useEffect(() => {
    const planSlug = searchParams.get('plan');
    const refCode = searchParams.get('ref');
    if (planSlug) localStorage.setItem('pending_plan', planSlug);
    if (refCode) setFormData(prev => ({ ...prev, referral_code: refCode }));
  }, [searchParams]);

  const mutation = useMutation({
    mutationFn: (data: any) => api.post('/register', data),
    onSuccess: (data) => {
      toast.success("Registration Successful!", {
        description: "Please check your email/SMS to verify your account.",
      });
      router.push(`/verify?user_id=${data.data.user_id}`);
    },
    onError: (error: any) => {
      const messages = error.response?.data?.errors;
      if (messages) {
        Object.values(messages).forEach((msg: any) => toast.error(msg[0]));
      } else {
        toast.error("Registration Failed", { description: error.response?.data?.message || "An error occurred." });
      }
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!isAccepted) {
      toast.error("Acceptance Required", { description: "You must agree to the Terms and Policies to register." });
      return;
    }

    // Prepare the versioned acceptance payload
    // We map ALL currently active documents to this user's acceptance
    const legal_acceptances = legalDocuments?.map((doc: any) => ({
        id: doc.id,
        version: doc.version
    })) || [];

    const payload = {
      ...formData,
      accept_terms: true,       // Legacy support
      accept_privacy: true,     // Legacy support
      accept_risk_disclosure: true, // Legacy support
      accept_aml_kyc: true,     // Legacy support
      legal_acceptances: legal_acceptances // New robust system
    };

    mutation.mutate(payload);
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const openDoc = (e: React.MouseEvent, type: string) => {
    e.preventDefault();
    setActiveDoc(type);
  };

  return (
    <div className="container max-w-lg py-16 pt-20 pb-12">
      <div className="text-center mb-8">
        <h1 className="text-3xl font-bold">Create Your Account</h1>
        <p className="text-muted-foreground mt-2">Join us to start your investment journey.</p>
      </div>
      
      <form onSubmit={handleSubmit} className="space-y-5">
        <div className="grid grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label htmlFor="first_name">First Name</Label>
            <Input id="first_name" value={formData.first_name} onChange={(e) => handleInputChange('first_name', e.target.value)} required />
          </div>
          <div className="space-y-2">
            <Label htmlFor="last_name">Last Name</Label>
            <Input id="last_name" value={formData.last_name} onChange={(e) => handleInputChange('last_name', e.target.value)} required />
          </div>
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="username">Username</Label>
          <Input id="username" value={formData.username} onChange={(e) => handleInputChange('username', e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="email">Email</Label>
          <Input id="email" type="email" value={formData.email} onChange={(e) => handleInputChange('email', e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="mobile">Mobile Number</Label>
          <Input id="mobile" value={formData.mobile} onChange={(e) => handleInputChange('mobile', e.target.value)} required placeholder="10 digit mobile number" />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password">Password</Label>
          <Input id="password" type="password" value={formData.password} onChange={(e) => handleInputChange('password', e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirm Password</Label>
          <Input id="password_confirmation" type="password" value={formData.password_confirmation} onChange={(e) => handleInputChange('password_confirmation', e.target.value)} required />
        </div>
        <div className="space-y-2">
          <Label htmlFor="referral_code">Referral Code (Optional)</Label>
          <Input id="referral_code" value={formData.referral_code} onChange={(e) => handleInputChange('referral_code', e.target.value)} placeholder="If you have one" />
        </div>

        {/* --- Unified Legal Consent --- */}
        <div className="flex items-start space-x-3 pt-2">
            <Checkbox 
                id="legal_consent" 
                checked={isAccepted} 
                onCheckedChange={(c) => setIsAccepted(c as boolean)}
                className="mt-1"
            />
            <div className="grid gap-1.5 leading-none">
                <label
                    htmlFor="legal_consent"
                    className="text-sm font-medium leading-relaxed text-muted-foreground"
                >
                    By creating an account, I agree to the{' '}
                    <button onClick={(e) => openDoc(e, 'terms_of_service')} className="text-primary hover:underline font-semibold">Terms of Service</button>,{' '}
                    <button onClick={(e) => openDoc(e, 'privacy_policy')} className="text-primary hover:underline font-semibold">Privacy Policy</button>,{' '}
                    <button onClick={(e) => openDoc(e, 'risk_disclosure')} className="text-primary hover:underline font-semibold">Risk Disclosure</button>,{' '}
                    and{' '}
                    <button onClick={(e) => openDoc(e, 'aml_kyc_policy')} className="text-primary hover:underline font-semibold">AML Policy</button>.
                </label>
            </div>
        </div>

        <Button type="submit" className="w-full" size="lg" disabled={mutation.isPending}>
          {mutation.isPending ? "Creating Account..." : "Create Account"}
        </Button>
        
        <p className="text-center text-sm text-muted-foreground">
            Already have an account? <a href="/login" className="text-primary hover:underline">Log in</a>
        </p>
      </form>

      {/* Modal for viewing documents without leaving the page */}
      {activeDoc && (
          <LegalAcceptanceModal 
            isOpen={!!activeDoc}
            slug={activeDoc} 
            // In guest mode, version doesn't matter for the fetch, but modal needs a prop
            version="latest" 
            onClose={() => setActiveDoc(null)}
            mode="guest"
            onSuccess={() => { /* View only, no action needed */ }}
          />
      )}
    </div>
  );
}