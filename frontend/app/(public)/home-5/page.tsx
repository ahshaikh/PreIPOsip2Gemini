"use client";

import Link from "next/link";
import { useState } from "react";
import {
  TrendingUp,
  Shield,
  CheckCircle2,
  ArrowRight,
  BarChart3,
  Users,
  Lock,
  Award,
  Clock,
  Zap,
  PieChart,
  DollarSign,
  Target,
  FileCheck,
  Star,
  ChevronRight,
} from "lucide-react";

export default function Home5() {
  const [activeTab, setActiveTab] = useState("returns");

  const stats = [
    { label: "Total Investors", value: "50,000+", icon: Users, color: "blue" },
    { label: "Total Invested", value: "₹2,500Cr+", icon: DollarSign, color: "green" },
    { label: "Avg. Returns", value: "287%", icon: TrendingUp, color: "purple" },
    { label: "Success Rate", value: "94%", icon: Target, color: "amber" },
  ];

  const features = [
    {
      icon: Shield,
      title: "SEBI Registered Platform",
      description: "Fully compliant with Indian regulations. Your investments are secure and transparent.",
    },
    {
      icon: BarChart3,
      title: "Real-Time Portfolio Tracking",
      description: "Monitor your investments with institutional-grade analytics and live updates.",
    },
    {
      icon: Lock,
      title: "Bank-Grade Security",
      description: "256-bit encryption with ISO 27001 certified infrastructure protecting your data.",
    },
    {
      icon: Clock,
      title: "24/7 Support",
      description: "Expert investment advisors available round the clock for your queries.",
    },
  ];

  const companies = [
    { name: "TechUnicorn", sector: "Technology", min: "₹50,000", returns: "+340%", risk: "Medium" },
    { name: "FinServe", sector: "Finance", min: "₹1,00,000", returns: "+285%", risk: "Low" },
    { name: "HealthPlus", sector: "Healthcare", min: "₹75,000", returns: "+412%", risk: "Medium" },
    { name: "EduTech Pro", sector: "Education", min: "₹40,000", returns: "+198%", risk: "Low" },
  ];

  const steps = [
    {
      number: "01",
      title: "Sign Up & Complete KYC",
      description: "Create your account in minutes. Complete instant KYC verification with Aadhaar.",
    },
    {
      number: "02",
      title: "Browse & Research Deals",
      description: "Access curated pre-IPO opportunities with detailed analysis and founder insights.",
    },
    {
      number: "03",
      title: "Invest & Track Growth",
      description: "Execute deals securely and monitor your portfolio with real-time analytics.",
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-slate-950">
      {/* Hero Section - Clean & Professional */}
      <section className="relative bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Left Content */}
            <div>
              {/* Trust Badge */}
              <div className="inline-flex items-center space-x-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-full px-4 py-2 mb-6">
                <Shield className="w-4 h-4 text-green-600 dark:text-green-400" />
                <span className="text-sm font-medium text-green-700 dark:text-green-300">
                  SEBI Registered • RBI Approved
                </span>
              </div>

              <h1 className="text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                Invest in Pre-IPO
                <span className="block text-blue-600 dark:text-blue-400">Companies With Confidence</span>
              </h1>

              <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                Access exclusive pre-IPO investment opportunities in India's fastest-growing companies.
                Transparent, secure, and built for serious investors.
              </p>

              {/* Key Points */}
              <div className="space-y-4 mb-8">
                {[
                  "Zero hidden fees or charges",
                  "Minimum investment from ₹40,000",
                  "Average returns of 287% across portfolio",
                ].map((point, i) => (
                  <div key={i} className="flex items-center space-x-3">
                    <CheckCircle2 className="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" />
                    <span className="text-gray-700 dark:text-gray-300">{point}</span>
                  </div>
                ))}
              </div>

              {/* CTAs */}
              <div className="flex flex-col sm:flex-row gap-4">
                <Link
                  href="/signup"
                  className="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white bg-blue-600 dark:bg-blue-500 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors shadow-lg shadow-blue-600/20"
                >
                  Open Free Account
                  <ArrowRight className="ml-2 w-5 h-5" />
                </Link>
                <Link
                  href="/products"
                  className="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-slate-800 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                >
                  View All Deals
                </Link>
              </div>
            </div>

            {/* Right - Stats Dashboard */}
            <div className="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700">
              <div className="mb-6">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                  Platform Performance
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Real-time statistics updated daily
                </p>
              </div>

              <div className="grid grid-cols-2 gap-4 mb-6">
                {stats.map((stat, index) => (
                  <div
                    key={index}
                    className="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-700"
                  >
                    <div className={`w-10 h-10 bg-${stat.color}-100 dark:bg-${stat.color}-900/30 rounded-lg flex items-center justify-center mb-3`}>
                      <stat.icon className={`w-5 h-5 text-${stat.color}-600 dark:text-${stat.color}-400`} />
                    </div>
                    <div className="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                      {stat.value}
                    </div>
                    <div className="text-xs text-gray-600 dark:text-gray-400">
                      {stat.label}
                    </div>
                  </div>
                ))}
              </div>

              {/* Mini Chart Visualization */}
              <div className="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-700">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Portfolio Growth
                  </span>
                  <span className="text-sm text-green-600 dark:text-green-400 font-semibold">
                    +287%
                  </span>
                </div>
                <div className="flex items-end space-x-2 h-32">
                  {[45, 52, 48, 65, 70, 68, 85, 92, 88, 95, 98, 100].map((height, i) => (
                    <div
                      key={i}
                      className="flex-1 bg-gradient-to-t from-blue-600 to-blue-400 dark:from-blue-500 dark:to-blue-300 rounded-t"
                      style={{ height: `${height}%` }}
                    />
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Bar */}
      <section className="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {[
              { icon: FileCheck, label: "SEBI Registered", value: "100%" },
              { icon: Award, label: "Success Rate", value: "94%" },
              { icon: Users, label: "Active Investors", value: "50K+" },
              { icon: Star, label: "Platform Rating", value: "4.9/5" },
            ].map((item, index) => (
              <div key={index} className="text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-slate-800 rounded-lg mb-3">
                  <item.icon className="w-6 h-6 text-gray-700 dark:text-gray-300" />
                </div>
                <div className="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                  {item.value}
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">{item.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Live Deals Section */}
      <section className="py-16 bg-gray-50 dark:bg-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Live Pre-IPO Opportunities
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
              Carefully curated deals from high-growth companies across sectors
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {companies.map((company, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6 hover:shadow-lg transition-shadow"
              >
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-1">
                      {company.name}
                    </h3>
                    <span className="inline-block px-3 py-1 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-full">
                      {company.sector}
                    </span>
                  </div>
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold ${
                      company.risk === "Low"
                        ? "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"
                        : "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400"
                    }`}
                  >
                    {company.risk} Risk
                  </span>
                </div>

                <div className="grid grid-cols-2 gap-4 mb-6">
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      Min. Investment
                    </div>
                    <div className="text-lg font-bold text-gray-900 dark:text-white">
                      {company.min}
                    </div>
                  </div>
                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      Expected Returns
                    </div>
                    <div className="text-lg font-bold text-green-600 dark:text-green-400">
                      {company.returns}
                    </div>
                  </div>
                </div>

                <Link
                  href={`/products/${company.name.toLowerCase()}`}
                  className="flex items-center justify-center w-full px-4 py-3 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                >
                  View Details
                  <ChevronRight className="ml-2 w-5 h-5" />
                </Link>
              </div>
            ))}
          </div>

          <div className="text-center mt-8">
            <Link
              href="/products"
              className="inline-flex items-center text-blue-600 dark:text-blue-400 font-semibold hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
            >
              View All 50+ Deals
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-16 bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Why Choose PreIPOsip?
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
              Built for investors who demand transparency, security, and exceptional returns
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <div key={index} className="text-center">
                <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-2xl mb-4">
                  <feature.icon className="w-8 h-8 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">
                  {feature.title}
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-16 bg-gray-50 dark:bg-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4">
              How It Works
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400">
              Start investing in 3 simple steps
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {steps.map((step, index) => (
              <div key={index} className="relative">
                {index < steps.length - 1 && (
                  <div className="hidden md:block absolute top-12 left-1/2 w-full h-0.5 bg-gray-300 dark:bg-slate-700" />
                )}
                <div className="relative bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-8 text-center">
                  <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-600 dark:bg-blue-500 text-white text-2xl font-bold rounded-full mb-6">
                    {step.number}
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                    {step.title}
                  </h3>
                  <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {step.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Final CTA */}
      <section className="py-20 bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl lg:text-5xl font-bold text-white mb-6">
            Ready to Start Investing?
          </h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Join 50,000+ investors building wealth through pre-IPO opportunities
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-blue-600 bg-white rounded-lg hover:bg-gray-100 transition-colors shadow-xl"
            >
              Open Free Account
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/contact"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white border-2 border-white rounded-lg hover:bg-white/10 transition-colors"
            >
              Talk to Expert
            </Link>
          </div>

          <div className="flex items-center justify-center space-x-8 mt-12 text-white/80">
            <div className="flex items-center space-x-2">
              <Shield className="w-5 h-5" />
              <span className="text-sm">100% Secure</span>
            </div>
            <div className="flex items-center space-x-2">
              <Lock className="w-5 h-5" />
              <span className="text-sm">Bank-Grade Encryption</span>
            </div>
            <div className="flex items-center space-x-2">
              <Award className="w-5 h-5" />
              <span className="text-sm">SEBI Registered</span>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
