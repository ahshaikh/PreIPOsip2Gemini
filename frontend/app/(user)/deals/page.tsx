'use client';

import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Building2, TrendingUp, Calendar, DollarSign, ArrowRight, Search, CheckCircle2 } from "lucide-react";
import { useState, useEffect } from "react";
import { InvestmentModal } from "@/components/features/InvestmentModal";
import { useSearchParams } from "next/navigation";
import { toast } from "sonner";

export default function DealsPage() {
  const searchParams = useSearchParams();
  const [selectedDeal, setSelectedDeal] = useState<any>(null);
  const [showInvestModal, setShowInvestModal] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // Show welcome toast for first-time visitors
  useEffect(() => {
    if (searchParams.get('welcome') === 'true') {
      toast.success("Welcome to Investments!", {
        description: "Your subscription is active. Start investing in pre-IPO companies now!",
        duration: 5000,
      });
    }
  }, [searchParams]);

  const { data: response, isLoading } = useQuery({
    queryKey: ['userDeals', searchQuery],
    queryFn: async () => {
      const params = searchQuery ? { search: searchQuery } : {};
      return (await api.get('/user/deals', { params })).data;
    },
  });

  const deals = response?.deals || [];

  const handleInvest = (deal: any) => {
    setSelectedDeal(deal);
    setShowInvestModal(true);
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (isLoading) {
    return (
      <div className="container py-20">
        <div className="animate-pulse space-y-4">
          <div className="h-12 bg-gray-200 rounded w-1/3"></div>
          <div className="h-4 bg-gray-200 rounded w-1/2"></div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="h-80 bg-gray-200 rounded-lg"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container py-8">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-4xl font-bold mb-2">Available Deals</h1>
        <p className="text-muted-foreground">
          Browse and invest in pre-IPO companies from your subscription
        </p>
      </div>

      {/* Search */}
      <div className="mb-6">
        <div className="relative max-w-md">
          <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search by company name..."
            className="pl-10"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
      </div>

      {/* Deals Grid */}
      {deals.length === 0 ? (
        <Card>
          <CardContent className="py-16 text-center">
            <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-2xl font-semibold mb-2">No Deals Available</h3>
            <p className="text-muted-foreground mb-6">
              {searchQuery
                ? "No deals match your search. Try a different query."
                : "Check back soon for new investment opportunities!"}
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {deals.map((deal: any) => (
            <Card key={deal.id} className="hover:shadow-lg transition-shadow flex flex-col">
              <CardHeader>
                <div className="flex items-start justify-between mb-2">
                  <div className="flex-1">
                    <CardTitle className="text-xl mb-1">{deal.title}</CardTitle>
                    <p className="text-sm text-muted-foreground font-medium">{deal.company_name}</p>
                  </div>
                  {deal.is_featured && (
                    <Badge variant="secondary" className="bg-purple-100 text-purple-700 ml-2">
                      Featured
                    </Badge>
                  )}
                </div>
                {deal.sector && (
                  <Badge variant="outline" className="w-fit">
                    {deal.sector}
                  </Badge>
                )}
              </CardHeader>
              <CardContent className="space-y-4 flex-1 flex flex-col">
                <div className="space-y-2 flex-1">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground flex items-center gap-1">
                      <DollarSign className="w-4 h-4" />
                      Share Price
                    </span>
                    <span className="font-semibold">{formatCurrency(deal.share_price)}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground flex items-center gap-1">
                      <TrendingUp className="w-4 h-4" />
                      Min Investment
                    </span>
                    <span className="font-semibold">{formatCurrency(deal.min_investment)}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Available Shares</span>
                    <span className="font-semibold">
                      {deal.remaining_shares?.toLocaleString('en-IN') || deal.available_shares?.toLocaleString('en-IN')}
                    </span>
                  </div>
                  {deal.deal_closes_at && (
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground flex items-center gap-1">
                        <Calendar className="w-4 h-4" />
                        Closes
                      </span>
                      <span className="font-semibold">{formatDate(deal.deal_closes_at)}</span>
                    </div>
                  )}

                  {deal.user_investment && (
                    <div className="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
                      <div className="flex items-center gap-2 text-green-700 dark:text-green-400">
                        <CheckCircle2 className="w-4 h-4" />
                        <span className="text-sm font-medium">
                          You own {deal.user_investment.shares_allocated} shares
                        </span>
                      </div>
                    </div>
                  )}
                </div>

                <Button
                  onClick={() => handleInvest(deal)}
                  className="w-full"
                  disabled={!deal.is_available}
                >
                  {deal.user_investment ? 'Invest More' : 'Invest Now'}
                  <ArrowRight className="ml-2 w-4 h-4" />
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Investment Modal */}
      {showInvestModal && selectedDeal && (
        <InvestmentModal
          isOpen={showInvestModal}
          onClose={() => {
            setShowInvestModal(false);
            setSelectedDeal(null);
          }}
          deal={selectedDeal}
        />
      )}
    </div>
  );
}
