// V-PHASE5-1730-120
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export default function BonusesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['bonuses'],
    queryFn: async () => (await api.get('/user/bonuses')).data,
  });

  if (isLoading) return <div>Loading bonuses...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">My Bonuses</h1>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {Object.entries(data?.summary || {}).map(([type, total]) => (
          <Card key={type}>
            <CardHeader>
              <CardTitle className="text-sm font-medium capitalize">{type.replace('_', ' ')}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">₹{total as number}</div>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Bonus Transactions</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Amount</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.transactions.data.map((tx: any) => (
                <TableRow key={tx.id}>
                  <TableCell>{new Date(tx.created_at).toLocaleDateString()}</TableCell>
                  <TableCell className="capitalize">{tx.type.replace('_', ' ')}</TableCell>
                  <TableCell>{tx.description}</TableCell>
                  <TableCell>₹{tx.amount}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {/* TODO: Add pagination controls */}
        </CardContent>
      </Card>
    </div>
  );
}