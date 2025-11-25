"use client";
import Link from "next/link";
import { FileText, ArrowRight } from "lucide-react";

export default function ReportsPage() {
  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      <section className="pt-32 pb-20 bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-5xl font-black text-gray-900 dark:text-white mb-4">
              Research <span className="text-purple-600 dark:text-purple-400">Reports</span>
            </h1>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              In-depth industry reports and company analysis
            </p>
          </div>
        </div>
      </section>
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <p className="text-gray-600 dark:text-gray-400 text-center">Content coming soon...</p>
        </div>
      </section>
    </div>
  );
}