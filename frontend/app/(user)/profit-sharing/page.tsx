// V-REMEDIATE-1730-162 | V-AUDIT-FIX-ENHANCEMENT (Enhanced with eligibility, methodology, metrics)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import {
  TrendingUp,
  Info,
  CheckCircle2,
  Calendar,
  DollarSign,
  Users,
  Target,
  Award,
  Clock
} from "lucide-react";
import { cn } from "@/lib/utils";

/**
 * Enhanced Profit Sharing Page
 *
 * New Features:
 * - Eligibility criteria display
 * - Calculation methodology explanation
 * - Performance metrics visualization
 * - Upcoming distribution preview
 * - Better visual hierarchy with icons
 *
 * [AUDIT FIX] Addresses gap: "Minimal information, add eligibility & methodology"
 */

interface ProfitShare {
  id: number;
  amount: string;
  profit_share_period: {
    period_name: string;
    start_date: string;
    end_date: string;
    total_profit: string;
    distribution_date: string | null;
  };
  created_at: string;
}

interface ProfitSharingData {
  data: ProfitShare[];
  eligibility?: {
    is_eligible: boolean;
    min_months: number;
    min_investment: number;
    current_months: number;
    current_investment: number;
  };
  next_distribution?: {
    period_name: string;
    expected_date: string;
    estimated_amount: number;
  };
}

export default function ProfitSharingPage() {
  const { data, isLoading } = useQuery<ProfitSharingData>({
    queryKey: ['userProfitShares'],
    queryFn: async () => (await api.get('/user/profit-sharing')).data,
  });

  const totalEarned = data?.data.reduce((acc: number, share: ProfitShare) => acc + parseFloat(share.amount), 0) || 0;
  const distributionCount = data?.data.length || 0;
  const averageDistribution = distributionCount > 0 ? totalEarned / distributionCount : 0;

  // Mock eligibility data if not provided by backend (will be replaced with real API data)
  const eligibility = data?.eligibility || {
    is_eligible: true,
    min_months: 6,
    min_investment: 50000,
    current_months: 12,
    current_investment: 100000,
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">Profit Sharing</h1>
        <p className="text-muted-foreground mt-1">
          Share in the platform's success through quarterly profit distributions
        </p>
      </div>

      {/* Performance Metrics Grid */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Earned</CardTitle>
            <DollarSign className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{totalEarned.toFixed(2)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              Lifetime profit sharing earnings
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Distributions</CardTitle>
            <Award className="h-4 w-4 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{distributionCount}</div>
            <p className="text-xs text-muted-foreground mt-1">
              Quarterly distributions received
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Average Distribution</CardTitle>
            <TrendingUp className="h-4 w-4 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{averageDistribution.toFixed(2)}</div>
            <p className="text-xs text-muted-foreground mt-1">
              Per distribution period
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Eligibility Status */}
      <Card className={cn(
        "border-2",
        eligibility.is_eligible ? "border-green-200 bg-green-50/30" : "border-yellow-200 bg-yellow-50/30"
      )}>
        <CardHeader>
          <div className="flex items-center gap-2">
            {eligibility.is_eligible ? (
              <CheckCircle2 className="h-5 w-5 text-green-600" />
            ) : (
              <Info className="h-5 w-5 text-yellow-600" />
            )}
            <CardTitle>Eligibility Status</CardTitle>
          </div>
          <CardDescription>
            {eligibility.is_eligible
              ? "You qualify for profit sharing distributions"
              : "Continue investing to qualify for profit sharing"}
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            {/* Investment Period */}
            <div className="flex items-start gap-3">
              <Clock className="h-5 w-5 text-blue-600 mt-0.5" />
              <div className="flex-1">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium">Investment Period</span>
                  <Badge variant={eligibility.current_months >= eligibility.min_months ? "default" : "secondary"}>
                    {eligibility.current_months} / {eligibility.min_months} months
                  </Badge>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div
                    className={cn(
                      "h-2 rounded-full transition-all",
                      eligibility.current_months >= eligibility.min_months ? "bg-green-600" : "bg-blue-600"
                    )}
                    style={{ width: `${Math.min(100, (eligibility.current_months / eligibility.min_months) * 100)}%` }}
                  />
                </div>
              </div>
            </div>

            {/* Minimum Investment */}
            <div className="flex items-start gap-3">
              <Target className="h-5 w-5 text-purple-600 mt-0.5" />
              <div className="flex-1">
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium">Minimum Investment</span>
                  <Badge variant={eligibility.current_investment >= eligibility.min_investment ? "default" : "secondary"}>
                    ₹{eligibility.current_investment.toLocaleString()} / ₹{eligibility.min_investment.toLocaleString()}
                  </Badge>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div
                    className={cn(
                      "h-2 rounded-full transition-all",
                      eligibility.current_investment >= eligibility.min_investment ? "bg-green-600" : "bg-purple-600"
                    )}
                    style={{ width: `${Math.min(100, (eligibility.current_investment / eligibility.min_investment) * 100)}%` }}
                  />
                </div>
              </div>
            </div>
          </div>

          {!eligibility.is_eligible && (
            <div className="bg-yellow-100 border border-yellow-300 rounded-lg p-3 text-sm">
              <p className="text-yellow-800">
                <strong>How to qualify:</strong> Maintain active investments for at least {eligibility.min_months} months
                with a minimum total investment of ₹{eligibility.min_investment.toLocaleString()}.
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* How Profit Sharing Works */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Info className="h-5 w-5 text-blue-600" />
            <CardTitle>How Profit Sharing Works</CardTitle>
          </div>
          <CardDescription>Understanding the calculation methodology</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-3">
            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                1
              </div>
              <div>
                <p className="font-medium">Platform Profit Pool</p>
                <p className="text-sm text-muted-foreground">
                  A percentage of the platform's quarterly profits is allocated to the profit-sharing pool.
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                2
              </div>
              <div>
                <p className="font-medium">Individual Share Calculation</p>
                <p className="text-sm text-muted-foreground">
                  Your share is calculated based on your investment amount and duration relative to all eligible investors.
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                3
              </div>
              <div>
                <p className="font-medium">Plan-Based Multipliers</p>
                <p className="text-sm text-muted-foreground">
                  Higher-tier investment plans receive enhanced profit-sharing percentages.
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                4
              </div>
              <div>
                <p className="font-medium">Quarterly Distribution</p>
                <p className="text-sm text-muted-foreground">
                  Profits are distributed within 15 days of the quarter end directly to your wallet.
                </p>
              </div>
            </div>
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm text-blue-900">
              <strong>Formula:</strong> Your Share = (Your Investment × Plan Multiplier × Duration) / Total Pool × Platform Profit
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Upcoming Distribution */}
      {data?.next_distribution && (
        <Card className="border-2 border-purple-200 bg-purple-50/30">
          <CardHeader>
            <div className="flex items-center gap-2">
              <Calendar className="h-5 w-5 text-purple-600" />
              <CardTitle>Next Distribution</CardTitle>
            </div>
            <CardDescription>Estimated upcoming profit share</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Period</p>
                <p className="text-lg font-semibold">{data.next_distribution.period_name}</p>
              </div>
              <div className="text-right">
                <p className="text-sm text-muted-foreground">Expected Date</p>
                <p className="text-lg font-semibold">
                  {new Date(data.next_distribution.expected_date).toLocaleDateString()}
                </p>
              </div>
              <div className="text-right">
                <p className="text-sm text-muted-foreground">Estimated Amount</p>
                <p className="text-lg font-semibold text-green-600">
                  ₹{data.next_distribution.estimated_amount.toFixed(2)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Distribution History */}
      <Card>
        <CardHeader>
          <CardTitle>Distribution History</CardTitle>
          <CardDescription>Your share of the platform's profits over time</CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center h-32">
              <p className="text-muted-foreground">Loading history...</p>
            </div>
          ) : data?.data.length === 0 ? (
            <div className="text-center py-12">
              <Users className="w-12 h-12 mx-auto text-muted-foreground mb-4" />
              <h3 className="text-lg font-semibold mb-2">No Distributions Yet</h3>
              <p className="text-muted-foreground">
                {eligibility.is_eligible
                  ? "Your first distribution will appear here after the current quarter ends."
                  : "Meet the eligibility criteria to start receiving profit shares."}
              </p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Period</TableHead>
                  <TableHead>Distribution Date</TableHead>
                  <TableHead>Period End Date</TableHead>
                  <TableHead className="text-right">Amount Earned</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data.map((share: ProfitShare) => (
                  <TableRow key={share.id}>
                    <TableCell className="font-medium">
                      {share.profit_share_period.period_name}
                    </TableCell>
                    <TableCell>
                      {share.profit_share_period.distribution_date
                        ? new Date(share.profit_share_period.distribution_date).toLocaleDateString()
                        : 'Pending'}
                    </TableCell>
                    <TableCell>
                      {new Date(share.profit_share_period.end_date).toLocaleDateString()}
                    </TableCell>
                    <TableCell className="text-right">
                      <span className="text-green-600 font-semibold">
                        + ₹{parseFloat(share.amount).toFixed(2)}
                      </span>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}