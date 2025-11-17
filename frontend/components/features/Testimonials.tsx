// V-FINAL-1730-199 (VISUAL UPGRADE)
export default function Testimonials() {
  const list = [
    {
      rating: "★★★★★",
      text: "\"Finally, a platform that gives bonuses instead of taking fees! I've earned ₹12,000 in bonuses in just 12 months. Highly recommend!\"",
      initials: "RK",
      name: "Rajesh Kumar",
      plan: "Wealth Builder Plan",
    },
    {
      rating: "★★★★★",
      text: "\"Zero fees is amazing! I saved ₹54,000 that other platforms would have charged. My portfolio is up 60% with Swiggy listing!\"",
      initials: "PS",
      name: "Priya Sharma",
      plan: "Growth Accelerator",
    },
    {
      rating: "★★★★★",
      text: "\"The referral multiplier is genius! I referred 5 friends and now get 2× all bonuses. Making ₹72,000 extra over 3 years!\"",
      initials: "AM",
      name: "Amit Mehta",
      plan: "Wealth Builder Plan",
    },
  ];

  return (
    <section id="testimonials" className="py-20 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black text-gray-900 mb-4">What Our <span className="text-gradient">Investors Say</span></h2>
          <p className="text-xl text-gray-600">Join 5,000+ happy investors</p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          {list.map((t, i) => (
            <div key={i} className="bg-gray-50 rounded-2xl p-8">
              <div className="flex items-center mb-4">
                <div className="text-yellow-400">{t.rating}</div>
              </div>
              <p className="text-gray-700 mb-6">{t.text}</p>

              <div className="flex items-center space-x-3">
                <div className="w-12 h-12 gradient-primary rounded-full flex items-center justify-center text-white font-bold">{t.initials}</div>
                <div>
                  <div className="font-semibold text-gray-900">{t.name}</div>
                  <div className="text-sm text-gray-500">{t.plan}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
