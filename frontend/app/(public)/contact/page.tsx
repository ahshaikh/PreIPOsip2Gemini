"use client";
import { Mail, Phone, MapPin, Send } from "lucide-react";

export default function ContactPage() {
  return (
    <div className="min-h-screen bg-white dark:bg-slate-950">
      <section className="pt-32 pb-20 bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-900 dark:to-slate-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-5xl font-black text-gray-900 dark:text-white mb-4">
            Get in <span className="text-purple-600 dark:text-purple-400">Touch</span>
          </h1>
          <p className="text-xl text-gray-600 dark:text-gray-400">We're here to help you succeed</p>
        </div>
      </section>
      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12">
            <div>
              <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-6">Contact Information</h2>
              <div className="space-y-6">
                <div className="flex items-start space-x-4">
                  <Mail className="w-6 h-6 text-purple-600 dark:text-purple-400 mt-1" />
                  <div>
                    <div className="font-semibold text-gray-900 dark:text-white">Email</div>
                    <div className="text-gray-600 dark:text-gray-400">support@preiposip.com</div>
                  </div>
                </div>
                <div className="flex items-start space-x-4">
                  <Phone className="w-6 h-6 text-purple-600 dark:text-purple-400 mt-1" />
                  <div>
                    <div className="font-semibold text-gray-900 dark:text-white">Phone</div>
                    <div className="text-gray-600 dark:text-gray-400">+91 1800-123-4567</div>
                  </div>
                </div>
                <div className="flex items-start space-x-4">
                  <MapPin className="w-6 h-6 text-purple-600 dark:text-purple-400 mt-1" />
                  <div>
                    <div className="font-semibold text-gray-900 dark:text-white">Address</div>
                    <div className="text-gray-600 dark:text-gray-400">Mumbai, Maharashtra, India</div>
                  </div>
                </div>
              </div>
            </div>
            <div>
              <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-6">Send us a message</h2>
              <form className="space-y-4">
                <input type="text" placeholder="Name" className="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-900 dark:text-white" />
                <input type="email" placeholder="Email" className="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-900 dark:text-white" />
                <textarea placeholder="Message" rows={4} className="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-900 dark:text-white"></textarea>
                <button type="submit" className="w-full px-8 py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl font-bold flex items-center justify-center space-x-2">
                  <span>Send Message</span>
                  <Send className="w-5 h-5" />
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}