"use client";

import Link from "next/link";
import { useState } from "react";
import {
  TrendingUp,
  Shield,
  Zap,
  Star,
  ArrowRight,
  CheckCircle2,
  Lock,
  Award,
  BarChart3,
  Users,
  Target,
  Sparkles,
  Globe,
  ChevronRight,
  Play,
} from "lucide-react";

export default function Home4() {
  const [activeTestimonial, setActiveTestimonial] = useState(0);

  const companies = [
    { name: "TechCorp", growth: "+287%", amount: "₹2.5Cr" },
    { name: "FinanceX", growth: "+195%", amount: "₹1.8Cr" },
    { name: "HealthTech", growth: "+342%", amount: "₹3.2Cr" },
    { name: "EduSpace", growth: "+218%", amount: "₹1.5Cr" },
    { name: "LogiNext", growth: "+265%", amount: "₹2.1Cr" },
  ];

  const testimonials = [
    {
      name: "Rajesh Kumar",
      role: "Senior Investor",
      content: "PreIPOsip gave me access to unicorn companies before they went public. My portfolio grew 3x in 18 months.",
      returns: "+340%",
      image: "👨‍💼",
    },
    {
      name: "Priya Sharma",
      role: "Tech Executive",
      content: "The research and insights provided are institutional-grade. I made informed decisions and saw exceptional returns.",
      returns: "+285%",
      image: "👩‍💼",
    },
    {
      name: "Amit Patel",
      role: "Entrepreneur",
      content: "From KYC to deal execution, everything is seamless. The platform is designed for serious investors.",
      returns: "+412%",
      image: "👨‍💻",
    },
  ];

  const features = [
    {
      icon: Shield,
      title: "SEBI Compliant",
      description: "100% regulatory compliant with RBI-approved payment gateways",
      color: "emerald",
    },
    {
      icon: Lock,
      title: "Bank-Grade Security",
      description: "256-bit encryption with ISO 27001 certified infrastructure",
      color: "blue",
    },
    {
      icon: Award,
      title: "Exclusive Access",
      description: "Direct access to pre-IPO shares of India's fastest-growing companies",
      color: "amber",
    },
    {
      icon: BarChart3,
      title: "Real-Time Analytics",
      description: "Track your portfolio with institutional-grade analytics and insights",
      color: "purple",
    },
  ];

  const plans = [
    {
      name: "Explorer",
      price: "Free",
      description: "Start your pre-IPO investment journey",
      features: [
        "Access to 10+ pre-IPO listings",
        "Basic market insights",
        "Email support",
        "Mobile app access",
      ],
      cta: "Start Free",
      popular: false,
    },
    {
      name: "Investor",
      price: "₹999/mo",
      description: "For serious investors seeking growth",
      features: [
        "Access to 50+ exclusive deals",
        "Advanced analytics dashboard",
        "Priority support (24/7)",
        "Dedicated relationship manager",
        "Early deal notifications",
        "Portfolio tracking tools",
      ],
      cta: "Get Started",
      popular: true,
    },
    {
      name: "Elite",
      price: "₹4,999/mo",
      description: "Institutional-grade investment platform",
      features: [
        "Unlimited access to all deals",
        "Institutional research reports",
        "White-glove concierge service",
        "Direct founder connects",
        "Tax optimization advisory",
        "Quarterly performance reviews",
        "Legal documentation support",
      ],
      cta: "Contact Sales",
      popular: false,
    },
  ];

  return (
    <div className="min-h-screen bg-slate-950 text-white">
      {/* Hero Section with Video Background */}
      <section className="relative min-h-screen flex items-center justify-center overflow-hidden">
        {/* Dark gradient overlay */}
        <div className="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 opacity-95 z-10" />

        {/* Animated background pattern */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-0 -left-4 w-72 h-72 bg-amber-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse" />
          <div className="absolute top-0 -right-4 w-72 h-72 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse animation-delay-2000" />
          <div className="absolute -bottom-8 left-20 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse animation-delay-4000" />
        </div>

        <div className="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Left Content */}
            <div className="space-y-8">
              {/* Badge */}
              <div className="inline-flex items-center space-x-2 bg-amber-500/10 border border-amber-500/20 rounded-full px-4 py-2 backdrop-blur-sm">
                <Sparkles className="w-4 h-4 text-amber-400" />
                <span className="text-sm font-medium text-amber-400">
                  India's Most Trusted Pre-IPO Platform
                </span>
              </div>

              {/* Main Heading */}
              <h1 className="text-6xl lg:text-7xl font-bold leading-tight">
                Invest in the
                <span className="block text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600">
                  Next Unicorns
                </span>
                Before IPO
              </h1>

              <p className="text-xl text-slate-400 leading-relaxed">
                Access exclusive pre-IPO shares of India's fastest-growing companies.
                Join 50,000+ elite investors building generational wealth.
              </p>

              {/* Stats */}
              <div className="grid grid-cols-3 gap-6 pt-4">
                <div className="space-y-1">
                  <div className="text-3xl font-bold text-amber-400">₹2,500Cr+</div>
                  <div className="text-sm text-slate-500">Total Invested</div>
                </div>
                <div className="space-y-1">
                  <div className="text-3xl font-bold text-emerald-400">287%</div>
                  <div className="text-sm text-slate-500">Avg. Returns</div>
                </div>
                <div className="space-y-1">
                  <div className="text-3xl font-bold text-purple-400">50K+</div>
                  <div className="text-sm text-slate-500">Investors</div>
                </div>
              </div>

              {/* CTAs */}
              <div className="flex flex-col sm:flex-row gap-4 pt-4">
                <Link
                  href="/signup"
                  className="group inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 rounded-xl hover:shadow-2xl hover:shadow-amber-500/50 transition-all duration-300 hover:scale-105"
                >
                  Start Investing Now
                  <ArrowRight className="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
                </Link>
                <button className="group inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 backdrop-blur-sm transition-all duration-300">
                  <Play className="mr-2 w-5 h-5" />
                  Watch Demo
                </button>
              </div>

              {/* Trust Badges */}
              <div className="flex items-center space-x-6 pt-6 border-t border-slate-800">
                <div className="flex items-center space-x-2 text-sm text-slate-400">
                  <Shield className="w-5 h-5 text-emerald-400" />
                  <span>SEBI Registered</span>
                </div>
                <div className="flex items-center space-x-2 text-sm text-slate-400">
                  <Lock className="w-5 h-5 text-blue-400" />
                  <span>ISO 27001 Certified</span>
                </div>
                <div className="flex items-center space-x-2 text-sm text-slate-400">
                  <Star className="w-5 h-5 text-amber-400" />
                  <span>4.9/5 Rating</span>
                </div>
              </div>
            </div>

            {/* Right - Featured Companies Glass Card */}
            <div className="hidden lg:block">
              <div className="relative">
                {/* Glass card */}
                <div className="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
                  <div className="flex items-center justify-between mb-6">
                    <h3 className="text-xl font-semibold">Featured Opportunities</h3>
                    <span className="text-xs text-emerald-400 bg-emerald-400/10 px-3 py-1 rounded-full">
                      LIVE
                    </span>
                  </div>

                  <div className="space-y-4">
                    {companies.map((company, index) => (
                      <div
                        key={index}
                        className="group bg-white/5 hover:bg-white/10 border border-white/5 rounded-xl p-4 transition-all duration-300 cursor-pointer"
                      >
                        <div className="flex items-center justify-between mb-2">
                          <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-lg flex items-center justify-center text-slate-950 font-bold">
                              {company.name[0]}
                            </div>
                            <div>
                              <div className="font-semibold">{company.name}</div>
                              <div className="text-xs text-slate-500">Pre-IPO Series D</div>
                            </div>
                          </div>
                          <ChevronRight className="w-5 h-5 text-slate-600 group-hover:text-amber-400 transition-colors" />
                        </div>
                        <div className="flex items-center justify-between text-sm">
                          <div className="text-slate-400">Min. Investment</div>
                          <div className="font-semibold">{company.amount}</div>
                        </div>
                        <div className="flex items-center justify-between text-sm mt-1">
                          <div className="text-slate-400">Expected Returns</div>
                          <div className="text-emerald-400 font-semibold">{company.growth}</div>
                        </div>
                      </div>
                    ))}
                  </div>

                  <Link
                    href="/products"
                    className="block text-center mt-6 text-amber-400 hover:text-amber-300 font-semibold transition-colors"
                  >
                    View All Deals →
                  </Link>
                </div>

                {/* Decorative glow */}
                <div className="absolute -inset-0.5 bg-gradient-to-r from-amber-500 to-emerald-500 rounded-3xl blur-xl opacity-20 group-hover:opacity-30 transition-opacity -z-10" />
              </div>
            </div>
          </div>
        </div>

        {/* Scroll indicator */}
        <div className="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 animate-bounce">
          <div className="w-6 h-10 border-2 border-white/20 rounded-full flex justify-center">
            <div className="w-1.5 h-2 bg-amber-400 rounded-full mt-2 animate-pulse" />
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-24 bg-slate-900/50 border-t border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl lg:text-5xl font-bold mb-4">
              Why Elite Investors Choose
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600"> PreIPOsip</span>
            </h2>
            <p className="text-xl text-slate-400 max-w-2xl mx-auto">
              Institutional-grade infrastructure designed for sophisticated investors
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <div
                key={index}
                className="group relative bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
              >
                {/* Icon */}
                <div
                  className={`w-14 h-14 bg-gradient-to-br from-${feature.color}-400 to-${feature.color}-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform shadow-lg shadow-${feature.color}-500/50`}
                >
                  <feature.icon className="w-7 h-7 text-white" />
                </div>

                <h3 className="text-xl font-semibold mb-3">{feature.title}</h3>
                <p className="text-slate-400 leading-relaxed">{feature.description}</p>

                {/* Decorative glow on hover */}
                <div className={`absolute inset-0 bg-gradient-to-br from-${feature.color}-500/20 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity -z-10 blur-xl`} />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works Section */}
      <section className="py-24 border-t border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl lg:text-5xl font-bold mb-4">
              Start Investing in
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600"> 3 Simple Steps</span>
            </h2>
            <p className="text-xl text-slate-400">From signup to deal execution in under 24 hours</p>
          </div>

          <div className="grid md:grid-cols-3 gap-12">
            {[
              {
                step: "01",
                title: "Create Your Account",
                description: "Sign up in minutes with your PAN and Aadhaar. Complete KYC verification instantly with DigiLocker integration.",
                icon: Users,
                color: "amber",
              },
              {
                step: "02",
                title: "Browse Exclusive Deals",
                description: "Access curated pre-IPO opportunities with detailed research reports, founder videos, and financial analysis.",
                icon: Target,
                color: "emerald",
              },
              {
                step: "03",
                title: "Invest & Track",
                description: "Execute deals securely with UPI/Net Banking. Monitor your portfolio with real-time analytics and exit strategies.",
                icon: TrendingUp,
                color: "purple",
              },
            ].map((item, index) => (
              <div key={index} className="relative">
                {/* Connecting line (hidden on mobile) */}
                {index < 2 && (
                  <div className="hidden md:block absolute top-12 left-1/2 w-full h-0.5 bg-gradient-to-r from-slate-700 to-transparent" />
                )}

                <div className="relative bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all duration-300">
                  {/* Step number */}
                  <div className={`absolute -top-4 -right-4 w-16 h-16 bg-gradient-to-br from-${item.color}-400 to-${item.color}-600 rounded-full flex items-center justify-center text-2xl font-bold shadow-lg`}>
                    {item.step}
                  </div>

                  <div className={`w-14 h-14 bg-${item.color}-500/10 rounded-xl flex items-center justify-center mb-6`}>
                    <item.icon className={`w-7 h-7 text-${item.color}-400`} />
                  </div>

                  <h3 className="text-2xl font-semibold mb-4">{item.title}</h3>
                  <p className="text-slate-400 leading-relaxed">{item.description}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="text-center mt-12">
            <Link
              href="/how-it-works"
              className="inline-flex items-center text-amber-400 hover:text-amber-300 font-semibold transition-colors"
            >
              Learn More About Our Process
              <ChevronRight className="ml-1 w-5 h-5" />
            </Link>
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section className="py-24 bg-slate-900/50 border-t border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl lg:text-5xl font-bold mb-4">
              Trusted by
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600"> 50,000+ Investors</span>
            </h2>
            <p className="text-xl text-slate-400">Real stories from investors who transformed their portfolios</p>
          </div>

          {/* Featured Testimonial */}
          <div className="max-w-4xl mx-auto">
            <div className="relative bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-12 shadow-2xl">
              <div className="absolute top-8 left-8 text-6xl text-amber-500/20">"</div>

              <div className="relative z-10">
                <p className="text-2xl leading-relaxed mb-8 text-slate-200">
                  {testimonials[activeTestimonial].content}
                </p>

                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className="w-16 h-16 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center text-3xl">
                      {testimonials[activeTestimonial].image}
                    </div>
                    <div>
                      <div className="font-semibold text-lg">{testimonials[activeTestimonial].name}</div>
                      <div className="text-slate-400">{testimonials[activeTestimonial].role}</div>
                    </div>
                  </div>

                  <div className="text-right">
                    <div className="text-3xl font-bold text-emerald-400">
                      {testimonials[activeTestimonial].returns}
                    </div>
                    <div className="text-sm text-slate-500">Total Returns</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Testimonial dots */}
            <div className="flex justify-center space-x-2 mt-8">
              {testimonials.map((_, index) => (
                <button
                  key={index}
                  onClick={() => setActiveTestimonial(index)}
                  className={`w-3 h-3 rounded-full transition-all duration-300 ${
                    activeTestimonial === index
                      ? "bg-amber-400 w-8"
                      : "bg-slate-700 hover:bg-slate-600"
                  }`}
                />
              ))}
            </div>
          </div>

          {/* Trust metrics */}
          <div className="grid md:grid-cols-4 gap-8 mt-16 max-w-5xl mx-auto">
            {[
              { label: "Average Rating", value: "4.9/5", icon: Star },
              { label: "Success Rate", value: "94%", icon: TrendingUp },
              { label: "Active Investors", value: "50K+", icon: Users },
              { label: "Deals Completed", value: "1,200+", icon: CheckCircle2 },
            ].map((metric, index) => (
              <div key={index} className="text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 bg-amber-500/10 rounded-xl mb-3">
                  <metric.icon className="w-6 h-6 text-amber-400" />
                </div>
                <div className="text-3xl font-bold text-amber-400 mb-1">{metric.value}</div>
                <div className="text-sm text-slate-500">{metric.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section className="py-24 border-t border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl lg:text-5xl font-bold mb-4">
              Choose Your
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600"> Investment Plan</span>
            </h2>
            <p className="text-xl text-slate-400">Flexible plans designed for every investor level</p>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            {plans.map((plan, index) => (
              <div
                key={index}
                className={`relative bg-white/5 backdrop-blur-sm border rounded-3xl p-8 transition-all duration-300 hover:scale-105 ${
                  plan.popular
                    ? "border-amber-500 shadow-2xl shadow-amber-500/20"
                    : "border-white/10 hover:border-white/20"
                }`}
              >
                {plan.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-400 to-amber-600 text-slate-950 px-6 py-1 rounded-full text-sm font-semibold">
                    Most Popular
                  </div>
                )}

                <div className="text-center mb-8">
                  <h3 className="text-2xl font-bold mb-2">{plan.name}</h3>
                  <p className="text-slate-400 text-sm mb-6">{plan.description}</p>
                  <div className="text-5xl font-bold text-amber-400 mb-2">{plan.price}</div>
                </div>

                <ul className="space-y-4 mb-8">
                  {plan.features.map((feature, idx) => (
                    <li key={idx} className="flex items-start space-x-3">
                      <CheckCircle2 className="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" />
                      <span className="text-slate-300">{feature}</span>
                    </li>
                  ))}
                </ul>

                <Link
                  href={plan.cta === "Contact Sales" ? "/contact" : "/signup"}
                  className={`block text-center px-8 py-4 rounded-xl font-semibold transition-all duration-300 ${
                    plan.popular
                      ? "bg-gradient-to-r from-amber-400 to-amber-600 text-slate-950 hover:shadow-2xl hover:shadow-amber-500/50 hover:scale-105"
                      : "bg-white/5 border border-white/10 text-white hover:bg-white/10"
                  }`}
                >
                  {plan.cta}
                </Link>
              </div>
            ))}
          </div>

          <p className="text-center text-slate-500 mt-12">
            All plans include bank-grade security, SEBI compliance, and 24/7 customer support
          </p>
        </div>
      </section>

      {/* Final CTA Section */}
      <section className="py-24 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border-t border-slate-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="inline-flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-2 mb-8">
            <Globe className="w-4 h-4 text-emerald-400" />
            <span className="text-sm font-medium text-emerald-400">Join 50,000+ investors building wealth</span>
          </div>

          <h2 className="text-5xl lg:text-6xl font-bold mb-6">
            Ready to Invest in
            <span className="block text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">
              Tomorrow's Giants?
            </span>
          </h2>

          <p className="text-xl text-slate-400 mb-12 max-w-2xl mx-auto">
            Get exclusive access to pre-IPO opportunities and start building generational wealth today.
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <Link
              href="/signup"
              className="group inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-600 rounded-xl hover:shadow-2xl hover:shadow-amber-500/50 transition-all duration-300 hover:scale-105"
            >
              Start Your Free Account
              <ArrowRight className="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </Link>
            <Link
              href="/products"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 backdrop-blur-sm transition-all duration-300"
            >
              Browse Live Deals
            </Link>
          </div>

          <div className="grid grid-cols-3 gap-8 max-w-2xl mx-auto pt-12 border-t border-slate-700">
            <div>
              <div className="text-3xl font-bold text-amber-400 mb-2">100%</div>
              <div className="text-sm text-slate-500">SEBI Compliant</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-emerald-400 mb-2">24/7</div>
              <div className="text-sm text-slate-500">Support Available</div>
            </div>
            <div>
              <div className="text-3xl font-bold text-purple-400 mb-2">₹0</div>
              <div className="text-sm text-slate-500">Hidden Charges</div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
