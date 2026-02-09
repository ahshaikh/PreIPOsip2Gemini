'use client';

import { useState } from "react";
import companyApi from "@/lib/companyApi";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { DatePickerWithRange } from "@/components/ui/date-range-picker";
import {
  Eye,
  Download,
  FileText,
  TrendingUp,
  Users,
  Calendar,
  ArrowUp,
  ArrowDown
} from "lucide-react";
import { DateRange } from "react-day-picker";
import { addDays, format } from "date-fns";

interface AnalyticsData {
  date: string;
  profile_views: number;
  document_downloads: number;
  financial_report_downloads: number;
  deal_views: number;
  investor_interest_clicks: number;
}

interface AnalyticsTotals {
  profile_views: number;
  document_downloads: number;
  financial_report_downloads: number;
  deal_views: number;
  investor_interest_clicks: number;
}

export default function CompanyAnalyticsPage() {
  const [dateRange, setDateRange] = useState<DateRange | undefined>({
    from: addDays(new Date(), -30),
    to: new Date(),
  });

  const { data: response, isLoading } = useQuery({
    queryKey: ['companyAnalytics', dateRange],
    queryFn: async () => {
      const params = new URLSearchParams({
        start_date: dateRange?.from ? format(dateRange.from, 'yyyy-MM-dd') : '',
        end_date: dateRange?.to ? format(dateRange.to, 'yyyy-MM-dd') : '',
      });
      const { data } = await companyApi.get(`/analytics/dashboard?${params}`);
      return data;
    },
    enabled: !!dateRange?.from && !!dateRange?.to,
  });

  const analytics: AnalyticsData[] = response?.analytics || [];
  const totals: AnalyticsTotals = response?.totals || {
    profile_views: 0,
    document_downloads: 0,
    financial_report_downloads: 0,
    deal_views: 0,
    investor_interest_clicks: 0,
  };

  const statCards = [
    {
      title: "Profile Views",
      value: totals.profile_views,
      icon: Eye,
      color: "text-blue-600",
      bgColor: "bg-blue-100",
      trend: "+12%",
      trendUp: true,
    },
    {
      title: "Document Downloads",
      value: totals.document_downloads,
      icon: Download,
      color: "text-green-600",
      bgColor: "bg-green-100",
      trend: "+8%",
      trendUp: true,
    },
    {
      title: "Financial Report Downloads",
      value: totals.financial_report_downloads,
      icon: FileText,
      color: "text-purple-600",
      bgColor: "bg-purple-100",
      trend: "+15%",
      trendUp: true,
    },
    {
      title: "Deal Views",
      value: totals.deal_views,
      icon: TrendingUp,
      color: "text-orange-600",
      bgColor: "bg-orange-100",
      trend: "-3%",
      trendUp: false,
    },
    {
      title: "Investor Interest Clicks",
      value: totals.investor_interest_clicks,
      icon: Users,
      color: "text-pink-600",
      bgColor: "bg-pink-100",
      trend: "+20%",
      trendUp: true,
    },
  ];

  const handleExport = async () => {
    try {
      const params = new URLSearchParams({
        start_date: dateRange?.from ? format(dateRange.from, 'yyyy-MM-dd') : '',
        end_date: dateRange?.to ? format(dateRange.to, 'yyyy-MM-dd') : '',
      });

      const response = await companyApi.get(`/analytics/export?${params}`, {
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `analytics-${format(new Date(), 'yyyy-MM-dd')}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Analytics Dashboard</h1>
          <p className="text-muted-foreground">
            Track your company's profile performance and investor engagement
          </p>
        </div>
        <div className="flex gap-3">
          <DatePickerWithRange date={dateRange} setDate={setDateRange} />
          <Button onClick={handleExport} variant="outline">
            <Download className="w-4 h-4 mr-2" />
            Export
          </Button>
        </div>
      </div>

      {/* Stats Grid */}
      {isLoading ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[...Array(5)].map((_, i) => (
            <Card key={i} className="animate-pulse">
              <CardHeader>
                <div className="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                <div className="h-8 bg-gray-200 rounded w-3/4"></div>
              </CardHeader>
            </Card>
          ))}
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {statCards.map((stat) => (
            <Card key={stat.title}>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                  {stat.title}
                </CardTitle>
                <div className={`p-2 rounded-lg ${stat.bgColor}`}>
                  <stat.icon className={`w-5 h-5 ${stat.color}`} />
                </div>
              </CardHeader>
              <CardContent>
                <div className="text-3xl font-bold">{stat.value.toLocaleString()}</div>
                <div className="flex items-center gap-1 mt-2 text-sm">
                  {stat.trendUp ? (
                    <ArrowUp className="w-4 h-4 text-green-600" />
                  ) : (
                    <ArrowDown className="w-4 h-4 text-red-600" />
                  )}
                  <span className={stat.trendUp ? "text-green-600" : "text-red-600"}>
                    {stat.trend}
                  </span>
                  <span className="text-muted-foreground">vs last period</span>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Trends Chart */}
      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Profile Views Over Time</CardTitle>
            <CardDescription>Daily profile view trends</CardDescription>
          </CardHeader>
          <CardContent>
            {analytics.length > 0 ? (
              <div className="space-y-2">
                {analytics.slice(0, 10).map((data, index) => (
                  <div key={index} className="flex items-center gap-3">
                    <div className="w-24 text-sm text-muted-foreground">
                      {format(new Date(data.date), 'MMM dd')}
                    </div>
                    <div className="flex-1">
                      <div className="h-8 bg-blue-100 rounded flex items-center px-2">
                        <div
                          className="h-6 bg-blue-600 rounded"
                          style={{
                            width: `${(data.profile_views / Math.max(...analytics.map(a => a.profile_views))) * 100}%`
                          }}
                        ></div>
                      </div>
                    </div>
                    <div className="w-16 text-right font-semibold">
                      {data.profile_views}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="py-8 text-center text-muted-foreground">
                No data available for selected period
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Engagement Metrics</CardTitle>
            <CardDescription>Breakdown of user interactions</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {[
                { label: "Document Downloads", value: totals.document_downloads, color: "bg-green-600" },
                { label: "Financial Reports", value: totals.financial_report_downloads, color: "bg-purple-600" },
                { label: "Deal Views", value: totals.deal_views, color: "bg-orange-600" },
                { label: "Interest Clicks", value: totals.investor_interest_clicks, color: "bg-pink-600" },
              ].map((item) => {
                const maxValue = Math.max(
                  totals.document_downloads,
                  totals.financial_report_downloads,
                  totals.deal_views,
                  totals.investor_interest_clicks
                );
                const percentage = maxValue > 0 ? (item.value / maxValue) * 100 : 0;

                return (
                  <div key={item.label}>
                    <div className="flex justify-between mb-1 text-sm">
                      <span className="text-muted-foreground">{item.label}</span>
                      <span className="font-semibold">{item.value}</span>
                    </div>
                    <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                      <div
                        className={`h-full ${item.color} transition-all`}
                        style={{ width: `${percentage}%` }}
                      ></div>
                    </div>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Activity Table */}
      <Card>
        <CardHeader>
          <CardTitle>Daily Activity Log</CardTitle>
          <CardDescription>Detailed breakdown of daily metrics</CardDescription>
        </CardHeader>
        <CardContent>
          {analytics.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b">
                    <th className="text-left p-3 font-semibold">Date</th>
                    <th className="text-right p-3 font-semibold">Profile Views</th>
                    <th className="text-right p-3 font-semibold">Doc Downloads</th>
                    <th className="text-right p-3 font-semibold">Reports</th>
                    <th className="text-right p-3 font-semibold">Deal Views</th>
                    <th className="text-right p-3 font-semibold">Interest</th>
                  </tr>
                </thead>
                <tbody>
                  {analytics.map((data, index) => (
                    <tr key={index} className="border-b hover:bg-gray-50">
                      <td className="p-3">
                        <div className="flex items-center gap-2">
                          <Calendar className="w-4 h-4 text-muted-foreground" />
                          {format(new Date(data.date), 'MMM dd, yyyy')}
                        </div>
                      </td>
                      <td className="p-3 text-right">{data.profile_views}</td>
                      <td className="p-3 text-right">{data.document_downloads}</td>
                      <td className="p-3 text-right">{data.financial_report_downloads}</td>
                      <td className="p-3 text-right">{data.deal_views}</td>
                      <td className="p-3 text-right">{data.investor_interest_clicks}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-8 text-center text-muted-foreground">
              No activity data available for the selected period
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
