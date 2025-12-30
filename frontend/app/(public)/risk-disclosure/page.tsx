'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  AlertTriangle, TrendingDown, Lock, Clock, FileX, Scale,
  BarChart3, DollarSign, Shield, AlertCircle, Info
} from "lucide-react";

const generalRisks = [
  {
    icon: TrendingDown,
    title: "Market Risk",
    description: "Pre-IPO investments are subject to market volatility. The value of your investment may fluctuate significantly due to market conditions, economic factors, and investor sentiment. There is no guarantee of positive returns.",
  },
  {
    icon: Lock,
    title: "Liquidity Risk",
    description: "Pre-IPO shares typically have a lock-in period ranging from 6 months to 3 years. During this period, you cannot sell or transfer your shares. Even after lock-in, finding buyers may be difficult.",
  },
  {
    icon: Clock,
    title: "Timeline Risk",
    description: "IPO timelines are uncertain and subject to market conditions, regulatory approvals, and company decisions. The expected listing date may be delayed significantly or the IPO may not materialize at all.",
  },
  {
    icon: BarChart3,
    title: "Valuation Risk",
    description: "Pre-IPO valuations are based on projections and assumptions. The actual IPO price may be lower than your purchase price, resulting in losses. Past performance and valuations are not indicative of future results.",
  },
  {
    icon: DollarSign,
    title: "Price Risk",
    description: "The listing price of shares may be lower than your acquisition price. Market conditions at the time of listing can significantly impact share prices, potentially resulting in capital loss.",
  },
  {
    icon: FileX,
    title: "Company-Specific Risk",
    description: "Individual company risks including business model viability, management quality, competitive position, financial health, and operational execution can affect investment outcomes.",
  },
];

const specificRisks = [
  {
    category: "Regulatory Risk",
    items: [
      "Changes in SEBI regulations or securities laws may impact pre-IPO investments",
      "Regulatory approvals for IPO may be delayed or denied",
      "Compliance issues may affect company's ability to list",
      "Tax laws affecting capital gains may change unfavorably",
    ],
  },
  {
    category: "Operational Risk",
    items: [
      "Platform technical issues may affect order execution",
      "Settlement delays may occur due to operational challenges",
      "Third-party service provider failures may impact transactions",
      "Cybersecurity incidents may compromise data or transactions",
    ],
  },
  {
    category: "Investment-Specific Risk",
    items: [
      "Concentrated portfolio risk if over-invested in few companies",
      "Sector-specific risks based on industry dynamics",
      "Foreign exchange risk for companies with international operations",
      "Dilution risk from future fundraising rounds",
    ],
  },
  {
    category: "Legal Risk",
    items: [
      "Litigation against the company may affect valuation",
      "Intellectual property disputes may impact business",
      "Contractual disputes with partners or customers",
      "Corporate governance issues affecting investor rights",
    ],
  },
];

const importantDisclosures = [
  "Past performance of listed IPOs is not indicative of future results",
  "All investments in securities are subject to market risks",
  "There is no guarantee of capital protection or assured returns",
  "Investors should invest only after understanding the risks involved",
  "Investors should not invest more than they can afford to lose",
  "Pre-IPO investments are suitable only for investors with high risk appetite",
  "Investors should maintain a diversified portfolio across asset classes",
  "Independent financial advice should be sought before investing",
];

export default function RiskDisclosurePage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 pt-20 pb-12">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-orange-500/10 to-red-500/10 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="destructive" className="mb-4">
            <AlertTriangle className="h-3 w-3 mr-1" />
            Important Information
          </Badge>
          <h1 className="text-5xl font-bold mb-6">Risk Disclosure Statement</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            Pre-IPO investments carry significant risks. Please read this disclosure carefully
            and understand all risks before making any investment decision.
          </p>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Important Notice */}
        <Card className="mb-12 border-orange-500/50 bg-orange-500/5">
          <CardContent className="pt-6">
            <div className="flex items-start gap-4">
              <AlertCircle className="h-8 w-8 text-orange-500 flex-shrink-0 mt-1" />
              <div>
                <h3 className="text-xl font-bold mb-3">Critical Risk Warning</h3>
                <div className="space-y-2 text-sm">
                  <p className="font-semibold">
                    Pre-IPO investments are HIGH RISK and suitable only for investors who:
                  </p>
                  <ul className="list-disc pl-5 space-y-1 text-muted-foreground">
                    <li>Can afford to lose their entire investment</li>
                    <li>Have high risk tolerance and long investment horizon</li>
                    <li>Understand that returns are not guaranteed</li>
                    <li>Accept illiquidity for extended periods (6 months to 3+ years)</li>
                    <li>Are willing to hold investments through market volatility</li>
                  </ul>
                  <p className="font-semibold pt-2 text-orange-600">
                    If you cannot accept these conditions, pre-ipo investing is NOT suitable for you. Please do NOT Invest in pre-ipo shares, specially through preiposip.com
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* General Risks */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Key Investment Risks</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Understanding these fundamental risks is essential before investing in pre-IPO opportunities
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {generalRisks.map((risk, index) => {
              const Icon = risk.icon;
              return (
                <Card key={index} className="hover:shadow-lg transition-shadow border-orange-500/20">
                  <CardContent className="pt-6">
                    <div className="flex items-start gap-4">
                      <div className="flex-shrink-0">
                        <div className="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-orange-500/10">
                          <Icon className="h-6 w-6 text-orange-500" />
                        </div>
                      </div>
                      <div>
                        <h3 className="font-semibold mb-2 text-orange-600">{risk.title}</h3>
                        <p className="text-sm text-muted-foreground">{risk.description}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Specific Risk Categories */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Additional Risk Factors</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Detailed risk factors that may affect your investment
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {specificRisks.map((category, index) => (
              <Card key={index}>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2 text-lg">
                    <AlertTriangle className="h-5 w-5 text-orange-500" />
                    {category.category}
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <ul className="space-y-2">
                    {category.items.map((item, i) => (
                      <li key={i} className="flex items-start gap-2">
                        <span className="text-orange-500 mt-1">•</span>
                        <span className="text-sm text-muted-foreground">{item}</span>
                      </li>
                    ))}
                  </ul>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Risk Mitigation */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Risk Mitigation Strategies</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              While risks cannot be eliminated, following these principles may help manage them
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-6">
            <Card className="border-green-500/50">
              <CardContent className="pt-6 text-center">
                <Shield className="h-12 w-12 mx-auto mb-4 text-green-500" />
                <h3 className="font-semibold mb-2">Diversification</h3>
                <p className="text-sm text-muted-foreground">
                  Don't put all eggs in one basket. Spread investments across multiple companies,
                  sectors, and asset classes to reduce concentration risk.
                </p>
              </CardContent>
            </Card>

            <Card className="border-blue-500/50">
              <CardContent className="pt-6 text-center">
                <Scale className="h-12 w-12 mx-auto mb-4 text-blue-500" />
                <h3 className="font-semibold mb-2">Position Sizing</h3>
                <p className="text-sm text-muted-foreground">
                  Limit pre-IPO investments to a small portion (5-10%) of your overall portfolio.
                  Never invest money you cannot afford to lose.
                </p>
              </CardContent>
            </Card>

            <Card className="border-purple-500/50">
              <CardContent className="pt-6 text-center">
                <Info className="h-12 w-12 mx-auto mb-4 text-purple-500" />
                <h3 className="font-semibold mb-2">Due Diligence</h3>
                <p className="text-sm text-muted-foreground">
                  Research thoroughly before investing. Read all documents, understand the business,
                  and assess management quality and competitive position.
                </p>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Important Disclosures */}
        <Card className="mb-8">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileX className="h-5 w-5" />
              Important Disclosures
            </CardTitle>
          </CardHeader>
          <CardContent>
            <ul className="space-y-3">
              {importantDisclosures.map((disclosure, index) => (
                <li key={index} className="flex items-start gap-3">
                  <AlertCircle className="h-4 w-4 text-orange-500 flex-shrink-0 mt-0.5" />
                  <span className="text-sm">{disclosure}</span>
                </li>
              ))}
            </ul>
          </CardContent>
        </Card>

        {/* Regulatory Disclaimer */}
        <Card className="border-red-500/50 bg-red-500/5">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <AlertTriangle className="h-6 w-6 text-red-500 flex-shrink-0 mt-0.5" />
              <div className="space-y-3 text-sm">
                <p className="font-semibold text-red-600">
                  REGULATORY DISCLAIMER
                </p>
                <p>
                  <strong>Investments in securities are subject to market risks.</strong> Read all
                  scheme-related documents carefully before investing. Past performance is not indicative
                  of future returns. Please consider your specific investment requirements, risk tolerance,
                  investment goals, and time horizon before investing.
                </p>
                <p>
                  The information provided on this platform is for general information purposes only
                  and should not be construed as investment advice. Investors should consult with
                  qualified financial advisors before making investment decisions.
                </p>
                <p>
                  PreIPO SIP does not guarantee the accuracy or completeness of information provided
                  by companies. Investors should conduct their own due diligence and research before
                  making any investment decisions.
                </p>
                <p className="font-semibold">
                  By proceeding with any investment on this platform, you acknowledge that you have
                  read, understood, and accepted all risks disclosed herein.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Contact */}
        <div className="mt-8 text-center">
          <Card>
            <CardContent className="pt-6">
              <h3 className="font-semibold mb-2">Questions about Risks?</h3>
              <p className="text-sm text-muted-foreground mb-4">
                Our investment advisory team can help you understand risks and assess suitability.
              </p>
              <a href="mailto:support@preipo-sip.com" className="text-primary hover:underline">
                support@preipo-sip.com
              </a>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
