// V-FINAL-1730-197 (VISUAL UPGRADE - THEME AWARE)
export default function Bonuses() {
  const items = [
    { icon: "📈", title: "Consistency Rewards", desc: "Recognition for maintaining regular SIP participation over time." },
    { icon: "🎯", title: "Participation Milestones", desc: "Eligibility for rewards at select long-term participation milestones." },
    { icon: "⚡", title: "Streak Recognition", desc: "Additional recognition for maintaining uninterrupted SIP activity." },
    { icon: "👥", title: "Referral Recognition", desc: "Additional incentives for users who invite others to the platform." },
    { icon: "✅", title: "On-Time Contribution", desc: "Acknowledgement for timely and consistent SIP contributions." },
    { icon: "🎂", title: "Occasional Rewards", desc: "Special acknowledgements during festivals or account milestones." },
    { icon: "🎲", title: "Promotional Campaigns", desc: "Limited-time engagement initiatives run at the platform level." },
    { icon: "💰", title: "Growth Incentives", desc: "Select incentive programs linked to overall platform performance." },
  ];

  return (
    <section 
      id="features" 
      // UPDATED: Removed fixed gradient. Now adapts Light (Gray-50) vs Dark (Gray-900)
      className="py-20 bg-gray-50 dark:bg-gray-900 transition-colors duration-300"
    >
      <div className="max-w-6xl mx-auto px-4">
        
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4 text-gray-900 dark:text-white transition-colors duration-300">
            Participation-Based <span className="text-gradient">Rewards & Incentives</span>
          </h2>
          {/* UPDATED: Text color for readability on both backgrounds */}
          <p className="text-xl text-gray-600 dark:text-purple-200 transition-colors duration-300">
            Optional programs designed to encourage consistency and engagement.
          </p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {items.map((it, i) => (
            <div 
              key={i} 
              // UPDATED: Card styles
              // Light: White bg, shadow, border | Dark: Gray-800 bg, no shadow
              className="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300 group"
            >
              {/* Icon wrapper to make it pop */}
              <div className="text-4xl mb-4 transform group-hover:scale-110 transition-transform duration-300">
                {it.icon}
              </div>
              
              <h3 className="font-bold text-lg mb-2 text-gray-900 dark:text-white transition-colors duration-300">
                {it.title}
              </h3>
              
              <p className="text-sm text-gray-600 dark:text-gray-400 transition-colors duration-300">
                {it.desc}
              </p>
            </div>
          ))}
        </div>

        {/* Section-level Disclaimer */}
        <div className="mt-8 flex justify-center">
          {/* UPDATED: Disclaimer box styling to fit themes */}
          <div className="max-w-3xl text-center bg-white border border-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-lg px-4 py-3 shadow-sm transition-all duration-300">
            <p className="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
              Rewards & incentives are optional, subject to eligibility and program terms,
              and are not investment returns or guaranteed benefits.
            </p>
          </div>
        </div>

      </div>
    </section>
  );
}