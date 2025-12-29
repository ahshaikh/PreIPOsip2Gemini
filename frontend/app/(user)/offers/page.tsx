// V-FINAL-1730-530 (Fix 404: Offers -> Campaigns Migration)
'use client';

import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Calendar, Tag, ArrowRight, Loader2, AlertCircle } from "lucide-react";
import Link from "next/link";

export default function OffersPage() {
  const { data: campaigns, isLoading, error } = useQuery({
    queryKey: ['campaigns-active'], // Renamed from 'offers-active'
    // FIX: Pointed to correct backend endpoint
    queryFn: async () => (await api.get('/campaigns/active')).data, 
  });

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-8 text-center text-red-500">
        <AlertCircle className="h-12 w-12 mx-auto mb-4" />
        <h3 className="text-lg font-semibold">Unable to load offers</h3>
        <p className="text-sm">Please check your connection and try again.</p>
      </div>
    );
  }

  return (
    <div className="container py-10 space-y-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Active Offers & Campaigns</h1>
        <p className="text-muted-foreground mt-2">
          Exclusive deals and investment opportunities curated for you.
        </p>
      </div>

      {campaigns && campaigns.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {campaigns.map((campaign: any) => (
            <Card key={campaign.id} className="flex flex-col h-full hover:shadow-lg transition-shadow">
              {/* Image Handling (Optional) */}
              <div className="h-48 bg-gradient-to-br from-primary/10 to-primary/5 relative overflow-hidden rounded-t-xl">
                 {campaign.meta_data?.banner_url ? (
                    <img 
                      src={campaign.meta_data.banner_url} 
                      alt={campaign.title}
                      className="w-full h-full object-cover"
                    />
                 ) : (
                    <div className="flex items-center justify-center h-full text-primary/40">
                       <Tag className="h-16 w-16" />
                    </div>
                 )}
                 <Badge className="absolute top-4 right-4 capitalize">
                    {campaign.type}
                 </Badge>
              </div>

              <CardHeader>
                <CardTitle className="line-clamp-1">{campaign.title}</CardTitle>
                <CardDescription className="line-clamp-2">
                  {campaign.description}
                </CardDescription>
              </CardHeader>

              <CardContent className="flex-grow">
                <div className="space-y-4">
                   <div className="flex items-center text-sm text-muted-foreground">
                      <Calendar className="mr-2 h-4 w-4" />
                      <span>Valid until {new Date(campaign.end_date).toLocaleDateString()}</span>
                   </div>
                   {/* Display Benefit Summary */}
                   <div className="bg-muted/50 p-3 rounded-md text-sm">
                      <span className="font-semibold text-primary">Benefit: </span>
                      {campaign.benefit_type === 'percentage_discount' && `${campaign.benefit_value}% Off`}
                      {campaign.benefit_type === 'fixed_amount' && `₹${campaign.benefit_value} Flat Discount`}
                      {campaign.benefit_type === 'bonus_credit' && `₹${campaign.benefit_value} Bonus Credits`}
                   </div>
                </div>
              </CardContent>

              <CardFooter>
                <Button asChild className="w-full">
                  {/* Updated Link to Campaign Detail */}
                  <Link href={`/offers/${campaign.id}`}>
                    View Details <ArrowRight className="ml-2 h-4 w-4" />
                  </Link>
                </Button>
              </CardFooter>
            </Card>
          ))}
        </div>
      ) : (
        <div className="text-center py-20 bg-muted/30 rounded-xl border border-dashed">
          <Tag className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
          <h3 className="text-xl font-semibold">No Active Campaigns</h3>
          <p className="text-muted-foreground">Check back later for new exclusive offers.</p>
        </div>
      )}
    </div>
  );
}