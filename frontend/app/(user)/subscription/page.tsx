// V-FINAL-1730-264 (COMPLETE CONSOLIDATED FILE)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/lib/api";
import { usePlans } from "@/lib/hooks";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { useRouter } from "next/navigation";
import { Download, CreditCard, Zap, Settings, Building2 } from "lucide-react";
import { useState } from "react";
import { ManageSubscriptionModal } from "@/components/features/ManageSubscriptionModal";
import { ManualPaymentModal } from "@/components/features/ManualPaymentModal";

declare var Razorpay: any;

export default function SubscriptionPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  
  // UI State
  const [isDownloading, setIsDownloading] = useState<number | null>(null);
  const [isManageOpen, setIsManageOpen] = useState(false);
  const [isManualPayOpen, setIsManualPayOpen] = useState(false);
  
  // Payment State
  const [autoDebitEnabled, setAutoDebitEnabled] = useState(false);
  const [selectedPaymentId, setSelectedPaymentId] = useState<number | null>(null);
  const [paymentAmount, setPaymentAmount] = useState<number>(0);

  // Bank Details (Mocked or fetched from settings)
  const bankDetails = {
    bank_account_name: 'PreIPO SIP Pvt Ltd',
    bank_account_number: '123456789012',
    bank_ifsc: 'HDFC0001234',
    bank_upi_id: 'preiposip@hdfcbank',
    bank_qr_code: '' 
  };

  // 1. Fetch Subscription
  const { data: sub, isLoading: isSubLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => (await api.get('/user/subscription')).data,
    retry: false,
  });

  // 2. Fetch Plans
  const { data: plans } = usePlans();
  
  // 3. Create Subscription Mutation
  const createSubMutation = useMutation({
    mutationFn: (planId: number) => api.post('/user/subscription', { plan_id: planId }),
    onSuccess: () => {
      toast.success("Subscribed!", { description: "Please make your first payment." });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
    },
    onError: (error: any) => {
      toast.error("Subscription Failed", { description: error.response?.data?.message });
    }
  });

  // 4. Payment Initiation Mutation
  const paymentMutation = useMutation({
    mutationFn: (paymentId: number) => api.post('/user/payment/initiate', { 
      payment_id: paymentId, 
      enable_auto_debit: autoDebitEnabled 
    }),
    onSuccess: (response) => {
      const data = response.data;
      
      const options: any = {
        key: data.razorpay_key,
        name: data.name,
        description: data.description,
        prefill: data.prefill,
        handler: function (response: any) {
          toast.success("Payment Successful!", { description: "Verifying payment..." });
          queryClient.invalidateQueries({ queryKey: ['subscription'] });
          queryClient.invalidateQueries({ queryKey: ['portfolio'] });
          queryClient.invalidateQueries({ queryKey: ['dashboardSummary'] });
        },
      };

      if (data.type === 'subscription') {
        options.subscription_id = data.subscription_id; // Recurring
      } else {
        options.order_id = data.order_id; // One-time
        options.amount = data.amount;
        options.currency = "INR";
      }
      
      const rzp = new (window as any).Razorpay(options);
      rzp.open();
    },
    onError: (error: any) => {
      toast.error("Payment Failed", { description: error.response?.data?.message });
    }
  });

  // 5. Invoice Download Handler
  const handleDownloadInvoice = async (paymentId: number) => {
    setIsDownloading(paymentId);
    try {
      const response = await api.get(`/user/payments/${paymentId}/invoice`, {
        responseType: 'blob',
      });
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `receipt-${paymentId}.pdf`);
      document.body.appendChild(link);
      link.click();
      window.URL.revokeObjectURL(url);
      link.remove();
    } catch (error) {
      toast.error("Download Failed");
    } finally {
      setIsDownloading(null);
    }
  };

  const handleManualPayClick = (paymentId: number, amount: number) => {
    setSelectedPaymentId(paymentId);
    setPaymentAmount(amount);
    setIsManualPayOpen(true);
  };

  if (isSubLoading) return <div>Loading subscription...</div>;

  // --- STATE 1: NO SUBSCRIPTION ---
  if (!sub) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>No Active Subscription</CardTitle>
          <CardDescription>You do not have an active subscription. Please choose a plan to start.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-2 gap-4">
            {plans?.map((plan: any) => (
              <Card key={plan.id}>
                <CardHeader>
                  <CardTitle>{plan.name}</CardTitle>
                  <CardDescription>₹{plan.monthly_amount}/month</CardDescription>
                </CardHeader>
                <CardContent>
                  <Button 
                    className="w-full"
                    onClick={() => createSubMutation.mutate(plan.id)}
                    disabled={createSubMutation.isPending}
                  >
                    {createSubMutation.isPending ? "Subscribing..." : "Subscribe"}
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  // --- STATE 2: ACTIVE SUBSCRIPTION ---
  const pendingPayment = sub.payments.find((p: any) => p.status === 'pending');

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader className="flex flex-row items-start justify-between">
          <div>
              <CardTitle>My Subscription: {sub.plan.name}</CardTitle>
              <CardDescription>Status: <span className="capitalize font-medium text-primary">{sub.status}</span></CardDescription>
          </div>
          {sub.status !== 'cancelled' && (
              <Button variant="outline" size="sm" onClick={() => setIsManageOpen(true)}>
                  <Settings className="mr-2 h-4 w-4" /> Manage
              </Button>
          )}
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Pending Payment Alert */}
          {pendingPayment && (
            <Alert variant="default" className="border-primary/50 bg-primary/5">
              <Zap className="h-4 w-4" />
              <AlertTitle>Payment Due: ₹{pendingPayment.amount}</AlertTitle>
              <AlertDescription>
                <div className="mt-4 space-y-4">
                  {/* Auto Debit Toggle */}
                  {!sub.is_auto_debit && (
                    <div className="flex items-center space-x-2">
                      <Switch id="auto-debit" checked={autoDebitEnabled} onCheckedChange={setAutoDebitEnabled} />
                      <Label htmlFor="auto-debit">Enable Auto-Debit (Set up Recurring Mandate)</Label>
                    </div>
                  )}
                  
                  <div className="flex flex-col sm:flex-row gap-2">
                    <Button 
                      onClick={() => paymentMutation.mutate(pendingPayment.id)}
                      disabled={paymentMutation.isPending}
                      className="flex-1"
                    >
                      <CreditCard className="mr-2 h-4 w-4" />
                      {paymentMutation.isPending ? "Processing..." : (autoDebitEnabled ? "Pay & Setup Auto-Debit" : "Pay Online")}
                    </Button>
                    
                    <Button 
                      variant="outline"
                      onClick={() => handleManualPayClick(pendingPayment.id, parseFloat(pendingPayment.amount))}
                      className="flex-1"
                    >
                      <Building2 className="mr-2 h-4 w-4" />
                      Bank Transfer
                    </Button>
                  </div>
                </div>
              </AlertDescription>
            </Alert>
          )}
          
          {/* Payment History */}
          <div>
            <h3 className="font-semibold mb-2">Payment History</h3>
            <div className="border rounded-lg">
              {sub.payments.map((p: any) => (
                <div key={p.id} className="flex justify-between items-center p-4 border-b last:border-b-0">
                  <div>
                    <p className="font-medium">Payment for {new Date(p.created_at).toLocaleDateString()}</p>
                    <p className="text-sm text-muted-foreground">Amount: ₹{p.amount}</p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      p.status === 'paid' ? 'bg-green-100 text-green-800' :
                      p.status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' :
                      p.status === 'pending' ? 'bg-orange-100 text-orange-800' :
                      'bg-red-100 text-red-800'
                    }`}>
                      {p.status.replace('_', ' ')}
                    </span>
                    
                    {p.status === 'paid' && (
                      <Button 
                        variant="outline" 
                        size="sm"
                        onClick={() => handleDownloadInvoice(p.id)}
                        disabled={isDownloading === p.id}
                      >
                        <Download className="mr-2 h-4 w-4" />
                        {isDownloading === p.id ? "..." : "Receipt"}
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Modals */}
      <ManageSubscriptionModal 
        isOpen={isManageOpen} 
        onClose={() => setIsManageOpen(false)}
        currentPlanId={sub.plan.id}
        plans={plans || []}
        status={sub.status}
      />

      {selectedPaymentId && (
        <ManualPaymentModal 
          isOpen={isManualPayOpen}
          onClose={() => setIsManualPayOpen(false)}
          paymentId={selectedPaymentId}
          amount={paymentAmount}
          bankDetails={bankDetails}
        />
      )}
    </div>
  );
}