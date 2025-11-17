// V-FINAL-1730-312 (Dynamic)

"use client";

import Link from "next/link";

export default function HeroSection() {
  return (
    <section id="hero" className="pt-32 pb-20 px-4 relative overflow-hidden">
      <div className="absolute inset-0 bg-gradient-to-br from-purple-50 via-white to-blue-50 opacity-50" />

      <div className="max-w-7xl mx-auto relative z-10">
        <div className="grid lg:grid-cols-2 gap-12 items-center">

          {/* LEFT SIDE */}
          <div>

            <div className="inline-block px-4 py-2 bg-purple-100 rounded-full text-purple-700 font-semibold text-sm mb-6 animate-pulse">
              🎉 100% Zero Fees Forever!
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 mb-6 leading-tight">
              India's First <br />
              <span className="text-gradient">100% FREE</span> <br />
              Pre-IPO SIP Platform
            </h1>

            <p className="text-xl text-gray-600 mb-8 leading-relaxed">
              Invest in tomorrow's unicorns today! Get{" "}
              <strong className="text-purple-600">5-20% guaranteed bonuses</strong>{" "}
              + portfolio gains. No platform fees. No exit fees. No hidden charges.
            </p>

            {/* VALUE PROPS */}
            <div className="space-y-4 mb-8">
              {[
                "Zero Platform Fees (Save ₹54,000)",
                "Zero Exit Fees (Save ₹6,000)",
                "20% Guaranteed Bonuses (Earn ₹36,000)",
              ].map((text, i) => (
                <div key={i} className="flex items-center space-x-3">
                  <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                    <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <span className="text-gray-700 text-lg">{text}</span>
                </div>
              ))}
            </div>

            {/* CTA BUTTONS */}
            <div className="flex flex-col sm:flex-row gap-4">
              
              {/* Go to signup */}
              <Link href="/signup">
                <button className="gradient-primary text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition transform hover:scale-105">
                  Start Investing Free →
                </button>
              </Link>

              {/* Scroll to calculator */}
              <button
                onClick={() =>
                  document.getElementById("calculator")?.scrollIntoView({ behavior: "smooth" })
                }
                className="border-2 border-purple-600 text-purple-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-purple-50 transition"
              >
                Calculate Returns 📊
              </button>

            </div>

            {/* STATISTICS */}
            <div className="mt-8 flex items-center space-x-6 text-sm text-gray-600">
              <div className="flex items-center space-x-2"><span className="text-2xl">🔒</span><span>SEBI Registered</span></div>
              <div className="flex items-center space-x-2"><span className="text-2xl">✅</span><span>5,000+ Investors</span></div>
              <div className="flex items-center space-x-2"><span className="text-2xl">⭐</span><span>4.9/5 Rating</span></div>
            </div>

          </div>

          {/* RIGHT CARD */}
          <div className="relative animate-float">
            <div className="bg-white rounded-3xl shadow-2xl p-8 border border-gray-100">

              <div className="text-center mb-6">
                <div className="text-sm text-gray-500 mb-2">Your Investment Journey</div>
                <div className="text-4xl font-black text-gray-900">₹1,80,000</div>
                <div className="text-sm text-gray-500">Investment Over 3 Years</div>
              </div>

              <div className="space-y-4 mb-6">
                <CardItem label="20% Bonuses" value="+₹36,000" bg="bg-green-50" text="text-green-600" />
                <CardItem label="Avg Pre-IPO Gains" value="+₹81,000" bg="bg-blue-50" text="text-blue-600" />
                <CardItem label="Platform Fees Saved" value="+₹60,000" bg="bg-purple-50" text="text-purple-600" />
              </div>

              <div className="border-t-2 border-dashed border-gray-300 pt-6">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-semibold text-gray-900">Total Value</span>
                  <div className="text-right">
                    <div className="text-3xl font-black text-gradient">₹3,57,000</div>
                    <div className="text-sm text-green-600 font-semibold">98% Total Returns! 🚀</div>
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

function CardItem({ label, value, bg, text }: { label: string; value: string; bg: string; text: string }) {
  return (
    <div className={`flex justify-between items-center p-4 rounded-xl ${bg}`}>
      <span className="font-semibold text-gray-900">{label}</span>
      <span className={`text-2xl font-bold ${text}`}>{value}</span>
    </div>
  );
}
