'use client';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { TrendingUp, Trophy, Zap, Gift } from 'lucide-react';
import { calculateTotalBonuses, formatCurrency } from '@/lib/bonusCalculations';
import type { ProgressiveConfig, MilestoneEntry, ConsistencyConfig, WelcomeBonusConfig } from '@/types/plan';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, BarChart, Bar } from 'recharts';

interface BonusPreviewProps {
  paymentAmount: number;
  durationMonths: number;
  configs: {
    welcome?: WelcomeBonusConfig;
    progressive?: ProgressiveConfig;
    milestones?: MilestoneEntry[];
    consistency?: ConsistencyConfig;
  };
  multiplier?: number;
}

export function BonusPreview({ paymentAmount, durationMonths, configs, multiplier = 1.0 }: BonusPreviewProps) {
  if (paymentAmount <= 0 || durationMonths <= 0) {
    return (
      <div className="text-center py-12 bg-muted/30 rounded-lg border-2 border-dashed">
        <p className="text-sm text-muted-foreground">
          Enter payment amount and duration to see bonus preview
        </p>
      </div>
    );
  }

  const result = calculateTotalBonuses(paymentAmount, durationMonths, configs, multiplier);

  // Prepare chart data
  const chartData = result.monthlySchedule.map(month => ({
    month: `M${month.month}`,
    Progressive: month.progressive,
    Milestone: month.milestone,
    Consistency: month.consistency,
    Total: month.total,
  }));

  const totalInvestment = paymentAmount * durationMonths;
  const totalWithBonus = totalInvestment + result.totalBonus;
  const bonusPercentage = totalInvestment > 0 ? (result.totalBonus / totalInvestment) * 100 : 0;

  return (
    <div className="space-y-6">
      {/* Summary Cards */}
      <div className="grid md:grid-cols-4 gap-3">
        <Card>
          <CardHeader className="pb-1 pt-3 px-3">
            <CardTitle className="text-xs font-medium flex items-center gap-1.5">
              <Gift className="h-3.5 w-3.5 text-purple-500" />
              Welcome
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0 px-3 pb-3">
            <div className="text-base font-bold">{formatCurrency(result.breakdown.welcome)}</div>
            <div className="text-[10px] text-muted-foreground mt-0.5">First payment</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-1 pt-3 px-3">
            <CardTitle className="text-xs font-medium flex items-center gap-1.5">
              <TrendingUp className="h-3.5 w-3.5 text-blue-500" />
              Progressive
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0 px-3 pb-3">
            <div className="text-base font-bold">{formatCurrency(result.breakdown.progressive)}</div>
            <div className="text-[10px] text-muted-foreground mt-0.5">Over {durationMonths} months</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-1 pt-3 px-3">
            <CardTitle className="text-xs font-medium flex items-center gap-1.5">
              <Trophy className="h-3.5 w-3.5 text-yellow-500" />
              Milestone
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0 px-3 pb-3">
            <div className="text-base font-bold">{formatCurrency(result.breakdown.milestone)}</div>
            <div className="text-[10px] text-muted-foreground mt-0.5">{configs.milestones?.length || 0} milestones</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-1 pt-3 px-3">
            <CardTitle className="text-xs font-medium flex items-center gap-1.5">
              <Zap className="h-3.5 w-3.5 text-green-500" />
              Consistency
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0 px-3 pb-3">
            <div className="text-base font-bold">{formatCurrency(result.breakdown.consistency)}</div>
            <div className="text-[10px] text-muted-foreground mt-0.5">On-time payments</div>
          </CardContent>
        </Card>
      </div>

      {/* Total Summary */}
      <Card className="bg-gradient-to-r from-primary/10 to-primary/5 border-primary/30">
        <CardContent className="p-4">
          <div className="grid md:grid-cols-3 gap-4">
            <div>
              <div className="text-xs text-muted-foreground mb-0.5">Total Investment</div>
              <div className="text-lg font-bold">{formatCurrency(totalInvestment)}</div>
              <div className="text-[10px] text-muted-foreground mt-0.5">
                {durationMonths} × {formatCurrency(paymentAmount)}
              </div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground mb-0.5">Total Bonuses</div>
              <div className="text-lg font-bold text-primary">{formatCurrency(result.totalBonus)}</div>
              <div className="text-[10px] text-muted-foreground mt-0.5">
                <Badge variant="secondary" className="text-[10px] px-1.5 py-0">{bonusPercentage.toFixed(2)}% of investment</Badge>
              </div>
            </div>
            <div>
              <div className="text-xs text-muted-foreground mb-0.5">Grand Total</div>
              <div className="text-lg font-bold text-green-600">{formatCurrency(totalWithBonus)}</div>
              <div className="text-[10px] text-muted-foreground mt-0.5">
                Investment + Bonuses
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Monthly Bonus Chart */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Monthly Bonus Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip
                formatter={(value: number) => formatCurrency(value)}
                contentStyle={{ backgroundColor: 'hsl(var(--background))', border: '1px solid hsl(var(--border))' }}
              />
              <Legend />
              <Bar dataKey="Progressive" stackId="a" fill="#3b82f6" />
              <Bar dataKey="Milestone" stackId="a" fill="#f59e0b" />
              <Bar dataKey="Consistency" stackId="a" fill="#10b981" />
            </BarChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>

      {/* Cumulative Bonus Growth */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Cumulative Bonus Growth</CardTitle>
        </CardHeader>
        <CardContent>
          <ResponsiveContainer width="100%" height={250}>
            <LineChart
              data={chartData.map((item, index) => ({
                ...item,
                Cumulative: chartData.slice(0, index + 1).reduce((sum, m) => sum + m.Total, 0)
              }))}
            >
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip
                formatter={(value: number) => formatCurrency(value)}
                contentStyle={{ backgroundColor: 'hsl(var(--background))', border: '1px solid hsl(var(--border))' }}
              />
              <Legend />
              <Line
                type="monotone"
                dataKey="Cumulative"
                stroke="#8b5cf6"
                strokeWidth={3}
                dot={{ fill: '#8b5cf6', r: 4 }}
              />
            </LineChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>

      {/* Assumptions */}
      <div className="p-4 bg-muted/50 rounded-lg border">
        <div className="text-sm font-medium mb-2">Assumptions:</div>
        <ul className="text-xs text-muted-foreground space-y-1">
          <li>• All payments made on time (consistency bonus applied)</li>
          <li>• Bonus multiplier: {multiplier}x</li>
          <li>• Progressive bonus starts from month {configs.progressive?.start_month || 4}</li>
          <li>• Milestone bonuses require consecutive payments</li>
        </ul>
      </div>
    </div>
  );
}
