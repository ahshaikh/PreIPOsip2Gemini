// V-FINAL-1730-198 (VISUAL UPGRADE)
'use client';

import { usePlans } from '@/lib/hooks';
import { Button } from '@/components/ui/button';
import Link from 'next/link';
import { Check, Sprout, Gem, Rocket, Crown } from 'lucide-react';

export function PlanOverview() {
  const { data: plans, isLoading } = usePlans();

  const getIcon = (index: number) => {
    switch(index) {
      case 0: return <Sprout className="w-10 h-10 mb-2 text-green-500" />;
      case 1: return <Gem className="w-10 h-10 mb-2 text-blue-500" />;
      case 2: return <Rocket className="w-10 h-10 mb-2 text-purple-500" />;
      case 3: return <Crown className="w-10 h-10 mb-2 text-yellow-500" />;
      default: return <Sprout className="w-10 h-10 mb-2" />;
    }
  };

  if (isLoading) return <div className="py-20 text-center">Loading Plans...</div>;

  const featuredPlans = plans?.slice(0, 4) || [];

  return (
    <section id="plans" className="py-20 bg-gradient-to-br from-purple-50 to-blue-50">
      <div className="container mx-auto px-4">
        <div className="text-center mb-16">
          <h2 className="text-4xl lg:text-5xl font-black text-gray-900 mb-4">
            Choose Your <span className="text-gradient">Investment Plan</span>
          </h2>
          <p className="text-xl text-gray-600">All plans come with 10% guaranteed bonuses + ZERO fees!</p>
        </div>

        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {featuredPlans.map((plan, index) => {
            const isPopular = index === 1; // Assume 2nd plan is popular
            
            return (
              <div 
                key={plan.id} 
                className={`bg-white rounded-2xl p-6 shadow-lg hover-scale border-2 transition flex flex-col relative
                  ${isPopular ? 'border-purple-500 transform scale-105 z-10' : 'border-transparent hover:border-purple-300'}
                `}
              >
                {isPopular && (
                  <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-2 rounded-full font-bold text-sm shadow-md">
                    🔥 MOST POPULAR
                  </div>
                )}

                <div className="text-center mb-6 mt-2">
                  <div className="flex justify-center">{getIcon(index)}</div>
                  <h3 className="text-2xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                  <div className="text-4xl font-black text-gray-900 mb-1">₹{parseInt(plan.monthly_amount).toLocaleString()}</div>
                  <div className="text-sm text-gray-500">per month</div>
                </div>

                <div className="space-y-3 mb-6 flex-grow">
                  {plan.features.slice(0, 5).map((feature: any) => (
                    <div key={feature.id} className="flex items-start space-x-2">
                      <Check className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                      <span className="text-sm text-gray-700">{feature.feature_text}</span>
                    </div>
                  ))}
                </div>

                <div className={`rounded-lg p-4 mb-4 ${isPopular ? 'bg-purple-100' : 'bg-purple-50'}`}>
                  <div className="text-xs text-gray-600 mb-1">Total Value (36 months)</div>
                  <div className="text-2xl font-bold text-purple-600">
                    ₹{(parseInt(plan.monthly_amount) * 36 * 1.2).toLocaleString()}+
                  </div>
                  <div className="text-xs text-gray-600">on ₹{(parseInt(plan.monthly_amount) * 36).toLocaleString()} investment</div>
                </div>

                <Link href="/signup" className="w-full">
                  <button className={`w-full py-3 rounded-lg font-bold transition ${isPopular ? 'gradient-primary text-white hover:shadow-xl' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'}`}>
                    Choose Plan →
                  </button>
                </Link>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}