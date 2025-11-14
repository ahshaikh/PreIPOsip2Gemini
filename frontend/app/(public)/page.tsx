// V-FINAL-1730-315 (Connected to CMS)
'use client';

import { HeroSection } from '@/components/features/HeroSection';
import { PlanOverview } from '@/components/features/PlanOverview';
import { HowItWorks } from '@/components/features/HowItWorks';
import { ComparisonSection } from '@/components/features/ComparisonSection';
import { BonusFeatures } from '@/components/features/BonusFeatures';
import { Testimonials } from '@/components/features/Testimonials';
import Link from 'next/link';
import api from '@/lib/api';
import { useQuery } from '@tanstack/react-query';

export default function Home() {
  // Fetch CMS content for 'home' page
  const { data: pageData, isLoading } = useQuery({
    queryKey: ['homePageContent'],
    queryFn: async () => {
        try {
            const res = await api.get('/page/home');
            return res.data.content; // This is the JSON we seeded
        } catch (e) {
            return null; // Fallback to default
        }
    },
    staleTime: 60000
  });

  return (
    <div className="flex flex-col">
      {/* Pass CMS data to components */}
      <HeroSection data={pageData?.hero} />
      
      <ComparisonSection />
      <PlanOverview />
      
      <HowItWorks data={pageData?.how_it_works} />
      <ValueProps data={pageData?.value_props} /> {/* Need to import ValueProps if used */}
      
      <BonusFeatures />
      <Testimonials />
      
      {/* CTA Section */}
      <section className="py-20 gradient-primary text-white">
        <div className="max-w-4xl mx-auto px-4 text-center">
            <h2 className="text-5xl font-black mb-6">Ready to Start?</h2>
            <div className="flex justify-center gap-6">
                <Link href="/signup">
                  <button className="bg-white text-purple-600 px-12 py-5 rounded-xl font-bold text-xl hover:shadow-2xl transition transform hover:scale-105">
                      Start Free Now →
                  </button>
                </Link>
            </div>
        </div>
      </section>
    </div>
  );
}

// Helper for ValueProps since I didn't import it above
import { ValueProps } from '@/components/features/ValueProps';