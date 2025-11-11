// V-PHASE5-1730-122
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/components/ui/use-toast";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";

export default function WalletPage() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [bankDetails, setBankDetails] = useState(''); // Simplified to a JSON string

  const { data, isLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => (await api.get('/user/wallet')).data,
  });
  
  const mutation = useMutation({
    mutationFn: (data: { amount: number, bank_details: string }) => 
      api.post('/user/wallet/withdraw', data),
    onSuccess: () => {
      toast({ title: "Withdrawal Request Submitted", description: "Your request is pending approval." });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
    },
    onError: (error: any) => {
      toast({ title: "Withdrawal Failed", description: error.response?.data?.message, variant: "destructive" });
    }
  });

  const handleWithdraw = () => {
    try {
      // In a real app, this would be a structured form for bank details
      const parsedBankDetails = JSON.parse(bankDetails);
      mutation.mutate({ amount: parseFloat(withdrawAmount), bank_details: parsedBankDetails });
    } catch (e) {
      toast({ title: "Invalid Bank Details", description: "Please enter valid JSON.", variant: "destructive" });
    }
  };

  if (isLoading) return <div>Loading wallet...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">My Wallet</h1>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="md:col-span-1">
          <CardHeader>
            <CardTitle className="text-sm font-medium">Available Balance</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">₹{data?.wallet.balance}</div>
          </CardContent>
        </Card>
        <Card className="md:col-span-1">
          <CardHeader>
            <CardTitle className="text-sm font-medium">Locked Balance</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">₹{data?.wallet.locked_balance}</div>
            <p className="text-xs text-muted-foreground">Pending withdrawals</p>
          </CardContent>
        </Card>
        <Card className="md:col-span-1 flex items-center justify-center">
          <CardContent className="pt-6 flex gap-4">
            <Button disabled>Add Money</Button>
            <Dialog>
              <DialogTrigger asChild>
                <Button variant="outline">Withdraw</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle>Request Withdrawal</DialogTitle>
                  <DialogDescription>
                    Min withdrawal: ₹{1000}. Funds will be sent to your verified bank account.
                  </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="amount">Amount (INR)</Label>
                    <Input id="amount" value={withdrawAmount} onChange={(e) => setWithdrawAmount(e.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="bank">Bank Details (JSON for now)</Label>
                    <Input id="bank" value={bankDetails} onChange={(e) => setBankDetails(e.target.value)} placeholder='{"account": "123", "ifsc": "ABC"}' />
                  </div>
                  <Button onClick={handleWithdraw} disabled={mutation.isPending} className="w-full">
                    {mutation.isPending ? "Submitting..." : "Submit Request"}
                  </Button>
                </div>
              </DialogContent>
            </Dialog>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Transaction History</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Balance After</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.transactions.data.map((tx: any) => (
                <TableRow key={tx.id}>
                  <TableCell>{new Date(tx.created_at).toLocaleString()}</TableCell>
                  <TableCell className="capitalize">{tx.type.replace('_', ' ')}</TableCell>
                  <TableCell>{tx.description}</TableCell>
                  <TableCell className={tx.amount > 0 ? 'text-green-600' : 'text-red-600'}>
                    ₹{tx.amount}
                  </TableCell>
                  <TableCell>₹{tx.balance_after}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}