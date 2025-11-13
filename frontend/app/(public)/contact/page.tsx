// V-FINAL-1730-192
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import { useState } from "react";
import { Mail, Phone, MapPin } from "lucide-react";

export default function ContactPage() {
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    // In a real app, send to API. For now, simulate success.
    setTimeout(() => {
        toast.success("Message Sent!", { description: "We'll get back to you shortly." });
        setIsSubmitting(false);
    }, 1000);
  };

  return (
    <div className="container py-20">
      <h1 className="text-4xl font-bold text-center mb-12">Contact Us</h1>
      
      <div className="grid md:grid-cols-2 gap-12">
        <div>
          <h2 className="text-2xl font-semibold mb-6">Get in Touch</h2>
          <div className="space-y-6">
            <div className="flex items-start gap-4">
              <MapPin className="h-6 w-6 text-primary mt-1" />
              <div>
                <h3 className="font-medium">Office</h3>
                <p className="text-muted-foreground">123 Financial District, Mumbai, India</p>
              </div>
            </div>
            <div className="flex items-start gap-4">
              <Phone className="h-6 w-6 text-primary mt-1" />
              <div>
                <h3 className="font-medium">Phone</h3>
                <p className="text-muted-foreground">+91 98765 43210</p>
              </div>
            </div>
            <div className="flex items-start gap-4">
              <Mail className="h-6 w-6 text-primary mt-1" />
              <div>
                <h3 className="font-medium">Email</h3>
                <p className="text-muted-foreground">support@preiposip.com</p>
              </div>
            </div>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Send a Message</CardTitle>
            <CardDescription>We usually respond within 24 hours.</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Name</Label>
                <Input required placeholder="Your Name" />
              </div>
              <div className="space-y-2">
                <Label>Email</Label>
                <Input type="email" required placeholder="your@email.com" />
              </div>
              <div className="space-y-2">
                <Label>Message</Label>
                <Textarea required placeholder="How can we help?" rows={5} />
              </div>
              <Button type="submit" className="w-full" disabled={isSubmitting}>
                {isSubmitting ? "Sending..." : "Send Message"}
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}