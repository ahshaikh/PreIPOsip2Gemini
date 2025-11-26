'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Briefcase, MapPin, Clock, Users, Heart, Rocket,
  TrendingUp, Award, Coffee, Globe, DollarSign, Calendar
} from "lucide-react";

const jobOpenings = [
  {
    id: 1,
    title: "Senior Full Stack Developer",
    department: "Engineering",
    location: "Mumbai, India",
    type: "Full-time",
    experience: "5+ years",
    description: "Build and scale our pre-IPO investment platform with cutting-edge technologies.",
  },
  {
    id: 2,
    title: "Product Manager",
    department: "Product",
    location: "Bangalore, India",
    type: "Full-time",
    experience: "3+ years",
    description: "Lead product strategy and execution for our investment platform.",
  },
  {
    id: 3,
    title: "Financial Analyst",
    department: "Finance",
    location: "Delhi, India",
    type: "Full-time",
    experience: "2+ years",
    description: "Analyze pre-IPO companies and provide investment recommendations.",
  },
  {
    id: 4,
    title: "Marketing Manager",
    department: "Marketing",
    location: "Remote",
    type: "Full-time",
    experience: "4+ years",
    description: "Drive growth through digital marketing and brand building initiatives.",
  },
];

const benefits = [
  {
    icon: DollarSign,
    title: "Competitive Salary",
    description: "Industry-leading compensation packages with performance bonuses",
  },
  {
    icon: TrendingUp,
    title: "Stock Options",
    description: "Early employee stock options in a fast-growing fintech",
  },
  {
    icon: Heart,
    title: "Health Insurance",
    description: "Comprehensive health insurance for you and your family",
  },
  {
    icon: Coffee,
    title: "Flexible Work",
    description: "Work from anywhere with flexible hours and remote-first culture",
  },
  {
    icon: Award,
    title: "Learning Budget",
    description: "Annual budget for courses, conferences, and professional development",
  },
  {
    icon: Calendar,
    title: "Paid Time Off",
    description: "Generous vacation policy with 25+ days of paid leave annually",
  },
];

const values = [
  {
    icon: Rocket,
    title: "Innovation First",
    description: "We encourage experimentation and embrace new ideas",
  },
  {
    icon: Users,
    title: "Customer Obsessed",
    description: "Everything we build starts with understanding user needs",
  },
  {
    icon: Globe,
    title: "Transparency",
    description: "Open communication and honest feedback at all levels",
  },
  {
    icon: Heart,
    title: "Work-Life Balance",
    description: "We believe in sustainable growth and healthy team culture",
  },
];

export default function CareersPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">We're Hiring!</Badge>
          <h1 className="text-5xl font-bold mb-6">Join Our Team</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-8">
            Help us democratize pre-IPO investing and build India's leading investment platform.
            Work with a passionate team solving real problems for millions of investors.
          </p>
          <Button size="lg" onClick={() => document.getElementById('openings')?.scrollIntoView({ behavior: 'smooth' })}>
            View Open Positions
          </Button>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Why Join Us */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Why PreIPO SIP?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              We're building something special. Join us in revolutionizing how India invests.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {values.map((value, index) => {
              const Icon = value.icon;
              return (
                <Card key={index} className="text-center">
                  <CardContent className="pt-6">
                    <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 mb-4">
                      <Icon className="h-6 w-6 text-primary" />
                    </div>
                    <h3 className="font-semibold mb-2">{value.title}</h3>
                    <p className="text-sm text-muted-foreground">{value.description}</p>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Benefits */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Benefits & Perks</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              We take care of our team so they can focus on building great products.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {benefits.map((benefit, index) => {
              const Icon = benefit.icon;
              return (
                <Card key={index}>
                  <CardContent className="pt-6">
                    <div className="flex items-start gap-4">
                      <div className="flex-shrink-0">
                        <div className="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10">
                          <Icon className="h-5 w-5 text-primary" />
                        </div>
                      </div>
                      <div>
                        <h3 className="font-semibold mb-1">{benefit.title}</h3>
                        <p className="text-sm text-muted-foreground">{benefit.description}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* Open Positions */}
        <div id="openings" className="scroll-mt-20">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Open Positions</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Find your perfect role and apply today. We're always looking for talented individuals.
            </p>
          </div>

          <div className="space-y-6">
            {jobOpenings.map((job) => (
              <Card key={job.id} className="hover:shadow-lg transition-shadow">
                <CardHeader>
                  <div className="flex items-start justify-between">
                    <div>
                      <CardTitle className="text-xl mb-2">{job.title}</CardTitle>
                      <CardDescription className="text-base">{job.description}</CardDescription>
                    </div>
                    <Badge variant="secondary">{job.department}</Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-4 mb-4">
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                      <MapPin className="h-4 w-4" />
                      <span>{job.location}</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                      <Clock className="h-4 w-4" />
                      <span>{job.type}</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                      <Briefcase className="h-4 w-4" />
                      <span>{job.experience}</span>
                    </div>
                  </div>
                  <Button>Apply Now</Button>
                </CardContent>
              </Card>
            ))}
          </div>

          {/* No Perfect Match */}
          <Card className="mt-8 bg-muted/30">
            <CardContent className="pt-6">
              <div className="text-center">
                <h3 className="text-xl font-semibold mb-2">Don't see a perfect match?</h3>
                <p className="text-muted-foreground mb-4">
                  We're always interested in hearing from talented people. Send us your resume and
                  we'll keep you in mind for future opportunities.
                </p>
                <Button variant="outline">Send General Application</Button>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Contact */}
        <div className="mt-16 text-center">
          <Card>
            <CardContent className="pt-6">
              <h3 className="text-xl font-semibold mb-2">Questions about working with us?</h3>
              <p className="text-muted-foreground mb-4">
                Our HR team is here to help. Reach out at{' '}
                <a href="mailto:careers@preipo-sip.com" className="text-primary hover:underline">
                  careers@preipo-sip.com
                </a>
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
