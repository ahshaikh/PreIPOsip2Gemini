// V-FINAL-1730-184
import { ReturnsCalculator } from '@/components/features/ReturnsCalculator';

export default function CalculatorPage() {
  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold text-center mb-4">Returns Calculator</h1>
      <p className="text-xl text-muted-foreground text-center mb-12">
        See how your money grows with our zero-fee SIPs and bonuses.
      </p>
      <ReturnsCalculator />
    </div>
  );
}