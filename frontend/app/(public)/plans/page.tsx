// V-PHASE4-1730-110 (Created) | V-REMEDIATE-1730-167 | V-FINAL-1730-628 (Pending Plan Link)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Check } from "lucide-react";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import Link from 'next/link'; // <-- IMPORT LINK

export default function PlansPage() {

  const { data: plans, isLoading } = useQuery({
    queryKey: ['publicPlans'],
    queryFn: async () => (await api.get('/plans')).data,
    staleTime: 1000 * 60 * 5 // 5 minutes
  });

  if (isLoading) return <div>Loading plans...</div>;

  return (
    <div className="container py-20">
      <div className="text-center max-w-3xl mx-auto mb-16">
        <h1 className="text-4xl md:text-6xl font-black text-gray-900 mb-4">
          Find Your Perfect <span className="text-primary">Investment Plan</span>
        </h1>
        <p className="text-xl text-muted-foreground">
          All plans are 100% free. No platform fees, no exit fees.
          Your investment, your profit, plus our bonuses.
        </p>
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        {plans?.map((plan: any) => (
          <Card 
            key={plan.id} 
            className={`flex flex-col ${plan.is_featured ? 'border-2 border-primary shadow-2xl' : 'shadow-lg'}`}
          >
            {plan.is_featured && (
              <Badge className="w-fit self-center -mt-3 z-10">Most Popular</Badge>
            )}
            <CardHeader className="text-center">
              <CardTitle className="text-3xl font-bold mb-2">{plan.name}</CardTitle>
              <p className="text-4xl font-black text-primary">
                ₹{plan.monthly_amount.toLocaleString('en-IN')}
                <span className="text-sm font-medium text-muted-foreground">/month</span>
              </p>
              <CardDescription>{plan.description}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col flex-grow">
              <ul className="space-y-3 mb-6 flex-grow">
                {plan.features.map((feature: any) => (
                  <li key={feature.id} className="flex items-start">
                    <Check className="h-5 w-5 text-green-500 mr-2 shrink-0" />
                    <span className="text-sm text-muted-foreground">{feature.feature_text}</span>
                  </li>
                ))}
              </ul>
              
              {/* --- THIS IS THE CHANGE --- */}
              <Button asChild className="w-full" size="lg">
                <Link href={`/signup?plan=${plan.slug}`}>
                  Choose Plan
                </Link>
              </Button>
              {/* ------------------------- */}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}