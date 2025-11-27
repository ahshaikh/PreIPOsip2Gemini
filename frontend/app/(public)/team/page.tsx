'use client';

import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Linkedin, Mail, Award, Users, Target, Heart } from "lucide-react";

const leadership = [
  {
    name: "Rajesh Sharma",
    role: "Founder & CEO",
    image: "/api/placeholder/400/400",
    bio: "15+ years in fintech and investment banking. Previously led digital transformation at a leading investment firm.",
    linkedin: "#",
    email: "rajesh@preipo-sip.com",
  },
  {
    name: "Priya Mehta",
    role: "Co-Founder & CTO",
    image: "/api/placeholder/400/400",
    bio: "Tech entrepreneur with expertise in building scalable platforms. Former engineering lead at major tech companies.",
    linkedin: "#",
    email: "priya@preipo-sip.com",
  },
  {
    name: "Amit Patel",
    role: "Chief Investment Officer",
    image: "/api/placeholder/400/400",
    bio: "20+ years in private equity and pre-IPO investments. MBA from IIM Ahmedabad, CFA charterholder.",
    linkedin: "#",
    email: "amit@preipo-sip.com",
  },
  {
    name: "Sneha Reddy",
    role: "Head of Compliance",
    image: "/api/placeholder/400/400",
    bio: "Former SEBI official with deep expertise in regulatory compliance and investor protection.",
    linkedin: "#",
    email: "sneha@preipo-sip.com",
  },
];

const departments = [
  {
    name: "Engineering",
    head: "Vikram Singh",
    teamSize: 25,
    description: "Building cutting-edge technology for seamless investment experience",
  },
  {
    name: "Investment Research",
    head: "Dr. Arjun Kumar",
    teamSize: 15,
    description: "Analyzing and curating the best pre-IPO opportunities",
  },
  {
    name: "Customer Success",
    head: "Neha Gupta",
    teamSize: 30,
    description: "Ensuring every investor has a world-class experience",
  },
  {
    name: "Legal & Compliance",
    head: "Karthik Menon",
    teamSize: 12,
    description: "Maintaining highest standards of regulatory compliance",
  },
  {
    name: "Product",
    head: "Ananya Iyer",
    teamSize: 18,
    description: "Designing intuitive products for modern investors",
  },
  {
    name: "Marketing",
    head: "Rohan Malhotra",
    teamSize: 20,
    description: "Spreading financial literacy and investment awareness",
  },
];

const values = [
  {
    icon: Award,
    title: "Excellence",
    description: "We strive for excellence in everything we do, from investment selection to customer service.",
  },
  {
    icon: Users,
    title: "Investor First",
    description: "Our investors' success is our success. Every decision is made with their best interests in mind.",
  },
  {
    icon: Target,
    title: "Transparency",
    description: "We believe in complete transparency in fees, performance, and all aspects of our operations.",
  },
  {
    icon: Heart,
    title: "Integrity",
    description: "We operate with the highest standards of integrity and ethical business practices.",
  },
];

export default function TeamPage() {
  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-20">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">
            <Users className="h-3 w-3 mr-1" />
            Meet the Team
          </Badge>
          <h1 className="text-5xl font-bold mb-6">The People Behind PreIPO SIP</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            A diverse team of finance experts, technologists, and customer advocates united by a mission
            to democratize pre-IPO investing in India.
          </p>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Leadership Team */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Leadership Team</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              Our leadership brings together decades of experience in finance, technology, and regulatory compliance.
            </p>
          </div>

          <div className="grid md:grid-cols-2 gap-8">
            {leadership.map((leader, index) => (
              <Card key={index} className="hover:shadow-lg transition-shadow">
                <CardContent className="pt-6">
                  <div className="flex gap-6">
                    <div className="flex-shrink-0">
                      <div className="w-32 h-32 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                        <Users className="h-16 w-16 text-primary/50" />
                      </div>
                    </div>
                    <div className="flex-1">
                      <h3 className="text-xl font-bold mb-1">{leader.name}</h3>
                      <p className="text-primary font-semibold mb-3">{leader.role}</p>
                      <p className="text-sm text-muted-foreground mb-4">{leader.bio}</p>
                      <div className="flex gap-2">
                        <Button size="sm" variant="outline">
                          <Linkedin className="h-4 w-4 mr-1" />
                          LinkedIn
                        </Button>
                        <Button size="sm" variant="outline">
                          <Mail className="h-4 w-4 mr-1" />
                          Email
                        </Button>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Departments */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Our Departments</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              120+ talented professionals across diverse functions working together to serve our investors.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {departments.map((dept, index) => (
              <Card key={index} className="hover:shadow-lg transition-shadow">
                <CardContent className="pt-6">
                  <div className="flex items-start justify-between mb-3">
                    <div>
                      <h3 className="text-lg font-bold mb-1">{dept.name}</h3>
                      <p className="text-sm text-muted-foreground">Led by {dept.head}</p>
                    </div>
                    <Badge>{dept.teamSize} members</Badge>
                  </div>
                  <p className="text-sm text-muted-foreground">{dept.description}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Our Values */}
        <div className="mb-16">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold mb-4">Our Core Values</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto">
              The principles that guide everything we do
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {values.map((value, index) => {
              const Icon = value.icon;
              return (
                <Card key={index}>
                  <CardContent className="pt-6 text-center">
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

        {/* Stats */}
        <div className="mb-16">
          <Card className="bg-gradient-to-r from-primary/5 to-primary/10">
            <CardContent className="pt-8">
              <div className="grid md:grid-cols-4 gap-8 text-center">
                <div>
                  <p className="text-4xl font-bold mb-2">120+</p>
                  <p className="text-muted-foreground">Team Members</p>
                </div>
                <div>
                  <p className="text-4xl font-bold mb-2">15+</p>
                  <p className="text-muted-foreground">Years Avg Experience</p>
                </div>
                <div>
                  <p className="text-4xl font-bold mb-2">50K+</p>
                  <p className="text-muted-foreground">Happy Investors</p>
                </div>
                <div>
                  <p className="text-4xl font-bold mb-2">₹1000Cr+</p>
                  <p className="text-muted-foreground">Assets Under Management</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Join Us CTA */}
        <div className="text-center">
          <Card>
            <CardContent className="pt-8">
              <Users className="h-12 w-12 mx-auto mb-4 text-primary" />
              <h3 className="text-2xl font-bold mb-2">Want to Join Our Team?</h3>
              <p className="text-muted-foreground mb-6 max-w-2xl mx-auto">
                We're always looking for passionate individuals who want to revolutionize investing in India.
                Check out our open positions and be part of something special.
              </p>
              <Button size="lg" onClick={() => window.location.href = '/careers'}>
                View Open Positions
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
