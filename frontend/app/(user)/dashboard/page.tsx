// V-PHASE5-1730-116 | V-ENHANCED-DASHBOARD
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import {
  CreditCard, ShieldAlert, Target, TrendingUp, Wallet,
  Gift, Users, Calendar, Bell, ArrowUpRight, ArrowDownRight,
  PieChart, Activity, Clock, CheckCircle, AlertCircle, Zap, X
} from "lucide-react";
import Link from "next/link";
import { useState } from "react";

export default function DashboardPage() {
  const [activeTab, setActiveTab] = useState('overview');
  const [kycBannerDismissed, setKycBannerDismissed] = useState(() => {
    if (typeof window !== 'undefined') {
      const dismissed = localStorage.getItem('kyc_banner_dismissed');
      return dismissed ? parseInt(dismissed) >= 2 : false;
    }
    return false;
  });

  // Fetch user profile
  const { data: user } = useQuery({
    queryKey: ['userProfile'],
    queryFn: async () => {
      const { data } = await api.get('/user/profile');
      return data.user || data;
    }
  });

  // Dashboard summary
  const { data: summary, isLoading } = useQuery({
    queryKey: ['dashboardSummary'],
    queryFn: async () => {
      const { data } = await api.get('/user/portfolio');
      return data.summary;
    }
  });

  // KYC Status
  const { data: kycStatus } = useQuery({
    queryKey: ['kycStatus'],
    queryFn: async () => {
      try {
        const { data } = await api.get('/user/kyc');
        return data?.status || data?.kyc?.status || 'pending';
      } catch (error) {
        return 'pending';
      }
    },
    retry: false,
  });

  // Subscription status
  const { data: subscription } = useQuery({
    queryKey: ['subscription'],
    queryFn: async () => (await api.get('/user/subscription')).data,
    retry: false,
  });

  // Bonuses summary
  const { data: bonuses } = useQuery({
    queryKey: ['bonuses'],
    queryFn: async () => (await api.get('/user/bonuses')).data,
  });

  // Referrals summary
  const { data: referrals } = useQuery({
    queryKey: ['referrals'],
    queryFn: async () => (await api.get('/user/referrals')).data,
  });

  // Wallet balance
  const { data: wallet } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => (await api.get('/user/wallet')).data,
  });

  // Recent activity
  const { data: activity } = useQuery({
    queryKey: ['userActivity'],
    queryFn: async () => (await api.get('/user/activity')).data,
  });

  // Notifications
  const { data: notifications } = useQuery({
    queryKey: ['userNotifications'],
    queryFn: async () => (await api.get('/user/notifications?unread=true')).data,
  });

  // Calculate portfolio gain/loss percentage
  const portfolioChange = summary?.total_invested > 0
    ? (((summary?.current_value - summary?.total_invested) / summary?.total_invested) * 100).toFixed(2)
    : '0.00';
  const isPositive = parseFloat(portfolioChange) >= 0;

  // Get pending payment
  const pendingPayment = subscription?.payments?.find((p: any) => p.status === 'pending');

  // Calculate total bonuses
  const totalBonuses = bonuses?.summary
    ? Object.values(bonuses.summary).reduce((acc: number, val: any) => acc + (Number(val) || 0), 0)
    : 0;

  const handleDismissKycBanner = () => {
    if (typeof window !== 'undefined') {
      const count = parseInt(localStorage.getItem('kyc_banner_dismissed') || '0');
      localStorage.setItem('kyc_banner_dismissed', (count + 1).toString());
      setKycBannerDismissed(count + 1 >= 2);
    }
  };

  const firstName = user?.profile?.first_name || user?.first_name || '';

  return (
    <div className="space-y-6">
      {/* Welcome Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">
            Welcome Back{firstName ? `, ${firstName}` : ''}!
          </h1>
          <p className="text-muted-foreground">Here's an overview of your investments and activities.</p>
        </div>
        <div className="flex items-center gap-2">
          {notifications?.length > 0 && (
            <Button variant="outline" size="sm" asChild>
              <Link href="/notifications">
                <Bell className="mr-2 h-4 w-4" />
                {notifications.length} New
              </Link>
            </Button>
          )}
        </div>
      </div>

      {/* KYC Alerts */}
      {kycStatus === 'verified' && !kycBannerDismissed && (
        <Alert variant="success" className="border-green-500/50 bg-green-500/10 relative">
          <CheckCircle className="h-4 w-4 text-green-500" />
          <AlertTitle className="text-green-600">KYC Verified!</AlertTitle>
          <AlertDescription>
            Your account is fully verified. You can now start investing.
          </AlertDescription>
          <button
            onClick={handleDismissKycBanner}
            className="absolute top-2 right-2 p-1 hover:bg-green-500/20 rounded"
          >
            <X className="h-4 w-4 text-green-600" />
          </button>
        </Alert>
      )}

      {kycStatus === 'pending' && (
        <Alert className="border-yellow-500/50 bg-yellow-500/10">
          <AlertCircle className="h-4 w-4 text-yellow-500" />
          <AlertTitle className="text-yellow-600">Complete Your Verification</AlertTitle>
          <AlertDescription>
            Your account is pending verification. Please complete KYC to start investing.
            <Button asChild variant="link" className="p-0 ml-1 h-auto">
              <Link href="/kyc">Complete KYC Now</Link>
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {kycStatus === 'submitted' && (
        <Alert className="border-blue-500/50 bg-blue-500/10">
          <Clock className="h-4 w-4 text-blue-500" />
          <AlertTitle className="text-blue-600">KYC Submitted</AlertTitle>
          <AlertDescription>
            Your documents are under review. This usually takes 24-48 hours.
          </AlertDescription>
        </Alert>
      )}

      {kycStatus === 'rejected' && (
        <Alert variant="destructive">
          <ShieldAlert className="h-4 w-4" />
          <AlertTitle>KYC Rejected</AlertTitle>
          <AlertDescription>
            There was an issue with your documents.
            <Button asChild variant="link" className="p-0 ml-1 h-auto">
              <Link href="/kyc">Please resubmit.</Link>
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {/* Pending Payment Alert */}
      {pendingPayment && (
        <Alert className="border-primary/50 bg-primary/5">
          <Zap className="h-4 w-4 text-primary" />
          <AlertTitle>Payment Due</AlertTitle>
          <AlertDescription className="flex items-center justify-between">
            <span>You have a pending payment of ₹{pendingPayment.amount}</span>
            <Button asChild size="sm">
              <Link href="/subscription">Pay Now</Link>
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {/* Main Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Portfolio Value */}
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Portfolio Value</CardTitle>
            <PieChart className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{summary?.current_value || '0.00'}</div>
            <div className="flex items-center mt-1">
              {isPositive ? (
                <ArrowUpRight className="h-4 w-4 text-green-500 mr-1" />
              ) : (
                <ArrowDownRight className="h-4 w-4 text-red-500 mr-1" />
              )}
              <span className={`text-xs ${isPositive ? 'text-green-500' : 'text-red-500'}`}>
                {isPositive ? '+' : ''}{portfolioChange}%
              </span>
              <span className="text-xs text-muted-foreground ml-1">from invested</span>
            </div>
          </CardContent>
        </Card>

        {/* Total Invested */}
        <Card className="border-l-4 border-l-green-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Invested</CardTitle>
            <CreditCard className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{summary?.total_invested || '0.00'}</div>
            <p className="text-xs text-muted-foreground">
              {subscription ? `Plan: ${subscription.plan?.name}` : 'No active plan'}
            </p>
          </CardContent>
        </Card>

        {/* Wallet Balance */}
        <Card className="border-l-4 border-l-purple-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Wallet Balance</CardTitle>
            <Wallet className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{wallet?.balance || '0.00'}</div>
            <Button variant="link" asChild className="p-0 h-auto text-xs">
              <Link href="/wallet">Manage Wallet</Link>
            </Button>
          </CardContent>
        </Card>

        {/* Total Bonuses */}
        <Card className="border-l-4 border-l-yellow-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Bonuses</CardTitle>
            <Gift className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{totalBonuses.toFixed(2)}</div>
            <Button variant="link" asChild className="p-0 h-auto text-xs">
              <Link href="/bonuses">View Details</Link>
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Secondary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Subscription Status */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Subscription</CardTitle>
            <Target className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between">
              <div>
                <div className="text-xl font-bold">
                  {subscription?.plan?.name || 'No Plan'}
                </div>
                <Badge variant={subscription?.status === 'active' ? 'default' : 'secondary'}>
                  {subscription?.status || 'Inactive'}
                </Badge>
              </div>
              <Button variant="outline" size="sm" asChild>
                <Link href="/subscription">Manage</Link>
              </Button>
            </div>
            {subscription?.next_payment_date && (
              <p className="text-xs text-muted-foreground mt-2">
                Next payment: {new Date(subscription.next_payment_date).toLocaleDateString()}
              </p>
            )}
          </CardContent>
        </Card>

        {/* Referrals */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Referrals</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between">
              <div>
                <div className="text-xl font-bold">
                  {referrals?.total_referrals || 0}
                </div>
                <p className="text-xs text-muted-foreground">
                  {referrals?.active_referrals || 0} active
                </p>
              </div>
              <Button variant="outline" size="sm" asChild>
                <Link href="/referrals">View</Link>
              </Button>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              Earned: ₹{referrals?.total_earnings || '0.00'}
            </p>
          </CardContent>
        </Card>

        {/* Unrealized Gains */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Unrealized Gain</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className={`text-xl font-bold ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
              {isPositive ? '+' : ''}₹{summary?.unrealized_gain || '0.00'}
            </div>
            <Progress
              value={Math.min(Math.abs(parseFloat(portfolioChange)), 100)}
              className="mt-2"
            />
            <p className="text-xs text-muted-foreground mt-1">
              {isPositive ? 'Growth' : 'Decline'} since investment
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions & Activity */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Quick Actions</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/subscribe">
                <Target className="mr-2 h-4 w-4" /> Subscribe to a Plan
              </Link>
            </Button>
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/wallet">
                <Wallet className="mr-2 h-4 w-4" /> Add Money to Wallet
              </Link>
            </Button>
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/wallet?action=withdraw">
                <ArrowDownRight className="mr-2 h-4 w-4" /> Withdraw Money
              </Link>
            </Button>
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/referrals">
                <Users className="mr-2 h-4 w-4" /> Invite Friends
              </Link>
            </Button>
            <Button variant="outline" className="w-full justify-start" asChild>
              <Link href="/support">
                <Bell className="mr-2 h-4 w-4" /> Get Support
              </Link>
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
              {activity?.slice(0, 5).map((item: any, idx: number) => (
                <div key={idx} className="flex items-start gap-3 pb-3 border-b last:border-0">
                  <div className={`p-2 rounded-full ${
                    item.type === 'payment' ? 'bg-green-500/10' :
                    item.type === 'bonus' ? 'bg-yellow-500/10' :
                    item.type === 'referral' ? 'bg-blue-500/10' :
                    'bg-muted'
                  }`}>
                    {item.type === 'payment' && <CreditCard className="h-4 w-4 text-green-500" />}
                    {item.type === 'bonus' && <Gift className="h-4 w-4 text-yellow-500" />}
                    {item.type === 'referral' && <Users className="h-4 w-4 text-blue-500" />}
                    {!['payment', 'bonus', 'referral'].includes(item.type) && (
                      <Activity className="h-4 w-4" />
                    )}
                  </div>
                  <div className="flex-1">
                    <p className="text-sm font-medium">{item.description}</p>
                    <p className="text-xs text-muted-foreground">
                      {new Date(item.created_at).toLocaleString()}
                    </p>
                  </div>
                  {item.amount && (
                    <span className={`text-sm font-medium ${
                      item.amount > 0 ? 'text-green-500' : 'text-red-500'
                    }`}>
                      {item.amount > 0 ? '+' : ''}₹{Math.abs(item.amount)}
                    </span>
                  )}
                </div>
              ))}
              {(!activity || activity.length === 0) && (
                <p className="text-center text-muted-foreground py-4">
                  No recent activity
                </p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Upcoming Events / Payments */}
      {subscription && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
              <Calendar className="h-4 w-4" /> Upcoming
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-primary/10 rounded-full">
                  <CreditCard className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="font-medium">Next SIP Payment</p>
                  <p className="text-sm text-muted-foreground">
                    {subscription.plan?.name} - ₹{subscription.plan?.monthly_amount}
                  </p>
                </div>
              </div>
              <div className="text-right">
                <p className="font-medium">
                  {subscription.next_payment_date
                    ? new Date(subscription.next_payment_date).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                      })
                    : 'N/A'}
                </p>
                <Badge variant="outline">Scheduled</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
