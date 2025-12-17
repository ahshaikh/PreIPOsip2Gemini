"use client";

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Briefcase,
  MapPin,
  Clock,
  Users,
  Heart,
  Rocket,
  TrendingUp,
  Award,
  Coffee,
  Globe,
  DollarSign,
  Calendar,
} from "lucide-react";

const jobOpenings = [
  {
    id: 1,
    title: "Senior Full Stack Developer",
    department: "Engineering",
    location: "Mumbai, India",
    type: "Full-time",
    experience: "5+ years",
    description: `You’ll help design and build the core systems behind our pre-IPO investment platform — from early architecture decisions to features that real users depend on.

This role exists because we’re scaling beyond prototypes and need strong engineering foundations from the start.`,
  },
  {
    id: 2,
    title: "Product Manager",
    department: "Product",
    location: "Bangalore, India",
    type: "Full-time",
    experience: "3+ years",
    description: `You’ll shape what we build and why — translating user problems into clear priorities and execution plans.

This role exists because product decisions at this stage define the platform for years to come.`,
  },
  {
    id: 3,
    title: "Financial Analyst",
    department: "Finance",
    location: "Delhi, India",
    type: "Full-time",
    experience: "2+ years",
    description: `You’ll analyze pre-IPO companies, assess risk, and help define how investment insights are presented to users.

This role exists because trust and clarity are critical in early-stage investing.`,
  },
  {
    id: 4,
    title: "Marketing Manager",
    department: "Marketing",
    location: "Remote",
    type: "Full-time",
    experience: "4+ years",
    description: `You’ll build our early growth engine — from positioning and content to experiments that help us learn what truly resonates.

This role exists because we want thoughtful growth, not noise.`,
  },
];

const benefits = [
  {
    icon: DollarSign,
    title: "Fair, Transparent Compensation",
    description:
      "We offer competitive pay aligned with role, responsibility, and long-term impact — reviewed as we grow.",
  },
  {
    icon: TrendingUp,
    title: "Meaningful Ownership",
    description:
      "Early team members receive equity because builders should own what they help create.",
  },
  {
    icon: Heart,
    title: "Health Comes First",
    description:
      "Comprehensive health insurance so you can focus on meaningful work without worrying about the basics.",
  },
  {
    icon: Coffee,
    title: "Trust-Based Flexibility",
    description:
      "We care about outcomes, not hours. Work remotely or hybrid with mutual trust and accountability.",
  },
  {
    icon: Award,
    title: "Learning Is Part of the Job",
    description:
      "We support continuous learning through books, courses, and resources that help you grow with the company.",
  },
  {
    icon: Calendar,
    title: "Time to Recharge",
    description:
      "We encourage taking time off. A sustainable pace always beats burnout.",
  },
];

const values = [
  {
    icon: Rocket,
    title: "Execution Over Noise",
    description:
      "We value progress over perfection. Shipping, learning, and improving matter more than endless debate.",
  },
  {
    icon: Users,
    title: "Built From the User Backward",
    description:
      "We don’t build features to fill roadmaps. We build to create clarity, trust, and reliability for users.",
  },
  {
    icon: Globe,
    title: "Clear Decisions, Open Context",
    description:
      "You’ll know why decisions are made — even when they’re hard. We value context over hierarchy.",
  },
  {
    icon: Heart,
    title: "Sustainable Pace",
    description:
      "Long-term thinking requires energy and focus. We avoid fake urgency and respect personal boundaries.",
  },
];

export default function CareersPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* HERO */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">
            Now Hiring – Early Team Members
          </Badge>

          <h1 className="text-3xl font-bold mb-6">
            Build the Foundation of India’s Next Pre-IPO Investment Platform
          </h1>

          <p className="text-xl text-muted-foreground max-w-3xl mx-auto mb-8">
            We’re a focused, early-stage fintech team building long-term
            infrastructure for pre-IPO investing in India.
            <br />
            <br />
            If you care about ownership, clean execution, and solving real
            problems early, you’ll feel at home here.
          </p>

          <Button
            size="lg"
            onClick={() =>
              document
                .getElementById("openings")
                ?.scrollIntoView({ behavior: "smooth" })
            }
          >
            Explore Open Roles
          </Button>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* WHERE WE ARE TODAY */}
        <div className="mb-16 text-center">
          <h2 className="text-3xl font-bold mb-4">Where We Are Today</h2>
          <p className="text-muted-foreground max-w-2xl mx-auto mb-6">
            We’re an early-stage fintech startup building the core infrastructure
            for pre-IPO investing in India.
          </p>

          <div className="max-w-3xl mx-auto text-muted-foreground space-y-2 text-left">
            <p>• Small, focused team with high ownership</p>
            <p>• Zero legacy systems — built from first principles</p>
            <p>• Early users, real feedback, fast iteration</p>
            <p>• Decisions made quickly, with shared context</p>
            <p>• Strong emphasis on clean systems and long-term thinking</p>
            <p className="pt-4 font-medium text-foreground">
              If you enjoy shaping things early — not just maintaining them —
              this is the right stage to join.
            </p>
          </div>
        </div>

        {/* VALUES */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Why PreIPO SIP?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              We’re early, deliberate, and building with long-term intent.
              These are not slogans — they’re how we work every day.
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
                    <p className="text-sm text-muted-foreground">
                      {value.description}
                    </p>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* BENEFITS */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Benefits & Perks</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              We focus on what actually matters for doing great work over the
              long term.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {benefits.map((benefit, index) => {
              const Icon = benefit.icon;
              return (
                <Card key={index}>
                  <CardContent className="pt-6 flex gap-4">
                    <div className="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                      <Icon className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="font-semibold mb-1">{benefit.title}</h3>
                      <p className="text-sm text-muted-foreground">
                        {benefit.description}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </div>

        {/* OPEN POSITIONS */}
        <div id="openings" className="scroll-mt-20">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Open Positions</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Find a role where you can make an early, meaningful impact.
            </p>
          </div>

          <div className="space-y-6">
            {jobOpenings.map((job) => (
              <Card key={job.id}>
                <CardHeader>
                  <CardTitle>{job.title}</CardTitle>
                  <CardDescription className="whitespace-pre-line">
                    {job.description}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="flex flex-wrap gap-4 mb-4 text-sm text-muted-foreground">
                    <span className="flex items-center gap-2">
                      <MapPin className="h-4 w-4" /> {job.location}
                    </span>
                    <span className="flex items-center gap-2">
                      <Clock className="h-4 w-4" /> {job.type}
                    </span>
                    <span className="flex items-center gap-2">
                      <Briefcase className="h-4 w-4" /> {job.experience}
                    </span>
                  </div>
                  <Button>Apply Now</Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
