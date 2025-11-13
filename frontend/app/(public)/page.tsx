// V-FINAL-1730-201 (VISUAL UPGRADE)
import { HeroSection } from '@/components/features/HeroSection';
import { PlanOverview } from '@/components/features/PlanOverview';
import { HowItWorks } from '@/components/features/HowItWorks';
import { ComparisonSection } from '@/components/features/ComparisonSection';
import { BonusFeatures } from '@/components/features/BonusFeatures';
import { Testimonials } from '@/components/features/Testimonials';
import Link from 'next/link';

export default function Home() {
  return (
    <div className="flex flex-col">
      <HeroSection />
      <ComparisonSection />
      <PlanOverview />
      <HowItWorks />
      <BonusFeatures />
      <Testimonials />
      
      {/* CTA Section at Bottom */}
      <section className="py-20 gradient-primary text-white">
        <div className="max-w-4xl mx-auto px-4 text-center">
            <h2 className="text-5xl font-black mb-6">Ready to Start Your<br/>Investment Journey?</h2>
            <p className="text-2xl text-purple-100 mb-12">Join 5,000+ investors earning bonuses with ZERO fees!</p>
            
            <div className="flex flex-col sm:flex-row gap-6 justify-center">
                <Link href="/signup">
                  <button className="bg-white text-purple-600 px-12 py-5 rounded-xl font-bold text-xl hover:shadow-2xl transition transform hover:scale-105">
                      Start Free Now →
                  </button>
                </Link>
                <Link href="/contact">
                  <button className="border-2 border-white text-white px-12 py-5 rounded-xl font-bold text-xl hover:bg-white/10 transition">
                      Talk to Expert
                  </button>
                </Link>
            </div>

            <div className="mt-12 flex items-center justify-center space-x-8 text-purple-100">
                <div className="flex items-center space-x-2">
                    <span className="text-xl">💳</span>
                    <span>No credit card required</span>
                </div>
                <div className="flex items-center space-x-2">
                    <span className="text-xl">🔓</span>
                    <span>Free forever</span>
                </div>
            </div>
        </div>
      </section>
    </div>
  );
}