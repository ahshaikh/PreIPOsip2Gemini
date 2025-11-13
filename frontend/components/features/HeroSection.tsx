// V-FINAL-1730-195 (VISUAL UPGRADE)
'use client';

import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Check, Star, Lock, ArrowRight } from 'lucide-react';

export function HeroSection() {
  return (
    <section className="pt-20 pb-20 px-4 relative overflow-hidden">
      {/* Background */}
      <div className="absolute inset-0 bg-gradient-to-br from-purple-50 via-white to-blue-50 opacity-50 -z-10"></div>
      
      <div className="container mx-auto relative z-10">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          {/* Left Content */}
          <div>
            <div className="inline-block px-4 py-2 bg-purple-100 rounded-full text-purple-700 font-semibold text-sm mb-6 animate-pulse-slow">
              🎉 100% Zero Fees Forever!
            </div>
            
            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 mb-6 leading-tight">
              India's First<br/>
              <span className="text-gradient">100% FREE</span><br/>
              Pre-IPO SIP Platform
            </h1>
            
            <p className="text-xl text-gray-600 mb-8 leading-relaxed">
              Invest in tomorrow's unicorns today! Get <strong className="text-purple-600">10-20% guaranteed bonuses</strong> + portfolio gains. No platform fees. No exit fees. No hidden charges.
            </p>

            {/* Value Props List */}
            <div className="space-y-4 mb-8">
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <Check className="w-5 h-5 text-green-600" />
                </div>
                <span className="text-gray-700 text-lg"><strong>Zero Platform Fees</strong> (Save ₹54,000)</span>
              </div>
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <Check className="w-5 h-5 text-green-600" />
                </div>
                <span className="text-gray-700 text-lg"><strong>Zero Exit Fees</strong> (Save ₹6,000)</span>
              </div>
              <div className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <Check className="w-5 h-5 text-green-600" />
                </div>
                <span className="text-gray-700 text-lg"><strong>10% Guaranteed Bonuses</strong> (Earn ₹36,000)</span>
              </div>
            </div>

            {/* CTA Buttons */}
            <div className="flex flex-col sm:flex-row gap-4">
              <Link href="/signup">
                <button className="gradient-primary text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition transform hover:scale-105 flex items-center">
                  Start Investing Free <ArrowRight className="ml-2 h-5 w-5" />
                </button>
              </Link>
              <Link href="/calculator">
                <button className="border-2 border-purple-600 text-purple-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-purple-50 transition">
                  Calculate Returns 📊
                </button>
              </Link>
            </div>

            <div className="mt-8 flex items-center space-x-6 text-sm text-gray-600">
              <div className="flex items-center space-x-2">
                <Lock className="h-5 w-5 text-gray-400" />
                <span>SEBI Registered</span>
              </div>
              <div className="flex items-center space-x-2">
                <Check className="h-5 w-5 text-green-500" />
                <span>5,000+ Investors</span>
              </div>
              <div className="flex items-center space-x-2">
                <Star className="h-5 w-5 text-yellow-400 fill-yellow-400" />
                <span>4.9/5 Rating</span>
              </div>
            </div>
          </div>

          {/* Right Visualization - Floating Card */}
          <div className="relative animate-float hidden lg:block">
            <div className="bg-white rounded-3xl shadow-2xl p-8 border border-gray-100">
              <div className="text-center mb-6">
                <div className="text-sm text-gray-500 mb-2">Your Investment Journey</div>
                <div className="text-4xl font-black text-gray-900">₹1,80,000</div>
                <div className="text-sm text-gray-500">Investment Over 3 Years</div>
              </div>

              <div className="space-y-4 mb-6">
                <div className="flex justify-between items-center p-4 bg-green-50 rounded-xl">
                  <span className="font-semibold text-gray-900">10% Bonuses</span>
                  <span className="text-2xl font-bold text-green-600">+₹18,000</span>
                </div>
                <div className="flex justify-between items-center p-4 bg-blue-50 rounded-xl">
                  <span className="font-semibold text-gray-900">Avg Pre-IPO Gains</span>
                  <span className="text-2xl font-bold text-blue-600">+₹81,000</span>
                </div>
                <div className="flex justify-between items-center p-4 bg-purple-50 rounded-xl">
                  <span className="font-semibold text-gray-900">Platform Fees Saved</span>
                  <span className="text-2xl font-bold text-purple-600">+₹60,000</span>
                </div>
              </div>

              <div className="border-t-2 border-dashed border-gray-300 pt-6">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-semibold text-gray-900">Total Value</span>
                  <div className="text-right">
                    <div className="text-3xl font-black text-gradient">₹3,39,000</div>
                    <div className="text-sm text-green-600 font-semibold">88% Total Returns! 🚀</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}