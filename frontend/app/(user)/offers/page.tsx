'use client';

import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar, Gift, Percent, Tag, ArrowRight, Clock, CheckCircle } from "lucide-react";
import Link from "next/link";
import api from "@/lib/api";

export default function OffersPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['activeOffers'],
    queryFn: async () => {
      const response = await api.get('/offers/active');
      const responseData = response.data;
      if (Array.isArray(responseData)) return responseData;
      if (responseData?.data && Array.isArray(responseData.data)) return responseData.data;
      if (responseData?.offers && Array.isArray(responseData.offers)) return responseData.offers;
      return [];
    },
  });

  const offers = data || [];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-muted-foreground">Loading offers...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">Active Offers</h1>
        <p className="text-muted-foreground">
          Exclusive deals and promotions for our valued investors
        </p>
      </div>

      {/* Offers Grid */}
      {offers.length === 0 ? (
        <Card>
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Gift className="h-16 w-16 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">No Active Offers</h3>
            <p className="text-sm text-muted-foreground text-center">
              Check back soon for exciting deals and promotions!
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {offers.map((offer: any) => (
            <Card key={offer.id} className="overflow-hidden hover:shadow-lg transition-shadow">
              {/* Offer Image/Banner */}
              {offer.image_url && (
                <div className="relative h-48 bg-gradient-to-r from-primary/20 to-primary/5">
                  <img
                    src={offer.image_url}
                    alt={offer.title}
                    className="w-full h-full object-cover"
                  />
                  {offer.discount_percent && (
                    <div className="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full font-bold">
                      {offer.discount_percent}% OFF
                    </div>
                  )}
                </div>
              )}

              <CardHeader>
                <div className="flex items-start justify-between gap-2">
                  <div className="flex-1">
                    <CardTitle className="text-xl mb-2">{offer.title || offer.code}</CardTitle>
                    <CardDescription className="line-clamp-2">
                      {offer.description}
                    </CardDescription>
                  </div>
                  <Badge variant="secondary" className="font-mono">
                    {offer.code}
                  </Badge>
                </div>
              </CardHeader>

              <CardContent className="space-y-4">
                {/* Offer Details */}
                <div className="space-y-2 text-sm">
                  {offer.discount_amount && (
                    <div className="flex items-center gap-2">
                      <Tag className="h-4 w-4 text-muted-foreground" />
                      <span>Save ₹{offer.discount_amount.toLocaleString('en-IN')}</span>
                    </div>
                  )}
                  {offer.min_investment && (
                    <div className="flex items-center gap-2">
                      <Percent className="h-4 w-4 text-muted-foreground" />
                      <span>Min. investment: ₹{offer.min_investment.toLocaleString('en-IN')}</span>
                    </div>
                  )}
                  {offer.expiry && (
                    <div className="flex items-center gap-2">
                      <Clock className="h-4 w-4 text-muted-foreground" />
                      <span>Valid until {new Date(offer.expiry).toLocaleDateString('en-IN')}</span>
                    </div>
                  )}
                  {offer.usage_limit && (
                    <div className="flex items-center gap-2">
                      <CheckCircle className="h-4 w-4 text-muted-foreground" />
                      <span>{offer.usage_limit - (offer.usage_count || 0)} uses remaining</span>
                    </div>
                  )}
                </div>

                {/* CTA Button */}
                <Button className="w-full" asChild>
                  <Link href={`/offers/${offer.id}`}>
                    View Details <ArrowRight className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
