// V-REMEDIATE-1730-214 (With Verification Logic)
'use client';

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { CheckCircle, XCircle, Loader2 } from "lucide-react";

export default function KycPage() {
  const queryClient = useQueryClient();
  const [formData, setFormData] = useState({
    pan_number: '',
    aadhaar_number: '',
    demat_account: '',
    bank_account: '',
    bank_ifsc: '',
  });
  
  // Verification States
  const [panStatus, setPanStatus] = useState<'idle' | 'verifying' | 'verified' | 'failed'>('idle');
  const [bankStatus, setBankStatus] = useState<'idle' | 'verifying' | 'verified' | 'failed'>('idle');

  // ... (Existing Query and File States) ...
  const { data: kyc, isLoading: isKycLoading } = useQuery({
    queryKey: ['kycData'],
    queryFn: async () => {
      const { data } = await api.get('/user/profile'); // Need profile for name matching
      const kycData = await api.get('/user/kyc');
      // ... setFormData logic ...
      return { ...kycData.data, user_name: data.profile.first_name + ' ' + data.profile.last_name };
    }
  });

  // ... (Existing Mutation for File Upload) ...

  // PAN Verification Mutation
  const verifyPanMutation = useMutation({
    mutationFn: () => api.post('/user/kyc/verify-pan', { 
      pan_number: formData.pan_number,
      full_name: kyc.user_name 
    }),
    onMutate: () => setPanStatus('verifying'),
    onSuccess: () => {
      setPanStatus('verified');
      toast.success("PAN Verified Successfully");
    },
    onError: () => {
      setPanStatus('failed');
      toast.error("PAN Mismatch", { description: "Name on PAN does not match profile name." });
    }
  });

  // Bank Verification Mutation
  const verifyBankMutation = useMutation({
    mutationFn: () => api.post('/user/kyc/verify-bank', { 
      account_number: formData.bank_account,
      ifsc: formData.bank_ifsc,
      full_name: kyc.user_name 
    }),
    onMutate: () => setBankStatus('verifying'),
    onSuccess: () => {
      setBankStatus('verified');
      toast.success("Bank Account Verified");
    },
    onError: () => {
      setBankStatus('failed');
      toast.error("Bank Verification Failed");
    }
  });

  // ... (Render Logic) ...

  return (
    <Card>
      {/* ... Header ... */}
      <CardContent>
        <div className="space-y-6">
          {/* PAN Section with Verification */}
          <div className="space-y-2">
            <Label>PAN Number</Label>
            <div className="flex gap-2">
              <Input 
                value={formData.pan_number} 
                onChange={(e) => setFormData({...formData, pan_number: e.target.value})}
                disabled={panStatus === 'verified'}
              />
              <Button 
                type="button"
                variant="outline"
                onClick={() => verifyPanMutation.mutate()}
                disabled={panStatus === 'verified' || panStatus === 'verifying'}
              >
                {panStatus === 'verifying' ? <Loader2 className="animate-spin" /> : 
                 panStatus === 'verified' ? <CheckCircle className="text-green-500" /> : "Verify"}
              </Button>
            </div>
          </div>

          {/* Bank Section with Verification */}
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Bank Account</Label>
              <Input 
                value={formData.bank_account} 
                onChange={(e) => setFormData({...formData, bank_account: e.target.value})}
              />
            </div>
            <div className="space-y-2">
              <Label>IFSC</Label>
              <div className="flex gap-2">
                <Input 
                  value={formData.bank_ifsc} 
                  onChange={(e) => setFormData({...formData, bank_ifsc: e.target.value})}
                />
                <Button 
                  type="button"
                  variant="outline"
                  onClick={() => verifyBankMutation.mutate()}
                  disabled={bankStatus === 'verified' || bankStatus === 'verifying'}
                >
                  {bankStatus === 'verifying' ? <Loader2 className="animate-spin" /> : 
                   bankStatus === 'verified' ? <CheckCircle className="text-green-500" /> : "Verify"}
                </Button>
              </div>
            </div>
          </div>

          {/* ... Rest of the form (File Inputs & Submit) ... */}
        </div>
      </CardContent>
    </Card>
  );
}