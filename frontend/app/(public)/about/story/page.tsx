"use client";

import Link from "next/link";
import { Rocket, Users, TrendingUp, Award, ArrowRight, Sparkles } from "lucide-react";

export default function OurStoryPage() {
  const timeline = [
    {
      year: "2019",
      title: "The Idea",
      description:
        "Our founders, frustrated by the lack of access to pre-IPO investments for retail investors, envisioned a platform that would democratize wealth creation.",
    },
    {
      year: "2020",
      title: "PreIPOsip is Born",
      description:
        "After months of research and regulatory approvals, PreIPOsip was officially launched. We onboarded our first 100 investors and facilitated ₹5 Crores in investments.",
    },
    {
      year: "2021",
      title: "Rapid Growth",
      description:
        "Crossed 10,000 investors and ₹500 Crores AUM. Launched our mobile app and expanded to 50+ pre-IPO companies across multiple sectors.",
    },
    {
      year: "2022",
      title: "Unicorn Portfolio",
      description:
        "Several of our portfolio companies achieved unicorn status. Our investors saw average returns of 287%. Crossed 25,000 investor milestone.",
    },
    {
      year: "2023",
      title: "Industry Leader",
      description:
        "Became India's most trusted pre-IPO platform with 50,000+ investors and ₹2,500+ Crores AUM. Won 'Best FinTech Platform' award.",
    },
    {
      year: "2024",
      title: "The Future",
      description:
        "Expanding our offerings with secondary market trading, IPO advisory, and institutional-grade research. Our mission continues.",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-purple-100 dark:bg-purple-900/30 rounded-full px-6 py-3 mb-8">
              <Sparkles className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span className="font-semibold text-purple-900 dark:text-purple-300">Our Journey</span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              From a Bold Idea to
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
                India's Leading Platform
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
              The story of how we're revolutionizing pre-IPO investments in India
            </p>
          </div>
        </div>
      </section>

      {/* The Beginning */}
      <section className="py-20 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                It Started With A Simple Question
              </h2>
              <div className="space-y-6 text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                <p>
                  In 2019, our founders were frustrated. They had watched their wealthy friends and
                  institutional investors make massive returns by investing in companies before their IPOs.
                  Meanwhile, retail investors like them were locked out of these opportunities.
                </p>
                <p>
                  <strong className="text-gray-900 dark:text-white">
                    "Why should only the ultra-rich have access to unicorn investments?"
                  </strong>
                </p>
                <p>
                  This simple question sparked an idea: What if we could create a platform that made
                  pre-IPO investments accessible, transparent, and simple for every Indian investor?
                </p>
                <p>
                  After months of research, countless regulatory hurdles, and building relationships with
                  top pre-IPO companies, PreIPOsip was born in 2020.
                </p>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-6">
              {[
                { icon: Rocket, label: "Innovation First", color: "purple" },
                { icon: Users, label: "Customer Obsessed", color: "blue" },
                { icon: TrendingUp, label: "Results Driven", color: "green" },
                { icon: Award, label: "Excellence Always", color: "amber" },
              ].map((value, i) => (
                <div
                  key={i}
                  className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700"
                >
                  <div className={`w-16 h-16 bg-${value.color}-100 dark:bg-${value.color}-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto`}>
                    <value.icon className={`w-8 h-8 text-${value.color}-600 dark:text-${value.color}-400`} />
                  </div>
                  <div className="text-sm font-bold text-gray-900 dark:text-white">{value.label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Timeline */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Our Journey Through The Years
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              From humble beginnings to industry leadership
            </p>
          </div>

          <div className="relative">
            {/* Timeline line */}
            <div className="hidden lg:block absolute left-1/2 top-0 bottom-0 w-1 bg-gradient-to-b from-purple-600 via-blue-600 to-green-600" />

            <div className="space-y-12">
              {timeline.map((item, index) => (
                <div
                  key={index}
                  className={`relative grid lg:grid-cols-2 gap-8 items-center ${
                    index % 2 === 1 ? "lg:flex-row-reverse" : ""
                  }`}
                >
                  {/* Content */}
                  <div
                    className={`${
                      index % 2 === 1 ? "lg:text-right lg:col-start-1" : "lg:col-start-2"
                    }`}
                  >
                    <div
                      className={`inline-block bg-gradient-to-r from-purple-600 to-blue-600 text-white text-xl font-black px-6 py-3 rounded-xl mb-4 shadow-lg`}
                    >
                      {item.year}
                    </div>
                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                      {item.title}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 text-lg leading-relaxed">
                      {item.description}
                    </p>
                  </div>

                  {/* Empty space for alternating layout */}
                  <div
                    className={`hidden lg:block ${
                      index % 2 === 1 ? "lg:col-start-2" : "lg:col-start-1"
                    }`}
                  />

                  {/* Timeline dot */}
                  <div className="hidden lg:block absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-slate-950 border-4 border-purple-600 rounded-full z-10" />
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Milestones */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Key Milestones
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Achievements we're proud of
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              { value: "50,000+", label: "Happy Investors" },
              { value: "₹2,500Cr+", label: "Total AUM" },
              { value: "287%", label: "Avg. Returns" },
              { value: "100+", label: "Portfolio Companies" },
            ].map((stat, i) => (
              <div
                key={i}
                className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700"
              >
                <div className="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400 mb-3">
                  {stat.value}
                </div>
                <div className="text-gray-600 dark:text-gray-400 font-medium">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            Be Part of Our Story
          </h2>
          <p className="text-xl text-purple-100 mb-8">
            Join thousands of investors who are building wealth with PreIPOsip
          </p>

          <Link
            href="/signup"
            className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
          >
            Start Your Journey Today
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>
        </div>
      </section>
    </div>
  );
}
