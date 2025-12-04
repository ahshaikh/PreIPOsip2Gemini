'use client';

// V-PHASE5-1730-117 (Created - Revised) | V-REMEDIATE-1730-214 | V-FINAL-1730-481 (DigiLocker UI)


import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { CheckCircle, XCircle, Loader2, ShieldCheck } from "lucide-react";
import { useSearchParams } from "next/navigation";

export default function KycPage() {
  const queryClient = useQueryClient();
  const searchParams = useSearchParams();
  const [formData, setFormData] = useState({
    pan_number: '',
    aadhaar_number: 'Verified via DigiLocker', // Placeholder
    demat_account: '',
    bank_account: '',
    bank_ifsc: '',
  });
  
  // File states
  const [panFile, setPanFile] = useState<File | null>(null);
  const [bankFile, setBankFile] = useState<File | null>(null);
  const [dematFile, setDematFile] = useState<File | null>(null);

  // Status states
  const [panStatus, setPanStatus] = useState<'idle' | 'verifying' | 'verified' | 'failed'>('idle');
  const [bankStatus, setBankStatus] = useState<'idle' | 'verifying' | 'verified' | 'failed'>('idle');

  // Check for callback status
  useEffect(() => {
    if (searchParams.get('status') === 'digilocker_success') {
      toast.success("Aadhaar Verified!", { description: "Your e-Aadhaar was fetched successfully." });
      queryClient.invalidateQueries({ queryKey: ['kycData'] });
    }
    if (searchParams.get('status') === 'digilocker_failed') {
      toast.error("Aadhaar Failed", { description: "We could not verify your Aadhaar. Please try again." });
    }
  }, [searchParams, queryClient]);

  // Fetch data
  const { data: kyc, isLoading: isKycLoading } = useQuery({
    queryKey: ['kycData'],
    queryFn: async () => {
      const { data } = await api.get('/user/profile');
      const kycData = await api.get('/user/kyc');

      // Safe check for aadhaar verification
      const isAadhaarVerified = (kycData.data?.documents || []).some((d:any) => d.doc_type === 'aadhaar_front' && d.processing_status === 'verified');

      setFormData({
        pan_number: kycData.data?.pan_number || '',
        aadhaar_number: isAadhaarVerified ? 'Verified via DigiLocker' : '',
        demat_account: kycData.data?.demat_account || '',
        bank_account: kycData.data?.bank_account || '',
        bank_ifsc: kycData.data?.bank_ifsc || '',
      });
      return {
        ...kycData.data,
        user_name: (data.profile?.first_name || '') + ' ' + (data.profile?.last_name || ''),
        isAadhaarVerified,
        // Ensure status has a default value if not present
        status: kycData.data?.status || 'pending'
      };
    }
  });

  // --- Mutations ---
  const digilockerMutation = useMutation({
    mutationFn: () => api.get('/user/kyc/digilocker/redirect'),
    onSuccess: (data) => {
      // Redirect user to DigiLocker
      window.location.href = data.data.redirect_url;
    },
    onError: () => toast.error("Could not connect to DigiLocker. Please try again.")
  });
  
  const verifyPanMutation = useMutation({ /* ... same as before ... */ });
  const verifyBankMutation = useMutation({ /* ... same as before ... */ });

  // Main Submit (File Uploads)
  const submitMutation = useMutation({
    mutationFn: (fd: FormData) => api.post('/user/kyc', fd, { 
      headers: { 'Content-Type': 'multipart/form-data' } 
    }),
    onSuccess: () => {
      toast.success("KYC Submitted for Review", { description: "Auto-verification is in progress." });
      queryClient.invalidateQueries({ queryKey: ['kycData'] });
    },
    onError: (e: any) => toast.error("Submission Failed", { description: e.response?.data?.message })
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!panFile || !bankFile || !dematFile || !kyc.isAadhaarVerified) {
      toast.error("Missing Information", { description: "Please verify Aadhaar and upload all required documents."});
      return;
    }

    const fd = new FormData();
    fd.append('pan_number', formData.pan_number);
    fd.append('aadhaar_number', kyc.aadhaar_number);
    fd.append('demat_account', formData.demat_account);
    fd.append('bank_account', formData.bank_account);
    fd.append('bank_ifsc', formData.bank_ifsc);
    
    fd.append('pan', panFile);
    fd.append('bank_proof', bankFile);
    fd.append('demat_proof', dematFile);
    
    submitMutation.mutate(fd);
  };
  
  if (isKycLoading) return <div>Loading...</div>;
  if (!kyc) return <div>Failed to load KYC status.</div>;

  // Show completion UI if KYC is verified
  if (kyc.status === 'verified') {
    return (
      <div className="space-y-6">
        <Card className="border-green-500 bg-green-50 dark:bg-green-900/10">
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="p-3 bg-green-500 rounded-full">
                <CheckCircle className="h-6 w-6 text-white" />
              </div>
              <div>
                <CardTitle className="text-2xl text-green-700 dark:text-green-500">KYC Verified Successfully!</CardTitle>
                <CardDescription className="text-green-600 dark:text-green-400">
                  Your account is fully verified and ready for investments
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">
                <div className="flex items-center gap-2 mb-2">
                  <CheckCircle className="h-4 w-4 text-green-500" />
                  <span className="font-medium">PAN Number</span>
                </div>
                <p className="text-sm text-muted-foreground">{kyc.pan_number || 'Verified'}</p>
              </div>
              <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">
                <div className="flex items-center gap-2 mb-2">
                  <CheckCircle className="h-4 w-4 text-green-500" />
                  <span className="font-medium">Aadhaar</span>
                </div>
                <p className="text-sm text-muted-foreground">Verified via DigiLocker</p>
              </div>
              <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">
                <div className="flex items-center gap-2 mb-2">
                  <CheckCircle className="h-4 w-4 text-green-500" />
                  <span className="font-medium">Bank Account</span>
                </div>
                <p className="text-sm text-muted-foreground">{kyc.bank_account || 'Verified'}</p>
              </div>
              <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">
                <div className="flex items-center gap-2 mb-2">
                  <CheckCircle className="h-4 w-4 text-green-500" />
                  <span className="font-medium">Demat Account</span>
                </div>
                <p className="text-sm text-muted-foreground">{kyc.demat_account || 'Verified'}</p>
              </div>
            </div>
            <div className="pt-4 border-t">
              <p className="text-sm text-muted-foreground mb-4">
                Your KYC verification was completed on {kyc.verified_at ? new Date(kyc.verified_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' }) : 'recently'}.
              </p>
              <Button asChild>
                <a href="/plans">Start Investing Now</a>
              </Button>
            </div>
          </CardContent>
        </Card>

        <Alert className="border-blue-500/50 bg-blue-500/10">
          <ShieldCheck className="h-4 w-4 text-blue-500" />
          <AlertTitle className="text-blue-600">Your Account is Secure</AlertTitle>
          <AlertDescription>
            All your documents are encrypted and stored securely. If you need to update any information, please contact support.
          </AlertDescription>
        </Alert>
      </div>
    );
  }

  // Show submitted/pending state
  if (kyc.status === 'submitted' || kyc.status === 'under_review') {
    return (
      <Card className="border-blue-500 bg-blue-50 dark:bg-blue-900/10">
        <CardHeader>
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-500 rounded-full">
              <Loader2 className="h-6 w-6 text-white animate-spin" />
            </div>
            <div>
              <CardTitle className="text-2xl text-blue-700 dark:text-blue-500">KYC Under Review</CardTitle>
              <CardDescription className="text-blue-600 dark:text-blue-400">
                Your documents are being verified by our team
              </CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground mb-4">
            We're currently reviewing your KYC documents. This process usually takes 24-48 hours. You'll receive a notification once the verification is complete.
          </p>
          <Alert>
            <AlertDescription>
              Submitted Documents: PAN Card, Aadhaar (DigiLocker), Bank Proof, Demat Proof
            </AlertDescription>
          </Alert>
        </CardContent>
      </Card>
    );
  }

  // Show rejected state
  if (kyc.status === 'rejected') {
    return (
      <div className="space-y-6">
        <Alert variant="destructive">
          <XCircle className="h-4 w-4" />
          <AlertTitle>KYC Verification Failed</AlertTitle>
          <AlertDescription>
            {kyc.rejection_reason || 'There was an issue with your submitted documents. Please review and resubmit.'}
          </AlertDescription>
        </Alert>

        <Card>
          <CardHeader>
            <CardTitle>Resubmit KYC Documents</CardTitle>
            <CardDescription>Please upload the correct documents to complete your verification</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
        
          {/* --- AADHAAR (DIGILOCKER) --- */}
          <Card className={kyc.isAadhaarVerified ? "bg-green-50 border-green-200" : ""}>
            <CardContent className="pt-6">
              <Label className="text-lg font-semibold">Aadhaar Verification</Label>
              <p className="text-sm text-muted-foreground mb-4">
                Verify your Aadhaar instantly via DigiLocker.
              </p>
              {kyc.isAadhaarVerified ? (
                <div className="flex items-center gap-2 text-green-600 font-medium">
                  <CheckCircle className="h-5 w-5" />
                  Aadhaar Verified Successfully
                </div>
              ) : (
                <Button 
                  type="button" 
                  onClick={() => digilockerMutation.mutate()} 
                  disabled={digilockerMutation.isPending}
                >
                  <ShieldCheck className="mr-2 h-4 w-4" /> Verify with DigiLocker
                </Button>
              )}
            </CardContent>
          </Card>

          {/* --- PAN (AUTO-VERIFY) --- */}
          <div className="space-y-2">
            <Label>PAN Number</Label>
            <div className="flex gap-2">
              <Input value={formData.pan_number} onChange={(e) => setFormData({...formData, pan_number: e.target.value})} />
              <Button type="button" variant="outline" onClick={() => verifyPanMutation.mutate()}>Verify</Button>
            </div>
          </div>
          
          {/* --- BANK (AUTO-VERIFY) --- */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Bank Account</Label>
              <Input value={formData.bank_account} onChange={(e) => setFormData({...formData, bank_account: e.target.value})} />
            </div>
            <div className="space-y-2">
              <Label>IFSC</Label>
              <div className="flex gap-2">
                <Input value={formData.bank_ifsc} onChange={(e) => setFormData({...formData, bank_ifsc: e.target.value})} />
                <Button type="button" variant="outline" onClick={() => verifyBankMutation.mutate()}>Verify</Button>
              </div>
            </div>
          </div>
          
          {/* --- DEMAT (MANUAL) --- */}
          <div className="space-y-2">
            <Label>Demat Account</Label>
            <Input value={formData.demat_account} onChange={(e) => setFormData({...formData, demat_account: e.target.value})} />
          </div>

          {/* --- FILE UPLOADS (MANUAL) --- */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>PAN Card (PDF, JPG)</Label>
              <Input type="file" onChange={(e) => setPanFile(e.target.files?.[0] || null)} required />
            </div>
            <div className="space-y-2">
              <Label>Bank Proof (Cheque/Statement)</Label>
              <Input type="file" onChange={(e) => setBankFile(e.target.files?.[0] || null)} required />
            </div>
            <div className="space-y-2">
              <Label>Demat Proof (Statement)</Label>
              <Input type="file" onChange={(e) => setDematFile(e.target.files?.[0] || null)} required />
            </div>
          </div>
          
          <Button type="submit" disabled={submitMutation.isPending} className="w-full">
            {submitMutation.isPending ? "Submitting..." : "Resubmit All Documents"}
          </Button>
        </form>
          </CardContent>
        </Card>
      </div>
    );
  }

  // Default form for new/pending KYC
  return (
    <Card>
      <CardHeader>
        <CardTitle>KYC Verification</CardTitle>
        <CardDescription>Complete your KYC verification to start investing</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-6">

          {/* --- AADHAAR (DIGILOCKER) --- */}
          <Card className={kyc.isAadhaarVerified ? "bg-green-50 border-green-200" : ""}>
            <CardContent className="pt-6">
              <Label className="text-lg font-semibold">Aadhaar Verification</Label>
              <p className="text-sm text-muted-foreground mb-4">
                Verify your Aadhaar instantly via DigiLocker.
              </p>
              {kyc.isAadhaarVerified ? (
                <div className="flex items-center gap-2 text-green-600 font-medium">
                  <CheckCircle className="h-5 w-5" />
                  Aadhaar Verified Successfully
                </div>
              ) : (
                <Button
                  type="button"
                  onClick={() => digilockerMutation.mutate()}
                  disabled={digilockerMutation.isPending}
                >
                  <ShieldCheck className="mr-2 h-4 w-4" /> Verify with DigiLocker
                </Button>
              )}
            </CardContent>
          </Card>

          {/* --- PAN (AUTO-VERIFY) --- */}
          <div className="space-y-2">
            <Label>PAN Number</Label>
            <div className="flex gap-2">
              <Input value={formData.pan_number} onChange={(e) => setFormData({...formData, pan_number: e.target.value})} />
              <Button type="button" variant="outline" onClick={() => verifyPanMutation.mutate()}>Verify</Button>
            </div>
          </div>

          {/* --- BANK (AUTO-VERIFY) --- */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Bank Account</Label>
              <Input value={formData.bank_account} onChange={(e) => setFormData({...formData, bank_account: e.target.value})} />
            </div>
            <div className="space-y-2">
              <Label>IFSC</Label>
              <div className="flex gap-2">
                <Input value={formData.bank_ifsc} onChange={(e) => setFormData({...formData, bank_ifsc: e.target.value})} />
                <Button type="button" variant="outline" onClick={() => verifyBankMutation.mutate()}>Verify</Button>
              </div>
            </div>
          </div>

          {/* --- DEMAT (MANUAL) --- */}
          <div className="space-y-2">
            <Label>Demat Account</Label>
            <Input value={formData.demat_account} onChange={(e) => setFormData({...formData, demat_account: e.target.value})} />
          </div>

          {/* --- FILE UPLOADS (MANUAL) --- */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-2">
              <Label>PAN Card (PDF, JPG)</Label>
              <Input type="file" onChange={(e) => setPanFile(e.target.files?.[0] || null)} required />
            </div>
            <div className="space-y-2">
              <Label>Bank Proof (Cheque/Statement)</Label>
              <Input type="file" onChange={(e) => setBankFile(e.target.files?.[0] || null)} required />
            </div>
            <div className="space-y-2">
              <Label>Demat Proof (Statement)</Label>
              <Input type="file" onChange={(e) => setDematFile(e.target.files?.[0] || null)} required />
            </div>
          </div>

          <Button type="submit" disabled={submitMutation.isPending} className="w-full">
            {submitMutation.isPending ? "Submitting..." : "Submit All Documents"}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}