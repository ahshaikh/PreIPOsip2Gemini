// V-PHASE5-1730-116
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { CreditCard, ShieldAlert, Target, TrendingUp } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function DashboardPage() {
  // We can fetch a dedicated dashboard summary endpoint
  const { data: summary, isLoading } = useQuery({
    queryKey: ['dashboardSummary'],
    queryFn: async () => {
      // This endpoint would be created in Phase 6, for now we use portfolio
      const { data } = await api.get('/user/portfolio'); 
      return data.summary;
    }
  });

  // We get the KYC status from the layout's auth check, but refetch here
  const { data: kycStatus } = useQuery({
    queryKey: ['kycStatus'],
    queryFn: async () => {
      const { data } = await api.get('/user/kyc');
      return data.status;
    }
  });

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Welcome to your Dashboard</h1>

      {kycStatus === 'verified' && (
        <Alert variant="success">
          <ShieldAlert className="h-4 w-4" />
          <AlertTitle>KYC Verified!</AlertTitle>
          <AlertDescription>
            Your account is fully verified. You can now start investing.
          </AlertDescription>
        </Alert>
      )}
      
      {kycStatus === 'pending' && (
        <Alert>
          <ShieldAlert className="h-4 w-4" />
          <AlertTitle>Complete Your Verification</AlertTitle>
          <AlertDescription>
            Your account is pending. Please complete KYC to start investing.
            <Button asChild variant="link" className="p-0 ml-1 h-auto">
              <Link href="/kyc">Complete KYC Now</Link>
            </Button>
          </AlertDescription>
        </Alert>
      )}
      
      {kycStatus === 'submitted' && (
        <Alert variant="warning">
          <ShieldAlert className="h-4 w-4" />
          <AlertTitle>KYC Submitted</AlertTitle>
          <AlertDescription>
            Your documents are under review. This usually takes 24 hours.
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

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Invested</CardTitle>
            <CreditCard className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{summary?.total_invested || '0.00'}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Current Value</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{summary?.current_value || '0.00'}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Next Payment</CardTitle>
            <Target className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">Nov 15, 2025</div>
            <p className="text-xs text-muted-foreground">for Plan B</p>
          </CardContent>
        </Card>
      </div>

      {/* TODO: Add charts and recent activity feed */}
    </div>
  );
}