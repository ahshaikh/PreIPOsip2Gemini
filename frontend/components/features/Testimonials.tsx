// V-FINAL-1730-199 (VISUAL UPGRADE)
'use client';

export function Testimonials() {
  return (
    <section className="py-20 bg-white">
      <div className="container mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl font-black text-gray-900 mb-4">What Our <span className="text-gradient">Investors Say</span></h2>
          <p className="text-xl text-gray-600">Join 5,000+ happy investors</p>
        </div>

        <div className="grid md:grid-cols-3 gap-8">
          <div className="bg-gray-50 rounded-2xl p-8 border border-gray-100">
            <div className="flex items-center mb-4 text-yellow-400 text-xl">★★★★★</div>
            <p className="text-gray-700 mb-6 italic">"Finally, a platform that gives bonuses instead of taking fees! I've earned ₹12,000 in bonuses in just 12 months. Highly recommend!"</p>
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 gradient-primary rounded-full flex items-center justify-center text-white font-bold">RK</div>
              <div>
                <div className="font-bold text-gray-900">Rajesh Kumar</div>
                <div className="text-sm text-gray-500">Wealth Builder Plan</div>
              </div>
            </div>
          </div>

          <div className="bg-gray-50 rounded-2xl p-8 border border-gray-100">
            <div className="flex items-center mb-4 text-yellow-400 text-xl">★★★★★</div>
            <p className="text-gray-700 mb-6 italic">"Zero fees is amazing! I saved ₹54,000 that other platforms would have charged. My portfolio is up 60% with Swiggy listing!"</p>
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 gradient-secondary rounded-full flex items-center justify-center text-white font-bold">PS</div>
              <div>
                <div className="font-bold text-gray-900">Priya Sharma</div>
                <div className="text-sm text-gray-500">Growth Accelerator</div>
              </div>
            </div>
          </div>

          <div className="bg-gray-50 rounded-2xl p-8 border border-gray-100">
            <div className="flex items-center mb-4 text-yellow-400 text-xl">★★★★★</div>
            <p className="text-gray-700 mb-6 italic">"The referral multiplier is genius! I referred 5 friends and now get 2× all bonuses. Making ₹72,000 extra over 3 years!"</p>
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-300 rounded-full flex items-center justify-center text-white font-bold">AM</div>
              <div>
                <div className="font-bold text-gray-900">Amit Mehta</div>
                <div className="text-sm text-gray-500">Wealth Builder Plan</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}