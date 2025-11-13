
// V-REMEDIATE-1730-162
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export default function ProfitSharingPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['userProfitShares'],
    queryFn: async () => (await api.get('/user/profit-sharing')).data,
  });

  const totalEarned = data?.data.reduce((acc: number, share: any) => acc + parseFloat(share.amount), 0) || 0;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">My Profit Sharing</h1>

      <Card>
        <CardHeader>
          <CardTitle className="text-sm font-medium">Total Earned from Profit Sharing</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-3xl font-bold">₹{totalEarned.toFixed(2)}</div>
          <p className="text-xs text-muted-foreground">This bonus is credited to your wallet quarterly.</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Distribution History</CardTitle>
          <CardDescription>Your share of the platform's profits.</CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading history...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Period</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Amount Earned</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data.map((share: any) => (
                  <TableRow key={share.id}>
                    <TableCell className="font-medium">{share.profit_share_period.period_name}</TableCell>
                    <TableCell>{new Date(share.profit_share_period.end_date).toLocaleDateString()}</TableCell>
                    <TableCell className="text-green-600 font-medium">+ ₹{parseFloat(share.amount).toFixed(2)}</TableCell>
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