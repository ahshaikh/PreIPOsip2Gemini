"use client";

import Link from "next/link";
import { Linkedin, Mail, ArrowRight, Users } from "lucide-react";

export default function TeamPage() {
  const team = [
    {
      name: "Abdul Hakim Shaikh",
      role: "Founder & CEO",
      bio: "15+ years in investment banking and venture capital. Ex-Goldman Sachs.",
      image: "👨‍💼",
      linkedin: "#",
    },
    {
      name: "Priya Sharma",
      role: "Co-Founder & CTO",
      bio: "Former Tech Lead at Google. MIT Computer Science graduate.",
      image: "👩‍💻",
      linkedin: "#",
    },
    {
      name: "Amit Patel",
      role: "Chief Investment Officer",
      bio: "20+ years in private equity. Ex-Sequoia Capital partner.",
      image: "👨‍💼",
      linkedin: "#",
    },
    {
      name: "Neha Gupta",
      role: "Head of Compliance",
      bio: "Ex-SEBI regulatory affairs expert. LLM from Harvard Law.",
      image: "👩‍⚖️",
      linkedin: "#",
    },
    {
      name: "Vikram Singh",
      role: "Head of Research",
      bio: "15 years analyzing pre-IPO companies. CFA Charterholder.",
      image: "👨‍🔬",
      linkedin: "#",
    },
    {
      name: "Ananya Reddy",
      role: "Head of Customer Success",
      bio: "Building world-class investor experiences. Ex-Zerodha.",
      image: "👩‍💼",
      linkedin: "#",
    },
  ];

  const advisors = [
    {
      name: "Dr. Suresh Iyer",
      role: "Investment Advisor",
      credentials: "Former MD, JP Morgan India",
      image: "👨‍🎓",
    },
    {
      name: "Kavita Menon",
      role: "Legal Advisor",
      credentials: "Senior Partner, Cyril Amarchand Mangaldas",
      image: "👩‍💼",
    },
    {
      name: "Arjun Malhotra",
      role: "Technology Advisor",
      credentials: "Co-founder, Flipkart",
      image: "👨‍💻",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      {/* Hero Section */}
      <section className="relative pt-32 pb-20 bg-gradient-to-br from-purple-50 via-blue-50 to-white dark:from-slate-900 dark:via-slate-800 dark:to-slate-950">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <div className="inline-flex items-center space-x-2 bg-purple-100 dark:bg-purple-900/30 rounded-full px-6 py-3 mb-8">
              <Users className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span className="font-semibold text-purple-900 dark:text-purple-300">Our Team</span>
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              Meet the People Behind
              <span className="block text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400">
                PreIPOsip
              </span>
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
              A team of seasoned professionals committed to democratizing pre-IPO investments in India
            </p>
          </div>
        </div>
      </section>

      {/* Leadership Team */}
      <section className="py-20 border-t border-gray-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Leadership Team
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Industry veterans with decades of combined experience
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {team.map((member, index) => (
              <div
                key={index}
                className="group bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-3xl p-8 border border-gray-200 dark:border-slate-700 hover:shadow-2xl transition-all duration-300"
              >
                {/* Avatar */}
                <div className="text-center mb-6">
                  <div className="w-32 h-32 bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900/30 dark:to-blue-900/30 rounded-full flex items-center justify-center text-6xl mx-auto mb-4 group-hover:scale-110 transition-transform">
                    {member.image}
                  </div>
                  <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    {member.name}
                  </h3>
                  <div className="text-purple-600 dark:text-purple-400 font-semibold mb-4">
                    {member.role}
                  </div>
                  <p className="text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
                    {member.bio}
                  </p>
                </div>

                {/* Social Links */}
                <div className="flex justify-center space-x-4 pt-6 border-t border-gray-200 dark:border-slate-700">
                  <a
                    href={member.linkedin}
                    className="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                    aria-label="LinkedIn"
                  >
                    <Linkedin className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                  </a>
                  <button
                    className="w-10 h-10 bg-gray-100 dark:bg-slate-800 rounded-full flex items-center justify-center hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors"
                    aria-label="Email"
                  >
                    <Mail className="w-5 h-5 text-gray-600 dark:text-gray-400" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Advisory Board */}
      <section className="py-20 bg-gray-50 dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
              Advisory Board
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Guided by some of India's most respected professionals
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {advisors.map((advisor, index) => (
              <div
                key={index}
                className="bg-white dark:bg-slate-800 rounded-2xl p-8 text-center border border-gray-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300"
              >
                <div className="w-24 h-24 bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900/30 dark:to-blue-900/30 rounded-full flex items-center justify-center text-5xl mx-auto mb-4">
                  {advisor.image}
                </div>
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                  {advisor.name}
                </h3>
                <div className="text-purple-600 dark:text-purple-400 font-semibold mb-3">
                  {advisor.role}
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {advisor.credentials}
                </p>
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
              Want to Join Our Team?
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
              We're always looking for talented individuals who share our passion for democratizing
              wealth creation. Check out our open positions.
            </p>

            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                href="/careers"
                className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl hover:shadow-xl transition-all duration-300"
              >
                View Open Positions
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
          <h2 className="text-4xl font-bold text-white mb-6">
            Ready to Start Investing?
          </h2>
          <p className="text-xl text-purple-100 mb-8">
            Join 50,000+ investors building wealth with our expert guidance
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
