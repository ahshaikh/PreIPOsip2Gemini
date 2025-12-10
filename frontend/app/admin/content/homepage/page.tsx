// V-CMS-ENHANCEMENT-014 | Homepage Section Manager
// Created: 2025-12-10 | Purpose: Manage homepage sections and content

'use client';

import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Switch } from '@/components/ui/switch';
import { Save, Layout, Star, BookOpen, Award } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

interface HeroSection {
  enabled: boolean;
  heading: string;
  subheading: string;
  cta_text: string;
  cta_url: string;
  background_image?: string;
}

interface ValuePropsSection {
  enabled: boolean;
  heading: string;
  items: Array<{
    icon: string;
    title: string;
    description: string;
  }>;
}

interface HowItWorksSection {
  enabled: boolean;
  heading: string;
  steps: Array<{
    number: number;
    title: string;
    description: string;
  }>;
}

interface TestimonialsSection {
  enabled: boolean;
  heading: string;
  testimonials: Array<{
    name: string;
    role: string;
    content: string;
    avatar?: string;
  }>;
}

interface HomepageConfig {
  hero: HeroSection;
  value_props: ValuePropsSection;
  how_it_works: HowItWorksSection;
  testimonials: TestimonialsSection;
}

const defaultConfig: HomepageConfig = {
  hero: {
    enabled: true,
    heading: 'Invest in Pre-IPO Companies',
    subheading: 'Start your SIP journey with exclusive access to high-growth companies',
    cta_text: 'Get Started',
    cta_url: '/signup',
  },
  value_props: {
    enabled: true,
    heading: 'Why Choose PreIPO SIP?',
    items: [
      { icon: 'Shield', title: 'Secure & Compliant', description: 'SEBI-registered platform with bank-grade security' },
      { icon: 'TrendingUp', title: 'High Growth Potential', description: 'Access pre-IPO companies with proven track records' },
      { icon: 'Users', title: 'Expert Guidance', description: 'Research-backed insights and dedicated support' },
    ],
  },
  how_it_works: {
    enabled: true,
    heading: 'How It Works',
    steps: [
      { number: 1, title: 'Sign Up', description: 'Create your account in minutes' },
      { number: 2, title: 'Complete KYC', description: 'Verify your identity securely' },
      { number: 3, title: 'Start Investing', description: 'Choose your plan and begin your SIP' },
    ],
  },
  testimonials: {
    enabled: true,
    heading: 'What Our Investors Say',
    testimonials: [
      { name: 'Rajesh Kumar', role: 'Investor', content: 'Amazing platform! Easy to use and great returns.' },
      { name: 'Priya Sharma', role: 'SIP Subscriber', content: 'The best decision I made for my financial future.' },
    ],
  },
};

export default function HomepageManagerPage() {
  const queryClient = useQueryClient();
  const [config, setConfig] = useState<HomepageConfig>(defaultConfig);

  // Fetch existing homepage configuration
  const { data, isLoading } = useQuery({
    queryKey: ['homepageConfig'],
    queryFn: async () => {
      const res = await api.get('/admin/homepage-config');
      return res.data;
    },
  });

  useEffect(() => {
    if (data?.config) {
      setConfig(data.config);
    }
  }, [data]);

  // Save mutation
  const saveMutation = useMutation({
    mutationFn: (configData: HomepageConfig) =>
      api.post('/admin/homepage-config', { config: configData }),
    onSuccess: () => {
      toast.success('Homepage configuration saved successfully');
      queryClient.invalidateQueries({ queryKey: ['homepageConfig'] });
    },
    onError: () => {
      toast.error('Failed to save configuration');
    },
  });

  const handleSave = () => {
    saveMutation.mutate(config);
  };

  const updateHero = (field: keyof HeroSection, value: any) => {
    setConfig((prev) => ({
      ...prev,
      hero: { ...prev.hero, [field]: value },
    }));
  };

  const updateValueProps = (field: keyof ValuePropsSection, value: any) => {
    setConfig((prev) => ({
      ...prev,
      value_props: { ...prev.value_props, [field]: value },
    }));
  };

  const updateHowItWorks = (field: keyof HowItWorksSection, value: any) => {
    setConfig((prev) => ({
      ...prev,
      how_it_works: { ...prev.how_it_works, [field]: value },
    }));
  };

  const updateTestimonials = (field: keyof TestimonialsSection, value: any) => {
    setConfig((prev) => ({
      ...prev,
      testimonials: { ...prev.testimonials, [field]: value },
    }));
  };

  if (isLoading) {
    return <div className="p-8">Loading...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Homepage Manager</h1>
          <p className="text-muted-foreground mt-1">
            Customize your homepage sections and content
          </p>
        </div>
        <Button onClick={handleSave} disabled={saveMutation.isPending}>
          <Save className="mr-2 h-4 w-4" />
          {saveMutation.isPending ? 'Saving...' : 'Save All Changes'}
        </Button>
      </div>

      <Tabs defaultValue="hero" className="space-y-4">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="hero">
            <Layout className="mr-2 h-4 w-4" />
            Hero Section
          </TabsTrigger>
          <TabsTrigger value="value_props">
            <Award className="mr-2 h-4 w-4" />
            Value Props
          </TabsTrigger>
          <TabsTrigger value="how_it_works">
            <BookOpen className="mr-2 h-4 w-4" />
            How It Works
          </TabsTrigger>
          <TabsTrigger value="testimonials">
            <Star className="mr-2 h-4 w-4" />
            Testimonials
          </TabsTrigger>
        </TabsList>

        {/* Hero Section */}
        <TabsContent value="hero">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Hero Section</CardTitle>
                  <CardDescription>Main banner at the top of your homepage</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Label>Enabled</Label>
                  <Switch
                    checked={config.hero.enabled}
                    onCheckedChange={(checked) => updateHero('enabled', checked)}
                  />
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Main Heading</Label>
                <Input
                  value={config.hero.heading}
                  onChange={(e) => updateHero('heading', e.target.value)}
                  placeholder="Invest in Pre-IPO Companies"
                />
              </div>

              <div className="space-y-2">
                <Label>Subheading</Label>
                <Textarea
                  value={config.hero.subheading}
                  onChange={(e) => updateHero('subheading', e.target.value)}
                  placeholder="Supporting text for your hero section"
                  rows={3}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>CTA Button Text</Label>
                  <Input
                    value={config.hero.cta_text}
                    onChange={(e) => updateHero('cta_text', e.target.value)}
                    placeholder="Get Started"
                  />
                </div>
                <div className="space-y-2">
                  <Label>CTA Button URL</Label>
                  <Input
                    value={config.hero.cta_url}
                    onChange={(e) => updateHero('cta_url', e.target.value)}
                    placeholder="/signup"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Background Image URL (Optional)</Label>
                <Input
                  value={config.hero.background_image || ''}
                  onChange={(e) => updateHero('background_image', e.target.value)}
                  placeholder="https://example.com/hero-bg.jpg"
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Value Props Section */}
        <TabsContent value="value_props">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Value Propositions</CardTitle>
                  <CardDescription>Highlight your platform's key benefits</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Label>Enabled</Label>
                  <Switch
                    checked={config.value_props.enabled}
                    onCheckedChange={(checked) => updateValueProps('enabled', checked)}
                  />
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Section Heading</Label>
                <Input
                  value={config.value_props.heading}
                  onChange={(e) => updateValueProps('heading', e.target.value)}
                  placeholder="Why Choose PreIPO SIP?"
                />
              </div>

              <div className="space-y-2">
                <Label>Value Props Items (JSON Array)</Label>
                <Textarea
                  value={JSON.stringify(config.value_props.items, null, 2)}
                  onChange={(e) => {
                    try {
                      const items = JSON.parse(e.target.value);
                      updateValueProps('items', items);
                    } catch (error) {
                      // Invalid JSON, don't update
                    }
                  }}
                  rows={12}
                  className="font-mono text-xs"
                  placeholder='[{"icon":"Shield","title":"...","description":"..."}]'
                />
                <p className="text-xs text-muted-foreground">
                  Format: Array of objects with icon, title, and description
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* How It Works Section */}
        <TabsContent value="how_it_works">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>How It Works</CardTitle>
                  <CardDescription>Step-by-step guide for your users</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Label>Enabled</Label>
                  <Switch
                    checked={config.how_it_works.enabled}
                    onCheckedChange={(checked) => updateHowItWorks('enabled', checked)}
                  />
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Section Heading</Label>
                <Input
                  value={config.how_it_works.heading}
                  onChange={(e) => updateHowItWorks('heading', e.target.value)}
                  placeholder="How It Works"
                />
              </div>

              <div className="space-y-2">
                <Label>Steps (JSON Array)</Label>
                <Textarea
                  value={JSON.stringify(config.how_it_works.steps, null, 2)}
                  onChange={(e) => {
                    try {
                      const steps = JSON.parse(e.target.value);
                      updateHowItWorks('steps', steps);
                    } catch (error) {
                      // Invalid JSON
                    }
                  }}
                  rows={12}
                  className="font-mono text-xs"
                  placeholder='[{"number":1,"title":"...","description":"..."}]'
                />
                <p className="text-xs text-muted-foreground">
                  Format: Array of objects with number, title, and description
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Testimonials Section */}
        <TabsContent value="testimonials">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Testimonials</CardTitle>
                  <CardDescription>Customer reviews and feedback</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Label>Enabled</Label>
                  <Switch
                    checked={config.testimonials.enabled}
                    onCheckedChange={(checked) => updateTestimonials('enabled', checked)}
                  />
                </div>
              </div>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Section Heading</Label>
                <Input
                  value={config.testimonials.heading}
                  onChange={(e) => updateTestimonials('heading', e.target.value)}
                  placeholder="What Our Investors Say"
                />
              </div>

              <div className="space-y-2">
                <Label>Testimonials (JSON Array)</Label>
                <Textarea
                  value={JSON.stringify(config.testimonials.testimonials, null, 2)}
                  onChange={(e) => {
                    try {
                      const testimonials = JSON.parse(e.target.value);
                      updateTestimonials('testimonials', testimonials);
                    } catch (error) {
                      // Invalid JSON
                    }
                  }}
                  rows={12}
                  className="font-mono text-xs"
                  placeholder='[{"name":"...","role":"...","content":"...","avatar":"..."}]'
                />
                <p className="text-xs text-muted-foreground">
                  Format: Array of objects with name, role, content, and optional avatar URL
                </p>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
