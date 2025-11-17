// V-FINAL-1730-183
'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Slider } from "@/components/ui/slider";

export default function ReturnsCalculator() {
  const [monthly, setMonthly] = useState(5000);
  const [months, setMonths] = useState(36);
  const [referrals, setReferrals] = useState(0);

  const totalInvested = monthly * months;
  const baseBonus = totalInvested * 0.10;
  const referralBonus = baseBonus * (referrals * 0.1);
  const totalValue = totalInvested + baseBonus + referralBonus;

  return (
    <section id="calculator" className="py-20 px-4">
      <div className="grid md:grid-cols-2 gap-8">
        <Card>
          <CardHeader>
            <CardTitle>Investment Inputs</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="space-y-2">
              <Label>Monthly Investment (₹{monthly})</Label>
              <Slider 
                value={[monthly]} 
                onValueChange={(v) => setMonthly(v[0])} 
                max={50000} 
                step={1000} 
                min={1000} 
              />
            </div>
            <div className="space-y-2">
              <Label>Duration ({months} Months)</Label>
              <Slider 
                value={[months]} 
                onValueChange={(v) => setMonths(v[0])} 
                max={60} 
                step={12} 
                min={12} 
              />
            </div>
            <div className="space-y-2">
              <Label>Referrals ({referrals})</Label>
              <Slider 
                value={[referrals]} 
                onValueChange={(v) => setReferrals(v[0])} 
                max={20} 
                step={1} 
              />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-primary text-primary-foreground">
          <CardHeader>
            <CardTitle className="text-primary-foreground">Projected Returns</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex justify-between text-lg">
              <span>Total Invested:</span>
              <span className="font-bold">₹{totalInvested.toLocaleString("en-IN")}</span>
            </div>
            <div className="flex justify-between text-lg">
              <span>Guaranteed Bonus:</span>
              <span className="font-bold text-green-300">
                +₹{(baseBonus + referralBonus).toLocaleString("en-IN")}
              </span>
            </div>
            <div className="pt-4 border-t border-primary-foreground/20">
              <div className="flex justify-between text-2xl font-bold">
                <span>Total Value:</span>
                <span>₹{totalValue.toLocaleString("en-IN")}</span>
              </div>
              <p className="text-xs mt-2 opacity-80 text-right">*Projections only. T&C apply.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </section>
  );
}
