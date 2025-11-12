// V-PHASE5-1730-118 (REVISED)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { usePlans } from "@/lib/hooks";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { useRouter } from "next/navigation";

declare var Razorpay: any;

export default function SubscriptionPage() {
  // const { toast } = useToast(); // <-- REMOVED
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data: sub, isLoading: isSubLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => (await api.get('/user/subscription')).data,
    retry: false,
  });

  const { data: plans } = usePlans();
  
  const createSubMutation = useMutation({
    mutationFn: (planId: number) => api.post('/user/subscription', { plan_id: planId }),
    onSuccess: () => {
      toast.success("Subscribed!", { description: "Please make your first payment." }); // <-- REVISED
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
    },
    onError: (error: any) => {
      toast.error("Subscription Failed", { description: error.response?.data?.message }); // <-- REVISED
    }
  });

  const paymentMutation = useMutation({
    mutationFn: (paymentId: number) => api.post('/user/payment/initiate', { payment_id: paymentId }),
    onSuccess: (response) => {
      const data = response.data;
      const options = {
        key: data.razorpay_key,
        amount: data.amount,
        currency: "INR",
        name: data.name,
        description: data.description,
        order_id: data.order_id,
        handler: function (response: any) {
          toast.success("Payment Successful!", { description: "Verifying payment..." }); // <-- REVISED
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
      toast.error("Payment Failed", { description: error.response?.data?.message }); // <-- REVISED
    }
  });

  if (isSubLoading) return <div>Loading subscription...</div>;

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