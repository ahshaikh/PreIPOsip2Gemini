// V-PHASE6-ANALYTICS-1208 (Created)
'use client';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { TrendingUp, TrendingDown, Users, IndianRupee, Award, Activity, Percent, Calendar } from 'lucide-react';

interface PlanAnalyticsProps {
  plans: any[];
}

export function PlanAnalyticsDashboard({ plans }: PlanAnalyticsProps) {
  // Calculate total subscribers across all plans
  const totalSubscribers = plans?.reduce((sum, plan) => sum + (plan.subscribers_count || 0), 0) || 0;

  // Calculate total monthly revenue
  const totalMonthlyRevenue = plans?.reduce((sum, plan) => {
    return sum + ((plan.subscribers_count || 0) * parseFloat(plan.monthly_amount || 0));
  }, 0) || 0;

  // Calculate total projected revenue (monthly * duration * subscribers)
  const totalProjectedRevenue = plans?.reduce((sum, plan) => {
    const monthly = parseFloat(plan.monthly_amount || 0);
    const duration = parseInt(plan.duration_months || 0);
    const subscribers = parseInt(plan.subscribers_count || 0);
    return sum + (monthly * duration * subscribers);
  }, 0) || 0;

  // Find most popular plan (by subscribers)
  const popularPlan = plans?.reduce((max, plan) => {
    return (plan.subscribers_count || 0) > (max.subscribers_count || 0) ? plan : max;
  }, plans[0] || {});

  // Find highest revenue plan (monthly amount * subscribers)
  const highestRevenuePlan = plans?.reduce((max, plan) => {
    const planRevenue = (plan.monthly_amount || 0) * (plan.subscribers_count || 0);
    const maxRevenue = (max.monthly_amount || 0) * (max.subscribers_count || 0);
    return planRevenue > maxRevenue ? plan : max;
  }, plans[0] || {});

  // Calculate active vs inactive ratio
  const activePlans = plans?.filter(p => p.is_active).length || 0;
  const inactivePlans = (plans?.length || 0) - activePlans;

  // Calculate average plan value
  const avgPlanValue = plans?.length > 0
    ? plans.reduce((sum, p) => sum + (parseFloat(p.monthly_amount) * parseInt(p.duration_months)), 0) / plans.length
    : 0;

  // Sort plans by revenue
  const plansByRevenue = [...(plans || [])].sort((a, b) => {
    const revenueA = (a.monthly_amount || 0) * (a.subscribers_count || 0);
    const revenueB = (b.monthly_amount || 0) * (b.subscribers_count || 0);
    return revenueB - revenueA;
  }).slice(0, 5);

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold">Plan Analytics</h2>
        <p className="text-muted-foreground">Comprehensive insights into plan performance</p>
      </div>

      {/* Key Metrics */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Subscribers</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalSubscribers.toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">
              Across {plans?.length || 0} plans
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Monthly Revenue</CardTitle>
            <IndianRupee className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{totalMonthlyRevenue.toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">
              Recurring monthly income
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Projected Revenue</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{(totalProjectedRevenue / 100000).toFixed(1)}L</div>
            <p className="text-xs text-muted-foreground">
              Total expected earnings
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Avg Plan Value</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{avgPlanValue.toLocaleString('en-IN', { maximumFractionDigits: 0 })}</div>
            <p className="text-xs text-muted-foreground">
              Average total investment
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Plan Status & Performance */}
      <div className="grid md:grid-cols-2 gap-4">
        <Card>
          <CardHeader>
            <CardTitle>Plan Status Distribution</CardTitle>
            <CardDescription>Active vs Inactive plans</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full bg-green-500" />
                <span className="text-sm font-medium">Active Plans</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-2xl font-bold">{activePlans}</span>
                <Badge variant="outline">
                  {plans?.length > 0 ? ((activePlans / plans.length) * 100).toFixed(0) : 0}%
                </Badge>
              </div>
            </div>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <div className="h-3 w-3 rounded-full bg-gray-400" />
                <span className="text-sm font-medium">Inactive Plans</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-2xl font-bold">{inactivePlans}</span>
                <Badge variant="outline">
                  {plans?.length > 0 ? ((inactivePlans / plans.length) * 100).toFixed(0) : 0}%
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Top Performers</CardTitle>
            <CardDescription>Best performing plans</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <div className="flex items-center gap-2 mb-1">
                <Award className="h-4 w-4 text-yellow-500" />
                <span className="text-sm font-medium">Most Popular</span>
              </div>
              <div className="text-lg font-semibold">{popularPlan?.name || 'N/A'}</div>
              <p className="text-xs text-muted-foreground">
                {popularPlan?.subscribers_count || 0} subscribers
              </p>
            </div>
            <div>
              <div className="flex items-center gap-2 mb-1">
                <TrendingUp className="h-4 w-4 text-green-500" />
                <span className="text-sm font-medium">Highest Revenue</span>
              </div>
              <div className="text-lg font-semibold">{highestRevenuePlan?.name || 'N/A'}</div>
              <p className="text-xs text-muted-foreground">
                ₹{((highestRevenuePlan?.monthly_amount || 0) * (highestRevenuePlan?.subscribers_count || 0)).toLocaleString('en-IN')}/mo
              </p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Revenue Breakdown Table */}
      <Card>
        <CardHeader>
          <CardTitle>Revenue Breakdown by Plan</CardTitle>
          <CardDescription>Top 5 plans by monthly revenue</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Plan Name</TableHead>
                <TableHead>Subscribers</TableHead>
                <TableHead>Monthly Amount</TableHead>
                <TableHead>Monthly Revenue</TableHead>
                <TableHead>Total Value</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {plansByRevenue.length > 0 ? (
                plansByRevenue.map((plan) => {
                  const monthlyRevenue = (plan.monthly_amount || 0) * (plan.subscribers_count || 0);
                  const totalValue = monthlyRevenue * (plan.duration_months || 0);

                  return (
                    <TableRow key={plan.id}>
                      <TableCell className="font-medium">{plan.name}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-1">
                          <Users className="h-3 w-3 text-muted-foreground" />
                          {plan.subscribers_count || 0}
                        </div>
                      </TableCell>
                      <TableCell>₹{plan.monthly_amount?.toLocaleString('en-IN')}</TableCell>
                      <TableCell className="font-semibold text-green-600">
                        ₹{monthlyRevenue.toLocaleString('en-IN')}
                      </TableCell>
                      <TableCell>₹{totalValue.toLocaleString('en-IN')}</TableCell>
                      <TableCell>
                        {plan.is_active ? (
                          <Badge variant="default">Active</Badge>
                        ) : (
                          <Badge variant="secondary">Inactive</Badge>
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })
              ) : (
                <TableRow>
                  <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                    No plans available for analysis
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
