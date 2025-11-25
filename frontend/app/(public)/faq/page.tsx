"use client";
import { useState } from "react";
import { ChevronDown, Search } from "lucide-react";

export default function FAQPage() {
  const [openIndex, setOpenIndex] = useState<number | null>(null);
  const faqs = [
    {
      question: "What is PreIPOsip?",
      answer: "PreIPOsip is India's leading platform for pre-IPO investments, allowing retail investors to invest in high-growth companies before they go public.",
    },
    {
      question: "How do I start investing?",
      answer: "Simply sign up, complete your KYC verification, browse available deals, and start investing with as low as ₹40,000.",
    },
    {
      question: "Is PreIPOsip SEBI registered?",
      answer: "Yes, PreIPOsip is fully SEBI registered and compliant with all Indian regulations.",
    },
  ];

  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      <section className="pt-32 pb-20 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-5xl font-black text-gray-900 dark:text-white mb-4">
            Frequently Asked <span className="text-purple-600 dark:text-purple-400">Questions</span>
          </h1>
          <p className="text-xl text-gray-600 dark:text-gray-400">Find answers to common questions</p>
        </div>
      </section>
      <section className="py-20">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="space-y-4">
            {faqs.map((faq, index) => (
              <div key={index} className="bg-gray-50 dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800">
                <button
                  onClick={() => setOpenIndex(openIndex === index ? null : index)}
                  className="w-full px-6 py-4 text-left flex items-center justify-between"
                >
                  <span className="font-bold text-gray-900 dark:text-white">{faq.question}</span>
                  <ChevronDown className={`w-5 h-5 transition-transform ${openIndex === index ? "rotate-180" : ""}`} />
                </button>
                {openIndex === index && (
                  <div className="px-6 pb-4 text-gray-600 dark:text-gray-400">{faq.answer}</div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}