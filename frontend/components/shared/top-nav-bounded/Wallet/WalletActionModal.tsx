import { useState, useEffect } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Loader2, AlertTriangle, ShieldCheck } from "lucide-react";
import { toast } from "sonner";
import api from "@/lib/api";
import { WalletConfig, WithdrawalQuote } from "@/types/domain/wallet";
import { useRouter } from "next/navigation";

interface WalletActionModalProps {
  isOpen: boolean;
  onClose: () => void;
  mode: "add" | "withdraw";
}

export function WalletActionModal({ isOpen, onClose, mode }: WalletActionModalProps) {
  const router = useRouter();
  const [amount, setAmount] = useState("");
  const [debouncedAmount, setDebouncedAmount] = useState("");

  // 1. FETCH AUTHORITY (Rules)
  const { data: rules, isLoading: rulesLoading, error: rulesError } = useQuery<WalletConfig>({
    queryKey: ["wallet-rules"],
    queryFn: async () => {
      const res = await api.get("/user/wallet/rules"); 
      return res.data.data;
    },
    enabled: isOpen,
    staleTime: 0, 
  });

  // Debounce logic
  useEffect(() => {
    const handler = setTimeout(() => { setDebouncedAmount(amount); }, 600);
    return () => clearTimeout(handler);
  }, [amount]);

  // 2. FETCH REALITY (Quote)
  const { data: quote, isLoading: quoteLoading, error: quoteError } = useQuery<WithdrawalQuote>({
    queryKey: ["withdrawal-quote", debouncedAmount],
    queryFn: async () => {
      const res = await api.post("/user/wallet/withdraw/preview", { amount: parseFloat(debouncedAmount) });
      return res.data.data;
    },
    enabled: mode === "withdraw" && !!debouncedAmount && !isNaN(parseFloat(debouncedAmount)),
    retry: false,
  });

  // 3. EXECUTE
  const executeTransaction = useMutation({
    mutationFn: async () => {
        if (mode === "add") {
             return Promise.resolve({ action: 'redirect', amount: parseFloat(amount) });
        }
        return await api.post("/user/wallet/withdraw", { amount: parseFloat(amount) });
    },
    onSuccess: (data: any) => {
      if (mode === "add") {
          onClose();
          router.push(`/payment/gateway?amount=${data.amount}&type=wallet`);
          return;
      }
      toast.success("Request Processed", { description: `Ref: ${data.data.reference_id}` });
      onClose();
    },
    onError: (err: any) => {
      toast.error("Transaction Rejected", { description: err.response?.data?.message || "Server denied request" });
    }
  });

  const currentLimits = mode === "add" ? rules?.limits.deposit : rules?.limits.withdrawal;
  const isBlocked = mode === "withdraw" && rules?.capabilities.can_withdraw === false;

  // [PROTOCOL 1 FIX] Explicitly access step from the deposit object, fallback to 1
  const stepValue = (mode === "add" && rules) ? rules.limits.deposit.step : 1;

  if (rulesLoading) return <Dialog open={isOpen}><DialogContent><div className="flex justify-center p-8"><Loader2 className="animate-spin" /></div></DialogContent></Dialog>;
  
  if (rulesError || !rules) return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent>
        <Alert variant="destructive"><AlertTriangle className="h-4 w-4" /><AlertDescription>Financial services currently unavailable.</AlertDescription></Alert>
      </DialogContent>
    </Dialog>
  );

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{mode === "add" ? "Add Funds" : "Withdraw Funds"}</DialogTitle>
          <DialogDescription>
             {rules.messages.sla_text}
          </DialogDescription>
        </DialogHeader>

        {isBlocked ? (
           <Alert variant="destructive">
             <AlertTriangle className="h-4 w-4" />
             <AlertDescription>{rules.messages.withdrawal_blocked}</AlertDescription>
           </Alert>
        ) : (
          <div className="grid gap-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="amount">Amount (INR)</Label>
              <Input
                id="amount"
                type="number"
                value={amount}
                min={currentLimits?.min}
                max={currentLimits?.max}
                step={stepValue}
                onChange={(e) => setAmount(e.target.value)}
                className={quoteError ? "border-red-500" : ""}
              />
              {quoteError && <p className="text-xs text-red-500">{(quoteError as any).response?.data?.message || "Invalid amount"}</p>}
              <p className="text-[10px] text-muted-foreground text-right">
                Limit: ₹{currentLimits?.min} - ₹{currentLimits?.max.toLocaleString()}
              </p>
            </div>

            {mode === "withdraw" && quote && (
              <div className="bg-muted/40 p-3 rounded-lg space-y-2 text-sm border border-dashed">
                <div className="flex justify-between"><span>Fees:</span><span>-₹{quote.breakdown.fee}</span></div>
                <div className="flex justify-between"><span>TDS:</span><span>-₹{quote.breakdown.tds}</span></div>
                <div className="flex justify-between font-bold pt-2 border-t"><span>Net Payout:</span><span>₹{quote.net_amount}</span></div>
                {quote.workflow.requires_manual_review && (
                  <div className="flex items-center gap-2 text-amber-600 text-xs mt-2 bg-amber-50 p-2 rounded">
                    <ShieldCheck className="h-3 w-3" />
                    <span>Compliance review required.</span>
                  </div>
                )}
                <p className="text-[10px] text-muted-foreground mt-2 text-center">{quote.disclaimer}</p>
              </div>
            )}
            {quoteLoading && <div className="text-center text-xs text-muted-foreground animate-pulse">Calculating...</div>}
          </div>
        )}

        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button 
            onClick={() => executeTransaction.mutate()}
            disabled={executeTransaction.isPending || isBlocked || !amount || (mode === "withdraw" && !quote)}
          >
            {executeTransaction.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            Confirm {mode === "add" ? "Payment" : "Withdrawal"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}