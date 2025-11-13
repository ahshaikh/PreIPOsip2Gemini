// V-FINAL-1730-196 (VISUAL UPGRADE)
'use client';

export function ComparisonSection() {
  return (
    <section className="py-20 bg-gray-900 text-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">Why Pay Fees When You Can Get <span className="text-gradient">Bonuses?</span></h2>
          <p className="text-xl text-gray-400">Compare and see the massive difference!</p>
        </div>

        <div className="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
          {/* Competitor */}
          <div className="bg-gray-800 rounded-2xl p-8 border-2 border-red-500">
            <div className="text-center mb-6">
              <div className="text-red-500 text-2xl font-bold mb-2">❌ Traditional Platforms</div>
              <div className="text-gray-400">What Others Charge You</div>
            </div>

            <div className="space-y-4">
              <div className="flex justify-between items-center p-4 bg-red-900/30 rounded-lg">
                <span>Platform Fee (₹1,500/month)</span>
                <span className="text-red-400 font-bold">-₹54,000</span>
              </div>
              <div className="flex justify-between items-center p-4 bg-red-900/30 rounded-lg">
                <span>Exit Fee (2.5%)</span>
                <span className="text-red-400 font-bold">-₹6,750</span>
              </div>
              <div className="flex justify-between items-center p-4 bg-red-900/30 rounded-lg">
                <span>Bonuses</span>
                <span className="text-red-400 font-bold">₹0</span>
              </div>
              <div className="flex justify-between items-center p-4 bg-red-900/30 rounded-lg">
                <span>Referral Rewards</span>
                <span className="text-red-400 font-bold">₹0</span>
              </div>
            </div>

            <div className="mt-6 p-4 bg-red-900/50 rounded-lg text-center">
              <div className="text-sm text-gray-400 mb-1">Total You LOSE</div>
              <div className="text-3xl font-black text-red-400">-₹60,750</div>
            </div>
          </div>

          {/* Our Platform */}
          <div className="gradient-primary rounded-2xl p-8 border-2 border-green-400 relative">
            <div className="absolute -top-4 -right-4 bg-green-400 text-gray-900 px-4 py-2 rounded-full font-bold animate-pulse-slow shadow-lg">
              🏆 BEST VALUE
            </div>

            <div className="text-center mb-6">
              <div className="text-white text-2xl font-bold mb-2">✅ PreIPO SIP</div>
              <div className="text-purple-200">What We Give You</div>
            </div>

            <div className="space-y-4">
              <div className="flex justify-between items-center p-4 bg-white/20 backdrop-blur-md rounded-lg">
                <span>Platform Fee</span>
                <span className="text-green-300 font-bold">₹0 FREE!</span>
              </div>
              <div className="flex justify-between items-center p-4 bg-white/20 backdrop-blur-md rounded-lg">
                <span>Exit Fee</span>
                <span className="text-green-300 font-bold">₹0 FREE!</span>
              </div>
              <div className="flex justify-between items-center p-4 bg-white/20 backdrop-blur-md rounded-lg">
                <span>10% Bonuses</span>
                <span className="text-green-300 font-bold">+₹18,000</span>
              </div>
              <div className="flex justify-between items-center p-4 bg-white/20 backdrop-blur-md rounded-lg">
                <span>Referral Rewards</span>
                <span className="text-green-300 font-bold">Up to 60%!</span>
              </div>
            </div>

            <div className="mt-6 p-4 bg-white/30 backdrop-blur-md rounded-lg text-center">
              <div className="text-sm text-purple-200 mb-1">Total You EARN</div>
              <div className="text-3xl font-black text-white">+₹78,000</div>
            </div>
          </div>
        </div>

        <div className="text-center mt-12">
          <div className="inline-block bg-green-500 text-white px-8 py-4 rounded-2xl shadow-lg transform hover:scale-105 transition">
            <div className="text-sm mb-1 font-medium">NET ADVANTAGE</div>
            <div className="text-4xl font-black">₹1,38,750 Better!</div>
          </div>
        </div>
      </div>
    </section>
  );
}