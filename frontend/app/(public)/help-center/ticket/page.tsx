"use client";
import Link from "next/link";
import { Lock, UserPlus, LogIn, ArrowRight, MessageSquare } from "lucide-react";

export default function TicketPage() {
  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      <section className="pt-32 pb-20 bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="w-24 h-24 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-8">
            <Lock className="w-12 h-12 text-purple-600 dark:text-purple-400" />
          </div>
          <h1 className="text-5xl font-black text-gray-900 dark:text-white mb-4">
            Raise a Support Ticket
          </h1>
          <p className="text-xl text-gray-600 dark:text-gray-400">Members-only support service</p>
        </div>
      </section>

      <section className="py-20">
        <div className="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-gradient-to-br from-gray-50 to-white dark:from-slate-900 dark:to-slate-800 rounded-3xl p-12 border border-gray-200 dark:border-slate-800 text-center">
            <MessageSquare className="w-16 h-16 text-purple-600 dark:text-purple-400 mx-auto mb-6" />
            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              This Page is for Members Only
            </h2>
            <p className="text-lg text-gray-600 dark:text-gray-400 mb-8">
              Submit support tickets and get priority assistance from our dedicated team. Our members receive 24-hour guaranteed response times.
            </p>

            <div className="space-y-4 mb-8 text-left max-w-md mx-auto">
              {[
                "Priority ticket resolution",
                "Direct access to support team",
                "Track ticket status in real-time",
                "Guaranteed 24-hour response",
              ].map((benefit, i) => (
                <div key={i} className="flex items-center space-x-3">
                  <div className="w-2 h-2 bg-purple-600 rounded-full"></div>
                  <span className="text-gray-700 dark:text-gray-300">{benefit}</span>
                </div>
              ))}
            </div>

            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                href="/signup"
                className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl hover:shadow-xl transition-all"
              >
                <UserPlus className="w-5 h-5 mr-2" />
                Register Now
                <ArrowRight className="w-5 h-5 ml-2" />
              </Link>
              <Link
                href="/login"
                className="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-gray-900 dark:text-white bg-white dark:bg-slate-800 border-2 border-gray-200 dark:border-slate-700 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 transition-all"
              >
                <LogIn className="w-5 h-5 mr-2" />
                Already a Member? Login
              </Link>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
