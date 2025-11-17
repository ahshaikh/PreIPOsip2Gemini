// V-FINAL-1730-197 (VISUAL UPGRADE)
export default function Bonuses() {
  const items = [
    { icon: "📈", title: "Progressive Monthly", desc: "Bonuses grow every month from 0.5% to 18%+" },
    { icon: "🎯", title: "Milestone Rewards", desc: "₹500-14,000 at 12, 24 & 36 months" },
    { icon: "💰", title: "Profit Sharing", desc: "5-20% of platform profits quarterly" },
    { icon: "👥", title: "Referral Multiplier", desc: "2-3× all bonuses with referrals" },
    { icon: "✅", title: "Consistency Bonus", desc: "₹7-175/month for on-time payments" },
    { icon: "🎂", title: "Celebration Bonuses", desc: "Birthday, anniversary & festival rewards" },
    { icon: "🎲", title: "Lucky Draw", desc: "Win up to ₹50,000 monthly!" },
    { icon: "⚡", title: "Streak Bonuses", desc: "Extra rewards at 6 & 12 month streaks" },
  ];

  return (
    <section id= "features" className="py-20 gradient-primary text-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">7 Ways to <span className="text-yellow-300">Earn Bonuses</span></h2>
          <p className="text-xl text-purple-100">Multiple bonus streams working for you 24/7!</p>
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
      </div>
    </section>
  );
}
