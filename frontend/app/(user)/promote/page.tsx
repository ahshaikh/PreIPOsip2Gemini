// V-FINAL-1730-202 | V-USER-PROMOTIONAL
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import Link from "next/link";
import {
  Share2, Copy, Facebook, Twitter, Linkedin, MessageCircle, Mail,
  QrCode, Download, Image, FileText, Video, Link2, ExternalLink,
  Users, Gift, Sparkles, TrendingUp, CheckCircle, Star, Trophy
} from "lucide-react";

// Social media post templates
const SOCIAL_TEMPLATES = [
  {
    id: 'invest',
    platform: 'all',
    title: 'Investment Opportunity',
    template: `I'm investing in pre-IPO companies through PreIPO SIP! Join me and start your wealth-building journey. Use my referral code: {CODE}

Sign up: {LINK}

#PreIPOSIP #Investing #WealthBuilding #PreIPO`,
  },
  {
    id: 'returns',
    platform: 'all',
    title: 'Returns Focused',
    template: `Looking for smart investment opportunities? I've been using PreIPO SIP to invest in promising companies before they go public!

Get started with my link: {LINK}
Referral code: {CODE}

#SmartInvesting #FinancialFreedom`,
  },
  {
    id: 'whatsapp',
    platform: 'whatsapp',
    title: 'WhatsApp Message',
    template: `Hey! I wanted to share something exciting with you.

I've been investing in pre-IPO companies through PreIPO SIP. It's a great way to build wealth by investing in companies before they go public.

If you're interested, you can sign up using my referral link: {LINK}

Or use my code: {CODE}

Let me know if you have any questions!`,
  },
  {
    id: 'email',
    platform: 'email',
    title: 'Email Template',
    template: `Subject: Exciting Investment Opportunity - PreIPO SIP

Hi,

I wanted to share an investment platform I've been using - PreIPO SIP. It allows you to invest in promising companies before they go public through systematic investment plans.

What I like about it:
- Invest in pre-IPO companies
- Start with small amounts
- Build wealth systematically
- Transparent and regulated

If you're interested, you can sign up using my referral link: {LINK}

Or use my referral code: {CODE}

Feel free to reach out if you have any questions!

Best regards`,
  },
];

export default function PromotePage() {
  const [activeTab, setActiveTab] = useState('share');
  const [selectedTemplate, setSelectedTemplate] = useState(SOCIAL_TEMPLATES[0]);
  const [customMessage, setCustomMessage] = useState('');

  // Fetch referral data
  const { data: referralData } = useQuery({
    queryKey: ['referrals'],
    queryFn: async () => (await api.get('/user/referrals')).data,
  });

  // Fetch promotional materials
  const { data: materials } = useQuery({
    queryKey: ['promotionalMaterials'],
    queryFn: async () => (await api.get('/user/promotional-materials')).data,
    enabled: activeTab === 'materials',
  });

  // Get referral link and code
  const baseUrl = typeof window !== 'undefined'
    ? window.location.origin
    : process.env.NEXT_PUBLIC_SITE_URL || 'https://preiposip.com';
  const referralCode = referralData?.stats?.referral_code || 'LOADING';
  const referralLink = `${baseUrl}/signup?ref=${referralCode}`;

  // Replace placeholders in template
  const getFormattedMessage = (template: string) => {
    return template
      .replace(/{LINK}/g, referralLink)
      .replace(/{CODE}/g, referralCode);
  };

  const copyToClipboard = (text: string, label: string = 'Text') => {
    navigator.clipboard.writeText(text);
    toast.success(`${label} Copied!`, { description: "Ready to paste" });
  };

  // Share functions
  const shareOnFacebook = (text: string) => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralLink)}&quote=${encodeURIComponent(text)}`, '_blank');
  };

  const shareOnTwitter = (text: string) => {
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareOnLinkedIn = () => {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(referralLink)}`, '_blank');
  };

  const shareOnWhatsApp = (text: string) => {
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Promote & Earn</h1>
          <p className="text-muted-foreground">Share with your network and earn rewards when they invest.</p>
        </div>
        <Link href="/referrals">
          <Button variant="outline">
            <Trophy className="mr-2 h-4 w-4" /> View Rewards
          </Button>
        </Link>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Your Referral Code</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Badge variant="secondary" className="font-mono text-lg px-3 py-1">
                {referralCode}
              </Badge>
              <Button variant="ghost" size="sm" onClick={() => copyToClipboard(referralCode, 'Code')}>
                <Copy className="h-4 w-4" />
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-green-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{referralData?.stats?.total_referrals || 0}</div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-yellow-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Multiplier</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">{referralData?.stats?.current_multiplier || 1}x</div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-purple-500">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Earnings</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-purple-600">
              ₹{Number(referralData?.stats?.total_earnings || 0).toLocaleString('en-IN')}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Your Link */}
      <Card className="bg-gradient-to-r from-primary/10 via-primary/5 to-background border-primary/20">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Link2 className="h-5 w-5" /> Your Unique Referral Link
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex gap-2">
            <Input value={referralLink} readOnly className="font-mono text-sm" />
            <Button onClick={() => copyToClipboard(referralLink, 'Link')}>
              <Copy className="h-4 w-4 mr-2" /> Copy
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Main Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="share">
            <Share2 className="mr-2 h-4 w-4" /> Quick Share
          </TabsTrigger>
          <TabsTrigger value="templates">
            <FileText className="mr-2 h-4 w-4" /> Message Templates
          </TabsTrigger>
          <TabsTrigger value="materials">
            <Image className="mr-2 h-4 w-4" /> Materials
          </TabsTrigger>
        </TabsList>

        {/* Quick Share Tab */}
        <TabsContent value="share">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Share on Social Media</CardTitle>
                <CardDescription>Spread the word on your favorite platforms</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <Button
                  className="w-full justify-start bg-green-500 hover:bg-green-600"
                  onClick={() => shareOnWhatsApp(getFormattedMessage(SOCIAL_TEMPLATES[2].template))}
                >
                  <MessageCircle className="mr-2 h-5 w-5" /> Share on WhatsApp
                </Button>
                <Button
                  className="w-full justify-start bg-blue-600 hover:bg-blue-700"
                  onClick={() => shareOnFacebook(getFormattedMessage(SOCIAL_TEMPLATES[0].template))}
                >
                  <Facebook className="mr-2 h-5 w-5" /> Share on Facebook
                </Button>
                <Button
                  className="w-full justify-start bg-sky-500 hover:bg-sky-600"
                  onClick={() => shareOnTwitter(getFormattedMessage(SOCIAL_TEMPLATES[0].template))}
                >
                  <Twitter className="mr-2 h-5 w-5" /> Share on Twitter
                </Button>
                <Button
                  className="w-full justify-start bg-blue-700 hover:bg-blue-800"
                  onClick={shareOnLinkedIn}
                >
                  <Linkedin className="mr-2 h-5 w-5" /> Share on LinkedIn
                </Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>How Sharing Works</CardTitle>
                <CardDescription>Earn rewards by referring friends</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {[
                  { step: 1, icon: Share2, text: 'Share your unique link on social media or with friends' },
                  { step: 2, icon: Users, text: 'Your friend signs up using your referral link' },
                  { step: 3, icon: CheckCircle, text: 'They complete KYC and make their first investment' },
                  { step: 4, icon: Gift, text: 'You both receive bonus rewards in your wallet!' },
                ].map((item) => (
                  <div key={item.step} className="flex items-start gap-3 p-3 bg-muted/30 rounded-lg">
                    <div className="p-2 bg-primary/10 rounded-full">
                      <item.icon className="h-4 w-4 text-primary" />
                    </div>
                    <div>
                      <p className="font-medium text-sm">Step {item.step}</p>
                      <p className="text-sm text-muted-foreground">{item.text}</p>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Templates Tab */}
        <TabsContent value="templates">
          <div className="grid gap-6 md:grid-cols-3">
            <div className="md:col-span-1 space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Select Template</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  {SOCIAL_TEMPLATES.map((template) => (
                    <div
                      key={template.id}
                      className={`p-3 border rounded-lg cursor-pointer transition-colors ${
                        selectedTemplate.id === template.id
                          ? 'border-primary bg-primary/5'
                          : 'hover:bg-muted/50'
                      }`}
                      onClick={() => setSelectedTemplate(template)}
                    >
                      <p className="font-medium text-sm">{template.title}</p>
                      <Badge variant="outline" className="text-xs mt-1">
                        {template.platform === 'all' ? 'All Platforms' : template.platform}
                      </Badge>
                    </div>
                  ))}
                </CardContent>
              </Card>
            </div>

            <div className="md:col-span-2">
              <Card>
                <CardHeader>
                  <CardTitle>{selectedTemplate.title}</CardTitle>
                  <CardDescription>Copy and share this message</CardDescription>
                </CardHeader>
                <CardContent>
                  <Textarea
                    value={getFormattedMessage(selectedTemplate.template)}
                    readOnly
                    rows={10}
                    className="font-mono text-sm"
                  />
                </CardContent>
                <CardFooter className="flex gap-2">
                  <Button onClick={() => copyToClipboard(getFormattedMessage(selectedTemplate.template), 'Message')}>
                    <Copy className="mr-2 h-4 w-4" /> Copy Message
                  </Button>
                  {selectedTemplate.platform === 'whatsapp' && (
                    <Button variant="outline" onClick={() => shareOnWhatsApp(getFormattedMessage(selectedTemplate.template))}>
                      <MessageCircle className="mr-2 h-4 w-4" /> Send on WhatsApp
                    </Button>
                  )}
                </CardFooter>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* Materials Tab */}
        <TabsContent value="materials">
          <Card>
            <CardHeader>
              <CardTitle>Promotional Materials</CardTitle>
              <CardDescription>Download banners, images, and videos to share</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-3">
                {materials?.length > 0 ? (
                  materials.map((material: any) => (
                    <Card key={material.id} className="overflow-hidden">
                      <div className="aspect-video bg-muted flex items-center justify-center">
                        {material.type === 'image' ? (
                          <img src={material.thumbnail_url || material.file_url} alt={material.title} className="object-cover w-full h-full" />
                        ) : material.type === 'video' ? (
                          <Video className="h-12 w-12 text-muted-foreground" />
                        ) : (
                          <FileText className="h-12 w-12 text-muted-foreground" />
                        )}
                      </div>
                      <CardContent className="p-4">
                        <h3 className="font-medium">{material.title}</h3>
                        <p className="text-sm text-muted-foreground">{material.description}</p>
                        <div className="flex gap-2 mt-3">
                          <Button size="sm" asChild>
                            <a href={material.file_url} download>
                              <Download className="mr-2 h-4 w-4" /> Download
                            </a>
                          </Button>
                        </div>
                      </CardContent>
                    </Card>
                  ))
                ) : (
                  <div className="col-span-3 text-center py-12 text-muted-foreground">
                    <Image className="h-12 w-12 mx-auto mb-4 opacity-50" />
                    <p className="text-lg font-medium">No Materials Available</p>
                    <p className="text-sm">Promotional materials will appear here when available.</p>
                    <Link href="/materials">
                      <Button variant="outline" className="mt-4">
                        Go to Materials Page
                      </Button>
                    </Link>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Tips Card */}
      <Card className="bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border-yellow-500/20">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Sparkles className="h-5 w-5 text-yellow-500" /> Pro Tips for More Referrals
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid md:grid-cols-4 gap-4">
            {[
              { tip: 'Share your personal experience with PreIPO SIP', icon: Star },
              { tip: 'Post during peak hours (evenings & weekends)', icon: TrendingUp },
              { tip: 'Use images and videos for better engagement', icon: Image },
              { tip: 'Follow up with interested friends personally', icon: MessageCircle },
            ].map((item, idx) => (
              <div key={idx} className="flex items-start gap-3 p-3 bg-background/50 rounded-lg border">
                <item.icon className="h-5 w-5 text-yellow-500 mt-0.5" />
                <p className="text-sm">{item.tip}</p>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
