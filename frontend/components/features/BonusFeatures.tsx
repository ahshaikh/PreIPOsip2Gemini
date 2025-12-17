// V-FINAL-1730-197 (VISUAL UPGRADE)
export default function Bonuses() {
  const items = [
    { icon: "📈", title: "Consistency Rewards", desc: "Recognition for maintaining regular SIP participation over time." },
    { icon: "🎯", title: "Participation Milestones", desc: "Eligibility for rewards at select long-term participation milestones." },
    { icon: "⚡", title: "Streak Recognition", desc: "Additional recognition for maintaining uninterrupted SIP activity." },
    { icon: "👥", title: "Referral Recognition", desc: "Additional incentives for users who invite others to the platform." },
    { icon: "✅", title: "On-Time Contribution Recognition", desc: "Acknowledgement for timely and consistent SIP contributions." },
    { icon: "🎂", title: "Occasional Engagement Rewards", desc: "Special acknowledgements during festivals or account milestones." },
    { icon: "🎲", title: "Periodic Promotional Campaigns", desc: "Limited-time engagement initiatives run at the platform level." },
    { icon: "💰", title: "Platform Growth Incentives", desc: "Select incentive programs linked to overall platform performance." },
  ];

  return (
    <section id= "features" className="py-20 gradient-primary text-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">Participation-Based <span className="text-yellow-300">Rewards & Incentives</span></h2>
          <p className="text-xl text-purple-100">Optional programs designed to encourage consistency and engagement.</p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {items.map((it, i) => (
            <div key={i} className="bg-white/10 backdrop-blur rounded-xl p-6">
              <div className="text-4xl mb-4">{it.icon}</div>
              <h3 className="font-bold text-lg mb-2">{it.title}</h3>
              <p className="text-purple-100 text-sm">{it.desc}</p>
            </div>
          ))}
        </div>
                {/* Section-level Disclaimer */}
        <div className="mt-8 flex justify-center">
          <div className="max-w-3xl text-center bg-white/60 backdrop-blur rounded-lg px-4 py-3">
            <p className="text-xs text-gray-500 leading-relaxed">
              Rewards & incentives are optional, subject to eligibility and program terms,
and are not investment returns or guaranteed benefits.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
