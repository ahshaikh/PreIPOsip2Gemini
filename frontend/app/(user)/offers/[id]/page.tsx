'use client';

import { useQuery } from "@tanstack/react-query";
import DOMPurify from 'dompurify';
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Calendar,
  Gift,
  Percent,
  Tag,
  Copy,
  CheckCircle,
  Clock,
  ArrowLeft,
  Share2,
  TrendingUp,
  FileText,
  Facebook,
  Twitter,
  Linkedin,
  Mail,
  MessageCircle,
  Send,
  Bot,
  Hash,
  MessageSquareText,
  Instagram,
} from "lucide-react";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import { useState } from "react";
import api from "@/lib/api";

export default function OfferDetailPage() {
  const params = useParams();
  const router = useRouter();
  const offerId = params.id;
  const [shareDialogOpen, setShareDialogOpen] = useState(false);

  const { data: offer, isLoading } = useQuery({
    queryKey: ['campaign', offerId],
    queryFn: async () => {
      const response = await api.get(`/campaigns/${offerId}`);
      return response.data?.data || response.data;
    },
  });

  const copyCode = () => {
    if (offer?.code) {
      navigator.clipboard.writeText(offer.code);
      toast.success("Code Copied!", { description: "Offer code copied to clipboard" });
    }
  };

  const scrollToHowToUse = () => {
    const howToUseElement = document.getElementById('how-to-use-section');
    if (howToUseElement) {
      howToUseElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  };

  // Share functions
  const baseUrl = typeof window !== 'undefined' ? window.location.origin : 'https://preiposip.com';
  const offerLink = `${baseUrl}/offers/${offerId}`;
  const offerCode = offer?.code || '';

  const shareOnFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(offerLink)}`, '_blank');
  };

  const shareOnTwitter = () => {
    const text = `Check out this amazing offer on PreIPO SIP! Use code ${offerCode} to get started:`;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(offerLink)}`, '_blank');
  };

  const shareOnLinkedIn = () => {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(offerLink)}`, '_blank');
  };

  const shareOnWhatsApp = () => {
    const text = `Check out this offer on PreIPO SIP! Use code ${offerCode}: ${offerLink}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareViaEmail = () => {
    const subject = `Check out this offer on PreIPO SIP - ${offer?.title || 'Special Offer'}`;
    const body = `Hi,\n\nI found this great offer on PreIPO SIP!\n\n${offer?.title || 'Special Offer'}\n${offer?.description || ''}\n\nUse code: ${offerCode}\nView offer: ${offerLink}`;
    window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  };

  const shareOnTelegram = () => {
    const text = `Check out this offer on PreIPO SIP! Use code ${offerCode}`;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(offerLink)}&text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareOnReddit = () => {
    const title = offer?.title || 'Great Investment Offer on PreIPO SIP';
    window.open(`https://reddit.com/submit?url=${encodeURIComponent(offerLink)}&title=${encodeURIComponent(title)}`, '_blank');
  };

  const shareOnThreads = () => {
    const text = `Check out this offer on PreIPO SIP! Use code: ${offerCode}`;
    window.open(`https://threads.net/intent/post?text=${encodeURIComponent(text + ' ' + offerLink)}`, '_blank');
  };

  const shareOnDiscord = () => {
    const message = `🎁 Great Offer on PreIPO SIP!\n\n${offer?.title || 'Special Offer'}\nUse code: **${offerCode}**\n${offerLink}`;
    navigator.clipboard.writeText(message);
    toast.success("Message Copied!", { description: "Paste this in Discord to share" });
  };

  const shareOnSignal = () => {
    const message = `Check out this offer on PreIPO SIP! Use code ${offerCode}: ${offerLink}`;
    navigator.clipboard.writeText(message);
    toast.success("Message Copied!", { description: "Paste this in Signal to share" });
  };

  const shareOnLine = () => {
    const text = `Check out this offer! Use code ${offerCode}`;
    window.open(`https://social-plugins.line.me/lineit/share?url=${encodeURIComponent(offerLink)}&text=${encodeURIComponent(text)}`, '_blank');
  };

  const shareOnInstagram = () => {
    const message = `📈 Great Offer on PreIPO SIP!\n\n${offer?.title || 'Special Offer'}\nUse code: ${offerCode}\nLink: ${offerLink}`;
    navigator.clipboard.writeText(message);
    toast.success("Message Copied!", { description: "Paste this in your Instagram Story or Bio" });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-muted-foreground">Loading offer details...</div>
      </div>
    );
  }

  if (!offer) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[400px] space-y-4">
        <Gift className="h-16 w-16 text-muted-foreground" />
        <h2 className="text-2xl font-bold">Offer Not Found</h2>
        <Button onClick={() => router.push('/offers')}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Back to Offers
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Back Button */}
      <Button variant="ghost" onClick={() => router.push('/offers')}>
        <ArrowLeft className="mr-2 h-4 w-4" />
        Back to All Offers
      </Button>

      {/* Hero Section with Image/Video */}
      <Card className="overflow-hidden">
        <div className="relative">
          {offer.hero_image || offer.image_url ? (
            <div className="relative h-64 md:h-96 bg-gradient-to-r from-primary/20 to-primary/10">
              <img
                src={offer.hero_image || offer.image_url}
                alt={offer.title}
                className="w-full h-full object-cover"
              />
              {offer.discount_percent && (
                <div className="absolute top-6 right-6 bg-red-500 text-white px-6 py-3 rounded-lg text-2xl font-bold shadow-lg">
                  {offer.discount_percent}% OFF
                </div>
              )}
            </div>
          ) : (
            <div className="h-64 md:h-96 bg-gradient-to-r from-primary/20 via-primary/10 to-primary/5 flex items-center justify-center">
              <Gift className="h-24 w-24 text-primary/40" />
            </div>
          )}
        </div>

        <CardContent className="p-8 space-y-6">
          {/* Title and Badge */}
          <div className="space-y-4">
            <div className="flex items-start justify-between gap-4">
              <h1 className="text-4xl font-bold">{offer.title || 'Exclusive Offer'}</h1>
              <Badge variant="secondary" className="text-lg px-4 py-2">
                {offer.status === 'active' ? 'Active' : offer.status}
              </Badge>
            </div>

            {offer.subtitle && (
              <p className="text-xl text-muted-foreground">{offer.subtitle}</p>
            )}
          </div>

          {/* Promo Code */}
          <Card className="bg-primary/5 border-primary/20">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground">Promo Code</p>
                  <p className="text-3xl font-bold font-mono tracking-wider">{offer.code}</p>
                </div>
                <Button onClick={copyCode} size="lg">
                  <Copy className="mr-2 h-4 w-4" />
                  Copy Code
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Key Benefits */}
          <div className="grid md:grid-cols-3 gap-4">
            {offer.discount_amount && (
              <Card>
                <CardContent className="p-6 text-center">
                  <Tag className="h-8 w-8 mx-auto mb-2 text-primary" />
                  <p className="text-2xl font-bold text-primary">
                    ₹{offer.discount_amount.toLocaleString('en-IN')}
                  </p>
                  <p className="text-sm text-muted-foreground">Instant Savings</p>
                </CardContent>
              </Card>
            )}

            {offer.min_investment && (
              <Card>
                <CardContent className="p-6 text-center">
                  <TrendingUp className="h-8 w-8 mx-auto mb-2 text-primary" />
                  <p className="text-2xl font-bold text-primary">
                    ₹{offer.min_investment.toLocaleString('en-IN')}
                  </p>
                  <p className="text-sm text-muted-foreground">Minimum Investment</p>
                </CardContent>
              </Card>
            )}

            {offer.end_at && (
              <Card>
                <CardContent className="p-6 text-center">
                  <Clock className="h-8 w-8 mx-auto mb-2 text-primary" />
                  <p className="text-2xl font-bold text-primary">
                    {new Date(offer.end_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' })}
                  </p>
                  <p className="text-sm text-muted-foreground">Valid Until</p>
                </CardContent>
              </Card>
            )}
          </div>

          <Separator />

          {/* Description/Marketing Content */}
          <div className="prose prose-lg max-w-none">
            <h2>About This Offer</h2>
            <p>{offer.description || 'Get amazing discounts on your investments!'}</p>

            {offer.long_description && (
              <div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(offer.long_description) }} />
            )}

            {/* Features List */}
            {offer.features && Array.isArray(offer.features) && (
              <>
                <h3>What You Get</h3>
                <ul>
                  {offer.features.map((feature: string, index: number) => (
                    <li key={index} className="flex items-start gap-2">
                      <CheckCircle className="h-5 w-5 text-green-500 mt-1 flex-shrink-0" />
                      <span>{feature}</span>
                    </li>
                  ))}
                </ul>
              </>
            )}

            {/* Video Embed */}
            {offer.video_url && (
              <>
                <h3>Watch How It Works</h3>
                <div className="relative aspect-video rounded-lg overflow-hidden bg-black">
                  {offer.video_url.includes('youtube.com') || offer.video_url.includes('youtu.be') ? (
                    <iframe
                      src={offer.video_url.replace('watch?v=', 'embed/')}
                      className="w-full h-full"
                      allowFullScreen
                    />
                  ) : (
                    <video controls className="w-full h-full">
                      <source src={offer.video_url} />
                    </video>
                  )}
                </div>
              </>
            )}

            {/* How to Use Section - ALWAYS EXISTS */}
            <div id="how-to-use-section">
              <h3>How to Use This Offer</h3>
              <ol className="space-y-2">
                <li>
                  <strong>Step 1:</strong> Copy the promo code <code className="bg-primary/10 px-2 py-1 rounded text-primary font-mono">{offer.code}</code> above
                </li>
                <li>
                  <strong>Step 2:</strong> Browse available companies on the <a href="/deals" className="text-primary underline">Deals page</a>
                </li>
                <li>
                  <strong>Step 3:</strong> Select a company and choose how many shares to buy
                </li>
                <li>
                  <strong>Step 4:</strong> At checkout, enter your campaign code in the "Campaign Code" field
                </li>
                <li>
                  <strong>Step 5:</strong> Click "Apply" to validate the code and see your discount
                </li>
                <li>
                  <strong>Step 6:</strong> Accept the campaign terms and complete your investment
                </li>
              </ol>
              {offer.min_investment && (
                <p className="text-sm text-muted-foreground mt-4">
                  <strong>Note:</strong> Minimum investment of ₹{offer.min_investment.toLocaleString('en-IN')} required to use this offer.
                </p>
              )}
            </div>

            {/* Terms and Conditions */}
            {offer.terms && (
              <div>
                <h3>Terms & Conditions</h3>
                {typeof offer.terms === 'string' ? (
                  <p className="text-sm text-muted-foreground">{offer.terms}</p>
                ) : (
                  <ul className="text-sm text-muted-foreground">
                    {offer.terms.map((term: string, index: number) => (
                      <li key={index}>{term}</li>
                    ))}
                  </ul>
                )}
              </div>
            )}
          </div>

          <Separator />

          {/* CTA Section */}
          <div className="flex flex-col sm:flex-row gap-4">
            <Button size="lg" className="flex-1" onClick={() => router.push('/deals')}>
              Start Investing Now
            </Button>
            <Button size="lg" variant="outline" className="flex-1" onClick={scrollToHowToUse}>
              <FileText className="mr-2 h-4 w-4" />
              How to Use
            </Button>
            <Button size="lg" variant="outline" onClick={() => setShareDialogOpen(true)}>
              <Share2 className="mr-2 h-4 w-4" />
              Share Offer
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Share Dialog */}
      <Dialog open={shareDialogOpen} onOpenChange={setShareDialogOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Share This Offer</DialogTitle>
            <DialogDescription>
              Share this amazing offer with your friends and family
            </DialogDescription>
          </DialogHeader>
          <div className="flex flex-wrap gap-2 py-4">
            <Button variant="outline" size="sm" onClick={shareOnWhatsApp} className="bg-green-500/10 hover:bg-green-500/20 border-green-500/30">
              <MessageCircle className="h-4 w-4 mr-2 text-green-500" /> WhatsApp
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnTelegram} className="bg-sky-400/10 hover:bg-sky-400/20 border-sky-400/30">
              <Send className="h-4 w-4 mr-2 text-sky-400" /> Telegram
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnFacebook} className="bg-blue-500/10 hover:bg-blue-500/20 border-blue-500/30">
              <Facebook className="h-4 w-4 mr-2 text-blue-500" /> Facebook
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnInstagram} className="bg-pink-500/10 hover:bg-pink-500/20 border-pink-500/30">
              <Instagram className="h-4 w-4 mr-2 text-pink-500" /> Instagram
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnTwitter} className="bg-sky-500/10 hover:bg-sky-500/20 border-sky-500/30">
              <Twitter className="h-4 w-4 mr-2 text-sky-500" /> Twitter
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnThreads} className="bg-black/10 hover:bg-black/20 border-black/30 dark:bg-white/10 dark:hover:bg-white/20 dark:border-white/30">
              <MessageSquareText className="h-4 w-4 mr-2" /> Threads
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnLinkedIn} className="bg-blue-700/10 hover:bg-blue-700/20 border-blue-700/30">
              <Linkedin className="h-4 w-4 mr-2 text-blue-700" /> LinkedIn
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnReddit} className="bg-orange-500/10 hover:bg-orange-500/20 border-orange-500/30">
              <Hash className="h-4 w-4 mr-2 text-orange-500" /> Reddit
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnDiscord} className="bg-indigo-500/10 hover:bg-indigo-500/20 border-indigo-500/30">
              <Bot className="h-4 w-4 mr-2 text-indigo-500" /> Discord
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnSignal} className="bg-blue-600/10 hover:bg-blue-600/20 border-blue-600/30">
              <MessageCircle className="h-4 w-4 mr-2 text-blue-600" /> Signal
            </Button>
            <Button variant="outline" size="sm" onClick={shareOnLine} className="bg-green-600/10 hover:bg-green-600/20 border-green-600/30">
              <MessageCircle className="h-4 w-4 mr-2 text-green-600" /> Line
            </Button>
            <Button variant="outline" size="sm" onClick={shareViaEmail}>
              <Mail className="h-4 w-4 mr-2" /> Email
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
