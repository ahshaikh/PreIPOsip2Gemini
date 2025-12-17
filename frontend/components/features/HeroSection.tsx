// V-PHASE4-1730-106 (Created) | V-FINAL-1730-312 (Dynamic)

"use client";

import Link from "next/link";

export default function HeroSection() {
  return (
    <section id="hero" className="pt-32 pb-20 px-4 relative overflow-hidden dark:bg-slate-900">
      <div className="absolute inset-0 bg-gradient-to-br from-purple-50 via-white to-blue-50 dark:from-slate-800 dark:via-slate-900 dark:to-slate-800 opacity-50" />

      <div className="max-w-7xl mx-auto relative z-10">
        <div className="grid lg:grid-cols-2 gap-12 items-center">

          {/* LEFT SIDE */}
          <div>

            <div className="inline-block px-4 py-2 bg-purple-100 dark:bg-purple-900/30 rounded-full text-purple-700 dark:text-purple-300 font-semibold text-sm mb-6 animate-pulse">
              🎉 100% Zero Fees Forever!
            </div>

            <h1 className="text-5xl lg:text-6xl font-black text-gray-900 dark:text-white mb-6 leading-tight">
              India's First <br />
              <span className="text-gradient">100% FREE</span> <br />
              Pre-IPO SIP Platform
            </h1>

            <p className="text-xl text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
              Access curated pre-IPO investment opportunities with a{" "}
              <strong className="text-purple-600 dark:text-purple-400">SIP-based approach</strong>{" "}
              — built for long-term investors who value transparency and control. No platform fees. No exit fees. No hidden charges —ever.
               </p>

            {/* VALUE PROPS */}
            <div className="space-y-4 mb-8">
              {[
                "Zero Platform Fees (save up to ₹54,000 over time)",
                "Zero Exit Fees — invest with flexibility",
                "Transparent pricing with no hidden charges",
              ].map((text, i) => (
                <div key={i} className="flex items-center space-x-3">
                  <div className="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <svg className="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <span className="text-gray-700 dark:text-gray-300 text-lg">{text}</span>
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
                className="border-2 border-purple-600 dark:border-purple-400 text-purple-600 dark:text-purple-400 px-8 py-4 rounded-xl font-bold text-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition"
              >
                Calculate Returns 📊
              </button>

            </div>

            {/* STATISTICS */}
            <div className="mt-8 flex items-center space-x-6 text-sm text-gray-600 dark:text-gray-400">
              <div className="flex items-center space-x-2"><span className="text-2xl">🔒</span><span>SEBI-Aligned</span></div>
              <div className="flex items-center space-x-2"><span className="text-2xl">✅</span><span>Growing Investors</span></div>
              <div className="flex items-center space-x-2"><span className="text-2xl">⭐</span><span>Loved by early users</span></div>
            </div>

          </div>

          {/* RIGHT CARD */}
          <div className="relative animate-float">
            <div className="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl p-8 border border-gray-100 dark:border-slate-700">

              {/* Header */}
              <div className="text-center mb-6">
                <div className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                  Illustrative Investment Scenario
                </div>
                <div className="text-4xl font-black text-gray-900 dark:text-white">
                  ₹1,80,000
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400">
                  Total Amount Invested Over 3 Years
                </div>
              </div>

              {/* Breakdown */}
              <div className="space-y-4 mb-6">
                <CardItem
                  label="Platform & Exit Fees Saved"
                  value="+₹60,000"
                  bg="bg-green-50 dark:bg-green-900/30"
                  text="text-green-600 dark:text-green-400"
                />

                <CardItem
                  label="SIP Advantage"
                  value="Regular investing habit"
                  bg="bg-blue-50 dark:bg-blue-900/30"
                  text="text-blue-600 dark:text-blue-400"
                />

          <CardItem
            label="Potential Rewards & Incentives*"
            value="Up to ₹36,000"
            bg="bg-purple-50 dark:bg-purple-900/30"
            text="text-purple-600 dark:text-purple-400"
          />
        </div>

        {/* What this illustrates (REPLACES Total Value / Returns) */}
        <div className="border-t-2 border-dashed border-gray-300 dark:border-slate-600 pt-6 space-y-3">
          <div className="text-lg font-semibold text-gray-900 dark:text-white">
            What This Illustrates
          </div>

          <ul className="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <li>• The impact of disciplined SIP-based investing</li>
            <li>• How zero platform and exit fees reduce long-term costs</li>
            <li>• Transparent pricing with no hidden charges</li>
          </ul>
        </div>

        {/* Disclaimer */}
        <div className="mt-4 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
          Figures shown are illustrative examples only.
          Investments in unlisted companies carry risk.
          Returns are not guaranteed and depend on company performance.
          Rewards and incentives are subject to program terms and eligibility.
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
      <span className="font-semibold text-gray-900 dark:text-white">{label}</span>
      <span className={`text-2xl font-bold ${text}`}>{value}</span>
    </div>
  );
}
