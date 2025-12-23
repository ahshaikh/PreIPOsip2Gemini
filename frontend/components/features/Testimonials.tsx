// V-FINAL-1730-199 (VISUAL UPGRADE - THEME AWARE + CTA)
import Link from "next/link";

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
    <section 
      id="testimonials" 
      // UPDATED: Theme transitions (White <-> Gray-900)
      className="py-20 bg-white dark:bg-gray-900 transition-colors duration-300"
    >
      <div className="max-w-6xl mx-auto px-4">
        
        {/* Header Section */}
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black text-gray-900 dark:text-white mb-4 transition-colors duration-300">
            What Our <span className="text-gradient">Investors Say</span>
          </h2>
          <p className="text-xl text-gray-600 dark:text-gray-400 transition-colors duration-300">
            Join our happy growing investors
          </p>

          {/* NEW CTA BUTTON ADDED HERE */}
          <div className="mt-8">
            <Link href="/signup">
              <button className="gradient-primary text-white px-10 py-3 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 shadow-lg shadow-purple-500/20">
                Join the Community →
              </button>
            </Link>
          </div>
        </div>

        {/* Testimonials Grid */}
        <div className="grid md:grid-cols-3 gap-8">
          {list.map((t, i) => (
            <div 
              key={i} 
              // UPDATED: Card Bg (Gray-50 <-> Gray-800)
              className="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8 border border-gray-100 dark:border-gray-700 transition-colors duration-300 hover:shadow-lg"
            >
              <div className="flex items-center mb-4">
                <div className="text-yellow-400 text-lg">{t.rating}</div>
              </div>
              
              {/* UPDATED: Testimonial text color */}
              <p className="text-gray-700 dark:text-gray-300 mb-6 transition-colors duration-300">
                {t.text}
              </p>

              <div className="flex items-center space-x-3">
                <div className="w-12 h-12 gradient-primary rounded-full flex items-center justify-center text-white font-bold shrink-0">
                  {t.initials}
                </div>
                <div>
                  <div className="font-semibold text-gray-900 dark:text-white transition-colors duration-300">
                    {t.name}
                  </div>
                  <div className="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-300">
                    {t.plan}
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Section-level Disclaimer */}
        <div className="mt-12 flex justify-center">
          <div className="max-w-3xl text-center bg-white/60 dark:bg-gray-800/60 backdrop-blur rounded-lg px-4 py-3 transition-colors duration-300">
            <p className="text-xs text-gray-500 dark:text-gray-500 leading-relaxed">
              Testimonials reflect individual user experiences and opinions.
              They do not represent investment outcomes or guaranteed results.
            </p>
          </div>
        </div>

      </div>
    </section>
  );
}