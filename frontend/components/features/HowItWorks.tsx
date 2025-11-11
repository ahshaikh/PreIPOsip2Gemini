// V-PHASE4-1730-108
'use client';
import { UserPlus, FileCheck, BarChart } from 'lucide-react';

export function HowItWorks() {
  const steps = [
    {
      icon: <UserPlus className="h-8 w-8 text-primary" />,
      title: '1. Sign Up & Complete KYC',
      description: 'Create your free account and verify your identity in 5 minutes.',
    },
    {
      icon: <FileCheck className="h-8 w-8 text-primary" />,
      title: '2. Choose Plan & Start SIP',
      description: 'Select an investment plan that fits your goals, starting from ₹1,000/month.',
    },
    {
      icon: <BarChart className="h-8 w-8 text-primary" />,
      title: '3. Earn Bonuses & Track Growth',
      description: 'Make monthly payments, earn bonuses, and watch your portfolio grow.',
    },
  ];

  return (
    <section className="py-20">
      <div className="container">
        <h2 className="text-3xl font-bold text-center mb-12">
          Start Investing in 3 Simple Steps
        </h2>
        <div className="grid md:grid-cols-3 gap-8">
          {steps.map((step) => (
            <div key={step.title} className="text-center p-6">
              <div className="flex justify-center mb-4">
                <div className="p-4 bg-primary/10 rounded-full">{step.icon}</div>
              </div>
              <h3 className="text-xl font-semibold mb-2">{step.title}</h3>
              <p className="text-muted-foreground">{step.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}