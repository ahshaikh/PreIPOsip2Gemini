"use client";
import Link from "next/link";
import {
  BookOpen,
  ArrowRight,
  PlayCircle,
  Clock,
  Award,
  Users,
  CheckCircle2,
  Star,
  Sparkles,
  TrendingUp,
  Shield,
  Target,
  FileText,
  Video,
  Headphones,
  Download,
  BarChart3,
} from "lucide-react";

export default function TutorialsPage() {
  const tutorialCategories = [
    {
      title: "Getting Started",
      level: "Beginner",
      count: 8,
      duration: "2-3 hours",
      icon: Sparkles,
      color: "blue",
      description: "Perfect for first-time pre-IPO investors",
    },
    {
      title: "Investment Strategies",
      level: "Intermediate",
      count: 12,
      duration: "4-5 hours",
      icon: Target,
      color: "purple",
      description: "Build a winning investment portfolio",
    },
    {
      title: "Risk Management",
      level: "Intermediate",
      count: 6,
      duration: "2-3 hours",
      icon: Shield,
      color: "green",
      description: "Protect your investments effectively",
    },
    {
      title: "Advanced Analysis",
      level: "Advanced",
      count: 10,
      duration: "6-8 hours",
      icon: BarChart3,
      color: "orange",
      description: "Master financial analysis and valuation",
    },
  ];

  const featuredTutorials = [
    {
      title: "Complete Guide to Pre-IPO Investing",
      description:
        "Learn everything from basics to advanced strategies for pre-IPO investments. This comprehensive course covers KYC, deal analysis, risk assessment, and exit strategies.",
      instructor: "Rajesh Kumar",
      instructorTitle: "Senior Investment Analyst",
      duration: "4h 32m",
      lessons: 24,
      students: "12.5K",
      rating: 4.8,
      level: "Beginner",
      thumbnail: "📈",
      tags: ["Fundamentals", "Strategy", "Risk"],
      isFeatured: true,
      isPremium: false,
    },
    {
      title: "Financial Analysis & Valuation",
      description:
        "Deep dive into company financials, understand key metrics, perform DCF analysis, and assess company valuations like a professional analyst.",
      instructor: "Priya Sharma",
      instructorTitle: "Ex-Goldman Sachs Analyst",
      duration: "3h 45m",
      lessons: 18,
      students: "8.2K",
      rating: 4.9,
      level: "Intermediate",
      thumbnail: "💹",
      tags: ["Analysis", "Valuation", "Metrics"],
      isFeatured: true,
      isPremium: true,
    },
    {
      title: "Sector-Specific Investment Guide",
      description:
        "Understand the unique characteristics of different sectors - FinTech, HealthTech, E-commerce, SaaS. Learn what makes each sector tick and how to evaluate companies.",
      instructor: "Amit Patel",
      instructorTitle: "Venture Capital Partner",
      duration: "5h 18m",
      lessons: 32,
      students: "6.8K",
      rating: 4.7,
      level: "Intermediate",
      thumbnail: "🏢",
      tags: ["Sectors", "Industry", "Analysis"],
      isFeatured: true,
      isPremium: false,
    },
  ];

  const beginnerTutorials = [
    {
      title: "What is Pre-IPO Investing?",
      duration: "12 min",
      type: "Video",
      views: "45K",
      icon: Video,
    },
    {
      title: "How to Complete KYC Verification",
      duration: "8 min",
      type: "Guide",
      views: "38K",
      icon: FileText,
    },
    {
      title: "Understanding Investment Risk",
      duration: "15 min",
      type: "Video",
      views: "32K",
      icon: Video,
    },
    {
      title: "Reading Company Financials 101",
      duration: "25 min",
      type: "Video",
      views: "52K",
      icon: Video,
    },
    {
      title: "How to Choose Your First Deal",
      duration: "18 min",
      type: "Guide",
      views: "41K",
      icon: FileText,
    },
    {
      title: "Pre-IPO vs IPO vs Public Market",
      duration: "14 min",
      type: "Podcast",
      views: "28K",
      icon: Headphones,
    },
  ];

  const learningPaths = [
    {
      title: "Beginner to Pro in 30 Days",
      courses: 8,
      duration: "12 hours",
      students: "15K+",
      description: "Complete learning path from absolute beginner to confident investor",
      modules: [
        "Week 1: Fundamentals & Platform Setup",
        "Week 2: Company Analysis Basics",
        "Week 3: Investment Strategies",
        "Week 4: Risk Management & Portfolio Building",
      ],
    },
    {
      title: "Sector Expert Track",
      courses: 12,
      duration: "18 hours",
      students: "8.5K+",
      description: "Master sector-specific analysis for FinTech, HealthTech, SaaS, and more",
      modules: [
        "FinTech Sector Deep Dive",
        "HealthTech & BioTech Analysis",
        "E-commerce & D2C Evaluation",
        "SaaS & Enterprise Software",
      ],
    },
    {
      title: "Advanced Analyst Certification",
      courses: 15,
      duration: "24 hours",
      students: "4.2K+",
      description: "Professional-grade financial modeling and valuation techniques",
      modules: [
        "Advanced Financial Modeling",
        "DCF & Comparable Analysis",
        "Market Research & Due Diligence",
        "Exit Strategy Planning",
      ],
    },
  ];

  const quickTips = [
    {
      tip: "Diversify Across Sectors",
      description: "Don't put all eggs in one basket. Spread investments across 4-5 different sectors.",
      icon: "🎯",
    },
    {
      tip: "Start Small, Scale Gradually",
      description: "Begin with minimum investment amounts. Increase as you gain experience.",
      icon: "📊",
    },
    {
      tip: "Read Every Document",
      description: "Always read company reports, financials, and risk disclosures before investing.",
      icon: "📄",
    },
    {
      tip: "Track Your Portfolio",
      description: "Regularly monitor company updates and portfolio performance.",
      icon: "📈",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-amber-50 via-orange-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-amber-100 dark:bg-amber-900/30 rounded-full px-6 py-3 mb-8 border border-amber-200 dark:border-amber-800">
              <BookOpen className="w-5 h-5 text-amber-600 dark:text-amber-400" />
              <span className="font-semibold text-amber-900 dark:text-amber-300">
                60+ Free Tutorials
              </span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Learn to Invest in
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 dark:from-amber-400 dark:via-orange-400 dark:to-red-400">
                Pre-IPO Companies
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
              From beginner basics to advanced strategies - comprehensive video tutorials, guides, and courses to master pre-IPO investing
            </p>

            <div className="flex items-center justify-center space-x-8 text-gray-600 dark:text-gray-400">
              <div className="flex items-center space-x-2">
                <Video className="w-5 h-5" />
                <span className="font-semibold">60+ Videos</span>
              </div>
              <div className="flex items-center space-x-2">
                <Users className="w-5 h-5" />
                <span className="font-semibold">50K+ Students</span>
              </div>
              <div className="flex items-center space-x-2">
                <Award className="w-5 h-5" />
                <span className="font-semibold">Certificates</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Tutorial Categories */}
      <section className="py-20 -mt-10 relative z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {tutorialCategories.map((category, index) => (
              <Link
                key={index}
                href={`/insights/tutorials?level=${category.level.toLowerCase()}`}
                className="group bg-white dark:bg-slate-900 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105"
              >
                <div className={`w-14 h-14 bg-${category.color}-100 dark:bg-${category.color}-900/30 rounded-xl flex items-center justify-center mb-4`}>
                  <category.icon className={`w-7 h-7 text-${category.color}-600 dark:text-${category.color}-400`} />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  {category.title}
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  {category.description}
                </p>
                <div className="space-y-2 text-sm">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600 dark:text-gray-400">Level</span>
                    <span className={`font-bold text-${category.color}-600 dark:text-${category.color}-400`}>
                      {category.level}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600 dark:text-gray-400">Tutorials</span>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {category.count}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600 dark:text-gray-400">Duration</span>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {category.duration}
                    </span>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Featured Tutorials */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Featured Courses
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Comprehensive courses from industry experts
            </p>
          </div>

          <div className="space-y-8">
            {featuredTutorials.map((tutorial, index) => (
              <div
                key={index}
                className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-3xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-2xl transition-all duration-300"
              >
                <div className="grid lg:grid-cols-4 gap-8">
                  <div className="lg:col-span-1">
                    <div className="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-12 flex items-center justify-center border border-amber-200 dark:border-amber-800">
                      <div className="text-8xl">{tutorial.thumbnail}</div>
                    </div>
                  </div>

                  <div className="lg:col-span-3">
                    <div className="flex items-start justify-between mb-4">
                      <div className="flex items-center space-x-3">
                        <span className="px-4 py-2 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-sm font-bold rounded-full">
                          {tutorial.level}
                        </span>
                        {tutorial.isPremium && (
                          <span className="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-sm font-bold rounded-full flex items-center">
                            <Star className="w-3 h-3 mr-1" />
                            Premium
                          </span>
                        )}
                      </div>
                      <div className="flex items-center space-x-1">
                        <Star className="w-5 h-5 fill-yellow-400 text-yellow-400" />
                        <span className="font-bold text-gray-900 dark:text-white">
                          {tutorial.rating}
                        </span>
                      </div>
                    </div>

                    <h3 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                      {tutorial.title}
                    </h3>

                    <p className="text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                      {tutorial.description}
                    </p>

                    <div className="flex items-center space-x-4 mb-6">
                      <div className="flex items-center space-x-2">
                        <div className="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                          <span className="text-lg">👨‍🏫</span>
                        </div>
                        <div>
                          <div className="font-semibold text-gray-900 dark:text-white">
                            {tutorial.instructor}
                          </div>
                          <div className="text-xs text-gray-600 dark:text-gray-400">
                            {tutorial.instructorTitle}
                          </div>
                        </div>
                      </div>
                    </div>

                    <div className="flex flex-wrap gap-2 mb-6">
                      {tutorial.tags.map((tag, i) => (
                        <span
                          key={i}
                          className="px-3 py-1 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg"
                        >
                          #{tag}
                        </span>
                      ))}
                    </div>

                    <div className="flex items-center justify-between">
                      <div className="flex items-center space-x-6 text-sm text-gray-600 dark:text-gray-400">
                        <div className="flex items-center space-x-2">
                          <PlayCircle className="w-4 h-4" />
                          <span>{tutorial.lessons} lessons</span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <Clock className="w-4 h-4" />
                          <span>{tutorial.duration}</span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <Users className="w-4 h-4" />
                          <span>{tutorial.students} students</span>
                        </div>
                      </div>

                      <Link
                        href={tutorial.isPremium ? "/signup" : "#"}
                        className="px-8 py-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold rounded-xl hover:shadow-xl transition-all flex items-center"
                      >
                        {tutorial.isPremium ? "Unlock Premium" : "Start Learning"}
                        <ArrowRight className="w-5 h-5 ml-2" />
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Quick Start Tutorials */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Quick Start Tutorials
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Bite-sized lessons to get started immediately
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {beginnerTutorials.map((tutorial, index) => (
              <Link
                key={index}
                href="#"
                className="group bg-white dark:bg-slate-800 rounded-2xl p-6 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300 hover:scale-105"
              >
                <div className="flex items-start space-x-4">
                  <div className="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                    <tutorial.icon className="w-6 h-6 text-amber-600 dark:text-amber-400" />
                  </div>
                  <div className="flex-1">
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                      {tutorial.title}
                    </h3>
                    <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                      <div className="flex items-center space-x-4">
                        <span>{tutorial.duration}</span>
                        <span className="px-2 py-1 bg-gray-100 dark:bg-slate-700 rounded text-xs">
                          {tutorial.type}
                        </span>
                      </div>
                      <span>{tutorial.views} views</span>
                    </div>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Learning Paths */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Structured Learning Paths
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Follow curated paths to master specific skills
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {learningPaths.map((path, index) => (
              <div
                key={index}
                className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className="text-4xl mb-4">
                  {index === 0 ? "🎯" : index === 1 ? "🏢" : "🎓"}
                </div>
                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                  {path.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400 mb-6">
                  {path.description}
                </p>

                <div className="space-y-2 mb-6 text-sm">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600 dark:text-gray-400">Courses</span>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {path.courses}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600 dark:text-gray-400">Duration</span>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {path.duration}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-gray-600 dark:text-gray-400">Students</span>
                    <span className="font-bold text-gray-900 dark:text-white">
                      {path.students}
                    </span>
                  </div>
                </div>

                <div className="space-y-2 mb-6">
                  {path.modules.map((module, i) => (
                    <div
                      key={i}
                      className="flex items-start space-x-2 text-sm text-gray-600 dark:text-gray-400"
                    >
                      <CheckCircle2 className="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                      <span>{module}</span>
                    </div>
                  ))}
                </div>

                <button className="w-full px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-xl hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                  Start Path
                </button>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Quick Tips */}
      <section className="py-20 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Pro Tips for Success
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Essential advice from successful investors
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {quickTips.map((tip, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 border border-gray-200 dark:border-slate-700"
              >
                <div className="flex items-start space-x-4">
                  <div className="text-4xl">{tip.icon}</div>
                  <div>
                    <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                      {tip.tip}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                      {tip.description}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 dark:from-amber-700 dark:via-orange-700 dark:to-red-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Start Your Learning Journey Today
          </h2>
          <p className="text-xl text-amber-100 mb-8">
            Get access to all tutorials, courses, and earn certification upon completion
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/signup"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-amber-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
            >
              Start Learning Free
              <ArrowRight className="ml-2 w-5 h-5" />
            </Link>
            <Link
              href="/products"
              className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white border-2 border-white rounded-xl hover:bg-white/10 transition-colors"
            >
              Browse Deals
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}