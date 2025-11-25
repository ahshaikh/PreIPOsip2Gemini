"use client";

import { useState } from "react";
import Link from "next/link";
import {
  TrendingUp,
  Shield,
  Award,
  Users,
  Zap,
  ArrowRight,
  CheckCircle2,
  Star,
  BarChart3,
  Wallet,
  Lock,
  Target,
  Sparkles,
} from "lucide-react";

export default function Home2() {
  const [selectedPlan, setSelectedPlan] = useState("premium");

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50">
      {/* Hero Section - Bold Gradient */}
      <section className="relative pt-32 pb-20 px-4 overflow-hidden">
        {/* Animated Background */}
        <div className="absolute inset-0 overflow-hidden">
          <div className="absolute -top-1/2 -right-1/4 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
          <div className="absolute -bottom-1/2 -left-1/4 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
          <div className="absolute top-1/4 left-1/2 w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
        </div>

        <div className="max-w-7xl mx-auto relative z-10">
          <div className="text-center">
            {/* Badge */}
            <div className="inline-flex items-center space-x-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white px-6 py-2 rounded-full text-sm font-semibold mb-8 shadow-lg">
              <Sparkles className="w-4 h-4" />
              <span>Trusted by 50,000+ Investors</span>
              <Shield className="w-4 h-4" />
            </div>

            {/* Main Heading */}
            <h1 className="text-6xl md:text-7xl font-bold mb-6 bg-gradient-to-r from-purple-600 via-blue-600 to-purple-600 bg-clip-text text-transparent leading-tight">
              Invest in Tomorrow's
              <br />
              <span className="text-7xl md:text-8xl">Unicorns</span> Today
            </h1>

            <p className="text-xl md:text-2xl text-gray-600 mb-12 max-w-3xl mx-auto leading-relaxed">
              Access exclusive pre-IPO opportunities with India's most trusted
              investment platform. Start with just ₹10,000.
            </p>

            {/* CTA Buttons */}
            <div className="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
              <Link
                href="/signup"
                className="group relative px-10 py-5 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-2xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 overflow-hidden"
              >
                <span className="relative z-10 flex items-center">
                  Start Investing Now
                  <ArrowRight className="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                </span>
                <div className="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
              </Link>

              <Link
                href="/plans"
                className="px-10 py-5 bg-white text-purple-600 rounded-2xl font-bold text-lg border-2 border-purple-600 hover:bg-purple-50 transition-all duration-300"
              >
                View Pricing Plans
              </Link>
            </div>

            {/* Trust Indicators */}
            <div className="flex items-center justify-center space-x-8 mt-16">
              <div className="flex items-center space-x-2 text-gray-600">
                <Shield className="w-5 h-5 text-green-500" />
                <span className="font-medium">SEBI Registered</span>
              </div>
              <div className="flex items-center space-x-2 text-gray-600">
                <Lock className="w-5 h-5 text-blue-500" />
                <span className="font-medium">Bank-Grade Security</span>
              </div>
              <div className="flex items-center space-x-2 text-gray-600">
                <Star className="w-5 h-5 text-yellow-500" />
                <span className="font-medium">4.9/5 Rating</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-16 px-4 bg-white/50 backdrop-blur-sm">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {[
              { label: "Total Investments", value: "₹250+ Cr", icon: TrendingUp },
              { label: "Active Investors", value: "50,000+", icon: Users },
              { label: "Pre-IPO Deals", value: "100+", icon: Target },
              { label: "Success Rate", value: "94%", icon: Award },
            ].map((stat, i) => (
              <div
                key={i}
                className="text-center p-6 rounded-2xl bg-gradient-to-br from-white to-gray-50 shadow-lg hover:shadow-xl transition-shadow"
              >
                <stat.icon className="w-10 h-10 mx-auto mb-3 text-purple-600" />
                <div className="text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent mb-2">
                  {stat.value}
                </div>
                <div className="text-gray-600 font-medium">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section - Card Grid */}
      <section className="py-20 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-5xl font-bold mb-4 bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
              Why Choose PreIPO SIP?
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              India's first systematic investment platform for pre-IPO stocks
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                icon: Zap,
                title: "Instant Access",
                description:
                  "Get access to exclusive pre-IPO deals within minutes of signing up",
                color: "from-yellow-400 to-orange-500",
              },
              {
                icon: Shield,
                title: "Fully Verified",
                description:
                  "All companies are thoroughly vetted by our expert team",
                color: "from-green-400 to-emerald-500",
              },
              {
                icon: BarChart3,
                title: "Real-Time Tracking",
                description:
                  "Monitor your investments with live portfolio analytics",
                color: "from-blue-400 to-indigo-500",
              },
              {
                icon: Wallet,
                title: "Low Minimums",
                description:
                  "Start investing with just ₹10,000 - no hidden fees",
                color: "from-purple-400 to-pink-500",
              },
              {
                icon: Target,
                title: "Expert Guidance",
                description:
                  "Get personalized recommendations from investment advisors",
                color: "from-red-400 to-rose-500",
              },
              {
                icon: Award,
                title: "Proven Returns",
                description:
                  "Average 3x returns within 18-24 months of IPO listing",
                color: "from-cyan-400 to-blue-500",
              },
            ].map((feature, i) => (
              <div
                key={i}
                className="group p-8 rounded-3xl bg-white shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
              >
                <div
                  className={`w-16 h-16 rounded-2xl bg-gradient-to-br ${feature.color} flex items-center justify-center mb-6 group-hover:scale-110 transition-transform`}
                >
                  <feature.icon className="w-8 h-8 text-white" />
                </div>
                <h3 className="text-2xl font-bold mb-3 text-gray-900">
                  {feature.title}
                </h3>
                <p className="text-gray-600 leading-relaxed">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-20 px-4 bg-gradient-to-br from-purple-600 to-blue-600 text-white">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-5xl font-bold mb-4">How It Works</h2>
            <p className="text-xl opacity-90">
              Start your pre-IPO investment journey in 3 simple steps
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                step: "01",
                title: "Create Account",
                description:
                  "Sign up in 2 minutes with your email and complete KYC verification",
              },
              {
                step: "02",
                title: "Choose Deals",
                description:
                  "Browse curated pre-IPO opportunities and select investments",
              },
              {
                step: "03",
                title: "Start Earning",
                description:
                  "Track your portfolio and watch your wealth grow over time",
              },
            ].map((step, i) => (
              <div key={i} className="relative">
                {i < 2 && (
                  <div className="hidden md:block absolute top-1/4 right-0 w-full h-0.5 bg-white/30"></div>
                )}
                <div className="relative bg-white/10 backdrop-blur-sm rounded-3xl p-8 hover:bg-white/20 transition-all">
                  <div className="text-7xl font-bold opacity-20 mb-4">
                    {step.step}
                  </div>
                  <h3 className="text-2xl font-bold mb-3">{step.title}</h3>
                  <p className="opacity-90 leading-relaxed">{step.description}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="text-center mt-12">
            <Link
              href="/how-it-works"
              className="inline-flex items-center text-white text-lg font-semibold hover:underline"
            >
              Learn More About Our Process
              <ArrowRight className="w-5 h-5 ml-2" />
            </Link>
          </div>
        </div>
      </section>

      {/* Pricing Plans */}
      <section className="py-20 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-5xl font-bold mb-4 bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
              Choose Your Plan
            </h2>
            <p className="text-xl text-gray-600">
              Flexible plans designed for every investor
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                name: "Starter",
                price: "₹999",
                period: "/month",
                features: [
                  "5 Pre-IPO deals per month",
                  "Basic portfolio analytics",
                  "Email support",
                  "Market insights",
                ],
                popular: false,
              },
              {
                name: "Premium",
                price: "₹2,499",
                period: "/month",
                features: [
                  "Unlimited pre-IPO deals",
                  "Advanced analytics & AI insights",
                  "Priority support",
                  "Expert consultation calls",
                  "Early access to new deals",
                ],
                popular: true,
              },
              {
                name: "Enterprise",
                price: "₹9,999",
                period: "/month",
                features: [
                  "Everything in Premium",
                  "Dedicated account manager",
                  "Custom investment strategies",
                  "Exclusive institutional deals",
                  "Tax optimization support",
                ],
                popular: false,
              },
            ].map((plan, i) => (
              <div
                key={i}
                className={`relative rounded-3xl p-8 ${
                  plan.popular
                    ? "bg-gradient-to-br from-purple-600 to-blue-600 text-white shadow-2xl scale-105"
                    : "bg-white shadow-lg"
                }`}
              >
                {plan.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-yellow-400 text-purple-900 px-6 py-2 rounded-full text-sm font-bold">
                    Most Popular
                  </div>
                )}

                <h3
                  className={`text-2xl font-bold mb-2 ${
                    plan.popular ? "text-white" : "text-gray-900"
                  }`}
                >
                  {plan.name}
                </h3>

                <div className="mb-6">
                  <span className="text-5xl font-bold">{plan.price}</span>
                  <span
                    className={`text-lg ${
                      plan.popular ? "text-white/80" : "text-gray-600"
                    }`}
                  >
                    {plan.period}
                  </span>
                </div>

                <ul className="space-y-4 mb-8">
                  {plan.features.map((feature, j) => (
                    <li key={j} className="flex items-start">
                      <CheckCircle2
                        className={`w-5 h-5 mr-3 flex-shrink-0 mt-0.5 ${
                          plan.popular ? "text-white" : "text-green-500"
                        }`}
                      />
                      <span
                        className={
                          plan.popular ? "text-white" : "text-gray-700"
                        }
                      >
                        {feature}
                      </span>
                    </li>
                  ))}
                </ul>

                <Link
                  href="/signup"
                  className={`block text-center py-4 rounded-xl font-bold transition-all ${
                    plan.popular
                      ? "bg-white text-purple-600 hover:shadow-xl"
                      : "bg-gradient-to-r from-purple-600 to-blue-600 text-white hover:shadow-xl"
                  }`}
                >
                  Get Started
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Final CTA */}
      <section className="py-20 px-4 bg-gradient-to-r from-purple-600 via-blue-600 to-purple-600">
        <div className="max-w-4xl mx-auto text-center text-white">
          <h2 className="text-5xl font-bold mb-6">
            Ready to Start Your Investment Journey?
          </h2>
          <p className="text-xl opacity-90 mb-10">
            Join 50,000+ investors who trust PreIPO SIP for their pre-IPO investments
          </p>

          <Link
            href="/signup"
            className="inline-flex items-center px-12 py-5 bg-white text-purple-600 rounded-2xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all"
          >
            Create Free Account
            <ArrowRight className="w-5 h-5 ml-2" />
          </Link>

          <p className="mt-6 text-sm opacity-75">
            No credit card required • Start with ₹10,000 • Cancel anytime
          </p>
        </div>
      </section>

      {/* Add animation styles */}
      <style jsx>{`
        @keyframes blob {
          0%,
          100% {
            transform: translate(0, 0) scale(1);
          }
          33% {
            transform: translate(30px, -50px) scale(1.1);
          }
          66% {
            transform: translate(-20px, 20px) scale(0.9);
          }
        }

        .animate-blob {
          animation: blob 7s infinite;
        }

        .animation-delay-2000 {
          animation-delay: 2s;
        }

        .animation-delay-4000 {
          animation-delay: 4s;
        }
      `}</style>
    </div>
  );
}
