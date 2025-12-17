'use client';

import { useState } from 'react';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Slider } from "@/components/ui/slider";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";

export default function ReturnsCalculator() {
  const [monthly, setMonthly] = useState(5000);
  const [months, setMonths] = useState(36);
  const [referrals, setReferrals] = useState(0);

  // Existing logic (unchanged)
  const totalInvested = monthly * months;
  const baseBonus = totalInvested * 0.10;
  const referralBonus = baseBonus * (referrals * 0.1);
  const totalValue = totalInvested + baseBonus + referralBonus;

  return (
    <section id="calculator" className="py-20 px-4">
      <div className="max-w-6xl mx-auto space-y-10">

        {/* Section Heading */}
        <div className="text-center max-w-3xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold text-gray-900">
            An Illustrative Participation & Incentive Scenario Calculator
          </h2>
          <p className="mt-3 text-gray-600 text-base md:text-lg">
            Explore how SIP amounts, durations, and optional referrals may
            interact under current platform incentive structures.
          </p>
        </div>

        {/* Calculator Grid */}
        <div className="grid md:grid-cols-2 gap-8">

          {/* Inputs */}
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

          {/* Output */}
          <Card className="bg-primary text-primary-foreground">
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="text-primary-foreground">
                Illustrative Scenario Summary
              </CardTitle>

              {/* 🔹 HOW THIS IS CALCULATED MODAL TRIGGER */}
              <Dialog>
                <DialogTrigger asChild>
                  <button className="text-xs underline text-primary-foreground/80 hover:text-white transition">
                    ⓘ How this is calculated
                  </button>
                </DialogTrigger>

                {/* 🔹 MODAL CONTENT */}
                <DialogContent className="max-w-lg">
                  <DialogHeader>
                    <DialogTitle>
                      How this scenario is calculated
                    </DialogTitle>
                  </DialogHeader>

                  <div className="space-y-4 text-sm text-gray-600 leading-relaxed">
                    <p>
                      This tool provides an <strong>illustrative example</strong> based
                      on the values you select. It is designed to help you
                      understand participation mechanics — not to predict or
                      guarantee outcomes.
                    </p>

                    <p>
                      <strong>SIP Contribution:</strong> Calculated by multiplying
                      your selected monthly amount by the chosen duration.
                    </p>

                    <p>
                      <strong>Rewards & Incentives:</strong> Shown here are
                      illustrative estimates based on current platform programs.
                      These are <em>non-investment incentives</em> and may change
                      over time.
                    </p>

                    <p>
                      <strong>Referrals:</strong> Referral-based incentives are
                      optional, non-investment rewards and are not linked to
                      portfolio performance.
                    </p>

                    <p className="text-xs text-gray-500">
                      This calculation does not represent investment returns or
                      expected profits. Actual outcomes depend on company
                      performance, eligibility criteria, and applicable program
                      terms.
                    </p>
                  </div>
                </DialogContent>
              </Dialog>
            </CardHeader>

            <CardContent className="space-y-4">
              <div className="flex justify-between text-lg">
                <span>Total SIP Contribution:</span>
                <span className="font-bold">
                  ₹{totalInvested.toLocaleString("en-IN")}
                </span>
              </div>

              <div className="flex justify-between text-lg">
                <span>Illustrative Rewards & Incentives*:</span>
                <span className="font-bold text-green-300">
                  +₹{(baseBonus + referralBonus).toLocaleString("en-IN")}
                </span>
              </div>

              <div className="pt-4 border-t border-primary-foreground/20">
                <div className="flex justify-between text-xl font-semibold">
                  <span>Illustrative Breakdown</span>
                  <span>₹{totalValue.toLocaleString("en-IN")}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Disclaimer */}
        <div className="max-w-4xl mx-auto text-center">
          <p className="text-xs text-gray-500 leading-relaxed">
            This calculator provides illustrative examples based on user inputs
            and current platform incentive structures. It does not represent
            investment returns or guaranteed outcomes. Rewards and incentives,
            if any, are subject to eligibility criteria, program terms, and may
            change over time.
          </p>
        </div>
      </div>
    </section>
  );
}
