'use client';

import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import {
  ArrowUpCircle,
  ArrowDownCircle,
  Download,
  Filter,
  Search,
} from "lucide-react";
import api from "@/lib/api";

interface Transaction {
  id: number;
  type: string;
  amount: number;
  balance_before: number;
  balance_after: number;
  description: string;
  status: string;
  created_at: string;
  reference_type?: string;
  reference_id?: number;
}

export default function TransactionsPage() {
  const [typeFilter, setTypeFilter] = useState<string>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ["transactions", page, typeFilter],
    queryFn: async () => {
      const response = await api.get('/user/transactions', {
        params: { page, type: typeFilter !== 'all' ? typeFilter : undefined }
      });
      const data = response.data;
      return data?.data || data || { data: [], meta: {} };
    },
  });

  const transactions = Array.isArray(data?.data) ? data.data : [];
  const meta = data?.meta || {};

  const getTransactionIcon = (type: string) => {
    if (type.includes('credit') || type.includes('bonus') || type.includes('refund') || type === 'reversal') {
      return <ArrowDownCircle className="h-5 w-5 text-green-500" />;
    }
    return <ArrowUpCircle className="h-5 w-5 text-red-500" />;
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, "default" | "secondary" | "destructive" | "outline"> = {
      completed: "default",
      pending: "secondary",
      failed: "destructive",
    };
    return (
      <Badge variant={variants[status] || "outline"}>
        {status}
      </Badge>
    );
  };

  const formatAmount = (amount: number) => {
    const isPositive = amount >= 0;
    return (
      <span className={isPositive ? "text-green-600 font-semibold" : "text-red-600 font-semibold"}>
        {isPositive ? '+' : ''}₹{Math.abs(amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
      </span>
    );
  };

  const handleExport = async () => {
    try {
      const response = await api.get('/user/transactions/export', {
        params: { type: typeFilter !== 'all' ? typeFilter : undefined },
        responseType: 'blob'
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `transactions-${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-muted-foreground">Loading transactions...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Transaction History</h1>
          <p className="text-muted-foreground">
            View all your wallet transactions and activity
          </p>
        </div>
        <Button onClick={handleExport} variant="outline">
          <Download className="mr-2 h-4 w-4" />
          Export CSV
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Filter and search your transactions</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search by description..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-9"
                />
              </div>
            </div>
            <Select value={typeFilter} onValueChange={setTypeFilter}>
              <SelectTrigger className="w-full md:w-[200px]">
                <Filter className="mr-2 h-4 w-4" />
                <SelectValue placeholder="Filter by type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Types</SelectItem>
                <SelectItem value="bonus_credit">Bonus Credits</SelectItem>
                <SelectItem value="refund">Refunds</SelectItem>
                <SelectItem value="withdrawal_request">Withdrawals</SelectItem>
                <SelectItem value="admin_adjustment">Adjustments</SelectItem>
                <SelectItem value="reversal">Reversals</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>All Transactions</CardTitle>
          <CardDescription>
            {meta.total || transactions.length} transaction{transactions.length !== 1 ? 's' : ''} found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[50px]"></TableHead>
                  <TableHead>Date & Time</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead className="text-right">Amount</TableHead>
                  <TableHead className="text-right">Balance After</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {transactions.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                      No transactions found
                    </TableCell>
                  </TableRow>
                ) : (
                  transactions
                    .filter((txn: Transaction) =>
                      !searchQuery || txn.description.toLowerCase().includes(searchQuery.toLowerCase())
                    )
                    .map((txn: Transaction) => (
                      <TableRow key={txn.id}>
                        <TableCell>{getTransactionIcon(txn.type)}</TableCell>
                        <TableCell className="font-medium">
                          {new Date(txn.created_at).toLocaleString('en-IN', {
                            dateStyle: 'medium',
                            timeStyle: 'short',
                          })}
                        </TableCell>
                        <TableCell>{txn.description}</TableCell>
                        <TableCell>
                          <Badge variant="outline" className="font-mono text-xs">
                            {txn.type.replace(/_/g, ' ').toUpperCase()}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          {formatAmount(txn.amount)}
                        </TableCell>
                        <TableCell className="text-right font-medium">
                          ₹{txn.balance_after.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                        </TableCell>
                        <TableCell>{getStatusBadge(txn.status)}</TableCell>
                      </TableRow>
                    ))
                )}
              </TableBody>
            </Table>
          </div>

          {meta.last_page > 1 && (
            <div className="flex justify-center gap-2 mt-6">
              <Button
                variant="outline"
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
              >
                Previous
              </Button>
              <span className="flex items-center px-4">
                Page {page} of {meta.last_page}
              </span>
              <Button
                variant="outline"
                disabled={page === meta.last_page}
                onClick={() => setPage(page + 1)}
              >
                Next
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
