"use client";

import { useState } from "react";
import Link from "next/link";
import {
  TrendingUp,
  Shield,
  CheckCircle,
  ArrowRight,
  Play,
  BarChart,
  PieChart,
  Activity,
  DollarSign,
  Target,
  Users,
  Building2,
  Briefcase,
  LineChart,
} from "lucide-react";

export default function Home3() {
  const [activeTab, setActiveTab] = useState("growth");

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section - Clean & Data-Driven */}
      <section className="relative pt-24 pb-16 px-4 bg-gradient-to-b from-gray-50 to-white">
        <div className="max-w-7xl mx-auto">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Left: Content */}
            <div>
              <div className="inline-flex items-center space-x-2 bg-blue-50 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                <TrendingUp className="w-4 h-4" />
                <span>94% Success Rate • SEBI Verified</span>
              </div>

              <h1 className="text-5xl lg:text-6xl font-bold text-gray-900 mb-6 leading-tight">
                Data-Driven Pre-IPO
                <span className="block text-blue-600">Investing Made Simple</span>
              </h1>

              <p className="text-xl text-gray-600 mb-8 leading-relaxed">
                Access institutional-grade pre-IPO deals with transparent data,
                verified companies, and expert insights. Minimum investment: ₹10,000.
              </p>

              <div className="flex items-center space-x-4 mb-8">
                <Link
                  href="/signup"
                  className="px-8 py-4 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition flex items-center"
                >
                  Start Investing
                  <ArrowRight className="w-5 h-5 ml-2" />
                </Link>

                <button className="px-8 py-4 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:border-gray-400 transition flex items-center">
                  <Play className="w-5 h-5 mr-2" />
                  Watch Demo
                </button>
              </div>

              <div className="flex items-center space-x-6 text-sm text-gray-500">
                <div className="flex items-center space-x-2">
                  <CheckCircle className="w-4 h-4 text-green-500" />
                  <span>No hidden fees</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="w-4 h-4 text-green-500" />
                  <span>Bank-grade security</span>
                </div>
              </div>
            </div>

            {/* Right: Data Visualization */}
            <div className="relative">
              <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-3xl p-8 shadow-xl">
                <div className="mb-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">
                    Portfolio Performance
                  </h3>

                  <div className="flex space-x-2 mb-6">
                    {["growth", "returns", "deals"].map((tab) => (
                      <button
                        key={tab}
                        onClick={() => setActiveTab(tab)}
                        className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                          activeTab === tab
                            ? "bg-blue-600 text-white"
                            : "bg-white text-gray-600 hover:bg-gray-50"
                        }`}
                      >
                        {tab.charAt(0).toUpperCase() + tab.slice(1)}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Simplified Chart Visualization */}
                <div className="bg-white rounded-2xl p-6 mb-4">
                  <div className="flex items-end space-x-3 h-48">
                    {[65, 72, 85, 78, 92, 88, 95, 98].map((height, i) => (
                      <div
                        key={i}
                        className="flex-1 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg transition-all hover:from-blue-700 hover:to-blue-500"
                        style={{ height: `${height}%` }}
                      />
                    ))}
                  </div>
                  <div className="flex justify-between mt-4 text-xs text-gray-500">
                    <span>Jan</span>
                    <span>Feb</span>
                    <span>Mar</span>
                    <span>Apr</span>
                    <span>May</span>
                    <span>Jun</span>
                    <span>Jul</span>
                    <span>Aug</span>
                  </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-white rounded-xl p-4">
                    <div className="text-2xl font-bold text-gray-900">
                      +287%
                    </div>
                    <div className="text-sm text-gray-500">Avg. Returns</div>
                  </div>
                  <div className="bg-white rounded-xl p-4">
                    <div className="text-2xl font-bold text-gray-900">₹250Cr+</div>
                    <div className="text-sm text-gray-500">AUM</div>
                  </div>
                </div>
              </div>

              {/* Floating Badge */}
              <div className="absolute -bottom-6 -left-6 bg-white rounded-2xl shadow-xl p-4 border border-gray-100">
                <div className="flex items-center space-x-3">
                  <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <TrendingUp className="w-6 h-6 text-green-600" />
                  </div>
                  <div>
                    <div className="text-lg font-bold text-gray-900">50,000+</div>
                    <div className="text-sm text-gray-500">Active Investors</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Stats Bar */}
      <section className="py-12 px-4 border-y border-gray-200 bg-gray-50">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {[
              { label: "Companies Listed", value: "100+", icon: Building2 },
              { label: "Total Investors", value: "50K+", icon: Users },
              { label: "Success Stories", value: "1,200+", icon: Target },
              { label: "Avg ROI", value: "287%", icon: BarChart },
            ].map((stat, i) => (
              <div key={i} className="text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-xl mb-3">
                  <stat.icon className="w-6 h-6 text-blue-600" />
                </div>
                <div className="text-3xl font-bold text-gray-900 mb-1">
                  {stat.value}
                </div>
                <div className="text-sm text-gray-600">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features - Clean Grid */}
      <section className="py-20 px-4">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Why Investors Choose Us
            </h2>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Transparent, secure, and backed by data
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {[
              {
                icon: Shield,
                title: "SEBI Verified Platform",
                description:
                  "Fully compliant with regulatory standards. Your investments are secure.",
              },
              {
                icon: BarChart,
                title: "Real-Time Analytics",
                description:
                  "Track your portfolio performance with institutional-grade tools.",
              },
              {
                icon: DollarSign,
                title: "Transparent Pricing",
                description:
                  "No hidden fees. Clear pricing structure for all investment plans.",
              },
              {
                icon: Activity,
                title: "Live Deal Flow",
                description:
                  "Access to verified pre-IPO opportunities updated in real-time.",
              },
              {
                icon: PieChart,
                title: "Diversified Portfolio",
                description:
                  "Invest across multiple sectors and reduce concentration risk.",
              },
              {
                icon: Briefcase,
                title: "Expert Curation",
                description:
                  "Every deal is vetted by our team of investment professionals.",
              },
            ].map((feature, i) => (
              <div
                key={i}
                className="p-8 border border-gray-200 rounded-2xl hover:border-blue-300 hover:shadow-lg transition-all group"
              >
                <div className="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-6 group-hover:bg-blue-600 transition-colors">
                  <feature.icon className="w-7 h-7 text-blue-600 group-hover:text-white transition-colors" />
                </div>
                <h3 className="text-xl font-bold text-gray-900 mb-3">
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

      {/* How It Works - Timeline */}
      <section className="py-20 px-4 bg-gray-50">
        <div className="max-w-5xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Start Investing in 3 Steps
            </h2>
            <p className="text-xl text-gray-600">
              Simple, fast, and completely secure
            </p>
          </div>

          <div className="relative">
            {/* Timeline Line */}
            <div className="hidden md:block absolute top-1/2 left-0 right-0 h-1 bg-gray-200"></div>

            <div className="grid md:grid-cols-3 gap-8 relative">
              {[
                {
                  step: "1",
                  title: "Create Account & KYC",
                  description:
                    "Sign up in 2 minutes. Complete one-time KYC verification.",
                  time: "2 min",
                },
                {
                  step: "2",
                  title: "Browse & Select Deals",
                  description:
                    "Explore curated pre-IPO opportunities. Review detailed analytics.",
                  time: "10 min",
                },
                {
                  step: "3",
                  title: "Invest & Track",
                  description:
                    "Make your investment. Monitor real-time performance.",
                  time: "Ongoing",
                },
              ].map((step, i) => (
                <div key={i} className="relative text-center">
                  <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-full font-bold text-2xl mb-6 shadow-lg relative z-10">
                    {step.step}
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-3">
                    {step.title}
                  </h3>
                  <p className="text-gray-600 mb-4">{step.description}</p>
                  <div className="inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                    {step.time}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Pricing - Clean Cards */}
      <section className="py-20 px-4">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Transparent Pricing
            </h2>
            <p className="text-xl text-gray-600">
              Choose a plan that fits your investment goals
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                name: "Starter",
                price: "999",
                features: [
                  "5 deals per month",
                  "Basic analytics",
                  "Email support",
                  "Mobile app access",
                ],
              },
              {
                name: "Premium",
                price: "2,499",
                features: [
                  "Unlimited deals",
                  "Advanced analytics",
                  "Priority support",
                  "Expert consultations",
                  "Early deal access",
                ],
                popular: true,
              },
              {
                name: "Enterprise",
                price: "9,999",
                features: [
                  "All Premium features",
                  "Dedicated manager",
                  "Custom strategies",
                  "Institutional deals",
                  "Tax optimization",
                ],
              },
            ].map((plan, i) => (
              <div
                key={i}
                className={`rounded-2xl p-8 border-2 ${
                  plan.popular
                    ? "border-blue-600 shadow-xl"
                    : "border-gray-200"
                }`}
              >
                {plan.popular && (
                  <div className="inline-block px-3 py-1 bg-blue-600 text-white rounded-full text-sm font-semibold mb-4">
                    Most Popular
                  </div>
                )}

                <h3 className="text-2xl font-bold text-gray-900 mb-2">
                  {plan.name}
                </h3>

                <div className="mb-6">
                  <span className="text-4xl font-bold text-gray-900">
                    ₹{plan.price}
                  </span>
                  <span className="text-gray-600">/month</span>
                </div>

                <ul className="space-y-3 mb-8">
                  {plan.features.map((feature, j) => (
                    <li key={j} className="flex items-center text-gray-700">
                      <CheckCircle className="w-5 h-5 text-green-500 mr-3 flex-shrink-0" />
                      {feature}
                    </li>
                  ))}
                </ul>

                <Link
                  href="/signup"
                  className={`block text-center py-3 rounded-lg font-semibold transition ${
                    plan.popular
                      ? "bg-blue-600 text-white hover:bg-blue-700"
                      : "bg-gray-100 text-gray-900 hover:bg-gray-200"
                  }`}
                >
                  Get Started
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials */}
      <section className="py-20 px-4 bg-gray-50">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 mb-4">
              Trusted by Thousands
            </h2>
            <p className="text-xl text-gray-600">
              See what our investors say about us
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {[
              {
                name: "Rajesh Kumar",
                role: "Software Engineer",
                text: "Invested ₹50K in Zomato pre-IPO through PreIPO SIP. Made 4.2x returns within 18 months!",
                rating: 5,
              },
              {
                name: "Priya Sharma",
                role: "Business Owner",
                text: "The platform is so easy to use. Real-time analytics help me track my entire portfolio.",
                rating: 5,
              },
              {
                name: "Amit Patel",
                role: "Investment Banker",
                text: "Impressed by the quality of deals and transparency. SEBI compliance gives me confidence.",
                rating: 5,
              },
            ].map((testimonial, i) => (
              <div key={i} className="bg-white rounded-2xl p-6 border border-gray-200">
                <div className="flex mb-4">
                  {[...Array(testimonial.rating)].map((_, j) => (
                    <svg
                      key={j}
                      className="w-5 h-5 text-yellow-400 fill-current"
                      viewBox="0 0 20 20"
                    >
                      <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                    </svg>
                  ))}
                </div>
                <p className="text-gray-700 mb-6 leading-relaxed">
                  "{testimonial.text}"
                </p>
                <div>
                  <div className="font-bold text-gray-900">{testimonial.name}</div>
                  <div className="text-sm text-gray-500">{testimonial.role}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 px-4 bg-blue-600 text-white">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-4xl font-bold mb-6">
            Ready to Build Your Pre-IPO Portfolio?
          </h2>
          <p className="text-xl mb-10 opacity-90">
            Join 50,000+ investors growing their wealth with PreIPO SIP
          </p>

          <Link
            href="/signup"
            className="inline-flex items-center px-10 py-4 bg-white text-blue-600 rounded-lg font-bold text-lg hover:shadow-xl transition"
          >
            Create Free Account
            <ArrowRight className="w-5 h-5 ml-2" />
          </Link>

          <p className="mt-6 text-sm opacity-75">
            No credit card required • Start with ₹10,000
          </p>
        </div>
      </section>
    </div>
  );
}
