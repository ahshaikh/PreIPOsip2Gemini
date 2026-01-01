'use client';

import { useEffect, useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api"; // Your Axios instance
import {
  Card, CardContent, CardHeader, CardTitle
} from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  CreditCard, ShieldAlert, Target, TrendingUp, Wallet,
  Gift, Users, Calendar, Bell, ArrowUpRight, ArrowDownRight,
  PieChart, Activity, Clock, CheckCircle, AlertCircle, Zap, X
} from "lucide-react";

// 1. Define Types matching Laravel Response
interface DashboardResponse {
  user: {
    first_name: string;
    email: string;
  };
  stats: {
    portfolio_value: number;
    total_invested: number;
    portfolio_change_percent: number;
    is_positive: boolean;
    unrealized_gain: number;
    wallet_balance: number;
    total_bonuses: number;
  };
  status: {
    kyc: string;
    subscription: {
      name: string;
      status: string;
      next_payment_date: string | null;
    };
    notification_count: number;
  };
  activity: Array<{
    description: string;
    created_at: string;
    type?: string;
  }>;
}

export default function DashboardPage() {
  const [isClient, setIsClient] = useState(false);
  const [kycBannerDismissed, setKycBannerDismissed] = useState(false);

  useEffect(() => {
    setIsClient(true);
    // V-FIX-KYC-BANNER: Check if banner was dismissed (boolean, not count)
    const dismissed = localStorage.getItem('kyc_banner_dismissed') === 'true';
    setKycBannerDismissed(dismissed);
  }, []);

  // 2. Fetch Data from Laravel
  const { data, isLoading, isError } = useQuery<DashboardResponse>({
    queryKey: ['dashboardOverview'],
    queryFn: async () => {
      // NOTE: Ensure your @/lib/api axios instance points to your Laravel backend
      const response = await api.get('/user/dashboard/overview');
      return response.data; 
    },
    staleTime: 1000 * 60 * 2, // 2 minutes
  });

  const handleDismissKycBanner = () => {
    // V-FIX-KYC-BANNER: Set to permanently dismissed immediately (show only once per user)
    localStorage.setItem('kyc_banner_dismissed', 'true');
    setKycBannerDismissed(true);
  };

  if (isError) {
    return (
      <div className="flex flex-col items-center justify-center p-10 text-center">
        <ShieldAlert className="h-12 w-12 text-red-500 mb-4" />
        <h3 className="text-lg font-bold">Failed to load dashboard</h3>
        <p className="text-muted-foreground mb-4">Could not connect to the server.</p>
        <Button onClick={() => window.location.reload()}>Try Again</Button>
      </div>
    );
  }

  if (isLoading || !data) {
    return (
      <div className="space-y-6">
        <div className="flex justify-between items-center">
           <div className="space-y-2">
             <Skeleton className="h-8 w-48" />
             <Skeleton className="h-4 w-64" />
           </div>
           <Skeleton className="h-10 w-10 rounded-full" />
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
           {[...Array(4)].map((_, i) => <Skeleton key={i} className="h-32 w-full rounded-xl" />)}
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
           <Skeleton className="h-64 w-full rounded-xl" />
           <Skeleton className="h-64 w-full col-span-2 rounded-xl" />
        </div>
      </div>
    );
  }

  const { user, stats, status, activity } = data;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Welcome Back, {user.first_name}!</h1>
          <p className="text-muted-foreground">Here is your investment overview.</p>
        </div>
        <div className="flex items-center gap-2">
          {status.notification_count > 0 && (
            <Button variant="outline" size="sm" asChild>
              <Link href="/notifications">
                <Bell className="mr-2 h-4 w-4" /> {status.notification_count} New
              </Link>
            </Button>
          )}
        </div>
      </div>

      {/* KYC Alert - Fixed "success" variant error by using classes */}
      {isClient && status.kyc === 'verified' && !kycBannerDismissed && (
        <Alert className="border-green-500/50 bg-green-500/10 text-green-900 relative">
          <CheckCircle className="h-4 w-4 text-green-600" />
          <AlertTitle className="text-green-700 font-semibold">KYC Verified</AlertTitle>
          <AlertDescription className="text-green-700">Your account is fully verified.</AlertDescription>
          <button onClick={handleDismissKycBanner} className="absolute top-2 right-2 p-1 hover:bg-green-500/20 rounded">
            <X className="h-4 w-4 text-green-700" />
          </button>
        </Alert>
      )}

      {status.kyc === 'pending' && (
        <Alert className="border-yellow-500/50 bg-yellow-500/10 text-yellow-900">
          <AlertCircle className="h-4 w-4 text-yellow-600" />
          <AlertTitle className="text-yellow-700">Action Required</AlertTitle>
          <AlertDescription className="text-yellow-700">
             Please complete your KYC verification. <Link href="/kyc" className="underline font-bold">Start Now</Link>
          </AlertDescription>
        </Alert>
      )}

      {/* Main Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Portfolio Value */}
        <Card className="border-l-4 border-l-blue-500 shadow-sm">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Portfolio Value</CardTitle>
            <PieChart className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{stats.portfolio_value.toLocaleString()}</div>
            <div className="flex items-center mt-1">
              {stats.is_positive ? 
                <ArrowUpRight className="h-4 w-4 text-green-500 mr-1" /> : 
                <ArrowDownRight className="h-4 w-4 text-red-500 mr-1" />
              }
              <span className={`text-xs ${stats.is_positive ? 'text-green-500' : 'text-red-500'}`}>
                {stats.is_positive ? '+' : ''}{stats.portfolio_change_percent}%
              </span>
            </div>
          </CardContent>
        </Card>

        {/* Total Invested */}
        <Card className="border-l-4 border-l-green-500 shadow-sm">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Invested</CardTitle>
            <CreditCard className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{stats.total_invested.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">Plan: {status.subscription.name}</p>
          </CardContent>
        </Card>

        {/* Wallet Balance */}
        <Card className="border-l-4 border-l-purple-500 shadow-sm">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Wallet Balance</CardTitle>
            <Wallet className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{stats.wallet_balance.toLocaleString()}</div>
            <Link href="/wallet" className="text-xs text-primary hover:underline">Manage Wallet</Link>
          </CardContent>
        </Card>

        {/* Total Bonuses */}
        <Card className="border-l-4 border-l-yellow-500 shadow-sm">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Bonuses</CardTitle>
            <Gift className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{stats.total_bonuses.toLocaleString()}</div>
            <Link href="/bonuses" className="text-xs text-primary hover:underline">View Details</Link>
          </CardContent>
        </Card>
      </div>

      {/* Recent Activity & Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Quick Actions */}
        <Card>
          <CardHeader><CardTitle className="text-lg">Quick Actions</CardTitle></CardHeader>
          <CardContent className="space-y-2">
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/plan"><Target className="mr-2 h-4 w-4" /> Subscribe Plan</Link>
            </Button>
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/wallet"><Wallet className="mr-2 h-4 w-4" /> Add Money</Link>
            </Button>
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/referrals"><Users className="mr-2 h-4 w-4" /> Invite Friends</Link>
            </Button>
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card className="lg:col-span-2">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-lg">Recent Activity</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
             <div className="space-y-4">
              {activity.map((item, idx) => (
                <div key={idx} className="flex items-start gap-3 pb-3 border-b last:border-0 last:pb-0">
                  <div className="p-2 rounded-full bg-muted">
                    <Activity className="h-4 w-4" />
                  </div>
                  <div className="flex-1">
                    <p className="text-sm font-medium">{item.description}</p>
                    <p className="text-xs text-muted-foreground">{new Date(item.created_at).toLocaleDateString()}</p>
                  </div>
                </div>
              ))}
              {activity.length === 0 && <p className="text-muted-foreground text-center py-4">No recent activity.</p>}
             </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}