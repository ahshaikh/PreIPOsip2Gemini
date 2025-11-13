
// V-REMEDIATE-1730-158
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export default function LuckyDrawsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['luckyDraws'],
    queryFn: async () => (await api.get('/user/lucky-draws')).data,
  });

  if (isLoading) return <div>Loading lucky draw info...</div>;

  const { active_draw, my_entries, past_draws } = data;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Monthly Lucky Draw</h1>

      {/* Active Draw Card */}
      {active_draw ? (
        <Card>
          <CardHeader>
            <CardTitle>{active_draw.name}</CardTitle>
            <CardDescription>
              Draw date: {new Date(active_draw.draw_date).toLocaleDateString()}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <Card>
                <CardHeader>
                  <CardTitle className="text-sm font-medium">Your Entries</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-3xl font-bold">{my_entries.length}</div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="text-sm font-medium">Total Prize Pool</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">
                    ₹{active_draw.prize_structure.reduce((acc: number, tier: any) => acc + (tier.count * tier.amount), 0).toLocaleString()}
                  </div>
                </CardContent>
              </Card>
            </div>
            
            <div>
              <h4 className="font-semibold mb-2">Prize Structure</h4>
              <ul className="list-disc pl-5 text-sm text-muted-foreground">
                {active_draw.prize_structure.map((tier: any) => (
                  <li key={tier.rank}>
                    {tier.count}x Winner(s) of ₹{tier.amount.toLocaleString()} (Rank {tier.rank})
                  </li>
                ))}
              </ul>
            </div>

            <div>
              <h4 className="font-semibold mb-2">Your Entry Codes</h4>
              {my_entries.length > 0 ? (
                <div className="flex flex-wrap gap-2">
                  {my_entries.map((entry: any) => (
                    <span key={entry.id} className="bg-muted px-2 py-1 rounded text-xs font-mono">
                      {entry.entry_code}
                    </span>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">Make an on-time payment to get entries!</p>
              )}
            </div>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardHeader>
            <CardTitle>No Active Draw</CardTitle>
            <CardDescription>The next lucky draw has not been announced yet. Check back soon!</CardDescription>
          </CardHeader>
        </Card>
      )}

      {/* Past Draws */}
      <Card>
        <CardHeader>
          <CardTitle>Past Draws</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Draw Name</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {past_draws.data.map((draw: any) => (
                <TableRow key={draw.id}>
                  <TableCell>{draw.name}</TableCell>
                  <TableCell>{new Date(draw.draw_date).toLocaleDateString()}</TableCell>
                  <TableCell className="capitalize">{draw.status}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}