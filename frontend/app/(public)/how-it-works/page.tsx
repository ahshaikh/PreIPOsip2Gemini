"use client";

import Link from "next/link";
import {
  UserPlus,
  Search,
  FileText,
  CreditCard,
  BarChart3,
  TrendingUp,
  Shield,
  CheckCircle2,
  ArrowRight,
  Clock,
  Award,
  Sparkles,
} from "lucide-react";

export default function HowItWorksPage() {
  const steps = [
    {
      number: "01",
      icon: UserPlus,
      title: "Sign Up & Complete KYC",
      description:
        "Create your free account in under 2 minutes. Complete instant KYC verification using DigiLocker integration with your Aadhaar and PAN.",
      duration: "2 mins",
      details: [
        "Email/phone verification",
        "Instant DigiLocker KYC",
        "Bank account linking",
        "Investment preferences setup",
      ],
    },
    {
      number: "02",
      icon: Search,
      title: "Browse Investment Opportunities",
      description:
        "Explore 50+ carefully curated pre-IPO deals across sectors. Each opportunity comes with detailed company analysis, financials, and expert insights.",
      duration: "15-30 mins",
      details: [
        "Browse by sector or investment size",
        "Read detailed research reports",
        "Watch founder pitch videos",
        "Check company financials",
      ],
    },
    {
      number: "03",
      icon: FileText,
      title: "Review & Analyze",
      description:
        "Access institutional-grade research reports, financial analysis, risk assessment, and growth projections for every company before making your decision.",
      duration: "1-2 hours",
      details: [
        "Company background & business model",
        "Financial performance analysis",
        "Risk factors & mitigation",
        "Expected returns & exit strategy",
      ],
    },
    {
      number: "04",
      icon: CreditCard,
      title: "Invest Securely",
      description:
        "Choose your investment amount and complete the transaction using UPI, Net Banking, or other RBI-approved payment methods with bank-grade security.",
      duration: "5 mins",
      details: [
        "Select investment amount",
        "Choose payment method",
        "Secure payment processing",
        "Instant investment confirmation",
      ],
    },
    {
      number: "05",
      icon: BarChart3,
      title: "Track Your Portfolio",
      description:
        "Monitor your investments in real-time with our advanced analytics dashboard. Get regular updates on portfolio company progress and market trends.",
      duration: "Ongoing",
      details: [
        "Real-time portfolio valuation",
        "Company milestone updates",
        "Performance analytics",
        "Exit opportunity alerts",
      ],
    },
    {
      number: "06",
      icon: TrendingUp,
      title: "Realize Returns",
      description:
        "When companies go public or get acquired, your shares are liquidated and returns are credited to your account. Average exit time is 12-36 months.",
      duration: "12-36 months",
      details: [
        "IPO or acquisition announcement",
        "Automatic share liquidation",
        "Returns credited to account",
        "Tax documentation provided",
      ],
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-blue-50 via-purple-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-blue-100 dark:bg-blue-900/30 rounded-full px-6 py-3 mb-8">
              <Sparkles className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              <span className="font-semibold text-blue-900 dark:text-blue-300">Simple Process</span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              How PreIPOsip
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400">
                Works
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
              From signup to returns in 6 simple steps. Start investing in tomorrow's unicorns today.
            </p>
          </div>
        </div>
      </section>

      <section className="py-20 bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">Ready to Get Started?</h2>
          <p className="text-xl text-blue-100 mb-8">
            Join 50,000+ investors building wealth with pre-IPO investments
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-blue-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              Start Investing Now
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/products"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              Browse Deals
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
