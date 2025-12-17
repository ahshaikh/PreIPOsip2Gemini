// V-FINAL-1730-196 (VISUAL UPGRADE)
export default function Comparison() {
  return (
    <section id="comparison" className="py-20 bg-gray-900 text-white">

      <div className="max-w-6xl mx-auto px-4">

        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">
            Compare Platform Costs & Pricing <span className="text-gradient">Transparency</span>
          </h2>
          <p className="text-xl text-gray-400">Understand how different fee structures impact your investment journey!</p>
        </div>

        <div className="grid md:grid-cols-2 gap-8">

          {/* Competitor Card */}
          <div className="bg-gray-800 rounded-2xl p-8 border-2 border-red-500">
            <h3 className="text-center mb-6 text-red-500 text-2xl font-bold">❌ Typical Investment Platforms</h3>

            <ComparisonItem label="Platform Fee (for example ₹1,500/month)" value="-₹54,000" />
            <ComparisonItem label="Exit Fee (for example 2.5%)" value="-₹6,750" />
            <ComparisonItem label="Rewards & Incentives" value="Not Applicable" />
            <ComparisonItem label="Referral Rewards" value="Not Applicable" />

            <div className="mt-6 p-4 bg-red-900/50 rounded-lg text-center">
              <div className="text-sm text-gray-400 mb-1">Total Estimated Costs</div>
              <div className="text-3xl font-black text-red-400">₹60,750</div>
            </div>
          </div>

          {/* Our Card */}
          <div className="gradient-primary rounded-2xl p-8 border-2 border-green-400 relative">
            <div className="absolute -top-4 -right-4 bg-green-400 text-gray-900 px-4 py-2 rounded-full font-bold animate-pulse">
              🏆 Transparent Pricing
            </div>

            <h3 className="text-center mb-6 text-white text-2xl font-bold">✅ PreIPO SIP</h3>

            <ComparisonItem label="Platform Fee" value="₹0" positive />
            <ComparisonItem label="Exit Fee" value="₹0" positive />
            <ComparisonItem label="Rewards & Incentives*" value="Available under select programs" positive />
            <ComparisonItem label="Referral Rewards" value="Applicable!" positive />

            <div className="mt-6 p-4 bg-white/30 backdrop-blur rounded-lg text-center">
              <div className="text-sm text-purple-200 mb-1">Estimated Platform Costs</div>
              <div className="text-3xl font-black text-white">₹0</div>
             </div>
                         {/* Rewards Disclaimer */}
            <p className="mt-2 text-xs text-purple-200 text-center">
              *Subject to eligibility and program terms. Not investment returns.
            </p>

          </div>

        </div>

        {/* Net Advantage */}
        <div className="text-center mt-12">
          <div className="inline-block bg-green-500 text-white px-8 py-4 rounded-2xl">
            <div className="text-sm mb-1">Potential Cost Savings Through Zero-Fee Structure</div>
            <div className="text-4xl font-black">₹60,750</div>
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
