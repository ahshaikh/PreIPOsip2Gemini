'use client';

import { Check, Sparkles, TrendingUp, Zap, Crown, Star, ArrowRight, ArrowLeft, Gift, Calendar, Target, Award } from "lucide-react";
import api from "@/lib/api";
import { formatCurrencyINR } from "@/lib/utils";
import { useQuery } from "@tanstack/react-query";
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import type { PlanWithRelations, PlanFeature } from "@/types/plan";

export default function PlanDetailPage() {
  const params = useParams();
  const planId = params.id;

  const { data: plan, isLoading, error } = useQuery<PlanWithRelations>({
    queryKey: ['publicPlan', planId],
    queryFn: async () => (await api.get(`/plans/${planId}`)).data,
    staleTime: 1000 * 60 * 5, // 5 minutes
    enabled: !!planId,
  });

  // Color schemes for different plan tiers
  const planColors = [
    {
      gradient: "from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20",
      border: "border-amber-200 dark:border-amber-800",
      iconBg: "bg-amber-100 dark:bg-amber-900/30",
      iconColor: "text-amber-600 dark:text-amber-400",
      priceGradient: "from-amber-600 to-orange-600 dark:from-amber-400 dark:to-orange-400",
      buttonGradient: "from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700",
      icon: Sparkles,
    },
    {
      gradient: "from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20",
      border: "border-blue-200 dark:border-blue-800",
      iconBg: "bg-blue-100 dark:bg-blue-900/30",
      iconColor: "text-blue-600 dark:text-blue-400",
      priceGradient: "from-blue-600 to-cyan-600 dark:from-blue-400 dark:to-cyan-400",
      buttonGradient: "from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700",
      icon: TrendingUp,
    },
    {
      gradient: "from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20",
      border: "border-purple-200 dark:border-purple-800",
      iconBg: "bg-purple-100 dark:bg-purple-900/30",
      iconColor: "text-purple-600 dark:text-purple-400",
      priceGradient: "from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400",
      buttonGradient: "from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700",
      icon: Crown,
    },
    {
      gradient: "from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20",
      border: "border-emerald-200 dark:border-emerald-800",
      iconBg: "bg-emerald-100 dark:bg-emerald-900/30",
      iconColor: "text-emerald-600 dark:text-emerald-400",
      priceGradient: "from-emerald-600 to-teal-600 dark:from-emerald-400 dark:to-teal-400",
      buttonGradient: "from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700",
      icon: Zap,
    },
  ];

  if (isLoading) {
    return (
      <div className="min-h-screen bg-white dark:bg-slate-950 flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-purple-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-lg text-gray-600 dark:text-gray-400">Loading plan details...</p>
        </div>
      </div>
    );
  }

  if (error || !plan) {
    return (
      <div className="min-h-screen bg-white dark:bg-slate-950 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">Plan Not Found</h1>
          <p className="text-gray-600 dark:text-gray-400 mb-8">The plan you&apos;re looking for doesn&apos;t exist or has been removed.</p>
          <Link href="/plans">
            <Button>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to Plans
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  const colorScheme = planColors[(plan.id || 0) % planColors.length];
  const PlanIcon = colorScheme.icon;
  const totalInvestment = Math.floor((plan.monthly_amount || 0) * (plan.duration_months || 0));

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Back Button */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        <Link href="/plans" className="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to All Plans
        </Link>
      </div>

      {/* Hero Section */}
      <section className="relative pt-12 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950 overflow-hidden">
        <div className="absolute inset-0 bg-grid-slate-200 dark:bg-grid-slate-800 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]"></div>

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            {/* Plan Card */}
            <div className={`relative bg-gradient-to-br ${colorScheme.gradient} rounded-3xl p-8 border-2 ${colorScheme.border} shadow-2xl`}>
              {plan.is_featured && (
                <div className="absolute -top-4 left-1/2 -translate-x-1/2 z-20">
                  <div className="bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-bold px-6 py-2 rounded-full shadow-lg flex items-center space-x-2">
                    <Crown className="w-4 h-4" />
                    <span>FEATURED PLAN</span>
                  </div>
                </div>
              )}

              <div className="text-center mb-6">
                <div className={`w-24 h-24 ${colorScheme.iconBg} rounded-2xl flex items-center justify-center mx-auto mb-6`}>
                  <PlanIcon className={`w-12 h-12 ${colorScheme.iconColor}`} />
                </div>

                <h1 className="text-3xl font-black text-gray-900 dark:text-white mb-4">
                  {plan.name}
                </h1>

                <div className="mb-4">
                  <div className={`text-6xl font-black text-transparent bg-clip-text bg-gradient-to-r ${colorScheme.priceGradient}`}>
                    {formatCurrencyINR(plan.monthly_amount || 0)}
                  </div>
                  <div className="text-lg font-medium text-gray-600 dark:text-gray-400 mt-1">
                    per month
                  </div>
                </div>

                <div className="flex justify-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <div className="flex items-center gap-1">
                    <Calendar className="w-4 h-4" />
                    <span>{plan.duration_months} months</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Target className="w-4 h-4" />
                    <span>Total: {formatCurrencyINR(totalInvestment)}</span>
                  </div>
                </div>
              </div>

              <Link
                href={`/signup?plan=${plan.slug}`}
                className={`block w-full text-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r ${colorScheme.buttonGradient} rounded-xl shadow-lg transition-all duration-300 hover:shadow-xl`}
              >
                <span className="flex items-center justify-center">
                  Get Started
                  <ArrowRight className="w-5 h-5 ml-2" />
                </span>
              </Link>
            </div>

            {/* Plan Details */}
            <div className="space-y-6">
              <div>
                <Badge variant="outline" className="mb-4">
                  {plan.is_active ? 'Active Plan' : 'Inactive'}
                </Badge>
                <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                  {plan.name}
                </h2>
                <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
                  {plan.description || 'A premium investment plan designed to help you grow your wealth through pre-IPO investments.'}
                </p>
              </div>

              {/* Key Stats */}
              <div className="grid grid-cols-2 gap-4">
                <Card>
                  <CardContent className="pt-6">
                    <div className="text-2xl font-bold text-primary">
                      {formatCurrencyINR(plan.monthly_amount || 0)}
                    </div>
                    <p className="text-sm text-muted-foreground">Monthly Investment</p>
                  </CardContent>
                </Card>
                <Card>
                  <CardContent className="pt-6">
                    <div className="text-2xl font-bold text-primary">
                      {plan.duration_months} months
                    </div>
                    <p className="text-sm text-muted-foreground">Plan Duration</p>
                  </CardContent>
                </Card>
                <Card>
                  <CardContent className="pt-6">
                    <div className="text-2xl font-bold text-primary">
                      {formatCurrencyINR(totalInvestment)}
                    </div>
                    <p className="text-sm text-muted-foreground">Total Investment</p>
                  </CardContent>
                </Card>
                <Card>
                  <CardContent className="pt-6">
                    <div className="text-2xl font-bold text-green-600">
                      0%
                    </div>
                    <p className="text-sm text-muted-foreground">Platform Fee</p>
                  </CardContent>
                </Card>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      {plan.features && plan.features.length > 0 && (
        <section className="py-20">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12">
              <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Plan Features
              </h2>
              <p className="text-lg text-gray-600 dark:text-gray-400">
                Everything included with {plan.name}
              </p>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {plan.features.map((feature: PlanFeature, index: number) => (
                <Card key={feature.id || index} className="border-2 hover:border-primary/50 transition-colors">
                  <CardContent className="pt-6">
                    <div className="flex items-start space-x-3">
                      <div className="flex-shrink-0">
                        <div className="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                          <Check className="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                      </div>
                      <span className="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {typeof feature === 'string' ? feature : feature.feature_text}
                      </span>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Benefits Section */}
      <section className="py-20 bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              Why Choose This Plan?
            </h2>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: Star,
                title: "Zero Platform Fees",
                description: "100% free. No platform fees, no subscription costs, no hidden charges.",
                gradient: "from-yellow-600 to-orange-600",
              },
              {
                icon: Gift,
                title: "Bonus Rewards",
                description: "Earn additional bonuses on top of your investment returns with our reward system.",
                gradient: "from-green-600 to-emerald-600",
              },
              {
                icon: Award,
                title: "Flexible Terms",
                description: plan.allow_pause
                  ? `Pause your plan up to ${plan.max_pause_count || 3} times if needed.`
                  : "Structured investment with consistent monthly payments.",
                gradient: "from-purple-600 to-pink-600",
              },
            ].map((benefit, i) => (
              <Card key={i} className="border-2 hover:shadow-xl transition-all duration-300">
                <CardContent className="pt-8 pb-6">
                  <div className={`w-14 h-14 bg-gradient-to-br ${benefit.gradient} rounded-xl flex items-center justify-center mb-6`}>
                    <benefit.icon className="w-7 h-7 text-white" />
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                    {benefit.title}
                  </h3>
                  <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {benefit.description}
                  </p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-gradient-to-r from-purple-600 via-blue-600 to-pink-600 dark:from-purple-700 dark:via-blue-700 dark:to-pink-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Ready to Start Investing?
          </h2>
          <p className="text-xl text-purple-100 mb-8">
            Join thousands of investors building wealth with {plan.name}
          </p>

          <Link
            href={`/signup?plan=${plan.slug}`}
            className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
          >
            Subscribe to {plan.name}
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>

          <p className="text-white/90 mt-6 text-sm">
            No credit card required for signup
          </p>
        </div>
      </section>
    </div>
  );
}
