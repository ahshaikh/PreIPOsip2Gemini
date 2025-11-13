// V-FINAL-1730-197 (VISUAL UPGRADE)
'use client';

import { TrendingUp, Target, Coins, Users, CheckCircle2, Gift, Ticket, Zap } from 'lucide-react';

export function BonusFeatures() {
  const features = [
    { icon: TrendingUp, title: "Progressive Monthly", desc: "Bonuses grow every month from 0.5% to 18%+" },
    { icon: Target, title: "Milestone Rewards", desc: "₹500-14,000 at 12, 24 & 36 months" },
    { icon: Coins, title: "Profit Sharing", desc: "5-20% of platform profits quarterly" },
    { icon: Users, title: "Referral Multiplier", desc: "2-3× all bonuses with referrals" },
    { icon: CheckCircle2, title: "Consistency Bonus", desc: "₹7-175/month for on-time payments" },
    { icon: Gift, title: "Celebration Bonuses", desc: "Birthday, anniversary & festival rewards" },
    { icon: Ticket, title: "Lucky Draw", desc: "Win up to ₹50,000 monthly!" },
    { icon: Zap, title: "Streak Bonuses", desc: "Extra rewards at 6 & 12 month streaks" },
  ];

  return (
    <section className="py-20 gradient-primary text-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black mb-4">7 Ways to <span className="text-yellow-300">Earn Bonuses</span></h2>
          <p className="text-xl text-purple-100">Multiple bonus streams working for you 24/7!</p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {features.map((f, i) => (
            <div key={i} className="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/10 hover:bg-white/20 transition">
              <div className="mb-4 p-3 bg-white/20 rounded-full w-fit">
                <f.icon className="w-8 h-8 text-white" />
              </div>
              <h3 className="font-bold text-lg mb-2">{f.title}</h3>
              <p className="text-purple-100 text-sm">{f.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}