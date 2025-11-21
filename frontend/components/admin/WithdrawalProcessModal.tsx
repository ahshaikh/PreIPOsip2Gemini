// V-PHASE6-1730-130 (Created - Revised)
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
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { useMutation } from "@tanstack/react-query";
import { useState } from "react";

export function WithdrawalProcessModal({ withdrawal, onClose }: { withdrawal: any, onClose: () => void }) {
  // const { toast } = useToast(); // <-- REMOVED
  const [step, setStep] = useState(withdrawal.status === 'pending' ? 'approve' : 'complete');
  const [reason, setReason] = useState('');
  const [utr, setUtr] = useState('');

  const approveMutation = useMutation({
    mutationFn: () => api.post(`/admin/withdrawal-queue/${withdrawal.id}/approve`),
    onSuccess: () => {
      toast.success("Withdrawal Approved", { description: "Ready to be processed." }); // <-- REVISED
      setStep('complete');
    },
  });

  const rejectMutation = useMutation({
    mutationFn: (reason: string) => api.post(`/admin/withdrawal-queue/${withdrawal.id}/reject`, { reason }),
    onSuccess: () => {
      toast.success("Withdrawal Rejected", { description: "Funds returned to user." }); // <-- REVISED
      onClose();
    },
  });

  const completeMutation = useMutation({
    mutationFn: (utr: string) => api.post(`/admin/withdrawal-queue/${withdrawal.id}/complete`, { utr_number: utr }),
    onSuccess: () => {
      toast.success("Withdrawal Completed!", { description: "Funds have been processed." }); // <-- REVISED
      onClose();
    },
  });

  return (
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Review Withdrawal: W-{withdrawal.id}</DialogTitle>
        <DialogDescription>
          User: {withdrawal.user.username} | Amount: ₹{withdrawal.amount}
        </DialogDescription>
      </DialogHeader>
      
      {step === 'approve' && (
        <div className="space-y-4">
          <h3 className="font-semibold">Step 1: Approve or Reject</h3>
          <p>Review the details and approve to move to processing.</p>
          <div className="space-y-2">
            <Label htmlFor="reason">Rejection Reason (if rejecting)</Label>
            <Textarea id="reason" value={reason} onChange={(e) => setReason(e.target.value)} />
          </div>
          <DialogFooter>
            <Button 
              variant="destructive" 
              onClick={() => rejectMutation.mutate(reason)}
              disabled={!reason || rejectMutation.isPending}
            >
              Reject
            </Button>
            <Button 
              variant="default"
              onClick={() => approveMutation.mutate()}
              disabled={approveMutation.isPending}
            >
              Approve
            </Button>
          </DialogFooter>
        </div>
      )}

      {step === 'complete' && (
        <div className="space-y-4">
          <h3 className="font-semibold">Step 2: Process & Complete</h3>
          <p className="text-green-600 font-medium">This request is APPROVED.</p>
          <p>Please send ₹{withdrawal.net_amount} to the user's bank. After completion, enter the UTR number to finalize.</p>
          <div className="space-y-2">
            <Label htmlFor="utr">UTR Number</Label>
            <Input id="utr" value={utr} onChange={(e) => setUtr(e.target.value)} required />
          </div>
          <DialogFooter>
            <Button 
              variant="default"
              onClick={() => completeMutation.mutate(utr)}
              disabled={!utr || completeMutation.isPending}
            >
              {completeMutation.isPending ? "Completing..." : "Mark as Completed"}
            </Button>
          </DialogFooter>
        </div>
      )}
    </DialogContent>
  );
}