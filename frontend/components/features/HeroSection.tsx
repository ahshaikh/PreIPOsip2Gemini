// V-PHASE4-1730-106
'use client';

import Link from 'next/link';
import { Button } from '@/components/ui/button';

export function HeroSection() {
  return (
    <section className="container py-20 md:py-32">
      <div className="max-w-3xl mx-auto text-center">
        <h1 className="text-4xl md:text-6xl font-bold tracking-tighter mb-6">
          India's First 100% FREE Pre-IPO SIP Platform
        </h1>
        <p className="text-lg md:text-xl text-muted-foreground mb-8">
          10% Guaranteed Bonuses · Zero Fees · Zero Hidden Charges
        </p>
        <div className="flex justify-center gap-4">
          <Button size="lg" asChild>
            <Link href="/signup">Start Investing Free</Link>
          </Button>
          <Button size="lg" variant="outline" asChild>
            <Link href="/calculator">Calculate Your Returns</Link>
          </Button>
        </div>
      </div>
    </section>
  );
}