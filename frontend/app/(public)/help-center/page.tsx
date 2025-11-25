"use client";
import Link from "next/link";
import { HelpCircle, Book, MessageSquare, Mail } from "lucide-react";

export default function SupportPage() {
  const resources = [
    { icon: Book, title: "FAQs", description: "Find answers to common questions", href: "/faq" },
    { icon: MessageSquare, title: "Raise a Ticket", description: "Get personalized support", href: "/support/ticket" },
    { icon: Mail, title: "Contact Us", description: "Reach out to our team", href: "/contact" },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      <section className="pt-32 pb-20 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-5xl font-black text-gray-900 dark:text-white mb-4">
            Help <span className="text-blue-600 dark:text-blue-400">Center</span>
          </h1>
          <p className="text-xl text-gray-600 dark:text-gray-400">How can we help you today?</p>
        </div>
      </section>
      <section className="py-20">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-3 gap-8">
            {resources.map((resource, i) => (
              <Link key={i} href={resource.href} className="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-gray-200 dark:border-slate-800 hover:shadow-xl transition-all">
                <resource.icon className="w-12 h-12 text-blue-600 dark:text-blue-400 mb-4" />
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">{resource.title}</h3>
                <p className="text-gray-600 dark:text-gray-400">{resource.description}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}