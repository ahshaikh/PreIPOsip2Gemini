"use client";
import Link from "next/link";
import {
  FileText,
  ArrowRight,
  Download,
  Eye,
  Clock,
  BarChart3,
  Building2,
  TrendingUp,
  Calendar,
  Star,
  Lock,
  CheckCircle2,
  FileCheck,
  Sparkles,
} from "lucide-react";

export default function ReportsPage() {
  const featuredReports = [
    {
      title: "Indian FinTech Sector Analysis 2025",
      category: "Sector Report",
      description:
        "Comprehensive analysis of India's booming FinTech sector with deep dives into payment gateways, neobanks, and InsureTech companies.",
      pages: 48,
      publishedDate: "Nov 15, 2025",
      downloads: "2.4K",
      views: "12.5K",
      isPremium: false,
      tags: ["FinTech", "Payments", "Neobanks"],
      color: "blue",
    },
    {
      title: "Pre-IPO Market Outlook 2025-26",
      category: "Market Report",
      description:
        "Annual market report covering IPO pipeline, sectoral trends, expected valuations, and investment opportunities for the upcoming year.",
      pages: 62,
      publishedDate: "Nov 10, 2025",
      downloads: "3.8K",
      views: "18.2K",
      isPremium: true,
      tags: ["Market", "IPO", "Outlook"],
      color: "purple",
    },
    {
      title: "Quick Commerce: The Next Big Wave",
      category: "Sector Report",
      description:
        "In-depth analysis of quick commerce companies including Zepto, Blinkit, Swiggy Instamart with growth metrics and market dynamics.",
      pages: 35,
      publishedDate: "Nov 8, 2025",
      downloads: "1.9K",
      views: "9.8K",
      isPremium: false,
      tags: ["E-Commerce", "Quick Commerce", "D2C"],
      color: "green",
    },
  ];

  const companyReports = [
    {
      company: "Swiggy",
      sector: "Food Tech",
      reportType: "IPO Analysis",
      pages: 28,
      date: "Nov 12, 2025",
      rating: "Strong Buy",
      isPremium: false,
    },
    {
      company: "PharmEasy",
      sector: "Health Tech",
      reportType: "Company Deep Dive",
      pages: 32,
      date: "Nov 9, 2025",
      rating: "Buy",
      isPremium: true,
    },
    {
      company: "Ola Electric",
      sector: "EV & Mobility",
      reportType: "Sector Comparison",
      pages: 24,
      date: "Nov 6, 2025",
      rating: "Buy",
      isPremium: false,
    },
    {
      company: "Meesho",
      sector: "E-Commerce",
      reportType: "Financial Analysis",
      pages: 29,
      date: "Nov 4, 2025",
      rating: "Hold",
      isPremium: false,
    },
    {
      company: "Zepto",
      sector: "Quick Commerce",
      reportType: "Growth Analysis",
      pages: 26,
      date: "Nov 1, 2025",
      rating: "Strong Buy",
      isPremium: true,
    },
    {
      company: "CRED",
      sector: "FinTech",
      reportType: "Business Model Study",
      pages: 31,
      date: "Oct 28, 2025",
      rating: "Buy",
      isPremium: false,
    },
  ];

  const sectorReports = [
    {
      sector: "HealthTech",
      reportsCount: 12,
      color: "green",
      icon: "🏥",
    },
    {
      sector: "EdTech",
      reportsCount: 15,
      color: "purple",
      icon: "📚",
    },
    {
      sector: "SaaS",
      reportsCount: 9,
      color: "cyan",
      icon: "💻",
    },
    {
      sector: "Logistics",
      reportsCount: 7,
      color: "amber",
      icon: "📦",
    },
  ];

  const reportTypes = [
    {
      type: "Sector Reports",
      description: "Industry-wide analysis and trends",
      count: 42,
      icon: BarChart3,
      color: "blue",
    },
    {
      type: "Company Reports",
      description: "Deep dives into individual companies",
      count: 68,
      icon: Building2,
      color: "purple",
    },
    {
      type: "IPO Analysis",
      description: "Detailed IPO readiness assessments",
      count: 34,
      icon: TrendingUp,
      color: "green",
    },
    {
      type: "Market Outlook",
      description: "Quarterly and annual market forecasts",
      count: 16,
      icon: Sparkles,
      color: "orange",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-purple-100 dark:bg-purple-900/30 rounded-full px-6 py-3 mb-8 border border-purple-200 dark:border-purple-800">
              <FileText className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span className="font-semibold text-purple-900 dark:text-purple-300">
                160+ Research Reports
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              In-Depth Research
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 dark:from-purple-400 dark:via-pink-400 dark:to-blue-400">
                Reports & Analysis
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
              Institutional-grade research on companies, sectors, and market trends. Make informed investment decisions with data-driven insights.
            </p>
          </div>
        </div>
      </section>

      {/* Report Types Grid */}
      <section className="py-20 -mt-10 relative z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {reportTypes.map((type, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105"
              >
                <div className={`w-14 h-14 bg-${type.color}-100 dark:bg-${type.color}-900/30 rounded-xl flex items-center justify-center mb-4`}>
                  <type.icon className={`w-7 h-7 text-${type.color}-600 dark:text-${type.color}-400`} />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  {type.type}
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  {type.description}
                </p>
                <div className="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600">
                  {type.count} Reports
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Featured Reports */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Featured Research Reports
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Latest and most popular reports from our research team
            </p>
          </div>

          <div className="space-y-8">
            {featuredReports.map((report, index) => (
              <div
                key={index}
                className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-3xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-2xl transition-all duration-300"
              >
                <div className="grid lg:grid-cols-3 gap-8">
                  <div className="lg:col-span-2">
                    <div className="flex items-start justify-between mb-4">
                      <div className="flex items-center space-x-3">
                        <span className="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-sm font-bold rounded-full">
                          {report.category}
                        </span>
                        {report.isPremium && (
                          <span className="px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-sm font-bold rounded-full flex items-center">
                            <Star className="w-3 h-3 mr-1" />
                            Premium
                          </span>
                        )}
                      </div>
                    </div>

                    <h3 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                      {report.title}
                    </h3>

                    <p className="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                      {report.description}
                    </p>

                    <div className="flex flex-wrap gap-2 mb-6">
                      {report.tags.map((tag, i) => (
                        <span
                          key={i}
                          className="px-3 py-1 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg"
                        >
                          #{tag}
                        </span>
                      ))}
                    </div>

                    <div className="flex items-center space-x-6 text-sm text-gray-600 dark:text-gray-400">
                      <div className="flex items-center space-x-2">
                        <FileText className="w-4 h-4" />
                        <span>{report.pages} pages</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Calendar className="w-4 h-4" />
                        <span>{report.publishedDate}</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Download className="w-4 h-4" />
                        <span>{report.downloads} downloads</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Eye className="w-4 h-4" />
                        <span>{report.views} views</span>
                      </div>
                    </div>
                  </div>

                  <div className="flex flex-col justify-between">
                    <div className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-2xl p-6 border border-purple-200 dark:border-purple-800">
                      <FileCheck className="w-12 h-12 text-purple-600 dark:text-purple-400 mb-4" />
                      <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        Institutional Grade
                      </div>
                      <div className="text-lg font-bold text-gray-900 dark:text-white">
                        Research Report
                      </div>
                    </div>

                    <div className="space-y-3 mt-6">
                      {report.isPremium ? (
                        <Link
                          href="/signup"
                          className="flex items-center justify-center w-full px-6 py-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold rounded-xl hover:shadow-xl transition-all"
                        >
                          <Lock className="w-5 h-5 mr-2" />
                          Unlock Premium
                        </Link>
                      ) : (
                        <>
                          <button className="flex items-center justify-center w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold rounded-xl hover:shadow-xl transition-all">
                            <Download className="w-5 h-5 mr-2" />
                            Download Report
                          </button>
                          <button className="flex items-center justify-center w-full px-6 py-4 bg-gray-100 dark:bg-slate-700 text-gray-900 dark:text-white font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                            <Eye className="w-5 h-5 mr-2" />
                            Preview
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Company Reports */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Company Analysis Reports
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Deep dives into pre-IPO companies
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {companyReports.map((report, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-1">
                      {report.company}
                    </h3>
                    <span className="text-sm text-purple-600 dark:text-purple-400 font-semibold">
                      {report.sector}
                    </span>
                  </div>
                  {report.isPremium && (
                    <Lock className="w-5 h-5 text-amber-600 dark:text-amber-400" />
                  )}
                </div>

                <div className="mb-4">
                  <span className="inline-block px-3 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg">
                    {report.reportType}
                  </span>
                </div>

                <div className="space-y-2 mb-4 text-sm text-gray-600 dark:text-gray-400">
                  <div className="flex items-center justify-between">
                    <span>Pages</span>
                    <span className="font-semibold">{report.pages}</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span>Published</span>
                    <span className="font-semibold">{report.date}</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span>Rating</span>
                    <span
                      className={`font-bold ${
                        report.rating === "Strong Buy"
                          ? "text-green-600 dark:text-green-400"
                          : report.rating === "Buy"
                          ? "text-blue-600 dark:text-blue-400"
                          : "text-amber-600 dark:text-amber-400"
                      }`}
                    >
                      {report.rating}
                    </span>
                  </div>
                </div>

                {report.isPremium ? (
                  <Link
                    href="/signup"
                    className="block w-full text-center px-4 py-3 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 font-semibold rounded-xl hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors"
                  >
                    Premium Only
                  </Link>
                ) : (
                  <button className="w-full px-4 py-3 bg-purple-600 text-white font-semibold rounded-xl hover:bg-purple-700 transition-colors">
                    Download Free
                  </button>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Sector Reports */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Browse by Sector
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Comprehensive sector-wise analysis
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {sectorReports.map((sector, index) => (
              <Link
                key={index}
                href={`/insights/reports?sector=${sector.sector.toLowerCase()}`}
                className="group bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300 hover:scale-105 text-center"
              >
                <div className="text-5xl mb-4">{sector.icon}</div>
                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  {sector.sector}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 mb-4">
                  {sector.reportsCount} reports available
                </p>
                <div className="flex items-center justify-center text-purple-600 dark:text-purple-400 font-semibold group-hover:translate-x-2 transition-transform">
                  View Reports
                  <ArrowRight className="w-4 h-4 ml-2" />
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 dark:from-purple-700 dark:via-pink-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Get Access to Premium Research
          </h2>
          <p className="text-xl text-purple-100 mb-8">
            Unlock institutional-grade reports and make data-driven investment decisions
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              Start Free Trial
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/plans"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              View Plans
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}