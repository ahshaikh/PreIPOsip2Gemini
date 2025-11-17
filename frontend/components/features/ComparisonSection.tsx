// V-FINAL-1730-196 (VISUAL UPGRADE)
export default function Comparison() {
  return (
    <section id="comparison" className="py-20 bg-gray-900 text-white">

      <div className="max-w-6xl mx-auto px-4">

        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">
            Why Pay Fees When You Can Get <span className="text-gradient">Bonuses?</span>
          </h2>
          <p className="text-xl text-gray-400">Compare and see the massive difference!</p>
        </div>

        <div className="grid md:grid-cols-2 gap-8">

          {/* Competitor Card */}
          <div className="bg-gray-800 rounded-2xl p-8 border-2 border-red-500">
            <h3 className="text-center mb-6 text-red-500 text-2xl font-bold">❌ Traditional Platforms</h3>

            <ComparisonItem label="Platform Fee (₹1,500/month)" value="-₹54,000" />
            <ComparisonItem label="Exit Fee (2.5%)" value="-₹6,750" />
            <ComparisonItem label="Bonuses" value="₹0" />
            <ComparisonItem label="Referral Rewards" value="₹0" />

            <div className="mt-6 p-4 bg-red-900/50 rounded-lg text-center">
              <div className="text-sm text-gray-400 mb-1">Total You LOSE</div>
              <div className="text-3xl font-black text-red-400">-₹60,750</div>
            </div>
          </div>

          {/* Our Card */}
          <div className="gradient-primary rounded-2xl p-8 border-2 border-green-400 relative">
            <div className="absolute -top-4 -right-4 bg-green-400 text-gray-900 px-4 py-2 rounded-full font-bold animate-pulse">
              🏆 BEST VALUE
            </div>

            <h3 className="text-center mb-6 text-white text-2xl font-bold">✅ PreIPO SIP</h3>

            <ComparisonItem label="Platform Fee" value="₹0 FREE!" positive />
            <ComparisonItem label="Exit Fee" value="₹0 FREE!" positive />
            <ComparisonItem label="20% Bonuses" value="+₹36,000" positive />
            <ComparisonItem label="Referral Rewards" value="Up to 60%!" positive />

            <div className="mt-6 p-4 bg-white/30 backdrop-blur rounded-lg text-center">
              <div className="text-sm text-purple-200 mb-1">Total You EARN</div>
              <div className="text-3xl font-black text-white">+₹96,000</div>
            </div>
          </div>

        </div>

        {/* Net Advantage */}
        <div className="text-center mt-12">
          <div className="inline-block bg-green-500 text-white px-8 py-4 rounded-2xl">
            <div className="text-sm mb-1">NET ADVANTAGE</div>
            <div className="text-4xl font-black">₹1,56,750 Better!</div>
          </div>
        </div>

      </div>
    </section>
  );
}

function ComparisonItem({
  label,
  value,
  positive = false,
}: {
  label: string;
  value: string;
  positive?: boolean;
}) {
  return (
    <div className="flex justify-between items-center p-4 bg-white/10 rounded-lg mb-3">
      <span>{label}</span>
      <span className={`${positive ? "text-green-300" : "text-red-400"} font-bold`}>
        {value}
      </span>
    </div>
  );
}
