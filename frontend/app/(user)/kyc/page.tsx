'use client';

// V-PHASE5-1730-117 (Created - Revised) | V-REMEDIATE-1730-214 | V-FINAL-1730-629 (Manual KYC - DigiLocker Removed)


import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { CheckCircle, XCircle, Loader2, Upload, ShieldCheck } from "lucide-react";

export default function KycPage() {
  const queryClient = useQueryClient();

  const [formData, setFormData] = useState({
    pan_number: '',
    aadhaar_number: '',
    demat_account: '',
    bank_account: '',
    bank_ifsc: '',
  });

  // File states - All required documents for manual verification
  const [panFile, setPanFile] = useState<File | null>(null);
  const [aadhaarFrontFile, setAadhaarFrontFile] = useState<File | null>(null);
  const [aadhaarBackFile, setAadhaarBackFile] = useState<File | null>(null);
  const [bankFile, setBankFile] = useState<File | null>(null);
  const [dematFile, setDematFile] = useState<File | null>(null);
  const [addressProofFile, setAddressProofFile] = useState<File | null>(null);
  const [photoFile, setPhotoFile] = useState<File | null>(null);
  const [signatureFile, setSignatureFile] = useState<File | null>(null);

  // Status states
  const [panStatus, setPanStatus] = useState<'idle' | 'verifying' | 'verified' | 'failed'>('idle');
  const [bankStatus, setBankStatus] = useState<'idle' | 'verifying' | 'verified' | 'failed'>('idle');

  // Fetch data
  const { data: kyc, isLoading: isKycLoading } = useQuery({
    queryKey: ['kycData'],
    queryFn: async () => {
      const { data } = await api.get('/user/profile');
      const kycData = await api.get('/user/kyc');

      setFormData({
        pan_number: kycData.data?.pan_number || '',
        aadhaar_number: kycData.data?.aadhaar_number || '',
        demat_account: kycData.data?.demat_account || '',
        bank_account: kycData.data?.bank_account || '',
        bank_ifsc: kycData.data?.bank_ifsc || '',
      });
      return {
        ...kycData.data,
        user_name: (data.profile?.first_name || '') + ' ' + (data.profile?.last_name || ''),
        // Ensure status has a default value if not present
        status: kycData.data?.status || 'pending'
      };
    }
  });

  // --- Mutations ---
  const verifyPanMutation = useMutation({
    mutationFn: () => api.post('/user/kyc/verify-pan', { pan_number: formData.pan_number }),
    onMutate: () => setPanStatus('verifying'),
    onSuccess: () => {
      setPanStatus('verified');
      toast.success("PAN Verified Successfully");
    },
    onError: () => {
      setPanStatus('failed');
      toast.error("PAN Verification Failed");
    }
  });

  const verifyBankMutation = useMutation({
    mutationFn: () => api.post('/user/kyc/verify-bank', {
      bank_account: formData.bank_account,
      bank_ifsc: formData.bank_ifsc
    }),
    onMutate: () => setBankStatus('verifying'),
    onSuccess: () => {
      setBankStatus('verified');
      toast.success("Bank Account Verified Successfully");
    },
    onError: () => {
      setBankStatus('failed');
      toast.error("Bank Verification Failed");
    }
  });

  // Main Submit (File Uploads)
  const submitMutation = useMutation({
    mutationFn: (fd: FormData) => api.post('/user/kyc', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
    onSuccess: () => {
      toast.success("KYC Submitted for Review", { description: "Your documents will be verified within 24-48 hours." });
      queryClient.invalidateQueries({ queryKey: ['kycData'] });
    },
    onError: (e: any) => toast.error("Submission Failed", { description: e.response?.data?.message })
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validate all required fields
    if (!formData.aadhaar_number || formData.aadhaar_number.length !== 12) {
      toast.error("Invalid Aadhaar", { description: "Please enter a valid 12-digit Aadhaar number." });
      return;
    }

    if (!panFile || !aadhaarFrontFile || !aadhaarBackFile || !bankFile || !dematFile || !addressProofFile || !photoFile || !signatureFile) {
      toast.error("Missing Documents", { description: "Please upload all required documents." });
      return;
    }

    const fd = new FormData();
    fd.append('pan_number', formData.pan_number);
    fd.append('aadhaar_number', formData.aadhaar_number);
    fd.append('demat_account', formData.demat_account);
    fd.append('bank_account', formData.bank_account);
    fd.append('bank_ifsc', formData.bank_ifsc);

    // Append all document files
    fd.append('pan', panFile);
    fd.append('aadhaar_front', aadhaarFrontFile);
    fd.append('aadhaar_back', aadhaarBackFile);
    fd.append('bank_proof', bankFile);
    fd.append('demat_proof', dematFile);
    fd.append('address_proof', addressProofFile);
    fd.append('photo', photoFile);
    fd.append('signature', signatureFile);

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
                <p className="text-sm text-muted-foreground">{kyc.aadhaar_number || 'Verified'}</p>
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

  // Show submitted/pending/processing state
  if (kyc.status === 'submitted' || kyc.status === 'under_review' || kyc.status === 'processing') {
    return (
      <Card className="border-blue-500 bg-blue-50 dark:bg-blue-900/10">
        <CardHeader>
          <div className="flex items-center gap-3">
            <div className="p-3 bg-blue-500 rounded-full">
              <Loader2 className="h-6 w-6 text-white animate-spin" />
            </div>
            <div>
              <CardTitle className="text-2xl text-blue-700 dark:text-blue-500">KYC Application Submitted Successfully!</CardTitle>
              <CardDescription className="text-blue-600 dark:text-blue-400">
                Your documents are being verified by our team
              </CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Thank you for submitting your KYC documents. We're currently reviewing your application. This process usually takes 24-48 hours. You'll receive a notification once the verification is complete.
          </p>
          <Alert className="border-blue-500/50 bg-blue-500/10">
            <AlertDescription>
              <strong>Submitted Documents:</strong> PAN Card, Aadhaar (Front & Back), Bank Proof, Demat Proof, Address Proof, Photo, Signature
            </AlertDescription>
          </Alert>
          <div className="pt-4 border-t">
            <p className="text-xs text-muted-foreground mb-3">
              Submitted on: {kyc.submitted_at ? new Date(kyc.submitted_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Just now'}
            </p>
            <Button variant="outline" asChild>
              <a href="/dashboard">Return to Dashboard</a>
            </Button>
          </div>
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

          {/* --- PERSONAL INFORMATION --- */}
          <Card>
            <CardContent className="pt-6 space-y-4">
              <div className="space-y-2">
                <Label htmlFor="pan_number">PAN Number *</Label>
                <div className="flex gap-2">
                  <Input
                    id="pan_number"
                    value={formData.pan_number}
                    onChange={(e) => setFormData({...formData, pan_number: e.target.value.toUpperCase()})}
                    placeholder="ABCDE1234F"
                    maxLength={10}
                    required
                  />
                  <Button type="button" variant="outline" onClick={() => verifyPanMutation.mutate()} disabled={panStatus === 'verifying'}>
                    {panStatus === 'verifying' ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Verify'}
                  </Button>
                </div>
                {panStatus === 'verified' && <p className="text-sm text-green-600 flex items-center gap-1"><CheckCircle className="h-4 w-4" /> Verified</p>}
                {panStatus === 'failed' && <p className="text-sm text-red-600 flex items-center gap-1"><XCircle className="h-4 w-4" /> Verification Failed</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="aadhaar_number">Aadhaar Number * (12 digits)</Label>
                <Input
                  id="aadhaar_number"
                  value={formData.aadhaar_number}
                  onChange={(e) => setFormData({...formData, aadhaar_number: e.target.value.replace(/\D/g, '')})}
                  placeholder="123456789012"
                  maxLength={12}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="bank_account">Bank Account Number *</Label>
                  <Input
                    id="bank_account"
                    value={formData.bank_account}
                    onChange={(e) => setFormData({...formData, bank_account: e.target.value})}
                    placeholder="1234567890"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="bank_ifsc">IFSC Code *</Label>
                  <div className="flex gap-2">
                    <Input
                      id="bank_ifsc"
                      value={formData.bank_ifsc}
                      onChange={(e) => setFormData({...formData, bank_ifsc: e.target.value.toUpperCase()})}
                      placeholder="SBIN0001234"
                      maxLength={11}
                      required
                    />
                    <Button type="button" variant="outline" onClick={() => verifyBankMutation.mutate()} disabled={bankStatus === 'verifying'}>
                      {bankStatus === 'verifying' ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Verify'}
                    </Button>
                  </div>
                  {bankStatus === 'verified' && <p className="text-sm text-green-600 flex items-center gap-1"><CheckCircle className="h-4 w-4" /> Verified</p>}
                  {bankStatus === 'failed' && <p className="text-sm text-red-600 flex items-center gap-1"><XCircle className="h-4 w-4" /> Verification Failed</p>}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="demat_account">Demat Account Number *</Label>
                <Input
                  id="demat_account"
                  value={formData.demat_account}
                  onChange={(e) => setFormData({...formData, demat_account: e.target.value})}
                  placeholder="1234567890123456"
                  required
                />
              </div>
            </CardContent>
          </Card>

          {/* --- DOCUMENT UPLOADS --- */}
          <Card>
            <CardContent className="pt-6">
              <Label className="text-lg font-semibold mb-4 block">Document Uploads (All Required)</Label>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="pan_file" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> PAN Card *
                  </Label>
                  <Input
                    id="pan_file"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setPanFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Upload clear copy of PAN card</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="aadhaar_front" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Aadhaar Front *
                  </Label>
                  <Input
                    id="aadhaar_front"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setAadhaarFrontFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Front side with photo</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="aadhaar_back" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Aadhaar Back *
                  </Label>
                  <Input
                    id="aadhaar_back"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setAadhaarBackFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Back side with address</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="bank_proof" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Bank Proof *
                  </Label>
                  <Input
                    id="bank_proof"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setBankFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Cancelled cheque or bank statement</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="demat_proof" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Demat Proof *
                  </Label>
                  <Input
                    id="demat_proof"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setDematFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Demat account statement</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="address_proof" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Address Proof *
                  </Label>
                  <Input
                    id="address_proof"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setAddressProofFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Utility bill, rental agreement, etc.</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="photo" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Recent Photo *
                  </Label>
                  <Input
                    id="photo"
                    type="file"
                    accept="image/*"
                    onChange={(e) => setPhotoFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Passport-size photograph</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="signature" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Signature *
                  </Label>
                  <Input
                    id="signature"
                    type="file"
                    accept="image/*"
                    onChange={(e) => setSignatureFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Clear signature on white paper</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Alert className="border-blue-500/50 bg-blue-500/10">
            <AlertDescription className="text-sm">
              <strong>Important:</strong> All documents must be clear, legible, and in PDF or image format (JPG, PNG).
              File size should not exceed 5MB per document.
            </AlertDescription>
          </Alert>

          <Button type="submit" disabled={submitMutation.isPending} className="w-full" size="lg">
            {submitMutation.isPending ? (
              <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Submitting...</>
            ) : (
              "Resubmit All Documents"
            )}
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
        <CardDescription>Complete your KYC verification to start investing. All fields are mandatory for fintech compliance.</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-6">

          {/* --- PERSONAL INFORMATION --- */}
          <Card>
            <CardContent className="pt-6 space-y-4">
              <div className="space-y-2">
                <Label htmlFor="pan_number">PAN Number *</Label>
                <div className="flex gap-2">
                  <Input
                    id="pan_number"
                    value={formData.pan_number}
                    onChange={(e) => setFormData({...formData, pan_number: e.target.value.toUpperCase()})}
                    placeholder="ABCDE1234F"
                    maxLength={10}
                    required
                  />
                  <Button type="button" variant="outline" onClick={() => verifyPanMutation.mutate()} disabled={panStatus === 'verifying'}>
                    {panStatus === 'verifying' ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Verify'}
                  </Button>
                </div>
                {panStatus === 'verified' && <p className="text-sm text-green-600 flex items-center gap-1"><CheckCircle className="h-4 w-4" /> Verified</p>}
                {panStatus === 'failed' && <p className="text-sm text-red-600 flex items-center gap-1"><XCircle className="h-4 w-4" /> Verification Failed</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="aadhaar_number">Aadhaar Number * (12 digits)</Label>
                <Input
                  id="aadhaar_number"
                  value={formData.aadhaar_number}
                  onChange={(e) => setFormData({...formData, aadhaar_number: e.target.value.replace(/\D/g, '')})}
                  placeholder="123456789012"
                  maxLength={12}
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="bank_account">Bank Account Number *</Label>
                  <Input
                    id="bank_account"
                    value={formData.bank_account}
                    onChange={(e) => setFormData({...formData, bank_account: e.target.value})}
                    placeholder="1234567890"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="bank_ifsc">IFSC Code *</Label>
                  <div className="flex gap-2">
                    <Input
                      id="bank_ifsc"
                      value={formData.bank_ifsc}
                      onChange={(e) => setFormData({...formData, bank_ifsc: e.target.value.toUpperCase()})}
                      placeholder="SBIN0001234"
                      maxLength={11}
                      required
                    />
                    <Button type="button" variant="outline" onClick={() => verifyBankMutation.mutate()} disabled={bankStatus === 'verifying'}>
                      {bankStatus === 'verifying' ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Verify'}
                    </Button>
                  </div>
                  {bankStatus === 'verified' && <p className="text-sm text-green-600 flex items-center gap-1"><CheckCircle className="h-4 w-4" /> Verified</p>}
                  {bankStatus === 'failed' && <p className="text-sm text-red-600 flex items-center gap-1"><XCircle className="h-4 w-4" /> Verification Failed</p>}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="demat_account">Demat Account Number *</Label>
                <Input
                  id="demat_account"
                  value={formData.demat_account}
                  onChange={(e) => setFormData({...formData, demat_account: e.target.value})}
                  placeholder="1234567890123456"
                  required
                />
              </div>
            </CardContent>
          </Card>

          {/* --- DOCUMENT UPLOADS --- */}
          <Card>
            <CardContent className="pt-6">
              <Label className="text-lg font-semibold mb-4 block">Document Uploads (All Required)</Label>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="pan_file" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> PAN Card *
                  </Label>
                  <Input
                    id="pan_file"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setPanFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Upload clear copy of PAN card</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="aadhaar_front" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Aadhaar Front *
                  </Label>
                  <Input
                    id="aadhaar_front"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setAadhaarFrontFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Front side with photo</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="aadhaar_back" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Aadhaar Back *
                  </Label>
                  <Input
                    id="aadhaar_back"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setAadhaarBackFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Back side with address</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="bank_proof" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Bank Proof *
                  </Label>
                  <Input
                    id="bank_proof"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setBankFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Cancelled cheque or bank statement</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="demat_proof" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Demat Proof *
                  </Label>
                  <Input
                    id="demat_proof"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setDematFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Demat account statement</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="address_proof" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Address Proof *
                  </Label>
                  <Input
                    id="address_proof"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={(e) => setAddressProofFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Utility bill, rental agreement, etc.</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="photo" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Recent Photo *
                  </Label>
                  <Input
                    id="photo"
                    type="file"
                    accept="image/*"
                    onChange={(e) => setPhotoFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Passport-size photograph</p>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="signature" className="flex items-center gap-2">
                    <Upload className="h-4 w-4" /> Signature *
                  </Label>
                  <Input
                    id="signature"
                    type="file"
                    accept="image/*"
                    onChange={(e) => setSignatureFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Clear signature on white paper</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Alert className="border-blue-500/50 bg-blue-500/10">
            <AlertDescription className="text-sm">
              <strong>Important:</strong> All documents must be clear, legible, and in PDF or image format (JPG, PNG).
              File size should not exceed 5MB per document.
            </AlertDescription>
          </Alert>

          <Button type="submit" disabled={submitMutation.isPending} className="w-full" size="lg">
            {submitMutation.isPending ? (
              <><Loader2 className="mr-2 h-4 w-4 animate-spin" /> Submitting...</>
            ) : (
              "Submit All Documents"
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}