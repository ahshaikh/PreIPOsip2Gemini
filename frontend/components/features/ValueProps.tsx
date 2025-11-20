// V-PHASE4-1730-107 (Created) | V-FINAL-1730-313 (Dynamic)
'use client';

import { Zap, ShieldCheck, TrendingUp } from 'lucide-react';

export function ValueProps({ data }: { data?: any }) {
  const defaults = [
    { title: 'Zero Fees', desc: 'Save thousands with zero platform fees and zero exit fees.' },
    { title: '10% Guaranteed Bonuses', desc: 'Earn a 10% bonus on your investments through our unique model.' },
    { title: 'Safe & Secure', desc: 'SEBI compliant processes and bank-grade security for your peace of mind.' },
  ];

  const items = data?.items || defaults;
  const title = data?.title || "Why Choose PreIPO SIP?";

  // Helper to map index to icon
  const getIcon = (i: number) => {
      if (i === 0) return <Zap className="h-8 w-8 text-primary" />;
      if (i === 1) return <TrendingUp className="h-8 w-8 text-primary" />;
      return <ShieldCheck className="h-8 w-8 text-primary" />;
  };

  return (
    <section className="bg-muted py-20">
      <div className="container mx-auto px-4">
        <h2 className="text-3xl font-bold text-center mb-12">{title}</h2>
        <div className="grid md:grid-cols-3 gap-8">
          {items.map((feature: any, i: number) => (
            <div key={i} className="bg-background p-6 rounded-lg shadow-sm">
              <div className="mb-4">{getIcon(i)}</div>
              <h3 className="text-xl font-semibold mb-2">{feature.title}</h3>
              <p className="text-muted-foreground">{feature.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}