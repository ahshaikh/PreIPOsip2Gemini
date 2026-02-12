'use client';

import { Check, Sparkles, TrendingUp, Zap, Crown, Star, ArrowRight } from "lucide-react";
import api from "@/lib/api";
import { formatCurrencyINR } from "@/lib/utils";
import { useQuery } from "@tanstack/react-query";
import Link from 'next/link';
import type { PlanWithRelations, PlanFeature } from "@/types/plan";

export default function PlansPage() {
  const { data: plans, isLoading } = useQuery<PlanWithRelations[]>({
    queryKey: ['publicPlans'],
    queryFn: async () => (await api.get('/plans')).data,
    staleTime: 1000 * 60 * 5 // 5 minutes
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
          <p className="text-lg text-gray-600 dark:text-gray-400">Loading investment plans...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950 overflow-hidden">
        <div className="absolute inset-0 bg-grid-slate-200 dark:bg-grid-slate-800 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]"></div>

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-gradient-to-r from-purple-100 to-blue-100 dark:from-purple-900/30 dark:to-blue-900/30 rounded-full px-6 py-3 mb-8 border border-purple-200 dark:border-purple-800">
              <Star className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span className="font-semibold text-purple-900 dark:text-purple-300">100% Free Plans</span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Find Your Perfect
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-blue-600 to-pink-600 dark:from-purple-400 dark:via-blue-400 dark:to-pink-400">
                Investment Plan
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
              All plans are 100% free. No platform fees, no exit fees.
            </p>
            <p className="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-400 dark:to-emerald-400">
              Your investment, your profit, plus our bonuses.
            </p>
          </div>
        </div>
      </section>

      {/* Plans Grid */}
      <section className="py-20 -mt-10 relative z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {plans?.map((plan: PlanWithRelations, index: number) => {
              const colorScheme = planColors[index % planColors.length];
              const PlanIcon = colorScheme.icon;

              return (
                <div
                  key={plan.id}
                  className={`group relative bg-gradient-to-br ${colorScheme.gradient} rounded-3xl p-8 border-2 ${colorScheme.border}
                    ${plan.is_featured ? 'shadow-2xl scale-105 ring-4 ring-purple-600/20 dark:ring-purple-400/20' : 'shadow-xl hover:shadow-2xl'}
                    transition-all duration-300 hover:scale-105`}
                >
                  {plan.is_featured && (
                    <div className="absolute -top-4 left-1/2 -translate-x-1/2 z-20">
                      <div className="bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-bold px-6 py-2 rounded-full shadow-lg flex items-center space-x-2">
                        <Crown className="w-4 h-4" />
                        <span>MOST POPULAR</span>
                      </div>
                    </div>
                  )}

                  <div className="text-center mb-6">
                    <div className={`w-20 h-20 ${colorScheme.iconBg} rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300`}>
                      <PlanIcon className={`w-10 h-10 ${colorScheme.iconColor}`} />
                    </div>

                    <h3 className="text-2xl font-black text-gray-900 dark:text-white mb-4">
                      {plan.name}
                    </h3>

                    <div className="mb-3">
                      <div className={`text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r ${colorScheme.priceGradient}`}>
                        {formatCurrencyINR(plan.monthly_amount)}
                      </div>
                      <div className="text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">
                        per month
                      </div>
                    </div>

                    <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                      {plan.description}
                    </p>
                  </div>

                  <div className="space-y-3 mb-8">
                    {plan.features.map((feature: PlanFeature) => (
                      <div key={feature.id} className="flex items-start space-x-3">
                        <div className="flex-shrink-0">
                          <Check className="w-5 h-5 text-green-600 dark:text-green-400 font-bold" />
                        </div>
                        <span className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                          {feature.feature_text}
                        </span>
                      </div>
                    ))}
                  </div>

                  <Link
                    href={`/signup?plan=${plan.slug}`}
                    className={`block w-full text-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r ${colorScheme.buttonGradient} rounded-xl shadow-lg transition-all duration-300 group-hover:shadow-xl`}
                  >
                    <span className="flex items-center justify-center">
                      Choose {plan.name}
                      <ArrowRight className="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                    </span>
                  </Link>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-20 bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Why Choose Our Plans?
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Every plan comes with incredible benefits
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: Star,
                title: "Zero Platform Fees",
                description: "All plans are 100% free. We don't charge any platform fees, subscription costs, or hidden charges.",
                gradient: "from-yellow-600 to-orange-600",
              },
              {
                icon: TrendingUp,
                title: "Bonus Returns",
                description: "Get additional bonus percentages on top of your investment returns. Higher plans = Higher bonuses!",
                gradient: "from-green-600 to-emerald-600",
              },
              {
                icon: Crown,
                title: "Premium Access",
                description: "Access exclusive pre-IPO deals, priority allocation, and early bird opportunities before general investors.",
                gradient: "from-purple-600 to-pink-600",
              },
            ].map((benefit, i) => (
              <div
                key={i}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className={`w-14 h-14 bg-gradient-to-br ${benefit.gradient} rounded-xl flex items-center justify-center mb-6`}>
                  <benefit.icon className="w-7 h-7 text-white" />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                  {benefit.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                  {benefit.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-gradient-to-r from-purple-600 via-blue-600 to-pink-600 dark:from-purple-700 dark:via-blue-700 dark:to-pink-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Ready to Start Your Investment Journey?
          </h2>
          <p className="text-xl text-purple-100 mb-8">
            Choose your plan and join 50,000+ investors building wealth with pre-IPO investments
          </p>

          <Link
            href="/signup"
            className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
          >
            Get Started Free
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>

          <p className="text-white/90 mt-6 text-sm">
            No credit card required • 100% Free Forever • Cancel anytime
          </p>
        </div>
      </section>
    </div>
  );
}