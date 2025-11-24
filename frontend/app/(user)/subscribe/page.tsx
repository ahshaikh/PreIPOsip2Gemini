// V-FINAL-1730-631 (Created)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Loader2, CheckCircle } from "lucide-react";
import { useAuth } from "@/context/AuthContext";

export default function SubscribePage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const { user } = useAuth();
  
  const [planSlug, setPlanSlug] = useState<string | null>(null);
  const [selectedPlan, setSelectedPlan] = useState<any>(null);

  // 1. Get all public plans
  const { data: plans, isLoading: isLoadingPlans } = useQuery({
    queryKey: ['publicPlans'],
    queryFn: async () => (await api.get('/plans')).data,
  });

  // 2. On load, find the user's chosen plan
  useEffect(() => {
    const slug = localStorage.getItem('pending_plan');
    setPlanSlug(slug);
    
    if (slug && plans) {
      const plan = plans.find((p: any) => p.slug === slug);
      if (plan) {
        setSelectedPlan(plan);
      } else {
        toast.error("Plan not found", { description: "The plan you selected is no longer available." });
        localStorage.removeItem('pending_plan');
        router.push('/plans');
      }
    }
  }, [plans, router]);

  // 3. Subscription Creation Mutation
  const createSubMutation = useMutation({
    mutationFn: (planId: number) => api.post('/user/subscription', { plan_id: planId }),
    onSuccess: (data)_ => {
      toast.success("Subscription Created!", { description: "Redirecting you to payment..." });
      localStorage.removeItem('pending_plan');
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      
      // Redirect to the dashboard, which will show the "Pay Now" button
      router.push('/dashboard');
    },
    onError: (e: any) => toast.error("Subscription Failed", { description: e.response?.data?.message })
  });

  const handleConfirm = ()_ => {
    if (!selectedPlan) return;
    createSubMutation.mutate(selectedPlan.id);
  };

  if (isLoadingPlans || !selectedPlan) {
    return (
      <div className="container flex items-center justify-center py-40">
        <Loader2 className="h-12 w-12 animate-spin text-primary" />
        <p className="ml-4 text-xl text-muted-foreground">Loading your plan...</p>
      </div>
    );
  }
  
  if (user?.kyc?.status !== 'verified') {
     return (
      <div className="container max-w-lg py-20 text-center">
        <AlertTriangle className="h-12 w-12 text-destructive mx-auto mb-4" />
        <h1 className="text-3xl font-bold mb-4">KYC Verification Required</h1>
        <p className="text-muted-foreground mb-6">
          Your KYC must be verified before you can start a subscription.
          Please complete your KYC, and then you can proceed with this plan.
        </p>
        <Button onClick={() => router.push('/kyc')}>Complete Your KYC</Button>
      </div>
    );
  }

  return (
    <div className="container max-w-lg py-20">
      <Card>
        <CardHeader className="text-center">
          <CheckCircle className="h-12 w-12 text-green-500 mx-auto mb-4" />
          <CardTitle className="text-3xl">One Last Step</CardTitle>
          <CardDescription>
            You're all set! Please confirm your subscription to the plan you selected.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <Card className="p-6 bg-muted">
            <div className="flex justify-between items-center">
              <div>
                <Label>Your Plan</Label>
                <p className="text-2xl font-bold">{selectedPlan.name}</p>
              </div>
              <div className="text-right">
                <Label>Monthly Amount</Label>
                <p className="text-2xl font-bold">₹{selectedPlan.monthly_amount.toLocaleString('en-IN')}</p>
              </div>
            </div>
          </Card>
          <Button 
            onClick={handleConfirm} 
            disabled={createSubMutation.isPending} 
            className="w-full" 
            size="lg"
          >
            {createSubMutation.isPending ? "Confirming..." : "Confirm & Proceed to Payment"}
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}

// Need to import AlertTriangle
import { AlertTriangle } from "lucide-react";