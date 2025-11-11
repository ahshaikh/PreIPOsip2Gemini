// V-PHASE5-1730-119
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export default function PortfolioPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['portfolio'],
    queryFn: async () => (await api.get('/user/portfolio')).data,
  });

  if (isLoading) return <div>Loading portfolio...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">My Portfolio</h1>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Total Invested</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{data?.summary.total_invested}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Current Value</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{data?.summary.current_value}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-medium">Unrealized Gain</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">₹{data?.summary.unrealized_gain}</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>My Holdings</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Product</TableHead>
                <TableHead>Total Units</TableHead>
                <TableHead>Total Value (Face)</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.holdings.map((holding: any) => (
                <TableRow key={holding.product.id}>
                  <TableCell className="font-medium">{holding.product.name}</TableCell>
                  <TableCell>{holding.total_units.toFixed(4)}</TableCell>
                  <TableCell>₹{holding.total_value}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}