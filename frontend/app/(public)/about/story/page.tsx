"use client";

import Link from "next/link";
import { Rocket, Users, TrendingUp, Award, ArrowRight, Sparkles } from "lucide-react";

export default function OurStoryPage() {
  const timeline = [
    {
      year: "2019",
      title: "The Idea",
      description:
        "Our founders noticed a pattern: pre-IPO opportunities were generating extraordinary wealth, but only for a select few. Retail investors—despite being the backbone of India’s public markets—were consistently excluded. This sparked a bold question: Can we democratize access to the private markets?",
    },
    {
      year: "2020",
      title: "Building the Foundation",
      description:
        "After months of research and understanding regulatory frameworks, we launched the early version of PreIPOsip. The goal was simple: offer transparent, structured access to private-market opportunities for everyday investors.",
    },
    {
      year: "2021",
      title: "Strengthening the Ecosystem",
      description:
        "We expanded our due-diligence capabilities, formed relationships with private companies across sectors, and enhanced the investor experience with new tools and transparency features.",
    },
    {
      year: "2022",
      title: "Product Evolution",
      description:
        "Introduced richer insights, improved dashboards, and a stronger compliance-first infrastructure. Focus shifted to scalability, investor education, and secure transaction workflows.",
    },
    {
      year: "2023",
      title: "Scaling the Vision",
      description:
        "We expanded our ecosystem, improved platform transparency, and refined operational processes to align with industry best practices and investor protection standards.",
    },
    {
      year: "2024",
      title: "The Road Ahead",
      description:
        "Our mission continues: building India’s most transparent, technology-driven platform for accessing private-market investments—designed for millions of future investors.",
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
              <span className="font-semibold text-purple-900 dark:text-purple-300">
                Our Journey
              </span>
            </div>

            <h1 className="text-5xl lg:text-5xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              From a Bold Question to
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
                A New Path for Indian Investors
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
              The story of how we're reimagining access to pre-IPO opportunities<br></br>—one transparent step at a time.
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
                  In 2019, our founders were struck by an uncomfortable truth: early-stage wealth creation was accessible only to institutions and ultra-wealthy investors. Retail investors—who actively participate in IPOs—were consistently left out of the most valuable phase.
                </p>

                <p>
                  <strong className="text-gray-900 dark:text-white">
                    “Why should only the ultra-rich have access to high-growth private companies?”
                  </strong>
                </p>

                <p>
                  This sparked the idea for PreIPOsip: a platform built to provide structured, transparent, and technology-driven access to private-market opportunities for everyday investors.
                </p>

                <p>
                  After deep research, regulatory understanding, and forming partnerships within the ecosystem, the first version of PreIPOsip launched in 2020.
                </p>
              </div>
            </div>

            {/* Values */}
            <div className="grid grid-cols-2 gap-6">
              {[
                { icon: Rocket, label: "Innovation First", color: "purple" },
                { icon: Users, label: "Investor Focused", color: "blue" },
                { icon: TrendingUp, label: "Growth Mindset", color: "green" },
                { icon: Award, label: "Excellence Always", color: "amber" },
              ].map((value, i) => (
                <div
                  key={i}
                  className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700"
                >
                  <div
                    className={`w-16 h-16 bg-${value.color}-100 dark:bg-${value.color}-900/30 rounded-xl flex items-center justify-center mb-4 mx-auto`}
                  >
                    <value.icon
                      className={`w-8 h-8 text-${value.color}-600 dark:text-${value.color}-400`}
                    />
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
              A timeline of evolution, learning, and continuous innovation.
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
                    <div className="inline-block bg-gradient-to-r from-purple-600 to-blue-600 text-white text-xl font-black px-6 py-3 rounded-xl mb-4 shadow-lg">
                      {item.year}
                    </div>
                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                      {item.title}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 text-lg leading-relaxed">
                      {item.description}
                    </p>
                  </div>

                  {/* Empty Spacer */}
                  <div
                    className={`hidden lg:block ${
                      index % 2 === 1 ? "lg:col-start-2" : "lg:col-start-1"
                    }`}
                  />

                  {/* Dot */}
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
              Highlights from our evolving journey.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[
              { value: "Growing", label: "Investor Community" },
              { value: "Expanding", label: "Private-Market Access" },
              { value: "Evolving", label: "Platform Capabilities" },
              { value: "Strengthening", label: "Partnership Network" },
            ].map((stat, i) => (
              <div
                key={i}
                className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700"
              >
                <div className="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400 mb-3">
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
          <h2 className="text-4xl font-bold text-white mb-6">Be Part of Our Story</h2>
          <p className="text-xl text-purple-100 mb-8">
            Join a growing community helping to shape the future of private-market investing in India.
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
