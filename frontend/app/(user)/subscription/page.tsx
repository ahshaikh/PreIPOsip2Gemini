// V-PHASE5-1730-118
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useToast } from "@/components/ui/use-toast";
import api from "@/lib/api";
import { usePlans } from "@/lib/hooks";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";
import { useRouter } from "next/navigation";

// This is a global declaration for Razorpay
declare var Razorpay: any;

export default function SubscriptionPage() {
  const { toast } = useToast();
  const router = useRouter();
  const queryClient = useQueryClient();

  // 1. Fetch user's subscription
  const { data: sub, isLoading: isSubLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => (await api.get('/user/subscription')).data,
    retry: false, // Don't retry on 404
  });

  // 2. Fetch all available plans
  const { data: plans } = usePlans();
  
  // 3. Mutation to create a subscription
  const createSubMutation = useMutation({
    mutationFn: (planId: number) => api.post('/user/subscription', { plan_id: planId }),
    onSuccess: () => {
      toast({ title: "Subscribed!", description: "Please make your first payment." });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
    },
    onError: (error: any) => {
      toast({ title: "Subscription Failed", description: error.response?.data?.message, variant: "destructive" });
    }
  });

  // 4. Mutation to initiate a payment
  const paymentMutation = useMutation({
    mutationFn: (paymentId: number) => api.post('/user/payment/initiate', { payment_id: paymentId }),
    onSuccess: (response) => {
      // 5. Open Razorpay checkout
      const data = response.data;
      const options = {
        key: data.razorpay_key,
        amount: data.amount,
        currency: "INR",
        name: data.name,
        description: data.description,
        order_id: data.order_id,
        handler: function (response: any) {
          // 6. Verify payment
          toast({ title: "Payment Successful!", description: "Verifying payment..." });
          // In a real app, we'd verify this, but we'll trust the webhook
          // and just invalidate the queries.
          queryClient.invalidateQueries({ queryKey: ['subscription'] });
          queryClient.invalidateQueries({ queryKey: ['portfolio'] });
          queryClient.invalidateQueries({ queryKey: ['dashboardSummary'] });
        },
        prefill: data.prefill,
      };
      
      const rzp = new (window as any).Razorpay(options);
      rzp.open();
    },
    onError: (error: any) => {
      toast({ title: "Payment Failed", description: error.response?.data?.message, variant: "destructive" });
    }
  });

  if (isSubLoading) return <div>Loading subscription...</div>;

  // --- RENDER STATE: NO SUBSCRIPTION ---
  if (!sub) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>No Active Subscription</CardTitle>
          <CardDescription>You do not have an active subscription. Please choose a plan to start.</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-2 gap-4">
            {plans?.map(plan => (
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

  // --- RENDER STATE: ACTIVE SUBSCRIPTION ---
  const pendingPayment = sub.payments.find((p: any) => p.status === 'pending');

  return (
    <Card>
      <CardHeader>
        <CardTitle>My Subscription: {sub.plan.name}</CardTitle>
        <CardDescription>Status: <span className="capitalize font-medium text-primary">{sub.status}</span></CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {pendingPayment && (
          <Alert variant="warning">
            <AlertTitle>Payment Due</AlertTitle>
            <AlertDescription>
              Your payment of ₹{pendingPayment.amount} is pending.
              <Button 
                className="ml-4" 
                onClick={() => paymentMutation.mutate(pendingPayment.id)}
                disabled={paymentMutation.isPending}
              >
                {paymentMutation.isPending ? "Loading..." : "Pay Now"}
              </Button>
            </AlertDescription>
          </Alert>
        )}
        
        <div>
          <h3 className="font-semibold mb-2">Payment History</h3>
          <div className="border rounded-lg">
            {sub.payments.map((p: any) => (
              <div key={p.id} className="flex justify-between items-center p-4 border-b last:border-b-0">
                <div>
                  <p className="font-medium">Payment for {new Date(p.created_at).toLocaleDateString()}</p>
                  <p className="text-sm text-muted-foreground">Amount: ₹{p.amount}</p>
                </div>
                <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                  p.status === 'paid' ? 'bg-green-100 text-green-800' :
                  p.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-red-100 text-red-800'
                }`}>
                  {p.status}
                </span>
              </div>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}