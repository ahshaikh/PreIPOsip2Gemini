"use client";
import Link from "next/link";
import {
  Newspaper,
  ArrowRight,
  TrendingUp,
  Clock,
  Calendar,
  Tag,
  Zap,
  AlertCircle,
  CheckCircle2,
  Bell,
  Sparkles,
  Building2,
  BarChart3,
  DollarSign,
} from "lucide-react";

export default function NewsPage() {
  const breakingNews = [
    {
      title: "Zepto Raises $350M at $5B Valuation",
      summary:
        "Quick commerce unicorn Zepto has raised $350 million in its latest funding round led by StepStone Group, valuing the company at $5 billion.",
      category: "Funding",
      time: "2 hours ago",
      isBreaking: true,
      image: "📦",
      link: "#",
    },
    {
      title: "Swiggy IPO Opens Tomorrow at ₹390 Per Share",
      summary:
        "Food delivery giant Swiggy's much-awaited IPO will open for subscription tomorrow with a price band of ₹371-₹390 per share.",
      category: "IPO",
      time: "5 hours ago",
      isBreaking: true,
      image: "🍔",
      link: "#",
    },
  ];

  const latestNews = [
    {
      title: "PharmEasy Files for ₹6,250 Cr IPO",
      summary:
        "Online pharmacy PharmEasy has filed draft papers with SEBI for an IPO to raise up to ₹6,250 crore. The company plans to use proceeds for debt repayment and business expansion.",
      category: "IPO",
      date: "Nov 20, 2025",
      readTime: "4 min",
      image: "💊",
      tags: ["HealthTech", "IPO", "Unicorn"],
    },
    {
      title: "Ola Electric Announces Q2 Results",
      summary:
        "Ola Electric reported a 67% YoY revenue growth in Q2 FY26, driven by strong scooter sales. The company reduced losses by 15% compared to the previous quarter.",
      category: "Earnings",
      date: "Nov 19, 2025",
      readTime: "3 min",
      image: "⚡",
      tags: ["EV", "Earnings", "Growth"],
    },
    {
      title: "Meesho Achieves First Profitable Quarter",
      summary:
        "E-commerce platform Meesho has turned profitable for the first time since inception, reporting a net profit of ₹45 crore in Q2. The milestone comes after years of aggressive growth.",
      category: "Milestone",
      date: "Nov 18, 2025",
      readTime: "5 min",
      image: "🛍️",
      tags: ["E-Commerce", "Profitability", "Milestone"],
    },
    {
      title: "SEBI Tightens Pre-IPO Listing Norms",
      summary:
        "Market regulator SEBI has introduced stricter disclosure norms for companies planning to list. The new rules aim to enhance transparency and investor protection.",
      category: "Regulation",
      date: "Nov 17, 2025",
      readTime: "6 min",
      image: "⚖️",
      tags: ["Regulation", "SEBI", "Policy"],
    },
    {
      title: "CRED Valued at $6.4B in Secondary Sale",
      summary:
        "FinTech unicorn CRED's valuation jumped to $6.4 billion in a recent secondary transaction, marking a 25% increase from its last primary funding round.",
      category: "Valuation",
      date: "Nov 16, 2025",
      readTime: "4 min",
      image: "💳",
      tags: ["FinTech", "Valuation", "Secondary"],
    },
    {
      title: "Byju's Restructures Business Amid IPO Plans",
      summary:
        "EdTech major Byju's is undergoing a major business restructuring as it prepares for a potential IPO. The company is focusing on profitability and core markets.",
      category: "Business",
      date: "Nov 15, 2025",
      readTime: "7 min",
      image: "📚",
      tags: ["EdTech", "Restructuring", "IPO"],
    },
  ];

  const categories = [
    { name: "IPO News", count: 24, icon: TrendingUp, color: "blue" },
    { name: "Funding Rounds", count: 18, icon: DollarSign, color: "green" },
    { name: "Market Updates", count: 32, icon: BarChart3, color: "purple" },
    { name: "Regulations", count: 12, icon: AlertCircle, color: "orange" },
    { name: "Company News", count: 45, icon: Building2, color: "cyan" },
    { name: "Milestones", count: 16, icon: CheckCircle2, color: "amber" },
  ];

  const trendingTopics = [
    { topic: "Quick Commerce IPOs", count: "12 articles" },
    { topic: "EV Sector Growth", count: "8 articles" },
    { topic: "FinTech Regulations", count: "15 articles" },
    { topic: "SaaS Valuations", count: "6 articles" },
    { topic: "EdTech Recovery", count: "10 articles" },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-green-50 via-emerald-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-green-100 dark:bg-green-900/30 rounded-full px-6 py-3 mb-8 border border-green-200 dark:border-green-800">
              <Zap className="w-5 h-5 text-green-600 dark:text-green-400" />
              <span className="font-semibold text-green-900 dark:text-green-300">
                Live Updates
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Latest Pre-IPO
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-green-600 via-emerald-600 to-blue-600 dark:from-green-400 dark:via-emerald-400 dark:to-blue-400">
                News & Updates
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
              Stay ahead with real-time news, IPO announcements, funding rounds, and market developments
            </p>

            <div className="flex items-center justify-center space-x-4">
              <button className="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                <Bell className="w-5 h-5 mr-2" />
                Subscribe to Alerts
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Breaking News */}
      {breakingNews.length > 0 && (
        <section className="py-12 -mt-10 relative z-10">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-3xl p-2 border-2 border-red-200 dark:border-red-800">
              <div className="flex items-center space-x-3 mb-4 px-6 pt-4">
                <Zap className="w-6 h-6 text-red-600 dark:text-red-400 animate-pulse" />
                <h2 className="text-2xl font-black text-red-600 dark:text-red-400">
                  BREAKING NEWS
                </h2>
              </div>

              <div className="space-y-3 pb-2">
                {breakingNews.map((news, index) => (
                  <Link
                    key={index}
                    href={news.link}
                    className="block bg-white dark:bg-slate-900 rounded-2xl p-6 border border-gray-200 dark:border-slate-800 hover:shadow-xl transition-all duration-300"
                  >
                    <div className="flex items-start space-x-4">
                      <div className="text-4xl">{news.image}</div>
                      <div className="flex-1">
                        <div className="flex items-center space-x-3 mb-2">
                          <span className="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold rounded-full">
                            {news.category}
                          </span>
                          <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <Clock className="w-4 h-4 mr-1" />
                            {news.time}
                          </div>
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                          {news.title}
                        </h3>
                        <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                          {news.summary}
                        </p>
                      </div>
                      <ArrowRight className="w-5 h-5 text-gray-400 group-hover:text-green-600 transition-colors" />
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          </div>
        </section>
      )}

      {/* Categories */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              Browse by Category
            </h2>
          </div>

          <div className="grid md:grid-cols-3 lg:grid-cols-6 gap-4">
            {categories.map((category, index) => (
              <Link
                key={index}
                href={`/insights/news?category=${category.name.toLowerCase().replace(/\s+/g, "-")}`}
                className="group bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-6 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300 hover:scale-105 text-center"
              >
                <div className={`w-12 h-12 bg-${category.color}-100 dark:bg-${category.color}-900/30 rounded-xl flex items-center justify-center mx-auto mb-3`}>
                  <category.icon className={`w-6 h-6 text-${category.color}-600 dark:text-${category.color}-400`} />
                </div>
                <h3 className="text-sm font-bold text-gray-900 dark:text-white mb-1">
                  {category.name}
                </h3>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  {category.count} articles
                </p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Latest News Grid */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between mb-12">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white">
              Latest News
            </h2>
            <div className="flex items-center space-x-3">
              <select className="px-4 py-2 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-gray-900 dark:text-white font-semibold">
                <option>All Categories</option>
                <option>IPO News</option>
                <option>Funding Rounds</option>
                <option>Market Updates</option>
              </select>
            </div>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {latestNews.map((news, index) => (
              <Link
                key={index}
                href="#"
                className="group bg-white dark:bg-slate-800 rounded-2xl overflow-hidden border border-gray-200 dark:border-slate-700 hover:shadow-2xl transition-all duration-300 hover:scale-105"
              >
                <div className="bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 p-12 flex items-center justify-center border-b border-gray-200 dark:border-slate-700">
                  <div className="text-7xl">{news.image}</div>
                </div>

                <div className="p-6">
                  <div className="flex items-center justify-between mb-3">
                    <span className="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold rounded-full">
                      {news.category}
                    </span>
                    <div className="flex items-center text-xs text-gray-600 dark:text-gray-400">
                      <Clock className="w-3 h-3 mr-1" />
                      {news.readTime} read
                    </div>
                  </div>

                  <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                    {news.title}
                  </h3>

                  <p className="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed line-clamp-3">
                    {news.summary}
                  </p>

                  <div className="flex flex-wrap gap-2 mb-4">
                    {news.tags.map((tag, i) => (
                      <span
                        key={i}
                        className="px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded"
                      >
                        #{tag}
                      </span>
                    ))}
                  </div>

                  <div className="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-700">
                    <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                      <Calendar className="w-4 h-4 mr-2" />
                      {news.date}
                    </div>
                    <ArrowRight className="w-5 h-5 text-gray-400 group-hover:text-green-600 group-hover:translate-x-1 transition-all" />
                  </div>
                </div>
              </Link>
            ))}
          </div>

          <div className="mt-12 text-center">
            <button className="px-8 py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-xl hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
              Load More Articles
            </button>
          </div>
        </div>
      </section>

      {/* Trending Topics Sidebar */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-3 gap-8">
            <div className="lg:col-span-2">
              <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-8">
                Editor's Picks
              </h2>

              <div className="space-y-6">
                {[
                  {
                    title: "The Rise of Quick Commerce in India",
                    excerpt:
                      "How 10-minute delivery startups are disrupting traditional e-commerce and preparing for massive IPOs.",
                    category: "Featured",
                    date: "Nov 14, 2025",
                  },
                  {
                    title: "Pre-IPO Investment Guide 2025-26",
                    excerpt:
                      "Everything you need to know about investing in pre-IPO companies: opportunities, risks, and strategies.",
                    category: "Guide",
                    date: "Nov 12, 2025",
                  },
                ].map((pick, index) => (
                  <div
                    key={index}
                    className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
                  >
                    <span className="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs font-bold rounded-full">
                      {pick.category}
                    </span>
                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white mt-4 mb-3">
                      {pick.title}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                      {pick.excerpt}
                    </p>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        {pick.date}
                      </span>
                      <Link
                        href="#"
                        className="text-green-600 dark:text-green-400 font-semibold hover:underline"
                      >
                        Read More →
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div>
              <div className="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-8 border border-green-200 dark:border-green-800 sticky top-24">
                <div className="flex items-center space-x-2 mb-6">
                  <Sparkles className="w-6 h-6 text-green-600 dark:text-green-400" />
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                    Trending Topics
                  </h3>
                </div>

                <div className="space-y-4">
                  {trendingTopics.map((topic, index) => (
                    <Link
                      key={index}
                      href="#"
                      className="block p-4 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 hover:shadow-lg transition-all"
                    >
                      <div className="flex items-start justify-between">
                        <div>
                          <div className="font-bold text-gray-900 dark:text-white mb-1">
                            {topic.topic}
                          </div>
                          <div className="text-sm text-gray-600 dark:text-gray-400">
                            {topic.count}
                          </div>
                        </div>
                        <TrendingUp className="w-5 h-5 text-green-600 dark:text-green-400" />
                      </div>
                    </Link>
                  ))}
                </div>

                <div className="mt-8 pt-8 border-t border-green-200 dark:border-green-800">
                  <h4 className="font-bold text-gray-900 dark:text-white mb-4">
                    Stay Updated
                  </h4>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Get daily news digest delivered to your inbox
                  </p>
                  <button className="w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                    Subscribe Now
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-green-600 via-emerald-600 to-blue-600 dark:from-green-700 dark:via-emerald-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Never Miss an Investment Opportunity
          </h2>
          <p className="text-xl text-green-100 mb-8">
            Get instant alerts on IPO filings, funding rounds, and market-moving news
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-green-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              <Bell className="mr-2 w-5 h-5" />
              Enable News Alerts
            </Link>
            <Link
              href="/products"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              Browse Live Deals
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}