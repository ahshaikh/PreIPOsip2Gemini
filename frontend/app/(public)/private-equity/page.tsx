'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  TrendingUp, Shield, Users, DollarSign, BarChart3, Target,
  CheckCircle2, AlertCircle, Briefcase, Award, Clock, ArrowRight
} from "lucide-react";

const privateEquityFunds = [
  {
    id: 1,
    name: "India Growth Fund III",
    manager: "Sequoia Capital India",
    aum: "₹12,500 Cr",
    minInvestment: "₹50,00,000",
    targetReturn: "25-30% IRR",
    tenure: "5-7 years",
    sectors: ["Technology", "Healthcare", "Fintech"],
    status: "Open for subscription",
    riskLevel: "Medium-High",
  },
  {
    id: 2,
    name: "Mid-Market Opportunities Fund",
    manager: "Chrys Capital",
    aum: "₹8,200 Cr",
    minInvestment: "₹75,00,000",
    targetReturn: "22-28% IRR",
    tenure: "6-8 years",
    sectors: ["Consumer", "Manufacturing", "Services"],
    status: "Open for subscription",
    riskLevel: "Medium",
  },
  {
    id: 3,
    name: "Emerging Leaders Fund II",
    manager: "Multiples Alternate Asset",
    aum: "₹6,800 Cr",
    minInvestment: "₹1,00,00,000",
    targetReturn: "20-25% IRR",
    tenure: "5-6 years",
    sectors: ["BFSI", "Education", "Logistics"],
    status: "Limited slots available",
    riskLevel: "Medium",
  },
];

const benefits = [
  {
    icon: TrendingUp,
    title: "Superior Returns",
    description: "Target returns of 20-30% IRR, significantly higher than traditional investments",
  },
  {
    icon: Shield,
    title: "Professional Management",
    description: "Experienced fund managers with proven track records managing your investments",
  },
  {
    icon: Target,
    title: "Portfolio Diversification",
    description: "Access to diverse companies across sectors reducing concentration risk",
  },
  {
    icon: Users,
    title: "Institutional Quality",
    description: "Co-invest alongside top institutional investors and family offices",
  },
];

const howItWorks = [
  {
    step: 1,
    title: "Choose Your Fund",
    description: "Select from our curated list of SEBI-registered PE funds matching your investment goals",
    icon: Target,
  },
  {
    step: 2,
    title: "Complete KYC",
    description: "Submit required documents and complete accredited investor verification",
    icon: CheckCircle2,
  },
  {
    step: 3,
    title: "Invest & Track",
    description: "Make your investment and track performance through our dashboard",
    icon: BarChart3,
  },
  {
    step: 4,
    title: "Receive Returns",
    description: "Get distributions and exit proceeds as per fund's investment cycle",
    icon: DollarSign,
  },
];

export default function PrivateEquityPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">
            <Briefcase className="h-3 w-3 mr-1" />
            Institutional Grade Investments
          </Badge>
          <h1 className="text-5xl font-bold mb-6">Private Equity Funds</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-8">
            Access exclusive private equity opportunities typically reserved for institutional investors.
            Invest in professionally managed funds targeting 20-30% IRR.
          </p>
          <div className="flex items-center justify-center gap-4">
            <Button size="lg" onClick={() => document.getElementById('funds')?.scrollIntoView({ behavior: 'smooth' })}>
              Explore Funds
              <ArrowRight className="h-4 w-4 ml-2" />
            </Button>
            <Button size="lg" variant="outline">
              Learn More
            </Button>
          </div>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Key Benefits */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Why Private Equity?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Private equity offers unique advantages for sophisticated investors seeking higher returns
              and portfolio diversification.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {benefits.map((benefit, index) => {
              const Icon = benefit.icon;
              return (
                <Card key={index}>
                  <CardContent className="pt-6 text-center">
                    <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 mb-4">
                      <Icon className="h-6 w-6 text-primary" />
                    </div>
                    <h3 className="font-semibold mb-2">{benefit.title}</h3>
                    <p className="text-sm text-muted-foreground">{benefit.description}</p>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* How It Works */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">How It Works</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Simple process to start your private equity investment journey
            </p>
          </div>

          <div className="grid md:grid-cols-4 gap-6">
            {howItWorks.map((item) => {
              const Icon = item.icon;
              return (
                <div key={item.step} className="relative">
                  <Card className="h-full">
                    <CardContent className="pt-6 text-center">
                      <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-foreground mb-4 font-bold">
                        {item.step}
                      </div>
                      <Icon className="h-8 w-8 mx-auto mb-3 text-primary" />
                      <h3 className="font-semibold mb-2">{item.title}</h3>
                      <p className="text-sm text-muted-foreground">{item.description}</p>
                    </CardContent>
                  </Card>
                  {item.step < 4 && (
                    <div className="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2">
                      <ArrowRight className="h-6 w-6 text-muted-foreground" />
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Available Funds */}
        <div id="funds" className="scroll-mt-20 mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Available PE Funds</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              SEBI-registered private equity funds curated for accredited investors
            </p>
          </div>

          <div className="space-y-6">
            {privateEquityFunds.map((fund) => (
              <Card key={fund.id} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="text-2xl mb-2">{fund.name}</CardTitle>
                      <CardDescription className="text-base">
                        Managed by <span className="font-semibold">{fund.manager}</span>
                      </CardDescription>
                    </div>
                    <Badge variant={fund.status === 'Open for subscription' ? 'default' : 'secondary'}>
                      {fund.status}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid md:grid-cols-3 gap-6 mb-6">
                    <div>
                      <p className="text-sm text-muted-foreground mb-1">Assets Under Management</p>
                      <p className="text-2xl font-bold">{fund.aum}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground mb-1">Minimum Investment</p>
                      <p className="text-2xl font-bold">{fund.minInvestment}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground mb-1">Target Return (IRR)</p>
                      <p className="text-2xl font-bold text-green-600">{fund.targetReturn}</p>
                    </div>
                  </div>

                  <div className="grid md:grid-cols-3 gap-4 mb-6">
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4 text-muted-foreground" />
                      <div>
                        <p className="text-xs text-muted-foreground">Investment Tenure</p>
                        <p className="font-semibold">{fund.tenure}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <AlertCircle className="h-4 w-4 text-muted-foreground" />
                      <div>
                        <p className="text-xs text-muted-foreground">Risk Level</p>
                        <p className="font-semibold">{fund.riskLevel}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Target className="h-4 w-4 text-muted-foreground" />
                      <div>
                        <p className="text-xs text-muted-foreground">Focus Sectors</p>
                        <p className="font-semibold">{fund.sectors.length} sectors</p>
                      </div>
                    </div>
                  </div>

                  <div className="mb-6">
                    <p className="text-sm font-medium mb-2">Target Sectors:</p>
                    <div className="flex flex-wrap gap-2">
                      {fund.sectors.map((sector) => (
                        <Badge key={sector} variant="outline">{sector}</Badge>
                      ))}
                    </div>
                  </div>

                  <div className="flex gap-3">
                    <Button disabled={fund.status !== 'Open for subscription'}>
                      {fund.status === 'Open for subscription' ? 'Invest Now' : 'Coming Soon'}
                    </Button>
                    <Button variant="outline">View Details</Button>
                    <Button variant="outline">Download Factsheet</Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Important Information */}
        <Card className="bg-muted/50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertCircle className="h-5 w-5" />
              Important Information
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex items-start gap-2">
              <CheckCircle2 className="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
              <p>
                <strong>Accredited Investors Only:</strong> PE funds are available only to accredited investors as per SEBI regulations. Minimum net worth and income criteria apply.
              </p>
            </div>
            <div className="flex items-start gap-2">
              <CheckCircle2 className="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
              <p>
                <strong>Lock-in Period:</strong> PE investments typically have a lock-in period of 5-8 years. Early exit may not be possible or may attract penalties.
              </p>
            </div>
            <div className="flex items-start gap-2">
              <CheckCircle2 className="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
              <p>
                <strong>Risk Factors:</strong> Private equity investments are subject to market risks, business risks, and liquidity constraints. Past performance is not indicative of future returns.
              </p>
            </div>
            <div className="flex items-start gap-2">
              <CheckCircle2 className="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
              <p>
                <strong>SEBI Registration:</strong> All funds listed are SEBI-registered Category II Alternative Investment Funds (AIF) and comply with applicable regulations.
              </p>
            </div>
          </CardContent>
        </Card>

        {/* CTA */}
        <div className="mt-12 text-center">
          <Card>
            <CardContent className="pt-6">
              <Award className="h-12 w-12 mx-auto mb-4 text-primary" />
              <h3 className="text-2xl font-bold mb-2">Ready to Invest in Private Equity?</h3>
              <p className="text-muted-foreground mb-6">
                Our investment advisors will help you select the right PE fund based on your
                financial goals and risk appetite.
              </p>
              <div className="flex items-center justify-center gap-4">
                <Button size="lg">
                  Schedule a Call
                </Button>
                <Button size="lg" variant="outline">
                  <a href="mailto:pe@preipo-sip.com" className="flex items-center">
                    Contact PE Team
                  </a>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
