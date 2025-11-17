// V-FINAL-1730-198 (VISUAL UPGRADE)
export default function Plans() {
  const plans = [
    {
      emoji: "🌱",
      title: "Family Starter",
      price: "₹1,000",
      period: "per month",
      highlights: ["5% bonuses", "5% profit sharing"],
      extras: ["1× lucky draw entries", "Up to 2× multipliers", "Priority Allocation"],
      totalValue: "₹42,300+",
      invested: "on ₹36,000 investment",
    },
    {
      emoji: "💎",
      title: "Wealth Builder",
      price: "₹5,000",
      period: "per month",
      highlights: ["10% bonuses", "10% profit sharing"],
      extras: ["3× lucky draw entries", "Up to 2× multipliers", "Dedicated RM"],
      totalValue: "₹2,16,000+",
      invested: "on ₹1,80,000 investment",
      featured: true,
    },
    {
      emoji: "🚀",
      title: "Growth Accelerator",
      price: "₹10,000",
      period: "per month",
      highlights: ["15% bonuses", "15% profit sharing"],
      extras: ["5× lucky draw entries", "Up to 2.5× multipliers", "VIP support"],
      totalValue: "₹4,32,000+",
      invested: "on ₹3,60,000 investment",
    },
    {
      emoji: "👑",
      title: "Elite Platinum",
      price: "₹25,000",
      period: "per month",
      highlights: ["20% bonuses", "20% profit sharing"],
      extras: ["10× lucky draw entries", "Up to 3× multipliers", "Concierge service"],
      totalValue: "₹10,80,000+",
      invested: "on ₹9,00,000 investment",
    },
  ];

  return (
    <section id="plans" className="py-20 bg-gradient-to-br from-purple-50 to-blue-50">
      <div className="max-w-7xl mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl lg:text-5xl font-black text-gray-900 mb-4">
            Choose Your <span className="text-gradient">Investment Plan</span>
          </h2>
          <p className="text-xl text-gray-600">All plans come with 20% guaranteed bonuses + ZERO fees!</p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {plans.map((p, idx) => (
            <div
              key={idx}
              className={`bg-white rounded-2xl p-6 shadow-lg hover-scale border-2 transition ${
                p.featured ? "border-purple-500 transform scale-105 shadow-2xl" : "border-transparent"
              }`}
            >
              {p.featured && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-2 rounded-full font-bold text-sm">
                  🔥 MOST POPULAR
                </div>
              )}

              <div className="text-center mb-6">
                <div className="text-3xl mb-2">{p.emoji}</div>
                <h3 className="text-2xl font-bold text-gray-900 mb-2">{p.title}</h3>
                <div className="text-4xl font-black text-gray-900 mb-1">{p.price}</div>
                <div className="text-sm text-gray-500">{p.period}</div>
              </div>

              <div className="space-y-3 mb-6">
                {p.highlights.map((h, i) => (
                  <div key={i} className="flex items-start space-x-2">
                    <svg className="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    <span className="text-sm text-gray-700"><strong>{h}</strong></span>
                  </div>
                ))}
              </div>

              <div className="space-y-3 mb-6">
                {p.extras.map((e, i) => (
                  <div key={i} className="flex items-start space-x-2">
                    <svg className="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    <span className="text-sm text-gray-700">{e}</span>
                  </div>
                ))}
              </div>

              <div className="bg-purple-50 rounded-lg p-4 mb-4">
                <div className="text-xs text-gray-600 mb-1">Total Value (36 months)</div>
                <div className="text-2xl font-bold text-purple-600">{p.totalValue}</div>
                <div className="text-xs text-gray-600">{p.invested}</div>
              </div>

  		<button className="w-full gradient-primary text-white py-3 rounded-lg font-bold">
    			Choose Plan →
  		</button>


            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
