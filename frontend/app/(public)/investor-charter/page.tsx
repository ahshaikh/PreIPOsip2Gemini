'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Shield, CheckCircle2, Scale, Eye, UserCheck, Clock,
  FileText, AlertCircle, Lock, TrendingUp, Award
} from "lucide-react";

const investorRights = [
  {
    icon: FileText,
    title: "Right to Information",
    description: "Receive complete and accurate information about investment products, risks, fees, and terms before making any investment decision.",
  },
  {
    icon: Eye,
    title: "Right to Transparency",
    description: "Access to clear, transparent information about all charges, portfolio holdings, performance metrics, and NAV calculations.",
  },
  {
    icon: Scale,
    title: "Right to Fair Treatment",
    description: "Be treated fairly and equitably without any discrimination. Equal opportunity to participate in all investment offerings.",
  },
  {
    icon: UserCheck,
    title: "Right to Suitability",
    description: "Receive investment advice and products that are suitable for your financial situation, risk profile, and investment objectives.",
  },
  {
    icon: Lock,
    title: "Right to Privacy",
    description: "Your personal and financial information will be kept confidential and used only for legitimate purposes as per data protection laws.",
  },
  {
    icon: Shield,
    title: "Right to Grievance Redressal",
    description: "Access to a robust grievance redressal mechanism with defined timelines for resolution of complaints.",
  },
];

const investorResponsibilities = [
  {
    icon: FileText,
    title: "Provide Complete Information",
    description: "Furnish accurate and complete information during registration, KYC, and investment process. Update information when changes occur.",
  },
  {
    icon: Eye,
    title: "Read and Understand",
    description: "Carefully read all documents including offer documents, terms & conditions, risk disclosures before investing. Seek clarification if needed.",
  },
  {
    icon: AlertCircle,
    title: "Understand Risks",
    description: "Be aware that investments are subject to market risks. Understand the specific risks associated with each investment product.",
  },
  {
    icon: UserCheck,
    title: "Timely Updates",
    description: "Keep your contact details, bank account, and other information updated. Respond promptly to communications from the platform.",
  },
  {
    icon: Clock,
    title: "Monitor Investments",
    description: "Regularly review your portfolio, track performance, and stay informed about your investments. Report any discrepancies immediately.",
  },
  {
    icon: Shield,
    title: "Maintain Security",
    description: "Keep login credentials confidential. Do not share OTPs, passwords, or account access with anyone. Report suspicious activities.",
  },
];

const commitments = [
  {
    title: "Transparent Pricing",
    description: "All fees and charges will be clearly disclosed upfront. No hidden costs or surprise charges.",
  },
  {
    title: "Timely Communications",
    description: "Regular updates on investments, performance, corporate actions, and other material information.",
  },
  {
    title: "Professional Standards",
    description: "Maintain highest professional and ethical standards in all dealings. SEBI-registered intermediaries only.",
  },
  {
    title: "Quick Grievance Resolution",
    description: "Acknowledge complaints within 24 hours. Resolve grievances within stipulated timelines.",
  },
  {
    title: "Data Protection",
    description: "Implement robust security measures to protect investor data. Comply with all data privacy regulations.",
  },
  {
    title: "Investor Education",
    description: "Provide educational resources, tools, and calculators to help investors make informed decisions.",
  },
];

export default function InvestorCharterPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 pt-20 pb-12">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">
            <Award className="h-3 w-3 mr-1" />
            Our Commitment to You
          </Badge>
          <h1 className="text-5xl font-bold mb-6">Investor Charter</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            A comprehensive framework outlining your rights, our responsibilities, and mutual commitments
            to ensure a transparent and fair investment experience.
          </p>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Vision Statement */}
        <Card className="mb-16 bg-gradient-to-r from-primary/5 to-primary/10">
          <CardContent className="pt-8">
            <div className="text-center">
              <Shield className="h-16 w-16 mx-auto mb-6 text-primary" />
              <h2 className="text-3xl font-bold mb-4">Our Vision</h2>
              <p className="text-lg text-muted-foreground max-w-3xl mx-auto">
                To democratize pre-IPO investing by providing retail investors with institutional-grade
                investment opportunities in a transparent, fair, and secure environment. We are committed
                to protecting investor interests and maintaining the highest standards of corporate governance.
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Investor Rights */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Your Rights as an Investor</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              We recognize and uphold the following fundamental rights for all our investors
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {investorRights.map((right, index) => {
              const Icon = right.icon;
              return (
                <Card key={index} className="hover:shadow-lg transition-shadow">
                  <CardContent className="pt-6">
                    <div className="flex items-start gap-4">
                      <div className="flex-shrink-0">
                        <div className="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-primary/10">
                          <Icon className="h-6 w-6 text-primary" />
                        </div>
                      </div>
                      <div>
                        <h3 className="font-semibold mb-2">{right.title}</h3>
                        <p className="text-sm text-muted-foreground">{right.description}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Investor Responsibilities */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Your Responsibilities</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              For a successful investment journey, investors are expected to fulfill these responsibilities
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {investorResponsibilities.map((responsibility, index) => {
              const Icon = responsibility.icon;
              return (
                <Card key={index} className="hover:shadow-lg transition-shadow">
                  <CardContent className="pt-6">
                    <div className="flex items-start gap-4">
                      <div className="flex-shrink-0">
                        <div className="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-orange-500/10">
                          <Icon className="h-6 w-6 text-orange-500" />
                        </div>
                      </div>
                      <div>
                        <h3 className="font-semibold mb-2">{responsibility.title}</h3>
                        <p className="text-sm text-muted-foreground">{responsibility.description}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Our Commitments */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Our Commitments</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              We pledge to uphold these commitments in all our operations and interactions
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {commitments.map((commitment, index) => (
              <Card key={index}>
                <CardContent className="pt-6">
                  <div className="flex items-start gap-2 mb-3">
                    <CheckCircle2 className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                    <h3 className="font-semibold">{commitment.title}</h3>
                  </div>
                  <p className="text-sm text-muted-foreground">{commitment.description}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Dos and Don'ts */}
        <div className="mb-16">
          <h2 className="text-3xl font-bold mb-8 text-center">Dos and Don'ts for Investors</h2>

          <div className="grid md:grid-cols-2 gap-6">
            <Card className="border-green-500/50">
              <CardHeader>
                <CardTitle className="text-green-600 flex items-center gap-2">
                  <CheckCircle2 className="h-5 w-5" />
                  DO's
                </CardTitle>
              </CardHeader>
              <CardContent>
                <ul className="space-y-3">
                  {[
                    "Complete your KYC with accurate information",
                    "Read all offer documents and risk disclosures carefully",
                    "Understand the investment tenure and lock-in periods",
                    "Keep your contact details and bank account updated",
                    "Monitor your investments regularly through the dashboard",
                    "Report any discrepancies or unauthorized transactions immediately",
                    "Use strong passwords and enable two-factor authentication",
                    "Verify all communications from official channels only",
                    "Seek professional advice if you don't understand something",
                    "Keep records of all transactions and communications",
                  ].map((item, i) => (
                    <li key={i} className="flex items-start gap-2">
                      <CheckCircle2 className="h-4 w-4 text-green-500 flex-shrink-0 mt-0.5" />
                      <span className="text-sm">{item}</span>
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>

            <Card className="border-red-500/50">
              <CardHeader>
                <CardTitle className="text-red-600 flex items-center gap-2">
                  <AlertCircle className="h-5 w-5" />
                  DON'Ts
                </CardTitle>
              </CardHeader>
              <CardContent>
                <ul className="space-y-3">
                  {[
                    "Don't share your login credentials, passwords, or OTPs",
                    "Don't invest without reading and understanding risk factors",
                    "Don't provide incorrect or false information during KYC",
                    "Don't ignore communications about your investments",
                    "Don't make investment decisions based on rumors or tips",
                    "Don't invest beyond your financial capacity or risk appetite",
                    "Don't use public WiFi for financial transactions",
                    "Don't respond to unsolicited investment offers or calls",
                    "Don't delay updating changed contact or bank details",
                    "Don't ignore suspicious activities in your account",
                  ].map((item, i) => (
                    <li key={i} className="flex items-start gap-2">
                      <AlertCircle className="h-4 w-4 text-red-500 flex-shrink-0 mt-0.5" />
                      <span className="text-sm">{item}</span>
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Grievance Redressal */}
        <Card className="bg-muted/50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Scale className="h-5 w-5" />
              Grievance Redressal Mechanism
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-muted-foreground">
              In case of any grievance, investors may contact our Grievance Redressal Officer:
            </p>

            <div className="grid md:grid-cols-2 gap-6">
              <div className="space-y-2">
                <h4 className="font-semibold">Contact Information</h4>
                <div className="space-y-1 text-sm">
                  <p><strong>Name:</strong> Mr. Karthik Menon</p>
                  <p><strong>Designation:</strong> Grievance Redressal Officer</p>
                  <p><strong>Email:</strong> grievance@preipo-sip.com</p>
                  <p><strong>Phone:</strong> +91-22-1234-5679</p>
                  <p><strong>Timings:</strong> Monday to Friday, 10:00 AM to 6:00 PM IST</p>
                </div>
              </div>

              <div className="space-y-2">
                <h4 className="font-semibold">Resolution Timeline</h4>
                <div className="space-y-3 text-sm">
                  <div className="flex items-start gap-2">
                    <Clock className="h-4 w-4 mt-0.5 text-muted-foreground flex-shrink-0" />
                    <div>
                      <p className="font-medium">Acknowledgment:</p>
                      <p className="text-muted-foreground">Within 24 hours</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <TrendingUp className="h-4 w-4 mt-0.5 text-muted-foreground flex-shrink-0" />
                    <div>
                      <p className="font-medium">Resolution:</p>
                      <p className="text-muted-foreground">Within 30 days (as per SEBI norms)</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div className="pt-4 border-t">
              <p className="text-sm text-muted-foreground">
                <strong>Note:</strong> If not satisfied with the resolution, you may escalate to SEBI
                through the SCORES portal at{' '}
                <a href="https://scores.gov.in" target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                  scores.gov.in
                </a>
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Regulatory Disclaimer */}
        <Card className="mt-8 border-primary/50">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <AlertCircle className="h-5 w-5 text-primary flex-shrink-0 mt-0.5" />
              <div className="space-y-2 text-sm">
                <p>
                  <strong>Regulatory Information:</strong> This Investor Charter is designed in accordance
                  with SEBI guidelines and applicable regulations. PreIPO SIP is committed to operating
                  within the regulatory framework and maintaining the highest standards of investor protection.
                </p>
                <p className="text-muted-foreground">
                  All investments are subject to market risks. Past performance is not indicative of
                  future returns. Please read all scheme-related documents carefully before investing.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
