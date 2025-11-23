// V-PHASE6-1730-125 (Created) | V-FINAL-1730-649 (Updated) | V-ENHANCED-DASHBOARD
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import Link from "next/link";
import { DollarSign, Users, FileText, Activity, ArrowUpRight, TrendingUp, Wallet, CreditCard, AlertCircle, CheckCircle2, Clock } from "lucide-react";
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';

export default function AdminDashboardPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['adminDashboard'],
    queryFn: async () => {
        const res = await api.get('/admin/dashboard');
        return res.data;
    },
    retry: 1
  });

  if (isLoading) {
      return <div className="p-8 text-center">Loading dashboard data...</div>;
  }

  if (error) {
      return <div className="p-8 text-center text-red-500">Error loading dashboard. Is the backend running?</div>;
  }

  const { kpis, charts, recent_activity } = data || {};

  // Calculate additional metrics
  const hasAlerts = (kpis?.pending_kyc || 0) > 10 || (kpis?.pending_withdrawals || 0) > 5;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Dashboard</h1>
        {hasAlerts && (
          <Badge variant="destructive" className="animate-pulse">
            <AlertCircle className="mr-1 h-3 w-3" /> Action Required
          </Badge>
        )}
      </div>

      {/* Alert Banner */}
      {hasAlerts && (
        <Card className="border-orange-500 bg-orange-50 dark:bg-orange-950">
          <CardContent className="pt-4">
            <div className="flex items-center gap-4">
              <AlertCircle className="h-5 w-5 text-orange-600" />
              <div className="flex-1">
                <p className="font-medium text-orange-800 dark:text-orange-200">Items Requiring Attention</p>
                <p className="text-sm text-orange-700 dark:text-orange-300">
                  {kpis?.pending_kyc > 10 && `${kpis.pending_kyc} KYC applications pending. `}
                  {kpis?.pending_withdrawals > 5 && `${kpis.pending_withdrawals} withdrawal requests pending.`}
                </p>
              </div>
              <div className="flex gap-2">
                {kpis?.pending_kyc > 0 && (
                  <Button size="sm" variant="outline" asChild>
                    <Link href="/admin/kyc-queue">Review KYC</Link>
                  </Button>
                )}
                {kpis?.pending_withdrawals > 0 && (
                  <Button size="sm" variant="outline" asChild>
                    <Link href="/admin/withdrawal-queue">Review Withdrawals</Link>
                  </Button>
                )}
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Primary KPIs */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="cursor-pointer hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{kpis?.total_revenue?.toLocaleString() || 0}</div>
            <p className="text-xs text-muted-foreground mt-1">
              <TrendingUp className="inline h-3 w-3 mr-1 text-green-500" />
              All time earnings
            </p>
          </CardContent>
        </Card>
        <Card className="cursor-pointer hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{kpis?.total_users || 0}</div>
            <p className="text-xs text-muted-foreground mt-1">
              <CheckCircle2 className="inline h-3 w-3 mr-1 text-green-500" />
              Registered accounts
            </p>
          </CardContent>
        </Card>
        <Card className={`cursor-pointer hover:shadow-md transition-shadow ${kpis?.pending_kyc > 10 ? 'border-orange-500' : ''}`}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending KYC</CardTitle>
            <FileText className={`h-4 w-4 ${kpis?.pending_kyc > 10 ? 'text-orange-500' : 'text-muted-foreground'}`} />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{kpis?.pending_kyc || 0}</div>
            <Link href="/admin/kyc-queue" className="text-xs text-primary hover:underline mt-1 inline-flex items-center">
              Review queue <ArrowUpRight className="h-3 w-3 ml-1" />
            </Link>
          </CardContent>
        </Card>
        <Card className={`cursor-pointer hover:shadow-md transition-shadow ${kpis?.pending_withdrawals > 5 ? 'border-orange-500' : ''}`}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Withdrawals</CardTitle>
            <Wallet className={`h-4 w-4 ${kpis?.pending_withdrawals > 5 ? 'text-orange-500' : 'text-muted-foreground'}`} />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{kpis?.pending_withdrawals || 0}</div>
            <Link href="/admin/withdrawal-queue" className="text-xs text-primary hover:underline mt-1 inline-flex items-center">
              Process requests <ArrowUpRight className="h-3 w-3 ml-1" />
            </Link>
          </CardContent>
        </Card>
      </div>

      {/* Secondary KPIs */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Subscriptions</CardTitle>
            <CreditCard className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{kpis?.active_subscriptions || 0}</div>
            <p className="text-xs text-muted-foreground mt-1">Currently active plans</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Monthly Revenue</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{kpis?.monthly_revenue?.toLocaleString() || 0}</div>
            <p className="text-xs text-muted-foreground mt-1">This month's collections</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">New Users (30d)</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{kpis?.new_users_30d || 0}</div>
            <p className="text-xs text-muted-foreground mt-1">Last 30 days signups</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Payments</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{kpis?.pending_payments || 0}</div>
            <Link href="/admin/payments" className="text-xs text-primary hover:underline mt-1 inline-flex items-center">
              View all <ArrowUpRight className="h-3 w-3 ml-1" />
            </Link>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
        {/* Chart */}
        <Card className="col-span-4">
          <CardHeader>
            <CardTitle>Revenue Overview</CardTitle>
          </CardHeader>
          <CardContent className="pl-2">
            <div className="h-[300px] w-full">
                <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={charts?.revenue_over_time || []}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="date" tickFormatter={(str) => new Date(str).toLocaleDateString()} />
                        <YAxis />
                        <Tooltip />
                        <Line type="monotone" dataKey="total" stroke="#8884d8" activeDot={{ r: 8 }} />
                    </LineChart>
                </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card className="col-span-3">
          <CardHeader>
            <CardTitle>Recent Activity</CardTitle>
          </CardHeader>
          <CardContent>
             <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>User</TableHead>
                        <TableHead>Action</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {recent_activity?.length > 0 ? (
                        recent_activity.map((log: any) => (
                            <TableRow key={log.id}>
                                <TableCell className="font-medium">
                                    <div className="flex flex-col">
                                        <span>{log.user?.username || 'System'}</span>
                                        <span className="text-xs text-muted-foreground">{new Date(log.created_at).toLocaleTimeString()}</span>
                                    </div>
                                </TableCell>
                                <TableCell className="text-xs">{log.description}</TableCell>
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell colSpan={2} className="text-center text-muted-foreground">No recent activity</TableCell>
                        </TableRow>
                    )}
                </TableBody>
             </Table>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}