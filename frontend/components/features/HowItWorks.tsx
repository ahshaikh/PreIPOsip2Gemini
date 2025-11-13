// V-FINAL-1730-200 (VISUAL UPGRADE)
'use client';

import Link from 'next/link';

export function HowItWorks() {
  return (
    <section id="how-it-works" className="py-20 bg-white">
        <div className="max-w-6xl mx-auto px-4">
            <div className="text-center mb-16">
                <h2 className="text-4xl font-black text-gray-900 mb-4">How It <span className="text-gradient">Works</span></h2>
                <p className="text-xl text-gray-600">Start investing in 3 simple steps</p>
            </div>

            <div className="grid md:grid-cols-3 gap-8 relative">
                {/* Connecting Line (Desktop) */}
                <div className="hidden md:block absolute top-10 left-[16%] right-[16%] h-1 bg-gradient-to-r from-indigo-200 via-purple-200 to-pink-200 -z-10"></div>

                <div className="text-center bg-white p-4">
                    <div className="w-20 h-20 gradient-primary rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-6 shadow-lg shadow-indigo-200">1</div>
                    <h3 className="text-xl font-bold text-gray-900 mb-3">Choose Your Plan</h3>
                    <p className="text-gray-600 leading-relaxed">Select from ₹1,000 to ₹25,000 monthly SIP. All plans come with guaranteed bonuses.</p>
                </div>

                <div className="text-center bg-white p-4">
                    <div className="w-20 h-20 gradient-primary rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-6 shadow-lg shadow-indigo-200">2</div>
                    <h3 className="text-xl font-bold text-gray-900 mb-3">Complete KYC</h3>
                    <p className="text-gray-600 leading-relaxed">Quick 5-minute KYC with Aadhaar, PAN & Demat account. 100% secure & SEBI compliant.</p>
                </div>

                <div className="text-center bg-white p-4">
                    <div className="w-20 h-20 gradient-primary rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-6 shadow-lg shadow-indigo-200">3</div>
                    <h3 className="text-xl font-bold text-gray-900 mb-3">Start Earning</h3>
                    <p className="text-gray-600 leading-relaxed">Invest monthly, earn bonuses automatically, and watch your portfolio grow!</p>
                </div>
            </div>

            <div className="text-center mt-12">
                <Link href="/signup">
                    <button className="gradient-primary text-white px-12 py-4 rounded-xl font-bold text-lg hover:shadow-2xl transition transform hover:scale-105">
                        Get Started Free →
                    </button>
                </Link>
            </div>
        </div>
    </section>
  );
}