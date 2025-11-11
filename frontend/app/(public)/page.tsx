// V-PHASE4-1730-105
import { HeroSection } from '@/components/features/HeroSection';
import { PlanOverview } from '@/components/features/PlanOverview';
import { HowItWorks } from '@/components/features/HowItWorks';
import { ValueProps } from '@/components/features/ValueProps';

// Homepage
export default function Home() {
  return (
    <div>
      <HeroSection />
      <ValueProps />
      <HowItWorks />
      <PlanOverview />
      {/* Other homepage sections (Testimonials, etc.) would go here */}
    </div>
  );
}