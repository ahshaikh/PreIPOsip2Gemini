"use client";
import Link from "next/link";
import {
  TrendingUp,
  ArrowRight,
  BarChart3,
  PieChart,
  Activity,
  Zap,
  Award,
  Clock,
  Target,
  LineChart,
  ChevronRight,
  AlertCircle,
  CheckCircle2,
  ArrowUpRight,
  ArrowDownRight,
} from "lucide-react";

export default function MarketAnalysisPage() {
  const sectors = [
    {
      name: "FinTech",
      growth: "+245%",
      deals: 18,
      avgReturn: "312%",
      trend: "up",
      color: "blue",
      avgValuation: "₹2,450 Cr",
    },
    {
      name: "HealthTech",
      growth: "+198%",
      deals: 12,
      avgReturn: "287%",
      trend: "up",
      color: "green",
      avgValuation: "₹1,850 Cr",
    },
    {
      name: "EdTech",
      growth: "+165%",
      deals: 15,
      avgReturn: "234%",
      trend: "up",
      color: "purple",
      avgValuation: "₹1,650 Cr",
    },
    {
      name: "E-Commerce",
      growth: "+142%",
      deals: 22,
      avgReturn: "198%",
      trend: "stable",
      color: "orange",
      avgValuation: "₹3,200 Cr",
    },
    {
      name: "SaaS",
      growth: "+178%",
      deals: 9,
      avgReturn: "265%",
      trend: "up",
      color: "cyan",
      avgValuation: "₹980 Cr",
    },
    {
      name: "Logistics",
      growth: "+125%",
      deals: 7,
      avgReturn: "176%",
      trend: "stable",
      color: "amber",
      avgValuation: "₹1,450 Cr",
    },
  ];

  const marketMetrics = [
    {
      label: "Total Market Size",
      value: "₹15,600 Cr",
      change: "+32%",
      trend: "up",
      icon: BarChart3,
    },
    {
      label: "Active Deals",
      value: "83",
      change: "+12",
      trend: "up",
      icon: Activity,
    },
    {
      label: "Avg. Return Rate",
      value: "287%",
      change: "+45%",
      trend: "up",
      icon: TrendingUp,
    },
    {
      label: "Success Rate",
      value: "94.2%",
      change: "+2.8%",
      trend: "up",
      icon: Target,
    },
  ];

  const insights = [
    {
      title: "FinTech Sector Dominates Pre-IPO Market",
      category: "Sector Analysis",
      date: "November 2025",
      summary:
        "FinTech continues to lead with 245% average growth. Payment platforms and neobanks are attracting maximum investor interest.",
      color: "blue",
    },
    {
      title: "IPO Pipeline Strong for 2025-26",
      category: "Market Outlook",
      date: "November 2025",
      summary:
        "Over 120 companies expected to go public in the next 18 months, creating significant exit opportunities for pre-IPO investors.",
      color: "green",
    },
    {
      title: "SaaS Companies Show Resilient Growth",
      category: "Sector Analysis",
      date: "November 2025",
      summary:
        "B2B SaaS companies demonstrate sustainable revenue models with 178% average growth and high investor confidence.",
      color: "purple",
    },
  ];

  const opportunities = [
    {
      sector: "Quick Commerce",
      companies: 4,
      investment: "₹50,000 - ₹2L",
      expectedReturn: "+320%",
      timeframe: "12-18 months",
      risk: "Medium",
    },
    {
      sector: "EV & Mobility",
      companies: 6,
      investment: "₹1L - ₹5L",
      expectedReturn: "+285%",
      timeframe: "18-24 months",
      risk: "Medium-High",
    },
    {
      sector: "HealthTech",
      companies: 8,
      investment: "₹75K - ₹3L",
      expectedReturn: "+287%",
      timeframe: "12-20 months",
      risk: "Low-Medium",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-blue-50 via-cyan-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-blue-100 dark:bg-blue-900/30 rounded-full px-6 py-3 mb-8 border border-blue-200 dark:border-blue-800">
              <Activity className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              <span className="font-semibold text-blue-900 dark:text-blue-300">
                Updated Nov 2025
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Pre-IPO Market
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 dark:from-blue-400 dark:via-cyan-400 dark:to-purple-400">
                Analysis & Insights
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
              Real-time market data, sector performance, and investment opportunities in the Indian pre-IPO ecosystem
            </p>
          </div>
        </div>
      </section>

      {/* Market Metrics */}
      <section className="py-20 -mt-10 relative z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {marketMetrics.map((metric, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105"
              >
                <div className="flex items-start justify-between mb-4">
                  <div className="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <metric.icon className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div
                    className={`flex items-center space-x-1 text-sm font-bold ${
                      metric.trend === "up"
                        ? "text-green-600 dark:text-green-400"
                        : "text-gray-600 dark:text-gray-400"
                    }`}
                  >
                    {metric.trend === "up" ? (
                      <ArrowUpRight className="w-4 h-4" />
                    ) : (
                      <ArrowDownRight className="w-4 h-4" />
                    )}
                    <span>{metric.change}</span>
                  </div>
                </div>
                <div className="text-3xl font-black text-gray-900 dark:text-white mb-1">
                  {metric.value}
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  {metric.label}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Sector Performance */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Sector Performance Analysis
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Deep dive into top performing sectors in pre-IPO market
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {sectors.map((sector, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className="flex items-start justify-between mb-6">
                  <div>
                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                      {sector.name}
                    </h3>
                    <div className="flex items-center space-x-2">
                      <span
                        className={`text-sm font-semibold px-3 py-1 rounded-full ${
                          sector.trend === "up"
                            ? "bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400"
                            : "bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400"
                        }`}
                      >
                        {sector.trend === "up" ? "Trending" : "Stable"}
                      </span>
                    </div>
                  </div>
                  <div className="text-3xl font-black text-green-600 dark:text-green-400">
                    {sector.growth}
                  </div>
                </div>

                <div className="space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                      Active Deals
                    </span>
                    <span className="text-lg font-bold text-gray-900 dark:text-white">
                      {sector.deals}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                      Avg. Return
                    </span>
                    <span className="text-lg font-bold text-blue-600 dark:text-blue-400">
                      {sector.avgReturn}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600 dark:text-gray-400">
                      Avg. Valuation
                    </span>
                    <span className="text-lg font-bold text-gray-900 dark:text-white">
                      {sector.avgValuation}
                    </span>
                  </div>
                </div>

                <Link
                  href={`/products?sector=${sector.name.toLowerCase()}`}
                  className="mt-6 flex items-center justify-center w-full px-6 py-3 bg-gray-100 dark:bg-slate-700 text-gray-900 dark:text-white font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors"
                >
                  View Deals
                  <ChevronRight className="w-5 h-5 ml-2" />
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Market Insights */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Latest Market Insights
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Expert analysis and market trends
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {insights.map((insight, index) => (
              <div
                key={index}
                className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className="flex items-center space-x-3 mb-4">
                  <span className="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold rounded-full">
                    {insight.category}
                  </span>
                  <span className="text-xs text-gray-500 dark:text-gray-500">
                    {insight.date}
                  </span>
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                  {insight.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                  {insight.summary}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Investment Opportunities */}
      <section className="py-20 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Top Investment Opportunities
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              High-potential sectors with strong fundamentals
            </p>
          </div>

          <div className="space-y-6">
            {opportunities.map((opp, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 shadow-lg hover:shadow-xl transition-all duration-300"
              >
                <div className="grid md:grid-cols-6 gap-6 items-center">
                  <div className="md:col-span-2">
                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                      {opp.sector}
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      {opp.companies} companies available
                    </p>
                  </div>

                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      Min. Investment
                    </div>
                    <div className="text-lg font-bold text-gray-900 dark:text-white">
                      {opp.investment}
                    </div>
                  </div>

                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      Expected Return
                    </div>
                    <div className="text-lg font-bold text-green-600 dark:text-green-400">
                      {opp.expectedReturn}
                    </div>
                  </div>

                  <div>
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      Timeframe
                    </div>
                    <div className="text-lg font-bold text-gray-900 dark:text-white">
                      {opp.timeframe}
                    </div>
                  </div>

                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        Risk Level
                      </div>
                      <div className="text-sm font-bold text-amber-600 dark:text-amber-400">
                        {opp.risk}
                      </div>
                    </div>
                    <Link
                      href={`/products?sector=${opp.sector.toLowerCase().replace(/\s+/g, "-")}`}
                      className="ml-4 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-xl hover:shadow-lg transition-all"
                    >
                      Explore
                      <ArrowRight className="inline w-4 h-4 ml-2" />
                    </Link>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Start Investing in High-Growth Sectors
          </h2>
          <p className="text-xl text-blue-100 mb-8">
            Access curated pre-IPO deals from top performing sectors
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/products"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-blue-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              Browse Deals
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              Create Free Account
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}