'use client';

import { useQuery } from "@tanstack/react-query";
import DOMPurify from 'dompurify';
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
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
} from "lucide-react";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import api from "@/lib/api";

export default function OfferDetailPage() {
  const params = useParams();
  const router = useRouter();
  const offerId = params.id;

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

            {/* Terms and Conditions */}
            {offer.terms && (
              <>
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
              </>
            )}
          </div>

          <Separator />

          {/* CTA Section */}
          <div className="flex flex-col sm:flex-row gap-4">
            <Button size="lg" className="flex-1" onClick={() => router.push('/plans')}>
              Start Investing Now
            </Button>
            <Button size="lg" variant="outline" className="flex-1" onClick={copyCode}>
              <Copy className="mr-2 h-4 w-4" />
              Copy Offer Code
            </Button>
            <Button size="lg" variant="outline">
              <Share2 className="mr-2 h-4 w-4" />
              Share Offer
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
