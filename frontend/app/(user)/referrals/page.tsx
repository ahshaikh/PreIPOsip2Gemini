// V-PHASE5-1730-121
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { Copy, Gift } from "lucide-react";

export default function ReferralsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['referrals'],
    queryFn: async () => (await api.get('/user/referrals')).data,
  });

  if (isLoading) return <div>Loading referrals...</div>;

  // Use environment variable for the base URL (SSR-safe with fallback)
  const baseUrl = typeof window !== 'undefined'
    ? window.location.origin
    : process.env.NEXT_PUBLIC_SITE_URL || 'https://preiposip.com';
  const referralLink = `${baseUrl}/signup?ref=${data?.stats.referral_code}`;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">My Referrals</h1>

      <Card>
        <CardHeader>
          <CardTitle>Share Your Link</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p>Share your link to earn bonuses and increase your multiplier!</p>
          <div className="flex gap-2">
            <Input value={referralLink} readOnly />
            <Button onClick={() => navigator.clipboard.writeText(referralLink)}>
              <Copy className="h-4 w-4" />
            </Button>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Current Multiplier</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{data?.stats.current_multiplier}x</div>
            <p className="text-xs text-muted-foreground">Applies to progressive & milestone bonuses</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Completed Referrals</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{data?.stats.completed_referrals}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{data?.stats.total_referrals}</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Your Referral List</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User</TableHead>
                <TableHead>Referred On</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.referrals.data.map((ref: any) => (
                <TableRow key={ref.id}>
                  <TableCell>{ref.referred.username}</TableCell>
                  <TableCell>{new Date(ref.created_at).toLocaleDateString()}</TableCell>
                  <TableCell>
                    <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      ref.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                      {ref.status}
                    </span>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}