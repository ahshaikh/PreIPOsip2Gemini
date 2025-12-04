"use client";

import Link from "next/link";
import {
  Shield,
  Lock,
  Award,
  CheckCircle2,
  FileCheck,
  Users,
  BarChart3,
  Star,
  ArrowRight,
  AlertCircle,
} from "lucide-react";

export default function WhyTrustUsPage() {
  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-green-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-green-100 dark:bg-green-900/30 rounded-full px-6 py-3 mb-8">
              <Shield className="w-5 h-5 text-green-600 dark:text-green-400" />
              <span className="font-semibold text-green-900 dark:text-green-300">
                Trust & Security
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Your Trust Is Our
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-blue-600 dark:from-green-400 dark:to-blue-400">
                Greatest Responsibility
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
              Trust isn’t demanded — it’s earned. Here’s how we work every day to build a secure,
              transparent, and reliable pre-IPO investing experience for our growing community.
            </p>
          </div>
        </div>
      </section>

      {/* Regulatory Alignment */}
      <section className="py-20 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Built with Regulatory Alignment
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Operating with strict standards of compliance, transparency, and investor protection.
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: Shield,
                title: "SEBI-Aligned Processes",
                description:
                  "Our platform follows SEBI-aligned frameworks for transparency, investor protection, and responsible access.",
                badge: "Aligned",
              },
              {
                icon: FileCheck,
                title: "RBI-Compliant Payments",
                description:
                  "All transactions are processed through certified RBI-compliant payment gateways, ensuring your money is always secure.",
                badge: "Verified Gateway",
              },
              {
                icon: Award,
                title: "Industry Best Practices",
                description:
                  "We follow industry-grade standards for security, data privacy, and operational integrity.",
                badge: "Standards",
              },
            ].map((item, index) => (
              <div
                key={index}
                className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-8  00 rounded-2xl p-8 border border-gray-200 dark:border-slate-700"
              >
                <div className="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mb-6">
                  <item.icon className="w-8 h-8 text-green-600 dark:text-green-400" />
                </div>
                <div className="inline-block bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold px-3 py-1 rounded-full mb-4">
                  {item.badge}
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                  {item.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                  {item.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Security Measures */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Bank-Grade Security
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Your data and transactions are protected with enterprise-level safeguards.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              {
                icon: Lock,
                title: "256-Bit Encryption",
                description:
                  "All sensitive data is protected with encryption standards used by global financial institutions.",
              },
              {
                icon: Shield,
                title: "Secure Authentication",
                description:
                  "Multi-layered authentication safeguards account access and transaction integrity.",
              },
              {
                icon: AlertCircle,
                title: "Risk & Fraud Monitoring",
                description:
                  "Automated risk detection systems continuously monitor platform activity for anomalies.",
              },
              {
                icon: FileCheck,
                title: "Independent Audits",
                description:
                  "External audit partners help ensure ongoing security, compliance, and operational rigor.",
              },
            ].map((measure, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 text-center"
              >
                <div className="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto">
                  <measure.icon className="w-7 h-7 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">
                  {measure.title}
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                  {measure.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Transparency */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                Radical Transparency
              </h2>
              <p className="text-lg text-gray-700 dark:text-gray-300 mb-8 leading-relaxed">
                We believe transparency builds trust. Every investor deserves clear, accessible,
                and complete information before making a decision.
              </p>

              <div className="space-y-4">
                {[
                  "Comprehensive company profiles and financial insights",
                  "Detailed risk disclosures and deal-by-deal transparency",
                  "Clear fee structure with no hidden charges",
                  "Real-time investment tracking and updates",
                  "Direct access to founder presentations and Q&As",
                  "Transparent exit strategies and timelines",
                ].map((item, i) => (
                  <div key={i} className="flex items-start space-x-3">
                    <CheckCircle2 className="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                    <span className="text-gray-700 dark:text-gray-300">{item}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-slate-900 dark:to-slate-800 rounded-3xl p-10 border border-gray-200 dark:border-slate-700">
              <div className="space-y-6">
                <div>
                  <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">Platform Fee</div>
                  <div className="text-3xl font-black text-gray-900 dark:text-white">₹0</div>
                  <div className="text-sm text-green-600 dark:text-green-400 font-semibold">
                    Zero Platform Charges
                  </div>
                </div>

                <div className="border-t border-gray-200 dark:border-slate-700 pt-6">
                  <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">Exit Fee</div>
                  <div className="text-3xl font-black text-gray-900 dark:text-white">₹0</div>
                  <div className="text-sm text-green-600 dark:text-green-400 font-semibold">
                    No Surprise Exit Charges
                  </div>
                </div>

                <div className="border-t border-gray-200 dark:border-slate-700 pt-6">
                  <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">Hidden Fees</div>
                  <div className="text-3xl font-black text-gray-900 dark:text-white">₹0</div>
                  <div className="text-sm text-green-600 dark:text-green-400 font-semibold">
                    Transparent Pricing Always
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Trust Indicators */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              A Platform Built for Trust
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Growing responsibly, transparently, and with your confidence at the center.
            </p>
          </div>

          <div className="grid md:grid-cols-4 gap-8">
            {[
              { icon: Users, value: "Growing", label: "Investor Community" },
              { icon: BarChart3, value: "Expanding", label: "Investment Opportunities" },
              { icon: Star, value: "Reliable", label: "User Experience" },
              { icon: Award, value: "Trusted", label: "Industry Alignment" },
            ].map((stat, i) => (
              <div
                key={i}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700"
              >
                <div className="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto">
                  <stat.icon className="w-7 h-7 text-purple-600 dark:text-purple-400" />
                </div>
                <div className="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400 mb-2">
                  {stat.value}
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400 font-medium">
                  {stat.label}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-green-600 to-blue-600 dark:from-green-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            See the Difference Yourself
          </h2>
          <p className="text-xl text-green-100 mb-8">
            Join our growing community and explore a transparent, secure approach to pre-IPO investing.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-green-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              Open Free Account
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/contact"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              Talk to Expert
            </Link>
          </div>

          <div className="flex items-center justify-center space-x-8 mt-12 text-white/90">
            <div className="flex items-center space-x-2">
              <Shield className="w-5 h-5" />
              <span className="text-sm">SEBI-Aligned</span>
            </div>
            <div className="flex items-center space-x-2">
              <Lock className="w-5 h-5" />
              <span className="text-sm">Bank-Grade Security</span>
            </div>
            <div className="flex items-center space-x-2">
              <Award className="w-5 h-5" />
              <span className="text-sm">Industry Best Practices</span>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
