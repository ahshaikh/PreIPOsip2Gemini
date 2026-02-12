'use client';

import { Check, Sparkles, TrendingUp, Zap, Crown, Star, ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import api from "@/lib/api";
import { formatCurrencyINR } from "@/lib/utils";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { useRouter } from "next/navigation";
import type { PlanWithRelations, PlanFeature } from "@/types/plan";

// API error type for mutations
type ApiError = Error & { response?: { data?: { message?: string } } };

export default function UserPlansPage() {
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data: plans, isLoading } = useQuery<PlanWithRelations[]>({
    queryKey: ['plans'],
    queryFn: async () => (await api.get('/plans')).data,
  });

  // Check if user already has a subscription
  const { data: subscription, isLoading: isSubscriptionLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => {
      const response = await api.get('/user/subscription');
      // Defensive check: Ensure null/undefined is treated as no subscription
      // Empty objects or arrays should also be treated as no subscription
      const sub = response.data;
      if (!sub || (typeof sub === 'object' && Object.keys(sub).length === 0)) {
        return null;
      }
      return sub;
    },
    retry: false,
  });

  // Defensive check: Treat invalid subscription data as no subscription
  const hasActiveSubscription = subscription && subscription.id && subscription.status;

  // Subscribe mutation (for new subscriptions)
  const subscribeMutation = useMutation({
    mutationFn: (planId: number) => api.post('/user/subscription', { plan_id: planId }),
    onSuccess: (response) => {
      const data = response.data;
      const paidFromWallet = data.paid_from_wallet;
      const redirectTo = data.redirect_to;

      // Show appropriate toast message
      toast.success(
        paidFromWallet ? "Subscription Activated!" : "Subscribed!",
        { description: data.message || (paidFromWallet ? "Payment deducted from wallet." : "Please complete your first payment.") }
      );

      queryClient.invalidateQueries({ queryKey: ['subscription'] });

      // Redirect based on payment method
      if (redirectTo === 'companies') {
        router.push('/deals'); // Browse available deals after wallet payment
      } else {
        router.push('/subscription'); // Complete payment via gateway
      }
    },
    onError: (error: ApiError) => {
      toast.error("Subscription Failed", { description: error.response?.data?.message });
    }
  });

  // Change plan mutation (for existing subscriptions)
  const changePlanMutation = useMutation({
    mutationFn: (planId: number) => api.post('/user/subscription/change-plan', { new_plan_id: planId }),
    onSuccess: (response) => {
      const message = response.data?.message || "Plan changed successfully!";
      toast.success("Plan Changed!", { description: message });
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      router.push('/subscription');
    },
    onError: (error: ApiError) => {
      toast.error("Plan Change Failed", { description: error.response?.data?.message || "Please try again" });
    }
  });

  // Color schemes for different plan tiers
  const planColors = [
    {
      gradient: "from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30",
      border: "border-amber-200 dark:border-amber-800",
      iconBg: "bg-amber-100 dark:bg-amber-900/50",
      iconColor: "text-amber-600 dark:text-amber-400",
      buttonClass: "bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700",
      icon: Sparkles,
    },
    {
      gradient: "from-blue-50 to-cyan-50 dark:from-blue-950/30 dark:to-cyan-950/30",
      border: "border-blue-200 dark:border-blue-800",
      iconBg: "bg-blue-100 dark:bg-blue-900/50",
      iconColor: "text-blue-600 dark:text-blue-400",
      buttonClass: "bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700",
      icon: TrendingUp,
    },
    {
      gradient: "from-purple-50 to-pink-50 dark:from-purple-950/30 dark:to-pink-950/30",
      border: "border-purple-200 dark:border-purple-800",
      iconBg: "bg-purple-100 dark:bg-purple-900/50",
      iconColor: "text-purple-600 dark:text-purple-400",
      buttonClass: "bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700",
      icon: Crown,
    },
    {
      gradient: "from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/30",
      border: "border-emerald-200 dark:border-emerald-800",
      iconBg: "bg-emerald-100 dark:bg-emerald-900/50",
      iconColor: "text-emerald-600 dark:text-emerald-400",
      buttonClass: "bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700",
      icon: Zap,
    },
  ];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-muted-foreground">Loading plans...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="text-center max-w-3xl mx-auto">
        {/*  <div className="inline-flex items-center space-x-2 bg-gradient-to-r from-purple-100 to-blue-100 dark:from-purple-900/50 dark:to-blue-900/50 rounded-full px-6 py-3 mb-6 border border-purple-200 dark:border-purple-800">
          <Star className="w-5 h-5 text-purple-600 dark:text-purple-400" />
           <span className="font-semibold text-purple-900 dark:text-purple-100">100% Free Plans</span>
        </div>
        */}
        <h1 className="text-4xl font-bold mb-4">
          Choose Your Investment Plan
        </h1>

       {/*  <p className="text-lg text-muted-foreground mb-2">
          All plans are 100% free. No platform fees, no exit fees.
        </p>
        <p className="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-400 dark:to-emerald-400">
          Your investment, your profit, plus our bonuses.
        </p> */}
      </div>

      {/* Current Subscription Alert */}
      {hasActiveSubscription && (
        <Card className="border-blue-500 dark:border-blue-700 bg-blue-50 dark:bg-blue-950/30">
          <CardContent className="pt-6">
            <p className="text-sm">
              You're currently subscribed to <strong>{subscription.plan?.name}</strong>.
              To change plans, please go to your <a href="/subscription" className="text-blue-600 dark:text-blue-400 hover:underline">subscription page</a>.
            </p>
          </CardContent>
        </Card>
      )}

      {/* Plans Grid */}
      <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        {plans?.map((plan: PlanWithRelations, index: number) => {
          const colorScheme = planColors[index % planColors.length];
          const PlanIcon = colorScheme.icon;
          const isCurrentPlan = subscription?.plan?.id === plan.id;

          return (
            <Card
              key={plan.id}
              className={`relative overflow-hidden bg-gradient-to-br ${colorScheme.gradient} border-2 ${colorScheme.border}
                ${plan.is_featured ? 'shadow-lg scale-105 ring-2 ring-purple-600/20' : 'shadow'}
                ${isCurrentPlan ? 'ring-2 ring-green-600' : ''}
                transition-all duration-300 hover:shadow-xl`}
            >
              {plan.is_featured && (
                <div className="absolute top-1 left-1/2 -translate-x-1/2 z-10">
                  <Badge className="bg-gradient-to-r from-purple-600 to-pink-600 text-white border-0">
                    <Crown className="w-3 h-3 mr-1" />
                    MOST POPULAR
                  </Badge>
                </div>
              )}

              {isCurrentPlan && (
                <div className="absolute top-4 right-4 z-10">

                </div>
              )}

              <CardHeader className="text-center pb-4">
                <div className={`w-16 h-16 ${colorScheme.iconBg} rounded-2xl flex items-center justify-center mx-auto mb-4`}>
                  <PlanIcon className={`w-8 h-8 ${colorScheme.iconColor}`} />
                </div>

                <CardTitle className="text-2xl">{plan.name}</CardTitle>

                <div className="mt-4">
                  <div className="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600">
                    {formatCurrencyINR(plan.monthly_amount)}
                  </div>
                  <div className="text-sm text-muted-foreground mt-1">per month</div>
                </div>

                <CardDescription className="mt-3">{plan.description}</CardDescription>
              </CardHeader>

              <CardContent className="space-y-4">
                <div className="space-y-2">
                  {plan.features?.map((feature: PlanFeature) => (
                    <div key={feature.id} className="flex items-start space-x-2">
                      <Check className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
                      <span className="text-sm">{feature.feature_text}</span>
                    </div>
                  ))}
                </div>

<Button
  className="w-full bg-transparent p-0 hover:bg-transparent"
  onClick={() => {
    if (hasActiveSubscription) {
      changePlanMutation.mutate(plan.id);
    } else {
      subscribeMutation.mutate(plan.id);
    }
  }}
  disabled={subscribeMutation.isPending || changePlanMutation.isPending || isCurrentPlan}
>
  <span
    className={`
      inline-flex
      items-center
      justify-center
      gap-2
      px-6
      py-3
      rounded-xl
      text-white
      text-center
      whitespace-normal
      break-words
      ${colorScheme.buttonClass}
    `}
  >
    {(subscribeMutation.isPending || changePlanMutation.isPending) ? (
      "Processing..."
    ) : isCurrentPlan ? (
      "Current Plan"
    ) : hasActiveSubscription ? (
      <>
        Change to this plan
        <ArrowRight className="w-4 h-4 flex-shrink-0" />
      </>
    ) : (
      <>
        Choose {plan.name}
        <ArrowRight className="w-4 h-4 flex-shrink-0" />
      </>
    )}
  </span>
</Button>


              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Features Section */}
      <div className="grid md:grid-cols-3 gap-6 mt-12">
        {[
          {
            icon: Star,
            title: "Zero Platform Fees",
            description: "All plans are 100% free. No hidden charges.",
          },
          {
            icon: TrendingUp,
            title: "Bonus Returns",
            description: "Get additional bonuses on your investment returns.",
          },
          {
            icon: Crown,
            title: "Premium Access",
            description: "Access exclusive pre-IPO deals and early opportunities.",
          },
        ].map((benefit, i) => (
          <Card key={i}>
            <CardHeader>
              <div className={`w-12 h-12 ${planColors[i].iconBg} rounded-xl flex items-center justify-center mb-3`}>
                <benefit.icon className={`w-6 h-6 ${planColors[i].iconColor}`} />
              </div>
              <CardTitle className="text-lg">{benefit.title}</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">{benefit.description}</p>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
