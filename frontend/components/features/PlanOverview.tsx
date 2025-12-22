// V-FINAL-1730-197 (THEME AWARE PLANS)
export default function Plans() {
  const plans = [
    {
      emoji: "🌱",
      title: "Family Starter",
      price: "₹1,000",
      period: "per month",
      highlights: ["Entry-level reward eligibility", "Curated pre-IPO opportunities"],
      extras: ["Participation in platform incentive programs", "Standard allocation priority", "Email & chat support"],
      totalValue: "₹36,000",
      invested: "over 36 months",
    },
    {
      emoji: "💎",
      title: "Wealth Builder",
      price: "₹5,000",
      period: "per month",
      highlights: ["Enhanced reward eligibility", "Select priority offerings"],
      extras: ["Increased participation in incentive programs", "Higher allocation priority", "Dedicated relationship support"],
      totalValue: "₹1,80,000",
      invested: "over 36 months",
      featured: true,
    },
    {
      emoji: "🚀",
      title: "Growth Accelerator",
      price: "₹10,000",
      period: "per month",
      highlights: ["Premium reward eligibility", "Access to premium listings"],
      extras: ["Moderate participation in incentive programs", "Highest allocation consideration", "Priority customer support"],
      totalValue: "₹3,60,000",
      invested: "over 36 months",
    },
    {
      emoji: "👑",
      title: "Elite Platinum",
      price: "₹25,000",
      period: "per month",
      highlights: ["Maximum reward eligibility", "Access to exclusive listings"],
      extras: ["Maximum participation in incentive programs", "Assured allocation priority", "Concierge-style relationship management"],
      totalValue: "₹9,00,000",
      invested: "over 36 months",
    },
  ];

  return (
    <section 
      id="plans" 
      // UPDATED: Gradient adapts from pastel purple/blue (light) to deep gray (dark)
      className="py-20 bg-gradient-to-br from-purple-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 transition-colors duration-300"
    >
      <div className="max-w-7xl mx-auto px-4">
        {/* Header */}
        <div className="text-center mb-16">
          <h2 className="text-4xl lg:text-5xl font-black text-gray-900 dark:text-white mb-4 transition-colors duration-300">
            Choose Your <span className="text-gradient">Investment Plan</span>
          </h2>
          <p className="text-xl text-gray-600 dark:text-gray-300 transition-colors duration-300">
            All plans come with specially curated opportunity + ZERO fees!
          </p>
        </div>

        {/* Plans Grid */}
        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {plans.map((p, idx) => (
            <div
              key={idx}
              // UPDATED: Card bg white -> gray-800. Added transition.
              className={`relative bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover-scale border-2 transition-all duration-300 ${
                p.featured
                  ? "border-purple-500 transform scale-105 shadow-2xl"
                  : "border-transparent dark:border-gray-700" // Added subtle border for non-featured in dark mode for definition
              }`}
            >
              {p.featured && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-2 rounded-full font-bold text-sm shadow-md">
                  🔥 MOST POPULAR
                </div>
              )}

              <div className="text-center mb-6">
                <div className="text-3xl mb-2">{p.emoji}</div>
                <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2 transition-colors duration-300">
                  {p.title}
                </h3>
                <div className="text-4xl font-black text-gray-900 dark:text-white mb-1 transition-colors duration-300">
                  {p.price}
                </div>
                <div className="text-sm text-gray-500 dark:text-gray-400">{p.period}</div>
              </div>

              <div className="space-y-3 mb-6">
                {p.highlights.map((h, i) => (
                  <div key={i} className="flex items-start space-x-2">
                    <svg
                      className="w-5 h-5 text-green-500 mt-0.5 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                    {/* UPDATED: Text color */}
                    <span className="text-sm text-gray-700 dark:text-gray-200 transition-colors duration-300">
                      <strong>{h}</strong>
                    </span>
                  </div>
                ))}
              </div>

              <div className="space-y-3 mb-6">
                {p.extras.map((e, i) => (
                  <div key={i} className="flex items-start space-x-2">
                    <svg
                      className="w-5 h-5 text-green-500 mt-0.5 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                    {/* UPDATED: Text color */}
                    <span className="text-sm text-gray-700 dark:text-gray-400 transition-colors duration-300">{e}</span>
                  </div>
                ))}
              </div>

              {/* Total Value Box */}
              {/* UPDATED: bg-purple-50 in light, bg-purple-900/30 in dark */}
              <div className="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-4 mb-4 transition-colors duration-300">
                <div className="text-xs text-gray-600 dark:text-purple-200 mb-1">
                  Total Contribution
                </div>
                <div className="text-2xl font-bold text-purple-600 dark:text-purple-300">
                  {p.totalValue}
                </div>
                <div className="text-xs text-gray-600 dark:text-purple-200/70">{p.invested}</div>
              </div>

              <button className="w-full gradient-primary text-white py-3 rounded-lg font-bold hover:opacity-90 transition-opacity">
                Choose Plan →
              </button>
            </div>
          ))}
        </div>

        {/* Section-level Disclaimer */}
        <div className="mt-8 flex justify-center">
          {/* UPDATED: Backdrop blur adapts to dark mode bg */}
          <div className="max-w-3xl text-center bg-white/60 dark:bg-gray-800/60 backdrop-blur rounded-lg px-4 py-3 transition-colors duration-300">
            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
              Rewards and incentives are subject to eligibility and program
              terms. They are not investment returns.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}