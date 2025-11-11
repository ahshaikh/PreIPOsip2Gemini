// V-PHASE4-1730-107
'use client';

import { Zap, ShieldCheck, TrendingUp } from 'lucide-react';

export function ValueProps() {
  const features = [
    {
      icon: <Zap className="h-8 w-8 text-primary" />,
      title: 'Zero Fees',
      description: 'Save thousands with zero platform fees and zero exit fees.',
    },
    {
      icon: <TrendingUp className="h-8 w-8 text-primary" />,
      title: '10% Guaranteed Bonuses',
      description: 'Earn a 10% bonus on your investments through our unique model.',
    },
    {
      icon: <ShieldCheck className="h-8 w-8 text-primary" />,
      title: 'Safe & Secure',
      description: 'SEBI compliant processes and bank-grade security for your peace of mind.',
    },
  ];

  return (
    <section className="bg-muted py-20">
      <div className="container">
        <h2 className="text-3xl font-bold text-center mb-12">
          Why Choose PreIPO SIP?
        </h2>
        <div className="grid md:grid-cols-3 gap-8">
          {features.map((feature) => (
            <div key={feature.title} className="bg-background p-6 rounded-lg shadow-sm">
              <div className="mb-4">{feature.icon}</div>
              <h3 className="text-xl font-semibold mb-2">{feature.title}</h3>
              <p className="text-muted-foreground">{feature.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}