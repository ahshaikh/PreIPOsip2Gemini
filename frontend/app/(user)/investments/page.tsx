'use client';

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { TrendingUp, TrendingDown, Building2, Calendar, DollarSign, PieChart, ArrowUpRight, ArrowDownRight } from "lucide-react";
import Link from "next/link";
// GAP 36 FIX: Import snapshot comparison components
import { SnapshotComparisonUI, SnapshotChangeIndicator } from "@/components/features/investment/SnapshotComparison";

export default function InvestmentsPage() {
  // GAP 36 FIX: State for snapshot comparison modal
  const [selectedInvestmentId, setSelectedInvestmentId] = useState<number | null>(null);

  const { data: portfolioResponse } = useQuery({
    queryKey: ['portfolio'],
    queryFn: async () => (await api.get('/user/portfolio')).data,
  });

  const { data: investmentsResponse, isLoading } = useQuery({
    queryKey: ['userInvestments'],
    queryFn: async () => (await api.get('/user/investments')).data,
  });

  const portfolio = portfolioResponse?.portfolio || {};
  const investments = investmentsResponse?.investments || [];

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const formatPercentage = (value: number) => {
    const formatted = Math.abs(value).toFixed(2);
    return value >= 0 ? `+${formatted}%` : `-${formatted}%`;
  };

  if (isLoading) {
    return (
      <div className="container py-20">
        <div className="animate-pulse space-y-4">
          <div className="h-12 bg-gray-200 rounded w-1/3"></div>
          <div className="grid md:grid-cols-4 gap-6">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="h-32 bg-gray-200 rounded-lg"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  const profitLossPercentage = portfolio.unrealized_profit_loss_percentage || 0;
  const isProfitable = profitLossPercentage >= 0;

  return (
    <div className="container py-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-4xl font-bold mb-2">My Investments</h1>
          <p className="text-muted-foreground">
            Track your portfolio and investment performance
          </p>
        </div>
        <Link href="/deals">
          <Button size="lg">
            <PieChart className="mr-2 w-4 h-4" />
            Browse Deals
          </Button>
        </Link>
      </div>

      {/* Portfolio Summary */}
      <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card>
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-muted-foreground">Total Invested</p>
              <DollarSign className="w-4 h-4 text-muted-foreground" />
            </div>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{formatCurrency(portfolio.total_invested || 0)}</p>
            <p className="text-xs text-muted-foreground mt-1">
              {portfolio.active_investments_count || 0} active investments
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-muted-foreground">Current Value</p>
              <TrendingUp className="w-4 h-4 text-muted-foreground" />
            </div>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{formatCurrency(portfolio.total_current_value || 0)}</p>
            <p className="text-xs text-muted-foreground mt-1">
              Live market value
            </p>
          </CardContent>
        </Card>

        <Card className={isProfitable ? 'border-green-200 bg-green-50/50 dark:bg-green-900/10' : 'border-red-200 bg-red-50/50 dark:bg-red-900/10'}>
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-muted-foreground">Profit/Loss</p>
              {isProfitable ? (
                <ArrowUpRight className="w-4 h-4 text-green-600" />
              ) : (
                <ArrowDownRight className="w-4 h-4 text-red-600" />
              )}
            </div>
          </CardHeader>
          <CardContent>
            <p className={`text-3xl font-bold ${isProfitable ? 'text-green-600' : 'text-red-600'}`}>
              {formatCurrency(portfolio.unrealized_profit_loss || 0)}
            </p>
            <p className={`text-xs font-semibold mt-1 ${isProfitable ? 'text-green-600' : 'text-red-600'}`}>
              {formatPercentage(profitLossPercentage)}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-muted-foreground">Holdings</p>
              <PieChart className="w-4 h-4 text-muted-foreground" />
            </div>
          </CardHeader>
          <CardContent>
            <p className="text-3xl font-bold">{portfolio.active_investments_count || 0}</p>
            <p className="text-xs text-muted-foreground mt-1">
              {portfolio.pending_investments_count || 0} pending
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Investments List */}
      <div className="space-y-4">
        <h2 className="text-2xl font-bold">Your Holdings</h2>

        {investments.length === 0 ? (
          <Card>
            <CardContent className="py-16 text-center">
              <Building2 className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
              <h3 className="text-2xl font-semibold mb-2">No Investments Yet</h3>
              <p className="text-muted-foreground mb-6">
                Start investing in pre-IPO companies to build your portfolio
              </p>
              <Link href="/deals">
                <Button size="lg">
                  Browse Available Deals
                </Button>
              </Link>
            </CardContent>
          </Card>
        ) : (
          investments.map((investment: any) => {
            const profitLoss = investment.unrealized_profit_loss || 0;
            const profitLossPercent = investment.profit_loss_percentage || 0;
            const isProfit = profitLoss >= 0;

            return (
              <Card key={investment.id} className="hover:shadow-md transition-shadow">
                <CardContent className="py-6">
                  <div className="flex items-start justify-between gap-4">
                    <div className="flex-1">
                      {/* Company Info */}
                      <div className="flex items-center gap-3 mb-3">
                        <div className="flex-1">
                          <h3 className="text-xl font-bold">{investment.deal?.company_name || 'Company'}</h3>
                          <p className="text-sm text-muted-foreground">{investment.deal?.title}</p>
                        </div>
                        <Badge variant={investment.status === 'active' ? 'default' : 'secondary'}>
                          {investment.status}
                        </Badge>
                      </div>

                      {/* Investment Stats */}
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                        <div>
                          <p className="text-xs text-muted-foreground mb-1">Shares</p>
                          <p className="font-semibold">{investment.shares_allocated}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground mb-1">Invested</p>
                          <p className="font-semibold">{formatCurrency(investment.total_amount)}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground mb-1">Current Value</p>
                          <p className="font-semibold">{formatCurrency(investment.current_value || investment.total_amount)}</p>
                        </div>
                        <div>
                          <p className="text-xs text-muted-foreground mb-1">P/L</p>
                          <div>
                            <p className={`font-semibold ${isProfit ? 'text-green-600' : 'text-red-600'}`}>
                              {isProfit ? '+' : ''}{formatCurrency(profitLoss)}
                            </p>
                            <p className={`text-xs font-medium ${isProfit ? 'text-green-600' : 'text-red-600'}`}>
                              {formatPercentage(profitLossPercent)}
                            </p>
                          </div>
                        </div>
                      </div>

                      {/* Additional Info */}
                      <div className="flex items-center gap-4 mt-4 text-sm text-muted-foreground">
                        <div className="flex items-center gap-1">
                          <Calendar className="w-3 h-3" />
                          <span>Invested: {new Date(investment.invested_at).toLocaleDateString('en-IN')}</span>
                        </div>
                        <div className="flex items-center gap-1">
                          <DollarSign className="w-3 h-3" />
                          <span>Avg Price: {formatCurrency(investment.price_per_share)}</span>
                        </div>
                        {/* GAP 36 FIX: Snapshot comparison indicator */}
                        <div className="ml-auto">
                          <SnapshotChangeIndicator
                            investmentId={investment.id}
                            onViewDetails={() => setSelectedInvestmentId(investment.id)}
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            );
          })
        )}
      </div>

      {/* GAP 36 FIX: Snapshot comparison modal */}
      <Dialog open={selectedInvestmentId !== null} onOpenChange={() => setSelectedInvestmentId(null)}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Platform State Comparison</DialogTitle>
          </DialogHeader>
          {selectedInvestmentId && (
            <SnapshotComparisonUI investmentId={selectedInvestmentId} />
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
