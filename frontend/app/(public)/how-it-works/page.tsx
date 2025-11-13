// V-FINAL-1730-182
import { HowItWorks } from '@/components/features/HowItWorks';

export default function HowItWorksPage() {
  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold text-center mb-8">How It Works</h1>
      <p className="text-xl text-muted-foreground text-center max-w-2xl mx-auto mb-12">
        We've simplified the pre-IPO investment process into three easy steps.
      </p>
      
      {/* Reuse the component we already built */}
      <HowItWorks />
      
      <div className="mt-20 bg-muted p-8 rounded-lg">
        <h2 className="text-2xl font-bold mb-4">Frequently Asked Questions</h2>
        <div className="space-y-4">
          <div>
            <h3 className="font-semibold">Is KYC mandatory?</h3>
            <p className="text-muted-foreground">Yes, as per SEBI regulations, KYC is mandatory for all investments.</p>
          </div>
          <div>
            <h3 className="font-semibold">When are bonuses credited?</h3>
            <p className="text-muted-foreground">Progressive bonuses are calculated monthly. Milestone bonuses are credited upon completing 12, 24, or 36 months.</p>
          </div>
        </div>
      </div>
    </div>
  );
}