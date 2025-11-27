'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Scale, Shield, FileText, CheckCircle2, Building2, Users,
  AlertCircle, ExternalLink, Award, Lock, Eye, BookOpen
} from "lucide-react";

const regulations = [
  {
    title: "SEBI (Issue of Capital and Disclosure Requirements) Regulations, 2018",
    description: "Governs the issuance of securities including IPOs, rights issues, and preferential allotments. Defines disclosure requirements and eligibility criteria for companies.",
    applicability: "IPO Listings & Public Offerings",
  },
  {
    title: "SEBI (Listing Obligations and Disclosure Requirements) Regulations, 2015",
    description: "Mandates ongoing disclosure requirements for listed companies including financial results, corporate governance, and material events.",
    applicability: "Post-Listing Compliance",
  },
  {
    title: "SEBI (Alternative Investment Funds) Regulations, 2012",
    description: "Regulates AIFs including venture capital funds and private equity funds that may invest in pre-IPO companies.",
    applicability: "PE/VC Fund Investments",
  },
  {
    title: "SEBI (Prohibition of Insider Trading) Regulations, 2015",
    description: "Prohibits insider trading and mandates disclosure of shareholding by insiders, protecting market integrity.",
    applicability: "All Market Participants",
  },
];

const complianceAreas = [
  {
    icon: FileText,
    title: "Registration & Licensing",
    points: [
      "Platform operators must be SEBI-registered intermediaries",
      "Compliance with fit and proper criteria for management",
      "Regular renewal of registrations and licenses",
      "Adherence to capital adequacy requirements",
    ],
  },
  {
    icon: Eye,
    title: "Disclosure Requirements",
    points: [
      "Complete disclosure of fees, charges, and commissions",
      "Risk disclosure to investors before transactions",
      "Transparent display of investment terms and conditions",
      "Regular reporting to SEBI on platform activities",
    ],
  },
  {
    icon: Users,
    title: "Investor Protection",
    points: [
      "Segregation of client funds from platform funds",
      "Robust KYC and anti-money laundering procedures",
      "Grievance redressal mechanism as per SEBI norms",
      "Investor education and awareness initiatives",
    ],
  },
  {
    icon: Lock,
    title: "Market Conduct",
    points: [
      "Prohibition of market manipulation and fraudulent practices",
      "Fair and non-discriminatory access to investment opportunities",
      "Prevention of conflicts of interest",
      "Compliance with code of conduct for intermediaries",
    ],
  },
];

const investorProtections = [
  {
    title: "SCORES - SEBI Complaints Redress System",
    description: "Online platform for investors to lodge and track complaints against listed companies and registered intermediaries. SEBI mandates resolution within 30 days.",
    icon: Shield,
  },
  {
    title: "Investor Protection Fund",
    description: "SEBI-mandated fund to compensate investors for valid claims in case of intermediary default or fraud, subject to specified limits and conditions.",
    icon: Award,
  },
  {
    title: "Audit Trail Requirements",
    description: "All transactions and communications must be recorded and maintained for specified periods, ensuring accountability and enabling dispute resolution.",
    icon: FileText,
  },
  {
    title: "Inspection & Enforcement",
    description: "SEBI conducts regular inspections of intermediaries and has powers to impose penalties, suspend licenses, or initiate prosecution for violations.",
    icon: Scale,
  },
];

const keyDefinitions = [
  {
    term: "Qualified Institutional Buyers (QIBs)",
    definition: "Institutional investors like mutual funds, insurance companies, banks, and FPIs who participate in IPO allocations.",
  },
  {
    term: "Anchor Investors",
    definition: "QIBs who apply for shares one day before the IPO opens, providing stability and price discovery.",
  },
  {
    term: "Lock-in Period",
    definition: "Time period during which shares acquired by certain investors cannot be sold or transferred, ranging from 6 months to 3 years.",
  },
  {
    term: "Minimum Promoter Contribution",
    definition: "Minimum percentage of post-issue capital that must be held by promoters (generally 20% for 3 years).",
  },
];

export default function SEBIRegulationsPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">
            <Scale className="h-3 w-3 mr-1" />
            Regulatory Framework
          </Badge>
          <h1 className="text-5xl font-bold mb-6">SEBI Regulations & Compliance</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            Understanding the regulatory framework that governs pre-IPO investments and protects
            investor interests in the Indian securities market.
          </p>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* About SEBI */}
        <Card className="mb-16 bg-gradient-to-r from-primary/5 to-primary/10">
          <CardContent className="pt-8">
            <div className="flex flex-col md:flex-row items-start gap-6">
              <div className="flex-shrink-0">
                <Building2 className="h-20 w-20 text-primary" />
              </div>
              <div>
                <h2 className="text-2xl font-bold mb-4">About SEBI</h2>
                <p className="text-muted-foreground mb-4">
                  The Securities and Exchange Board of India (SEBI) is the regulatory authority
                  responsible for regulating the securities market in India. Established in 1992,
                  SEBI's primary objectives are to protect investor interests, promote orderly
                  development of securities markets, and regulate market intermediaries.
                </p>
                <div className="grid md:grid-cols-3 gap-4 mt-6">
                  <div>
                    <p className="text-sm text-muted-foreground">Established</p>
                    <p className="font-semibold">April 12, 1992</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Headquarters</p>
                    <p className="font-semibold">Mumbai, India</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Jurisdiction</p>
                    <p className="font-semibold">Indian Securities Market</p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Key Regulations */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Key SEBI Regulations</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Regulatory frameworks governing pre-IPO investments and securities market operations
            </p>
          </div>

          <div className="space-y-6">
            {regulations.map((reg, index) => (
              <Card key={index} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <Badge variant="secondary" className="mb-2">{reg.applicability}</Badge>
                      <CardTitle className="text-xl mb-2">{reg.title}</CardTitle>
                      <CardDescription className="text-base">{reg.description}</CardDescription>
                    </div>
                    <Button variant="ghost" size="sm">
                      <ExternalLink className="h-4 w-4" />
                    </Button>
                  </div>
                </CardHeader>
              </Card>
            ))}
          </div>
        </div>

        {/* Compliance Areas */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Compliance Framework</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Key compliance areas that PreIPO SIP adheres to as a SEBI-regulated platform
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {complianceAreas.map((area, index) => {
              const Icon = area.icon;
              return (
                <Card key={index}>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-3">
                      <div className="p-2 bg-primary/10 rounded-lg">
                        <Icon className="h-5 w-5 text-primary" />
                      </div>
                      {area.title}
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <ul className="space-y-2">
                      {area.points.map((point, i) => (
                        <li key={i} className="flex items-start gap-2">
                          <CheckCircle2 className="h-4 w-4 text-green-500 flex-shrink-0 mt-0.5" />
                          <span className="text-sm text-muted-foreground">{point}</span>
                        </li>
                      ))}
                    </ul>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Investor Protections */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">SEBI Investor Protection Mechanisms</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Safeguards established by SEBI to protect investor interests
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {investorProtections.map((protection, index) => {
              const Icon = protection.icon;
              return (
                <Card key={index} className="hover:shadow-lg transition-shadow">
                  <CardContent className="pt-6">
                    <div className="flex items-start gap-4">
                      <div className="flex-shrink-0">
                        <div className="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-green-500/10">
                          <Icon className="h-6 w-6 text-green-500" />
                        </div>
                      </div>
                      <div>
                        <h3 className="font-semibold mb-2">{protection.title}</h3>
                        <p className="text-sm text-muted-foreground">{protection.description}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Key Definitions */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Key Regulatory Definitions</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Important terms and definitions as per SEBI regulations
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {keyDefinitions.map((item, index) => (
              <Card key={index}>
                <CardHeader>
                  <CardTitle className="text-lg flex items-start gap-2">
                    <BookOpen className="h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
                    {item.term}
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground">{item.definition}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Our Commitment */}
        <Card className="mb-8 bg-primary/5">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Shield className="h-5 w-5" />
              Our Commitment to Compliance
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm">
              PreIPO SIP is committed to operating in full compliance with all applicable SEBI
              regulations and guidelines. We maintain the highest standards of corporate governance,
              transparency, and investor protection.
            </p>
            <ul className="space-y-2">
              {[
                "Regular compliance audits and internal reviews",
                "Ongoing training for team members on regulatory requirements",
                "Proactive engagement with SEBI and industry bodies",
                "Prompt implementation of regulatory changes and circulars",
                "Transparent reporting and disclosure practices",
              ].map((item, i) => (
                <li key={i} className="flex items-start gap-2">
                  <CheckCircle2 className="h-4 w-4 text-primary flex-shrink-0 mt-0.5" />
                  <span className="text-sm">{item}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>

        {/* Important Links */}
        <Card>
          <CardHeader>
            <CardTitle>Important Regulatory Links</CardTitle>
            <CardDescription>
              Official SEBI resources for investors and market participants
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid md:grid-cols-2 gap-4">
              <Button variant="outline" className="justify-start" asChild>
                <a href="https://www.sebi.gov.in" target="_blank" rel="noopener noreferrer">
                  <ExternalLink className="h-4 w-4 mr-2" />
                  SEBI Official Website
                </a>
              </Button>
              <Button variant="outline" className="justify-start" asChild>
                <a href="https://scores.gov.in" target="_blank" rel="noopener noreferrer">
                  <ExternalLink className="h-4 w-4 mr-2" />
                  SCORES - Complaint Portal
                </a>
              </Button>
              <Button variant="outline" className="justify-start" asChild>
                <a href="https://www.sebi.gov.in/legal/regulations.html" target="_blank" rel="noopener noreferrer">
                  <ExternalLink className="h-4 w-4 mr-2" />
                  SEBI Regulations
                </a>
              </Button>
              <Button variant="outline" className="justify-start" asChild>
                <a href="https://www.investor.sebi.gov.in" target="_blank" rel="noopener noreferrer">
                  <ExternalLink className="h-4 w-4 mr-2" />
                  SEBI Investor Education
                </a>
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Disclaimer */}
        <Card className="mt-8 border-primary/50">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <AlertCircle className="h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
              <div className="space-y-2 text-sm">
                <p>
                  <strong>Disclaimer:</strong> This page provides general information about SEBI
                  regulations for educational purposes. It should not be construed as legal or
                  investment advice. Investors should refer to the official SEBI website and
                  consult with qualified advisors for specific guidance.
                </p>
                <p className="text-muted-foreground">
                  The regulatory framework is subject to changes. PreIPO SIP will update this
                  information periodically, but investors should verify current regulations from
                  official sources.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
