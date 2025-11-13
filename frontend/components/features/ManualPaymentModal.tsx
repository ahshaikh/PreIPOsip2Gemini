// V-REMEDIATE-1730-195
'use client';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation } from "@tanstack/react-query";
import { useState } from "react";
import { Copy, QrCode, Building2, UploadCloud } from "lucide-react";

interface ManualPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  paymentId: number;
  amount: number;
  bankDetails: any; // Passed from parent (fetched from settings)
}

export function ManualPaymentModal({ isOpen, onClose, paymentId, amount, bankDetails }: ManualPaymentModalProps) {
  const [utr, setUtr] = useState('');
  const [file, setFile] = useState<File | null>(null);

  const mutation = useMutation({
    mutationFn: (formData: FormData) => api.post('/user/payment/manual', formData),
    onSuccess: () => {
      toast.success("Proof Submitted", { description: "Admin will verify within 24 hours." });
      onClose();
    },
    onError: (e: any) => toast.error("Submission Failed", { description: e.response?.data?.message })
  });

  const handleSubmit = () => {
    if (!utr || !file) {
      toast.error("Missing Info", { description: "Please provide both UTR and Screenshot." });
      return;
    }
    const formData = new FormData();
    formData.append('payment_id', paymentId.toString());
    formData.append('utr_number', utr);
    formData.append('payment_proof', file);
    mutation.mutate(formData);
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    toast.success("Copied to clipboard");
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Bank Transfer / UPI</DialogTitle>
          <DialogDescription>
            Transfer <strong>₹{amount}</strong> using the details below, then upload proof.
          </DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="upi" className="w-full">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="upi">UPI / QR</TabsTrigger>
            <TabsTrigger value="bank">Bank Details</TabsTrigger>
          </TabsList>

          {/* UPI / QR Tab */}
          <TabsContent value="upi" className="space-y-4 py-4">
            <div className="flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-lg bg-muted/50">
              {bankDetails.bank_qr_code ? (
                <img src={bankDetails.bank_qr_code} alt="Payment QR" className="w-48 h-48 object-contain" />
              ) : (
                <QrCode className="w-32 h-32 text-muted-foreground opacity-20" />
              )}
              <p className="mt-2 text-xs text-muted-foreground">Scan to Pay</p>
            </div>
            
            <div className="space-y-2">
              <Label>UPI ID</Label>
              <div className="flex gap-2">
                <Input value={bankDetails.bank_upi_id || 'preiposip@bank'} readOnly />
                <Button variant="outline" size="icon" onClick={() => copyToClipboard(bankDetails.bank_upi_id)}>
                  <Copy className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </TabsContent>

          {/* Bank Details Tab */}
          <TabsContent value="bank" className="space-y-4 py-4">
            <div className="space-y-3">
              <div>
                <Label className="text-xs text-muted-foreground">Account Name</Label>
                <div className="flex justify-between items-center font-medium">
                  {bankDetails.bank_account_name || 'Company Name'}
                  <Copy className="h-3 w-3 cursor-pointer text-muted-foreground" onClick={() => copyToClipboard(bankDetails.bank_account_name)} />
                </div>
              </div>
              <div className="border-t pt-2">
                <Label className="text-xs text-muted-foreground">Account Number</Label>
                <div className="flex justify-between items-center font-medium">
                  {bankDetails.bank_account_number || 'XXXXXX'}
                  <Copy className="h-3 w-3 cursor-pointer text-muted-foreground" onClick={() => copyToClipboard(bankDetails.bank_account_number)} />
                </div>
              </div>
              <div className="border-t pt-2">
                <Label className="text-xs text-muted-foreground">IFSC Code</Label>
                <div className="flex justify-between items-center font-medium">
                  {bankDetails.bank_ifsc || 'XXXX000'}
                  <Copy className="h-3 w-3 cursor-pointer text-muted-foreground" onClick={() => copyToClipboard(bankDetails.bank_ifsc)} />
                </div>
              </div>
            </div>
          </TabsContent>
        </Tabs>

        {/* Submission Form */}
        <div className="border-t pt-4 space-y-4">
          <div className="space-y-2">
            <Label>Transaction ID / UTR</Label>
            <Input 
              placeholder="Enter 12-digit UTR" 
              value={utr} 
              onChange={(e) => setUtr(e.target.value)} 
            />
          </div>
          <div className="space-y-2">
            <Label>Upload Screenshot</Label>
            <Input 
              type="file" 
              accept="image/*,application/pdf"
              onChange={(e) => setFile(e.target.files ? e.target.files[0] : null)}
            />
          </div>
        </div>

        <DialogFooter>
          <Button className="w-full" onClick={handleSubmit} disabled={mutation.isPending}>
            {mutation.isPending ? "Submitting..." : "Submit Payment Proof"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}