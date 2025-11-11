// V-PHASE4-1730-110
'use client';

import { usePlans } from '@/lib/hooks';
import { Button } from '@/components/ui/button';
import Link from 'next/link';

// Full Plans Page
export default function PlansPage() {
  const { data: plans, isLoading, error } = usePlans();

  if (isLoading) return <div className="container py-12">Loading plans...</div>;
  if (error) return <div className="container py-12">Failed to load plans.</div>;

  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold text-center mb-4">
        Choose Your Investment Plan
      </h1>
      <p className="text-xl text-muted-foreground text-center mb-12">
        All plans include zero fees, guaranteed bonuses, and full transparency.
      </p>
      
      {/* TODO: Implement the full comparison table */}
      <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        {plans?.map((plan) => (
          <div key={plan.id} id={plan.slug} className="bg-card border p-6 rounded-lg shadow-sm flex flex-col">
            <h3 className="text-2xl font-semibold mb-2">{plan.name}</h3>
            <p className="text-3xl font-bold mb-4">
              ₹{plan.monthly_amount}<span className="text-base font-normal text-muted-foreground">/month</span>
            </p>
            <p className="text-muted-foreground mb-4 h-20">{plan.description}</p>
            <ul className="space-y-2 mb-6">
              {plan.features.map((feature) => (
                <li key={feature.id} className="flex items-center text-sm">
                  <svg className="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"></path></svg>
                  {feature.feature_text}
                </li>
              ))}
            </ul>
            <Button className="w-full mt-auto" asChild>
              <Link href="/signup">Subscribe Now</Link>
            </Button>
          </div>
        ))}
      </div>
    </div>
  );
}