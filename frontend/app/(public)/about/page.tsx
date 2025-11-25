"use client";

import Link from "next/link";
import {
  Shield,
  Award,
  TrendingUp,
  Users,
  Target,
  Zap,
  Heart,
  CheckCircle2,
  ArrowRight,
} from "lucide-react";

export default function AboutPage() {
  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-purple-100 dark:bg-purple-900/30 rounded-full px-6 py-3 mb-8">
              <Heart className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span className="font-semibold text-purple-900 dark:text-purple-300">
                Our Mission
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Democratizing Pre-IPO
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
                Investment Access
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
              We're on a mission to make pre-IPO investments accessible to every Indian investor.
              What was once exclusive to ultra-rich and institutional investors is now available to you.
            </p>
          </div>
        </div>
      </section>

      {/* Vision & Mission */}
      <section className="py-20 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12">
            {/* Vision */}
            <div className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 rounded-3xl p-10 border border-purple-100 dark:border-slate-700">
              <div className="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl flex items-center justify-center mb-6">
                <Target className="w-8 h-8 text-white" />
              </div>
              <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">Our Vision</h2>
              <p className="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                To become India's most trusted and transparent pre-IPO investment platform, enabling
                millions of retail investors to participate in the growth stories of tomorrow's unicorns.
              </p>
            </div>

            {/* Mission */}
            <div className="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-slate-800 dark:to-slate-900 rounded-3xl p-10 border border-blue-100 dark:border-slate-700">
              <div className="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center mb-6">
                <Zap className="w-8 h-8 text-white" />
              </div>
              <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">Our Mission</h2>
              <p className="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                To democratize wealth creation by providing seamless, secure, and compliant access to
                high-growth pre-IPO companies with complete transparency and expert guidance.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Core Values */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Our Core Values
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              The principles that guide everything we do
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              {
                icon: Shield,
                title: "Trust & Transparency",
                description: "Complete transparency in every deal. No hidden fees, no surprises.",
                color: "emerald",
              },
              {
                icon: Award,
                title: "Excellence",
                description: "We curate only the best opportunities with rigorous due diligence.",
                color: "blue",
              },
              {
                icon: Users,
                title: "Customer First",
                description: "Your success is our success. We're here to help you grow wealth.",
                color: "purple",
              },
              {
                icon: TrendingUp,
                title: "Innovation",
                description: "Leveraging technology to make investing simple, fast, and accessible.",
                color: "amber",
              },
            ].map((value, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className={`w-14 h-14 bg-${value.color}-100 dark:bg-${value.color}-900/30 rounded-xl flex items-center justify-center mb-6`}>
                  <value.icon className={`w-7 h-7 text-${value.color}-600 dark:text-${value.color}-400`} />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                  {value.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                  {value.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Why We Exist */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            {/* Left - Content */}
            <div>
              <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                Why PreIPOsip Exists
              </h2>
              <div className="space-y-6 text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                <p>
                  For decades, pre-IPO investments were an exclusive playground for venture capitalists,
                  ultra-rich individuals, and institutional investors. Regular retail investors were
                  locked out of this wealth-creating opportunity.
                </p>
                <p>
                  <strong className="text-gray-900 dark:text-white">We're changing that.</strong> PreIPOsip was founded with a simple belief: every
                  Indian investor deserves access to high-growth investment opportunities before companies
                  go public.
                </p>
                <p>
                  Through cutting-edge technology, regulatory compliance, and transparent processes,
                  we've created a platform where retail investors can invest in tomorrow's unicorns
                  with confidence.
                </p>
              </div>

              <div className="mt-8 space-y-4">
                {[
                  "50,000+ investors trust PreIPOsip",
                  "₹2,500+ Crores invested through our platform",
                  "287% average returns across portfolio",
                  "100% SEBI compliant and transparent",
                ].map((item, i) => (
                  <div key={i} className="flex items-center space-x-3">
                    <CheckCircle2 className="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" />
                    <span className="text-gray-700 dark:text-gray-300 font-medium">{item}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Right - Stats */}
            <div className="grid grid-cols-2 gap-6">
              {[
                { value: "2020", label: "Founded" },
                { value: "50K+", label: "Investors" },
                { value: "₹2,500Cr+", label: "AUM" },
                { value: "287%", label: "Avg Returns" },
                { value: "100+", label: "Companies" },
                { value: "94%", label: "Success Rate" },
              ].map((stat, i) => (
                <div
                  key={i}
                  className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700"
                >
                  <div className="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400 mb-2">
                    {stat.value}
                  </div>
                  <div className="text-sm text-gray-600 dark:text-gray-400 font-medium">
                    {stat.label}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Ready to Start Your Investment Journey?
          </h2>
          <p className="text-xl text-purple-100 mb-8">
            Join 50,000+ investors building wealth with pre-IPO investments
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              Get Started Free
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/about/team"
              className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              Meet Our Team
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
