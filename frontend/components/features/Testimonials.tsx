// V-FINAL-1730-199 (VISUAL UPGRADE)
export default function Testimonials() {
  const list = [
    {
      rating: "★★★★★",
      text: "\"Finally, a platform that focuses on transparency instead of hidden fees. The SIP-based approach makes it easy to stay disciplined, and everything is clearly explained.\"",
      initials: "RK",
      name: "Rajesh Kumar",
      plan: "Wealth Builder Plan",
    },
    {
      rating: "★★★★★",
      text: "\“What stood out for me was the zero platform and exit fees. The experience feels clean, professional, and very different from traditional investment platforms.\"",
      initials: "PS",
      name: "Priya Sharma",
      plan: "Growth Accelerator",
    },
    {
      rating: "★★★★★",
      text: "\“I like how the platform rewards long-term participation and community referrals without overcomplicating things. The overall experience feels fair and transparent.\"",
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
                {/* Section-level Disclaimer */}
        <div className="mt-8 flex justify-center">
          <div className="max-w-3xl text-center bg-white/60 backdrop-blur rounded-lg px-4 py-3">
            <p className="text-xs text-gray-500 leading-relaxed">
              Testimonials reflect individual user experiences and opinions.
              They do not represent investment outcomes or guaranteed results.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
