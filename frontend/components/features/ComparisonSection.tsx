// V-FINAL-1730-196 (VISUAL UPGRADE - THEME AWARE)
export default function Comparison() {
  return (
    <section 
      id="comparison" 
      // UPDATED: Base is light mode, dark classes added for toggle support
      className="py-20 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white transition-colors duration-300"
    >

      <div className="max-w-6xl mx-auto px-4">

        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">
            Compare Platform Costs & Pricing <span className="text-gradient">Transparency</span>
          </h2>
          {/* UPDATED: Text color adapts to theme */}
          <p className="text-xl text-gray-600 dark:text-gray-400">
            Understand how different fee structures impact your investment journey!
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-8">

          {/* Competitor Card - UPDATED */}
          {/* Light: White bg with shadow | Dark: Gray-800 bg with no shadow */}
          <div className="bg-white dark:bg-gray-800 rounded-2xl p-8 border-2 border-red-100 dark:border-red-500 shadow-xl dark:shadow-none transition-all duration-300">
            <h3 className="text-center mb-6 text-red-500 text-2xl font-bold">❌ Typical Investment Platforms</h3>

            <ComparisonItem label="Platform Fee (e.g. ₹1,500/mo)" value="-₹54,000" />
            <ComparisonItem label="Exit Fee (e.g. 2.5%)" value="-₹6,750" />
            <ComparisonItem label="Rewards & Incentives" value="Not Applicable" />
            <ComparisonItem label="Referral Rewards" value="Not Applicable" />

            {/* UPDATED: Warning box background adapts */}
            <div className="mt-6 p-4 bg-red-50 dark:bg-red-900/50 rounded-lg text-center transition-colors duration-300">
              <div className="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Estimated Costs</div>
              <div className="text-3xl font-black text-red-500 dark:text-red-400">₹60,750</div>
            </div>
          </div>

          {/* Our Card */}
          {/* Kept largely distinct to stand out, but added border transitions */}
          <div className="gradient-primary rounded-2xl p-8 border-2 border-green-400 relative shadow-2xl">
            <div className="absolute -top-4 -right-4 bg-green-400 text-gray-900 px-4 py-2 rounded-full font-bold animate-pulse">
              🏆 Transparent Pricing
            </div>

            {/* Force text-white here because the gradient background is always dark/colorful */}
            <h3 className="text-center mb-6 text-white text-2xl font-bold">✅ PreIPO SIP</h3>

            <ComparisonItem label="Platform Fee" value="₹0" positive forceTextWhite />
            <ComparisonItem label="Exit Fee" value="₹0" positive forceTextWhite />
            <ComparisonItem label="Rewards & Incentives*" value="Available" positive forceTextWhite />
            <ComparisonItem label="Referral Rewards" value="Applicable!" positive forceTextWhite />

            <div className="mt-6 p-4 bg-white/20 backdrop-blur rounded-lg text-center">
              <div className="text-sm text-purple-100 mb-1">Estimated Platform Costs</div>
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
          <div className="inline-block bg-green-500 text-white px-8 py-4 rounded-2xl shadow-lg">
            <div className="text-sm mb-1 opacity-90">Potential Cost Savings Through Zero-Fee Structure</div>
            <div className="text-4xl font-black">₹60,750</div>
          </div>
        </div>

      </div>
    </section>
  );
}

// UPDATED CHILD COMPONENT
function ComparisonItem({
  label,
  value,
  positive = false,
  forceTextWhite = false, // New prop to handle the gradient card specifically
}: {
  label: string;
  value: string;
  positive?: boolean;
  forceTextWhite?: boolean;
}) {
  return (
    // Row BG: Light gray in light mode, transparent white in dark mode
    // Text: Dark gray in light mode (unless forced), light gray in dark mode
    <div className={`flex justify-between items-center p-4 rounded-lg mb-3 transition-colors duration-300
      ${forceTextWhite ? 'bg-white/10 text-white' : 'bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-200'}
    `}>
      <span className="text-sm md:text-base font-medium">{label}</span>
      <span className={`${positive ? "text-green-300" : "text-red-500 dark:text-red-400"} font-bold`}>
        {value}
      </span>
    </div>
  );
}