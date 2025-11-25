"use client";

import Link from "next/link";
import { useState } from "react";
import {
  TrendingUp,
  Shield,
  Zap,
  Award,
  ArrowRight,
  Check,
  Users,
  BarChart3,
  Lock,
  Clock,
  Target,
  Sparkles,
  ChevronRight,
  Star,
  DollarSign,
  FileText,
  Globe,
  Briefcase,
} from "lucide-react";

export default function Home7() {
  const [selectedPlan, setSelectedPlan] = useState(1);

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section - Unique Glassmorphism Style */}
      <section className="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-50 via-blue-50 to-purple-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
        {/* Background Pattern */}
        <div className="absolute inset-0">
          <div className="absolute inset-0 bg-grid-slate-900/[0.04] dark:bg-grid-slate-100/[0.02] bg-[size:40px_40px]" />
          <div className="absolute top-0 right-0 w-1/2 h-1/2 bg-gradient-to-br from-emerald-400/20 to-transparent dark:from-emerald-600/20 rounded-full blur-3xl" />
          <div className="absolute bottom-0 left-0 w-1/2 h-1/2 bg-gradient-to-tr from-blue-400/20 to-transparent dark:from-blue-600/20 rounded-full blur-3xl" />
        </div>

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            {/* Left Content */}
            <div>
              {/* Premium Badge */}
              <div className="inline-flex items-center space-x-3 bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl border border-emerald-200 dark:border-emerald-800 rounded-full px-6 py-3 mb-8 shadow-lg">
                <div className="flex -space-x-2">
                  <div className="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 border-2 border-white dark:border-slate-900 flex items-center justify-center">
                    <Shield className="w-4 h-4 text-white" />
                  </div>
                  <div className="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 border-2 border-white dark:border-slate-900 flex items-center justify-center">
                    <Award className="w-4 h-4 text-white" />
                  </div>
                </div>
                <span className="font-bold text-gray-900 dark:text-white">
                  Trusted by 50,000+ Investors
                </span>
              </div>

              <h1 className="text-6xl lg:text-7xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
                Unlock Access to
                <span className="block text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 via-blue-600 to-purple-600 dark:from-emerald-400 dark:via-blue-400 dark:to-purple-400">
                  Tomorrow's Giants
                </span>
              </h1>

              <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                Invest in high-growth pre-IPO companies with institutional-grade research,
                transparent pricing, and unmatched returns. Your gateway to unicorn investments.
              </p>

              {/* Key Metrics */}
              <div className="grid grid-cols-3 gap-6 mb-10">
                {[
                  { value: "287%", label: "Avg Returns" },
                  { value: "₹2,500Cr+", label: "AUM" },
                  { value: "94%", label: "Success" },
                ].map((metric, i) => (
                  <div key={i} className="text-center">
                    <div className="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 to-blue-600 dark:from-emerald-400 dark:to-blue-400 mb-1">
                      {metric.value}
                    </div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 font-medium">
                      {metric.label}
                    </div>
                  </div>
                ))}
              </div>

              {/* CTAs */}
              <div className="flex flex-col sm:flex-row gap-4">
                <Link
                  href="/signup"
                  className="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r from-emerald-600 to-blue-600 rounded-xl shadow-xl shadow-emerald-600/30 hover:shadow-2xl hover:shadow-emerald-600/40 transition-all duration-300 overflow-hidden"
                >
                  <span className="relative z-10 flex items-center">
                    Get Started Free
                    <ArrowRight className="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
                  </span>
                  <div className="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity" />
                </Link>

                <Link
                  href="/products"
                  className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-gray-900 dark:text-white bg-white/60 dark:bg-slate-800/60 backdrop-blur-xl border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-white dark:hover:bg-slate-800 transition-all duration-300"
                >
                  Explore Deals
                </Link>
              </div>

              {/* Trust Badges */}
              <div className="flex flex-wrap items-center gap-6 mt-10 text-sm text-gray-600 dark:text-gray-400">
                <div className="flex items-center space-x-2">
                  <Shield className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                  <span>SEBI Registered</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Lock className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                  <span>Bank-Grade Security</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Star className="w-5 h-5 text-yellow-500 fill-yellow-500" />
                  <span>4.9/5 Rating</span>
                </div>
              </div>
            </div>

            {/* Right - Glassmorphism Card */}
            <div className="relative">
              {/* Floating card */}
              <div className="relative bg-white/40 dark:bg-slate-800/40 backdrop-blur-2xl border border-white/60 dark:border-slate-700/60 rounded-3xl p-8 shadow-2xl">
                {/* Live indicator */}
                <div className="flex items-center justify-between mb-6">
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                    Featured Opportunities
                  </h3>
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                    <span className="text-sm font-semibold text-gray-600 dark:text-gray-400">LIVE</span>
                  </div>
                </div>

                {/* Deals */}
                <div className="space-y-4 mb-6">
                  {[
                    { name: "TechCorp", sector: "AI/ML", min: "₹50K", returns: "+340%", color: "emerald" },
                    { name: "FinNext", sector: "FinTech", min: "₹1L", returns: "+285%", color: "blue" },
                    { name: "HealthX", sector: "Healthcare", min: "₹75K", returns: "+412%", color: "purple" },
                  ].map((deal, i) => (
                    <div
                      key={i}
                      className="group bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-gray-200 dark:border-slate-700 rounded-2xl p-5 hover:bg-white dark:hover:bg-slate-900 transition-all duration-300 cursor-pointer hover:scale-105"
                    >
                      <div className="flex items-center justify-between mb-3">
                        <div>
                          <div className="font-bold text-gray-900 dark:text-white text-lg">
                            {deal.name}
                          </div>
                          <div className="text-sm text-gray-600 dark:text-gray-400">{deal.sector}</div>
                        </div>
                        <ChevronRight className="w-5 h-5 text-gray-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" />
                      </div>
                      <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <div className="text-gray-600 dark:text-gray-400">Min. Investment</div>
                          <div className="font-bold text-gray-900 dark:text-white">{deal.min}</div>
                        </div>
                        <div>
                          <div className="text-gray-600 dark:text-gray-400">Expected Returns</div>
                          <div className={`font-bold text-${deal.color}-600 dark:text-${deal.color}-400`}>
                            {deal.returns}
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                <Link
                  href="/products"
                  className="block text-center py-4 bg-gradient-to-r from-emerald-600 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg transition-all duration-300"
                >
                  View All 50+ Deals →
                </Link>
              </div>

              {/* Decorative elements */}
              <div className="absolute -top-6 -right-6 w-24 h-24 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-2xl opacity-20 blur-2xl" />
              <div className="absolute -bottom-6 -left-6 w-32 h-32 bg-gradient-to-br from-blue-400 to-purple-600 rounded-2xl opacity-20 blur-2xl" />
            </div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-20 bg-gradient-to-b from-gray-50 to-white dark:from-slate-900 dark:to-slate-950 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              { icon: Users, value: "50,000+", label: "Active Investors", color: "emerald" },
              { icon: DollarSign, value: "₹2,500Cr+", label: "Assets Under Management", color: "blue" },
              { icon: TrendingUp, value: "287%", label: "Average Returns", color: "purple" },
              { icon: Award, value: "94%", label: "Deal Success Rate", color: "amber" },
            ].map((stat, i) => (
              <div
                key={i}
                className="relative group bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-8 hover:shadow-xl transition-all duration-300"
              >
                <div className={`w-14 h-14 bg-gradient-to-br from-${stat.color}-500 to-${stat.color}-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg shadow-${stat.color}-500/30`}>
                  <stat.icon className="w-7 h-7 text-white" />
                </div>
                <div className="text-4xl font-black text-gray-900 dark:text-white mb-2">
                  {stat.value}
                </div>
                <div className="text-gray-600 dark:text-gray-400">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section - Unique Layout */}
      <section className="py-20 bg-white dark:bg-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <div className="inline-flex items-center space-x-2 bg-gradient-to-r from-emerald-100 to-blue-100 dark:from-emerald-900/30 dark:to-blue-900/30 rounded-full px-4 py-2 mb-4">
              <Sparkles className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              <span className="text-sm font-bold text-gray-900 dark:text-white">
                Premium Features
              </span>
            </div>
            <h2 className="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white mb-4">
              Everything You Need to Succeed
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
              Professional tools and insights for serious investors
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              {
                icon: BarChart3,
                title: "Real-Time Analytics",
                description: "Track your portfolio performance with institutional-grade analytics dashboard.",
                gradient: "from-emerald-500 to-emerald-600",
              },
              {
                icon: FileText,
                title: "Detailed Research Reports",
                description: "Access comprehensive analysis, financials, and founder interviews for every deal.",
                gradient: "from-blue-500 to-blue-600",
              },
              {
                icon: Lock,
                title: "Bank-Grade Security",
                description: "Your data and investments protected with 256-bit encryption and ISO 27001 certification.",
                gradient: "from-purple-500 to-purple-600",
              },
              {
                icon: Clock,
                title: "24/7 Expert Support",
                description: "Dedicated investment advisors available round-the-clock for your queries.",
                gradient: "from-amber-500 to-amber-600",
              },
              {
                icon: Globe,
                title: "Diversified Portfolio",
                description: "Invest across sectors - Technology, Healthcare, Finance, and more.",
                gradient: "from-pink-500 to-pink-600",
              },
              {
                icon: Target,
                title: "Curated Opportunities",
                description: "Every deal vetted by experts. Only the best opportunities make it to you.",
                gradient: "from-indigo-500 to-indigo-600",
              },
            ].map((feature, i) => (
              <div
                key={i}
                className="group relative bg-gradient-to-b from-gray-50 to-white dark:from-slate-900 dark:to-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 overflow-hidden"
              >
                <div className={`w-14 h-14 bg-gradient-to-br ${feature.gradient} rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg`}>
                  <feature.icon className="w-7 h-7 text-white" />
                </div>

                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                  {feature.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                  {feature.description}
                </p>

                {/* Hover effect */}
                <div className={`absolute inset-0 bg-gradient-to-br ${feature.gradient} opacity-0 group-hover:opacity-5 transition-opacity`} />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works - Timeline Style */}
      <section className="py-20 bg-gradient-to-b from-gray-50 to-white dark:from-slate-900 dark:to-slate-950">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white mb-4">
              Your Investment Journey
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              From signup to returns in 3 simple steps
            </p>
          </div>

          <div className="relative">
            {/* Timeline line */}
            <div className="hidden md:block absolute left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-emerald-600 via-blue-600 to-purple-600" />

            <div className="space-y-16">
              {[
                {
                  number: "01",
                  title: "Create Account & Verify",
                  description: "Sign up in minutes. Complete instant KYC with DigiLocker. Start with as low as ₹40,000.",
                  icon: Users,
                  color: "emerald",
                },
                {
                  number: "02",
                  title: "Research & Select Deals",
                  description: "Browse curated pre-IPO opportunities. Read detailed reports, financials, and expert analysis.",
                  icon: Briefcase,
                  color: "blue",
                },
                {
                  number: "03",
                  title: "Invest & Track Growth",
                  description: "Execute deals securely. Monitor your portfolio in real-time with professional analytics.",
                  icon: TrendingUp,
                  color: "purple",
                },
              ].map((step, i) => (
                <div key={i} className={`relative grid md:grid-cols-2 gap-8 items-center ${i % 2 === 1 ? 'md:flex-row-reverse' : ''}`}>
                  {/* Content */}
                  <div className={`${i % 2 === 1 ? 'md:text-right md:col-start-1' : 'md:col-start-2'}`}>
                    <div className={`inline-block bg-gradient-to-br from-${step.color}-500 to-${step.color}-600 text-white text-2xl font-black px-6 py-3 rounded-xl mb-4 shadow-lg`}>
                      {step.number}
                    </div>
                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                      {step.title}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 text-lg leading-relaxed">
                      {step.description}
                    </p>
                  </div>

                  {/* Icon */}
                  <div className={`${i % 2 === 1 ? 'md:col-start-2' : 'md:col-start-1'} flex ${i % 2 === 1 ? 'md:justify-start' : 'md:justify-end'}`}>
                    <div className={`w-24 h-24 bg-gradient-to-br from-${step.color}-500 to-${step.color}-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-${step.color}-500/50`}>
                      <step.icon className="w-12 h-12 text-white" />
                    </div>
                  </div>

                  {/* Timeline dot */}
                  <div className="hidden md:block absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-6 h-6 bg-white dark:bg-slate-950 border-4 border-gradient-to-r from-emerald-600 to-blue-600 rounded-full z-10" />
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Final CTA - Premium Style */}
      <section className="relative py-32 overflow-hidden">
        {/* Background */}
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-600 via-blue-600 to-purple-600 dark:from-emerald-700 dark:via-blue-700 dark:to-purple-700" />

        {/* Glassmorphism overlay */}
        <div className="absolute inset-0 bg-white/5 backdrop-blur-3xl" />

        {/* Content */}
        <div className="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-xl rounded-full px-6 py-3 mb-8">
            <Zap className="w-5 h-5 text-white" />
            <span className="font-bold text-white">Limited Time Offer</span>
          </div>

          <h2 className="text-5xl lg:text-6xl font-black text-white mb-6 leading-tight">
            Start Building Wealth
            <br />
            With Pre-IPO Investments
          </h2>

          <p className="text-2xl text-white/90 mb-12 max-w-2xl mx-auto">
            Join 50,000+ investors who are already earning exceptional returns
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-12 py-6 text-xl font-bold text-emerald-600 bg-white rounded-xl shadow-2xl hover:scale-105 transition-transform duration-300"
            >
              Get Started Free
              <ArrowRight className="ml-3 w-6 h-6" />
            </Link>
            <Link
              href="/contact"
              className="inline-flex items-center justify-center px-12 py-6 text-xl font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 backdrop-blur-xl transition-all duration-300"
            >
              Talk to an Expert
            </Link>
          </div>

          {/* Trust indicators */}
          <div className="grid grid-cols-3 gap-8 mt-16 max-w-3xl mx-auto">
            {[
              { icon: Shield, label: "100% Secure" },
              { icon: Award, label: "SEBI Registered" },
              { icon: Star, label: "4.9/5 Rating" },
            ].map((item, i) => (
              <div key={i} className="text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 bg-white/20 backdrop-blur-xl rounded-xl mb-3">
                  <item.icon className="w-6 h-6 text-white" />
                </div>
                <div className="text-white/90 font-semibold">{item.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}
