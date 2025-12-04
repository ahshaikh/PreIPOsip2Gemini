"use client";

import Link from "next/link";
import { Users, Target, Lightbulb, HeartHandshake, Rocket, ArrowRight } from "lucide-react";

export default function TeamPage() {
  const principles = [
    {
      icon: Target,
      title: "Mission-Driven",
      description:
        "Every product decision starts with a simple question: Does this increase transparency, access, or trust for our investors?",
    },
    {
      icon: Lightbulb,
      title: "Innovation with Purpose",
      description:
        "We build technology not for complexity, but for clarity—simplifying access to private-market opportunities.",
    },
    {
      icon: HeartHandshake,
      title: "Integrity Always",
      description:
        "We operate with honesty, respect, and responsibility. Trust is earned, and we work every day to honor it.",
    },
    {
      icon: Rocket,
      title: "Relentless Growth Mindset",
      description:
        "We think long-term, move fast, and continually push toward a better, more equitable investment future.",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">

      {/* Hero Section */}
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          
          <div className="inline-flex items-center space-x-2 bg-purple-100 dark:bg-purple-900/30 rounded-full px-6 py-3 mb-8">
            <Users className="w-5 h-5 text-purple-600 dark:text-purple-400" />
            <span className="font-semibold text-purple-900 dark:text-purple-300">
              Our Team Philosophy
            </span>
          </div>

          <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
            We’re Building a Team Focused On
            <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
              Trust, Innovation & Impact
            </span>
          </h1>

          <p className="text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto leading-relaxed">
            PreIPOsip is powered by people who believe in democratizing wealth creation. 
            Our team brings together expertise from technology, finance, compliance, and customer experience—united by a mission to reshape private-market investing in India.
          </p>
        </div>
      </section>

      {/* Team Philosophy */}
      <section className="py-20 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">What We Believe</h2>
          <p className="text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto mb-16">
            We're not just building a platform; we’re building a movement. 
            A culture grounded in transparency, learning, responsibility, and meaningful progress.
          </p>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-10 mt-10">
            {principles.map((item, i) => (
              <div
                key={i}
                className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-3xl p-10 border border-gray-200 dark:border-slate-700 text-center hover:shadow-xl transition-all duration-300"
              >
                <div className="w-16 h-16 mx-auto mb-6 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center">
                  <item.icon className="w-8 h-8 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">{item.title}</h3>
                <p className="text-gray-600 dark:text-gray-400 leading-relaxed">{item.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Culture Section */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900 border-y border-gray-200 dark:border-slate-800">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

          <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">How We Work</h2>
          <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-12">
            Our culture is built around curiosity, respect, discipline, and ownership. 
            We believe great companies emerge from great teams—teams that care deeply about the mission and each other.
          </p>

          <div className="space-y-6 text-left max-w-3xl mx-auto">
            {[
              "We communicate openly and work collaboratively.",
              "We take responsibility for every decision made.",
              "We move fast but never compromise on accuracy.",
              "We learn constantly — from data, markets, and each other.",
              "We build for the long term, not for shortcuts.",
            ].map((item, i) => (
              <div key={i} className="flex space-x-3 text-gray-700 dark:text-gray-300">
                <span className="text-purple-600 dark:text-purple-400 text-xl font-bold">•</span>
                <p>{item}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Join Us */}
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800 rounded-3xl p-12 border border-gray-200 dark:border-slate-700 text-center">

            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-6">
              Want to Join Our Mission?
            </h2>

            <p className="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
              We’re building a team of thoughtful, ambitious people shaping the future of 
              private-market investing in India. Whether you're an engineer, designer, analyst, 
              or storyteller—there’s a place here for you.
            </p>

            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                href="/careers"
                className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl hover:shadow-xl transition-all duration-300"
              >
                Explore Roles
                <ArrowRight className="ml-2 w-5 h-5" />
              </Link>

              <Link
                href="/contact"
                className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-gray-900 dark:text-white bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors"
              >
                Get in Touch
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-700 dark:to-blue-700">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold text-white mb-6">Ready to Start Investing?</h2>
          <p className="text-xl text-purple-100 mb-8 max-w-xl mx-auto">
            Begin your journey toward early-stage investment access—built on transparency, 
            security, and long-term vision.
          </p>

          <Link
            href="/signup"
            className="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-purple-600 bg-white rounded-xl hover:bg-gray-100 transition-colors shadow-xl"
          >
            Open Free Account
            <ArrowRight className="ml-2 w-5 h-5" />
          </Link>
        </div>
      </section>

    </div>
  );
}
