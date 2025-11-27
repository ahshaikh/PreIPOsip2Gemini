'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Newspaper, Calendar, ExternalLink, Download, Award, TrendingUp, Users } from "lucide-react";

const pressReleases = [
  {
    id: 1,
    title: "PreIPO SIP Crosses 50,000 Investor Milestone",
    date: "November 15, 2024",
    category: "Company News",
    excerpt: "Platform achieves significant growth milestone, onboarding 50,000+ retail investors in pre-IPO opportunities across India.",
    readTime: "3 min read",
  },
  {
    id: 2,
    title: "Launch of Private Equity Fund Access for Retail Investors",
    date: "October 28, 2024",
    category: "Product Launch",
    excerpt: "PreIPO SIP democratizes access to institutional-grade private equity funds with investments starting from ₹50 lakhs.",
    readTime: "4 min read",
  },
  {
    id: 3,
    title: "Partnership with Leading SEBI-Registered Investment Banks",
    date: "September 12, 2024",
    category: "Partnerships",
    excerpt: "Strategic partnerships established to bring exclusive pre-IPO deals to retail investors across the country.",
    readTime: "2 min read",
  },
  {
    id: 4,
    title: "PreIPO SIP Receives 'Best Fintech Innovation Award 2024'",
    date: "August 5, 2024",
    category: "Awards",
    excerpt: "Recognized for democratizing pre-IPO investing and innovative approach to retail investor education.",
    readTime: "3 min read",
  },
  {
    id: 5,
    title: "Platform Processes Over ₹500 Crore in Pre-IPO Investments",
    date: "July 20, 2024",
    category: "Milestone",
    excerpt: "Cumulative investment value crosses ₹500 crore mark, showcasing strong investor confidence and platform growth.",
    readTime: "3 min read",
  },
];

const mediaFeatures = [
  {
    outlet: "The Economic Times",
    title: "How PreIPO SIP is Changing the Investment Landscape",
    date: "November 2024",
    logo: "/api/placeholder/200/80",
  },
  {
    outlet: "Business Standard",
    title: "Retail Investors Get Access to Pre-IPO Market",
    date: "October 2024",
    logo: "/api/placeholder/200/80",
  },
  {
    outlet: "Moneycontrol",
    title: "The Rise of Pre-IPO Investment Platforms in India",
    date: "September 2024",
    logo: "/api/placeholder/200/80",
  },
  {
    outlet: "LiveMint",
    title: "Democratizing High-Return Investments",
    date: "August 2024",
    logo: "/api/placeholder/200/80",
  },
];

const awards = [
  {
    title: "Best Fintech Innovation 2024",
    organization: "India Fintech Awards",
    year: "2024",
  },
  {
    title: "Emerging Investment Platform of the Year",
    organization: "Investment Excellence Awards",
    year: "2024",
  },
  {
    title: "Top 50 Startups to Watch",
    organization: "YourStory",
    year: "2024",
  },
];

export default function PressPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4">
          <Badge variant="secondary" className="mb-4">
            <Newspaper className="h-3 w-3 mr-1" />
            Press & Media
          </Badge>
          <h1 className="text-5xl font-bold mb-6">Press Room</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mb-8">
            Latest news, press releases, and media coverage about PreIPO SIP.
            For media inquiries, contact us at{' '}
            <a href="mailto:press@preipo-sip.com" className="text-primary hover:underline">
              press@preipo-sip.com
            </a>
          </p>
          <div className="flex gap-4">
            <Button size="lg">
              <Download className="h-4 w-4 mr-2" />
              Download Media Kit
            </Button>
            <Button size="lg" variant="outline">
              Contact PR Team
            </Button>
          </div>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Quick Stats */}
        <div className="grid md:grid-cols-3 gap-6 mb-16">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-primary/10 rounded-lg">
                  <Users className="h-6 w-6 text-primary" />
                </div>
                <div>
                  <p className="text-3xl font-bold">50K+</p>
                  <p className="text-sm text-muted-foreground">Active Investors</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-green-500/10 rounded-lg">
                  <TrendingUp className="h-6 w-6 text-green-500" />
                </div>
                <div>
                  <p className="text-3xl font-bold">₹500Cr+</p>
                  <p className="text-sm text-muted-foreground">Total Investment Volume</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-orange-500/10 rounded-lg">
                  <Award className="h-6 w-6 text-orange-500" />
                </div>
                <div>
                  <p className="text-3xl font-bold">3</p>
                  <p className="text-sm text-muted-foreground">Industry Awards</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Press Releases */}
        <div className="mb-16">
          <div className="mb-8">
            <h2 className="text-3xl font-bold mb-2">Press Releases</h2>
            <p className="text-muted-foreground">Latest announcements and company updates</p>
          </div>

          <div className="space-y-6">
            {pressReleases.map((release) => (
              <Card key={release.id} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <Badge variant="secondary">{release.category}</Badge>
                        <span className="text-sm text-muted-foreground flex items-center gap-1">
                          <Calendar className="h-3 w-3" />
                          {release.date}
                        </span>
                      </div>
                      <CardTitle className="text-2xl mb-2">{release.title}</CardTitle>
                      <CardDescription className="text-base">{release.excerpt}</CardDescription>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">{release.readTime}</span>
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm">
                        Read More
                        <ExternalLink className="h-3 w-3 ml-1" />
                      </Button>
                      <Button variant="outline" size="sm">
                        <Download className="h-3 w-3 mr-1" />
                        PDF
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Media Coverage */}
        <div className="mb-16">
          <div className="mb-8">
            <h2 className="text-3xl font-bold mb-2">In The News</h2>
            <p className="text-muted-foreground">Coverage from leading business publications</p>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            {mediaFeatures.map((feature, index) => (
              <Card key={index} className="hover:shadow-lg transition-shadow">
                <CardContent className="pt-6">
                  <div className="flex items-start gap-4">
                    <div className="flex-shrink-0 w-24 h-12 bg-muted rounded flex items-center justify-center">
                      <Newspaper className="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div className="flex-1">
                      <p className="font-semibold text-sm text-primary mb-1">{feature.outlet}</p>
                      <h3 className="font-semibold mb-2">{feature.title}</h3>
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">{feature.date}</span>
                        <Button variant="ghost" size="sm">
                          Read Article
                          <ExternalLink className="h-3 w-3 ml-1" />
                        </Button>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Awards & Recognition */}
        <div className="mb-16">
          <div className="mb-8">
            <h2 className="text-3xl font-bold mb-2">Awards & Recognition</h2>
            <p className="text-muted-foreground">Honored for innovation and excellence</p>
          </div>

          <div className="grid md:grid-cols-3 gap-6">
            {awards.map((award, index) => (
              <Card key={index}>
                <CardContent className="pt-6 text-center">
                  <Award className="h-12 w-12 mx-auto mb-4 text-primary" />
                  <h3 className="font-bold mb-2">{award.title}</h3>
                  <p className="text-sm text-muted-foreground mb-1">{award.organization}</p>
                  <Badge variant="secondary">{award.year}</Badge>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Media Contact */}
        <Card className="bg-gradient-to-r from-primary/5 to-primary/10">
          <CardContent className="pt-8">
            <div className="text-center">
              <Newspaper className="h-12 w-12 mx-auto mb-4 text-primary" />
              <h3 className="text-2xl font-bold mb-2">Media Inquiries</h3>
              <p className="text-muted-foreground mb-6 max-w-2xl mx-auto">
                For press inquiries, interview requests, or media kit downloads, please contact our PR team.
                We typically respond within 24 hours.
              </p>
              <div className="flex items-center justify-center gap-4">
                <Button size="lg">
                  <a href="mailto:press@preipo-sip.com" className="flex items-center">
                    Email PR Team
                  </a>
                </Button>
                <Button size="lg" variant="outline">
                  <Download className="h-4 w-4 mr-2" />
                  Download Media Kit
                </Button>
              </div>
              <div className="mt-6 text-sm text-muted-foreground">
                <p>PR Contact: media@preipo-sip.com | Phone: +91-22-1234-5678</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
