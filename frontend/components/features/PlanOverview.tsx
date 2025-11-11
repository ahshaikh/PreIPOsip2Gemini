// V-PHASE4-1730-109
'use client';

import { usePlans } from '@/lib/hooks';
import { Button } from '@/components/ui/button';
import Link from 'next/link';

export function PlanOverview() {
  const { data: plans, isLoading, error } = usePlans();

  if (isLoading) return <div>Loading plans...</div>;
  if (error) return <div>Failed to load plans.</div>;

  // Show only 4 featured plans on the homepage
  const featuredPlans = plans?.slice(0, 4) || [];

  return (
    <section className="bg-muted py-20">
      <div className="container">
        <h2 className="text-3xl font-bold text-center mb-12">
          Choose Your Investment Plan
        </h2>
        <div className="grid md:grid-cols-4 gap-6">
          {featuredPlans.map((plan) => (
            <div key={plan.id} className="bg-background p-6 rounded-lg shadow-sm border flex flex-col">
              <h3 className="text-2xl font-semibold mb-2">{plan.name}</h3>
              <p className="text-3xl font-bold mb-4">
                ₹{plan.monthly_amount}<span className="text-base font-normal text-muted-foreground">/month</span>
              </p>
              <p className="text-muted-foreground mb-4 h-20">{plan.description}</p>
              <ul className="space-y-2 mb-6">
                {plan.features.slice(0, 3).map((feature) => (
                  <li key={feature.id} className="flex items-center text-sm">
                    <svg className="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"></path></svg>
                    {feature.feature_text}
                  </li>
                ))}
              </ul>
              <Button className="w-full mt-auto" asChild>
                <Link href={`/plans#${plan.slug}`}>View Details</Link>
              </Button>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}