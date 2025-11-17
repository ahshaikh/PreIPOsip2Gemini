// V-FINAL-1730-315 (Connected to CMS)
// C:\PreIPOsip\frontend\app\page.tsx

import Navbar from "@/components/shared/Navbar";
import Footer from "@/components/shared/Footer";

import Hero from "@/components/features/HeroSection";
import Comparison from "@/components/features/ComparisonSection";
import Plans from "@/components/features/PlanOverview";
import HowItWorks from "@/components/features/HowItWorks";
import Bonuses from "@/components/features/BonusFeatures";
import Calculator from "@/components/features/ReturnsCalculator";
import Testimonials from "@/components/features/Testimonials";

export default function Page() {
  return (
    <div className="bg-white">
      <Navbar />

      {/* Sections */}
      <Hero />
      <Comparison />
      <Plans />
      <HowItWorks />
      <Bonuses />
      <Calculator />
      <Testimonials />


    </div>
  );
}

