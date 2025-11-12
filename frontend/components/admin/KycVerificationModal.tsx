// V-PHASE6-1730-128 (REVISED)
'use client';

import {
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useState } from "react";
import { Label } from "../ui/label";

export function KycVerificationModal({ kycId, onClose }: { kycId: number, onClose: () => void }) {
  // const { toast } = useToast(); // <-- REMOVED
  const [reason, setReason] = useState('');

  const { data: kyc, isLoading } = useQuery({
    queryKey: ['kycDetail', kycId],
    queryFn: async () => (await api.get(`/admin/kyc-queue/${kycId}`)).data,
  });

  const approveMutation = useMutation({
    mutationFn: () => api.post(`/admin/kyc-queue/${kycId}/approve`),
    onSuccess: () => {
      toast.success("KYC Approved"); // <-- REVISED
      onClose();
    },
    onError: (error: any) => {
      toast.error("Error", { description: error.response?.data?.message }); // <-- REVISED
    }
  });

  const rejectMutation = useMutation({
    mutationFn: (reason: string) => api.post(`/admin/kyc-queue/${kycId}/reject`, { reason }),
    onSuccess: () => {
      toast.success("KYC Rejected"); // <-- REVISED
      onClose();
    },
    onError: (error: any) => {
      toast.error("Error", { description: error.response?.data?.message }); // <-- REVISED
    }
  });

  if (isLoading) {
    return (
      <DialogContent>
        <DialogHeader><DialogTitle>Loading KYC Details...</DialogTitle></DialogHeader>
      </DialogContent>
    );
  }

  return (
    <DialogContent className="max-w-4xl">
      <DialogHeader>
        <DialogTitle>Review KYC: {kyc.user.username}</DialogTitle>
        <DialogDescription>
          Submitted at: {new Date(kyc.submitted_at).toLocaleString()}
        </DialogDescription>
      </DialogHeader>
      
      <div className="grid grid-cols-2 gap-6 max-h-[60vh] overflow-y-auto p-4">
        {/* User Details */}
        <div className="space-y-4">
          <h3 className="font-semibold">User Data</h3>
          <p><Label>PAN:</Label> {kyc.pan_number}</p>
          <p><Label>Aadhaar:</Label> {kyc.aadhaar_number}</p>
          <p><Label>Bank Account:</Label> {kyc.bank_account}</p>
          <p><Label>Bank IFSC:</Label> {kyc.bank_ifsc}</p>
          <p><Label>Demat Account:</Label> {kyc.demat_account}</p>
        </div>

        {/* Documents */}
        <div className="space-y-4">
          <h3 className="font-semibold">Documents</h3>
          {kyc.documents.map((doc: any) => (
            <div key={doc.id}>
              <Label className="capitalize">{doc.doc_type.replace('_', ' ')}</Label>
              <p><a href={`/storage/${doc.file_path}`} target="_blank" rel="noreferrer" className="text-blue-500 underline">View Document</a></p>
            </div>
          ))}
        </div>
      </div>
      
      <div className="space-y-2 mt-4">
        <Label htmlFor="reason">Rejection Reason (if rejecting)</Label>
        <Textarea id="reason" value={reason} onChange={(e) => setReason(e.target.value)} />
      </div>

      <DialogFooter>
        <Button 
          variant="destructive" 
          onClick={() => rejectMutation.mutate(reason)}
          disabled={!reason || rejectMutation.isPending}
        >
          {rejectMutation.isPending ? "Rejecting..." : "Reject"}
        </Button>
        <Button 
          variant="default"
          onClick={() => approveMutation.mutate()}
          disabled={approveMutation.isPending}
        >
          {approveMutation.isPending ? "Approving..." : "Approve"}
        </Button>
      </DialogFooter>
    </DialogContent>
  );
}