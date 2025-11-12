// V-PHASE5-1730-117 (REVISED)
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";

export default function KycPage() {
  // const { toast } = useToast(); // <-- REMOVED
  const queryClient = useQueryClient();
  const [formData, setFormData] = useState({
    pan_number: '',
    aadhaar_number: '',
    demat_account: '',
    bank_account: '',
    bank_ifsc: '',
  });
  const [files, setFiles] = useState<any>({
    aadhaar_front: null,
    aadhaar_back: null,
    pan: null,
    bank_proof: null,
    demat_proof: null,
  });

  const { data: kyc, isLoading: isKycLoading } = useQuery({
    queryKey: ['kycData'],
    queryFn: async () => {
      const { data } = await api.get('/user/kyc');
      setFormData({
        pan_number: data.pan_number || '',
        aadhaar_number: data.aadhaar_number || '',
        demat_account: data.demat_account || '',
        bank_account: data.bank_account || '',
        bank_ifsc: data.bank_ifsc || '',
      });
      return data;
    }
  });

  const mutation = useMutation({
    mutationFn: (kycFormData: FormData) => api.post('/user/kyc', kycFormData),
    onSuccess: () => {
      toast.success("KYC Submitted!", { description: "Your documents are now under review." }); // <-- REVISED
      queryClient.invalidateQueries({ queryKey: ['kycData'] });
      queryClient.invalidateQueries({ queryKey: ['kycStatus'] });
    },
    onError: (error: any) => {
      toast.error("Submission Failed", { // <-- REVISED
        description: error.response?.data?.message || "Please check your inputs.",
      });
    }
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      setFiles({ ...files, [e.target.id]: e.target.files[0] });
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const kycFormData = new FormData();
    
    Object.entries(formData).forEach(([key, value]) => {
      kycFormData.append(key, value);
    });
    
    Object.entries(files).forEach(([key, value]) => {
      if (value) {
        kycFormData.append(key, value as File);
      }
    });
    
    mutation.mutate(kycFormData);
  };

  if (isKycLoading) return <div>Loading KYC status...</div>;

  if (kyc.status === 'verified') {
    return (
      <Card>
        <CardHeader>
          <CardTitle>KYC Verified</CardTitle>
          <CardDescription>Your account is fully verified. No further action is needed.</CardDescription>
        </CardHeader>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>KYC Verification</CardTitle>
        <CardDescription>
          {kyc.status === 'rejected' && (
            <p className="text-destructive mb-4">
              <strong>Rejected:</strong> {kyc.rejection_reason}. Please correct and resubmit.
            </p>
          )}
          {kyc.status === 'submitted' && (
            <p className="text-yellow-600 mb-4">
              <strong>Submitted:</strong> Your documents are under review (approx. 24 hours).
            </p>
          )}
          {kyc.status === 'pending' && (
            <p className="mb-4">Please submit your documents to start investing.</p>
          )}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="pan_number">PAN Number</Label>
              <Input id="pan_number" value={formData.pan_number} onChange={handleChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="aadhaar_number">Aadhaar Number</Label>
              <Input id="aadhaar_number" value={formData.aadhaar_number} onChange={handleChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="demat_account">Demat Account</Label>
              <Input id="demat_account" value={formData.demat_account} onChange={handleChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="bank_account">Bank Account</Label>
              <Input id="bank_account" value={formData.bank_account} onChange={handleChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="bank_ifsc">Bank IFSC</Label>
              <Input id="bank_ifsc" value={formData.bank_ifsc} onChange={handleChange} required />
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="pan">PAN Card (PDF, JPG, PNG)</Label>
              <Input id="pan" type="file" onChange={handleFileChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="aadhaar_front">Aadhaar (Front)</Label>
              <Input id="aadhaar_front" type="file" onChange={handleFileChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="aadhaar_back">Aadhaar (Back)</Label>
              <Input id="aadhaar_back" type="file" onChange={handleFileChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="bank_proof">Bank Proof (Cheque/Statement)</Label>
              <Input id="bank_proof" type="file" onChange={handleFileChange} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="demat_proof">Demat Proof (Statement)</Label>
              <Input id="demat_proof" type="file" onChange={handleFileChange} required />
            </div>
          </div>
          
          <Button type="submit" disabled={mutation.isPending || kyc.status === 'submitted'}>
            {mutation.isPending ? "Submitting..." : (kyc.status === 'submitted' ? "Pending Review" : "Submit KYC")}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}