"use client";

import Link from "next/link";
import { useState } from "react";
import {
  TrendingUp,
  Zap,
  Shield,
  Star,
  ArrowRight,
  Sparkles,
  Rocket,
  Target,
  Award,
  Users,
  BarChart3,
  Lock,
  CheckCircle,
  ChevronRight,
  Play,
  DollarSign,
} from "lucide-react";

export default function Home6() {
  const [hoveredCard, setHoveredCard] = useState<number | null>(null);

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
      {/* Hero Section - Bold & Modern */}
      <section className="relative overflow-hidden pt-20 pb-32">
        {/* Animated Background Elements */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute -top-40 -right-40 w-96 h-96 bg-purple-400 dark:bg-purple-600 rounded-full mix-blend-multiply dark:mix-blend-normal filter blur-3xl opacity-20 animate-blob" />
          <div className="absolute -bottom-40 -left-40 w-96 h-96 bg-pink-400 dark:bg-pink-600 rounded-full mix-blend-multiply dark:mix-blend-normal filter blur-3xl opacity-20 animate-blob animation-delay-2000" />
          <div className="absolute top-1/2 left-1/2 w-96 h-96 bg-blue-400 dark:bg-blue-600 rounded-full mix-blend-multiply dark:mix-blend-normal filter blur-3xl opacity-20 animate-blob animation-delay-4000" />
        </div>

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            {/* Badge */}
            <div className="inline-flex items-center space-x-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full px-6 py-3 mb-8 shadow-lg shadow-purple-500/50 dark:shadow-purple-500/30">
              <Sparkles className="w-5 h-5" />
              <span className="font-bold">India's Coolest Pre-IPO Platform</span>
              <Zap className="w-5 h-5" />
            </div>

            {/* Main Headline */}
            <h1 className="text-6xl lg:text-8xl font-black mb-8 leading-tight">
              <span className="text-gray-900 dark:text-white">Invest Like</span>
              <br />
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 dark:from-purple-400 dark:via-pink-400 dark:to-blue-400">
                A Rockstar 🚀
              </span>
            </h1>

            <p className="text-2xl text-gray-700 dark:text-gray-300 mb-12 leading-relaxed max-w-3xl mx-auto">
              Get exclusive access to <strong className="text-purple-600 dark:text-purple-400">unicorn companies</strong> before
              they hit the stock market. Zero BS. Maximum returns. 💰
            </p>

            {/* CTA Buttons */}
            <div className="flex flex-col sm:flex-row gap-6 justify-center mb-12">
              <Link
                href="/signup"
                className="group relative inline-flex items-center justify-center px-12 py-6 text-xl font-bold text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl shadow-2xl shadow-purple-500/50 dark:shadow-purple-500/30 hover:scale-105 transition-transform duration-300 overflow-hidden"
              >
                <span className="relative z-10 flex items-center">
                  Start Investing Free
                  <ArrowRight className="ml-3 w-6 h-6 group-hover:translate-x-2 transition-transform" />
                </span>
                <div className="absolute inset-0 bg-gradient-to-r from-pink-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity" />
              </Link>

              <button className="group inline-flex items-center justify-center px-12 py-6 text-xl font-bold text-gray-900 dark:text-white bg-white dark:bg-slate-800 rounded-2xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300 border-2 border-gray-200 dark:border-slate-700">
                <Play className="mr-3 w-6 h-6" />
                Watch Demo
              </button>
            </div>

            {/* Social Proof */}
            <div className="flex flex-wrap items-center justify-center gap-8 text-sm">
              <div className="flex items-center space-x-2">
                <div className="flex -space-x-2">
                  {[1, 2, 3, 4].map((i) => (
                    <div
                      key={i}
                      className="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 border-2 border-white dark:border-slate-900"
                    />
                  ))}
                </div>
                <span className="font-semibold text-gray-700 dark:text-gray-300">
                  50,000+ investors
                </span>
              </div>
              <div className="flex items-center space-x-2">
                <Star className="w-5 h-5 text-yellow-500 fill-yellow-500" />
                <span className="font-semibold text-gray-700 dark:text-gray-300">4.9/5 rating</span>
              </div>
              <div className="flex items-center space-x-2">
                <Shield className="w-5 h-5 text-green-600 dark:text-green-400" />
                <span className="font-semibold text-gray-700 dark:text-gray-300">SEBI Registered</span>
              </div>
            </div>
          </div>

          {/* Floating Stats Cards */}
          <div className="grid md:grid-cols-3 gap-6 mt-20 max-w-5xl mx-auto">
            {[
              { icon: TrendingUp, value: "287%", label: "Avg. Returns", color: "purple" },
              { icon: Users, value: "50K+", label: "Happy Investors", color: "pink" },
              { icon: DollarSign, value: "₹2,500Cr+", label: "Total Invested", color: "blue" },
            ].map((stat, index) => (
              <div
                key={index}
                className="group relative bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-200 dark:border-slate-700"
              >
                <div className={`w-16 h-16 bg-gradient-to-br from-${stat.color}-500 to-${stat.color}-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-lg`}>
                  <stat.icon className="w-8 h-8 text-white" />
                </div>
                <div className="text-4xl font-black text-gray-900 dark:text-white mb-2">
                  {stat.value}
                </div>
                <div className="text-gray-600 dark:text-gray-400 font-medium">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section - Bold Cards */}
      <section className="py-20 relative">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6">
              Why We're{" "}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                Different
              </span>
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
              We're not your boring investment platform. We're built for the new generation of investors.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              {
                icon: Zap,
                title: "Lightning Fast KYC",
                description: "Complete verification in under 2 minutes with DigiLocker. No paperwork, no hassle.",
                gradient: "from-yellow-400 to-orange-500",
              },
              {
                icon: Shield,
                title: "Fort Knox Security",
                description: "Bank-grade 256-bit encryption. Your money is safer than in a Swiss vault.",
                gradient: "from-green-400 to-emerald-500",
              },
              {
                icon: Rocket,
                title: "Unicorn Access",
                description: "Invest in tomorrow's giants today. Get deals that VCs fight over.",
                gradient: "from-purple-400 to-pink-500",
              },
              {
                icon: BarChart3,
                title: "Real-Time Analytics",
                description: "Track your portfolio like a pro. Charts, graphs, and insights that actually make sense.",
                gradient: "from-blue-400 to-cyan-500",
              },
              {
                icon: Award,
                title: "Zero Hidden Fees",
                description: "What you see is what you pay. Absolutely ZERO surprise charges.",
                gradient: "from-pink-400 to-rose-500",
              },
              {
                icon: Target,
                title: "94% Success Rate",
                description: "Our track record speaks for itself. Most deals deliver exceptional returns.",
                gradient: "from-indigo-400 to-purple-500",
              },
            ].map((feature, index) => (
              <div
                key={index}
                onMouseEnter={() => setHoveredCard(index)}
                onMouseLeave={() => setHoveredCard(null)}
                className="group relative bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-200 dark:border-slate-700 overflow-hidden"
              >
                {/* Gradient overlay on hover */}
                <div
                  className={`absolute inset-0 bg-gradient-to-br ${feature.gradient} opacity-0 group-hover:opacity-5 transition-opacity`}
                />

                <div className="relative z-10">
                  <div
                    className={`w-16 h-16 bg-gradient-to-br ${feature.gradient} rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg`}
                  >
                    <feature.icon className="w-8 h-8 text-white" />
                  </div>

                  <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                    {feature.title}
                  </h3>
                  <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {feature.description}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works - Fun & Simple */}
      <section className="py-20 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6">
              Dead Simple Process
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              From zero to hero investor in 3 easy steps ⚡
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-12">
            {[
              {
                step: "01",
                emoji: "🎯",
                title: "Sign Up in Seconds",
                description: "Create account → Complete KYC → Done! Faster than ordering pizza.",
              },
              {
                step: "02",
                emoji: "🔍",
                title: "Browse Hot Deals",
                description: "Check out curated pre-IPO opportunities with all the juicy details.",
              },
              {
                step: "03",
                emoji: "💎",
                title: "Invest & Chill",
                description: "Pick your deals, invest, and watch your money grow. We handle the rest.",
              },
            ].map((step, index) => (
              <div key={index} className="text-center">
                <div className="relative inline-block mb-6">
                  <div className="text-8xl">{step.emoji}</div>
                  <div className="absolute -top-4 -right-4 w-16 h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center text-white text-2xl font-black shadow-lg">
                    {step.step}
                  </div>
                </div>
                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                  {step.title}
                </h3>
                <p className="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                  {step.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Social Proof / Testimonials */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6">
              Don't Just Take{" "}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400">
                Our Word
              </span>
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Real investors, real returns, real stories 🎉
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                name: "Rahul M.",
                role: "Software Engineer",
                returns: "+340%",
                quote: "I invested ₹2L and it's now worth ₹8.8L! This platform is absolutely insane! 🔥",
                avatar: "👨‍💻",
              },
              {
                name: "Priya K.",
                role: "Doctor",
                returns: "+285%",
                quote: "Finally, an investment platform that doesn't feel like reading a legal document. Love it!",
                avatar: "👩‍⚕️",
              },
              {
                name: "Amit S.",
                role: "Entrepreneur",
                returns: "+412%",
                quote: "Best financial decision I made in 2024. The returns are mind-blowing! 💰",
                avatar: "👨‍💼",
              },
            ].map((testimonial, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-3xl p-8 shadow-xl border border-gray-200 dark:border-slate-700 hover:scale-105 transition-transform duration-300"
              >
                <div className="flex items-center space-x-4 mb-6">
                  <div className="text-5xl">{testimonial.avatar}</div>
                  <div>
                    <div className="font-bold text-gray-900 dark:text-white text-lg">
                      {testimonial.name}
                    </div>
                    <div className="text-gray-600 dark:text-gray-400 text-sm">{testimonial.role}</div>
                  </div>
                </div>

                <p className="text-gray-700 dark:text-gray-300 text-lg mb-6 leading-relaxed">
                  "{testimonial.quote}"
                </p>

                <div className="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-slate-700">
                  <div className="flex space-x-1">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <Star key={star} className="w-5 h-5 text-yellow-400 fill-yellow-400" />
                    ))}
                  </div>
                  <div className="text-2xl font-black text-green-600 dark:text-green-400">
                    {testimonial.returns}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Final CTA - Super Bold */}
      <section className="relative py-32 overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 dark:from-purple-700 dark:via-pink-700 dark:to-blue-700" />

        {/* Animated patterns */}
        <div className="absolute inset-0">
          <div className="absolute top-0 left-0 w-full h-full opacity-10">
            <div className="absolute top-10 left-10 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse" />
            <div className="absolute bottom-10 right-10 w-80 h-80 bg-white rounded-full blur-3xl animate-pulse animation-delay-2000" />
          </div>
        </div>

        <div className="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="text-7xl mb-8">🚀💰✨</div>

          <h2 className="text-5xl lg:text-7xl font-black text-white mb-8 leading-tight">
            Ready to Make Some
            <br />
            Serious Money?
          </h2>

          <p className="text-2xl text-purple-100 mb-12 max-w-2xl mx-auto">
            Join 50,000+ smart investors who are already building wealth with pre-IPO investments
          </p>

          <Link
            href="/signup"
            className="inline-flex items-center justify-center px-16 py-8 text-2xl font-black text-purple-600 bg-white rounded-3xl shadow-2xl hover:scale-110 transition-transform duration-300"
          >
            Start Your Journey Now
            <Sparkles className="ml-4 w-8 h-8" />
          </Link>

          <div className="mt-12 text-white/80 text-sm">
            ✓ Free account ✓ No credit card required ✓ 2-minute setup
          </div>
        </div>
      </section>
    </div>
  );
}
