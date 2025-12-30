'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import {
  AlertCircle, Mail, Phone, Clock, CheckCircle2, FileText,
  Shield, HelpCircle, MessageSquare, Scale, User
} from "lucide-react";
import { Label } from "@/components/ui/label";

const grievanceCategories = [
  "Account & KYC Issues",
  "Payment & Refund",
  "Investment Related",
  "Technical Issues",
  "Documentation",
  "Other",
];

const escalationLevels = [
  {
    level: "Level 1",
    title: "Customer Support Team",
    timeline: "24-48 hours",
    contact: "support@preipo-sip.com",
    phone: "+91-22-1234-5678",
  },
  {
    level: "Level 2",
    title: "Grievance Redressal Officer",
    timeline: "3-5 business days",
    contact: "grievance@preipo-sip.com",
    phone: "+91-22-1234-5679",
  },
  {
    level: "Level 3",
    title: "Principal Officer",
    timeline: "7-10 business days",
    contact: "principal.officer@preipo-sip.com",
    phone: "+91-22-1234-5680",
  },
];

export default function GrievanceRedressalPage() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    category: '',
    description: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Handle form submission
    console.log('Grievance submitted:', formData);
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 pt-20 pb-12">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-primary/10 to-primary/5 py-16">
        <div className="max-w-6xl mx-auto px-4 text-center">
          <Badge variant="secondary" className="mb-4">
            <Shield className="h-3 w-3 mr-1" />
            Investor Protection
          </Badge>
          <h1 className="text-4xl md:text-5xl font-bold mb-4">Grievance Redressal</h1>
          <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
            We are committed to addressing your concerns promptly and fairly. Our grievance redressal
            mechanism ensures transparent and timely resolution of all investor complaints.
          </p>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 py-16">
        {/* Contact Cards */}
        <div className="grid md:grid-cols-3 gap-6 mb-16">
          <Card>
            <CardContent className="pt-6 text-center">
              <Mail className="h-8 w-8 mx-auto mb-3 text-primary" />
              <h3 className="font-semibold mb-2">Email Support</h3>
              <p className="text-sm text-muted-foreground mb-3">Get help via email</p>
              <a href="mailto:grievance@preipo-sip.com" className="text-sm text-primary hover:underline">
                grievance@preipo-sip.com
              </a>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6 text-center">
              <Phone className="h-8 w-8 mx-auto mb-3 text-primary" />
              <h3 className="font-semibold mb-2">Phone Support</h3>
              <p className="text-sm text-muted-foreground mb-3">Call us directly</p>
              <a href="tel:+912212345679" className="text-sm text-primary hover:underline">
                +91-22-1234-5679
              </a>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6 text-center">
              <Clock className="h-8 w-8 mx-auto mb-3 text-primary" />
              <h3 className="font-semibold mb-2">Response Time</h3>
              <p className="text-sm text-muted-foreground mb-3">Average resolution</p>
              <span className="text-sm font-semibold">24-48 hours</span>
            </CardContent>
          </Card>
        </div>

        {/* Grievance Form */}
        <div className="mb-16">
          <div className="text-center mb-8">
            <h2 className="text-3xl font-bold mb-2">Submit a Grievance</h2>
            <p className="text-muted-foreground">
              Please provide detailed information about your concern for faster resolution
            </p>
          </div>

          <Card className="max-w-2xl mx-auto">
            <CardHeader>
              <CardTitle>Grievance Form</CardTitle>
              <CardDescription>
                All fields are required. We will acknowledge your complaint within 24 hours.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Full Name *</Label>
                  <Input
                    id="name"
                    placeholder="Enter your full name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email">Email Address *</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="your.email@example.com"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="phone">Phone Number *</Label>
                  <Input
                    id="phone"
                    type="tel"
                    placeholder="+91 XXXXX XXXXX"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="category">Category *</Label>
                  <select
                    id="category"
                    className="w-full px-3 py-2 border rounded-md"
                    value={formData.category}
                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                    required
                  >
                    <option value="">Select category</option>
                    {grievanceCategories.map((cat) => (
                      <option key={cat} value={cat}>{cat}</option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description">Description *</Label>
                  <Textarea
                    id="description"
                    placeholder="Please provide detailed information about your grievance..."
                    rows={6}
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    required
                  />
                </div>

                <Button type="submit" className="w-full">
                  Submit Grievance
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>

        {/* Escalation Matrix */}
        <div className="mb-16">
          <div className="text-center mb-8">
            <h2 className="text-3xl font-bold mb-2">Escalation Matrix</h2>
            <p className="text-muted-foreground">
              If your grievance is not resolved at one level, it will be automatically escalated
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-6">
            {escalationLevels.map((level, index) => (
              <Card key={index} className={index === 0 ? 'border-primary' : ''}>
                <CardHeader>
                  <Badge className="w-fit mb-2">{level.level}</Badge>
                  <CardTitle className="text-xl">{level.title}</CardTitle>
                  <CardDescription>Response within {level.timeline}</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex items-start gap-2">
                    <Mail className="h-4 w-4 mt-0.5 text-muted-foreground flex-shrink-0" />
                    <div>
                      <p className="text-xs text-muted-foreground mb-1">Email</p>
                      <a href={`mailto:${level.contact}`} className="text-sm hover:underline">
                        {level.contact}
                      </a>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Phone className="h-4 w-4 mt-0.5 text-muted-foreground flex-shrink-0" />
                    <div>
                      <p className="text-xs text-muted-foreground mb-1">Phone</p>
                      <a href={`tel:${level.phone.replace(/[-\s]/g, '')}`} className="text-sm hover:underline">
                        {level.phone}
                      </a>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>

        {/* Grievance Process */}
        <div className="mb-16">
          <div className="text-center mb-8">
            <h2 className="text-3xl font-bold mb-2">How We Handle Your Grievance</h2>
            <p className="text-muted-foreground">Our systematic approach to complaint resolution</p>
          </div>

          <div className="grid md:grid-cols-4 gap-6">
            <Card>
              <CardContent className="pt-6 text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-foreground mb-4 font-bold">
                  1
                </div>
                <MessageSquare className="h-8 w-8 mx-auto mb-3 text-primary" />
                <h3 className="font-semibold mb-2">Submit</h3>
                <p className="text-sm text-muted-foreground">
                  File your complaint via email, phone, or online form
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6 text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-foreground mb-4 font-bold">
                  2
                </div>
                <FileText className="h-8 w-8 mx-auto mb-3 text-primary" />
                <h3 className="font-semibold mb-2">Acknowledge</h3>
                <p className="text-sm text-muted-foreground">
                  Receive acknowledgment within 24 hours with ticket number
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6 text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-foreground mb-4 font-bold">
                  3
                </div>
                <HelpCircle className="h-8 w-8 mx-auto mb-3 text-primary" />
                <h3 className="font-semibold mb-2">Investigate</h3>
                <p className="text-sm text-muted-foreground">
                  Our team investigates and analyzes the complaint
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6 text-center">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-foreground mb-4 font-bold">
                  4
                </div>
                <CheckCircle2 className="h-8 w-8 mx-auto mb-3 text-primary" />
                <h3 className="font-semibold mb-2">Resolve</h3>
                <p className="text-sm text-muted-foreground">
                  Resolution provided with detailed explanation
                </p>
              </CardContent>
            </Card>
          </div>
        </div>

        {/* Regulatory Information */}
        <Card className="bg-muted/50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Scale className="h-5 w-5" />
              Regulatory Framework
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <h4 className="font-semibold mb-2">Grievance Redressal Officer</h4>
                <div className="space-y-1 text-sm">
                  <p><strong>Name:</strong> Mr. Karthik Menon</p>
                  <p><strong>Designation:</strong> Head - Legal & Compliance</p>
                  <p><strong>Email:</strong> grievance@preipo-sip.com</p>
                  <p><strong>Phone:</strong> +91-22-1234-5679</p>
                  <p><strong>Address:</strong> 123, Business Tower, BKC, Mumbai - 400051</p>
                </div>
              </div>

              <div>
                <h4 className="font-semibold mb-2">SEBI Complaints (SCORES)</h4>
                <p className="text-sm text-muted-foreground mb-3">
                  If not satisfied with our resolution, you may escalate to SEBI
                </p>
                <Button variant="outline" size="sm" asChild>
                  <a href="https://scores.gov.in" target="_blank" rel="noopener noreferrer">
                    Visit SCORES Portal
                    <span className="ml-1">↗</span>
                  </a>
                </Button>
              </div>
            </div>

            <div className="pt-4 border-t">
              <div className="flex items-start gap-2">
                <AlertCircle className="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                <p className="text-sm text-muted-foreground">
                  <strong>Note:</strong> As per SEBI regulations, we are committed to resolving all
                  investor grievances within the stipulated timelines. All grievances are tracked
                  and monitored for timely closure.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
