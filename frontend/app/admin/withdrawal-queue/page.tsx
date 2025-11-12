// V-PHASE6-1730-129
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogTrigger } from "@/components/ui/dialog";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { WithdrawalProcessModal } from "@/components/admin/WithdrawalProcessModal";

export default function WithdrawalQueuePage() {
  const [selectedWithdrawal, setSelectedWithdrawal] = useState<any>(null);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['withdrawalQueue'],
    queryFn: async () => (await api.get('/admin/withdrawal-queue?status=pending')).data,
  });

  return (
    <Dialog open={!!selectedWithdrawal} onOpenChange={(open) => !open && setSelectedWithdrawal(null)}>
      <Card>
        <CardHeader>
          <CardTitle>Pending Withdrawal Queue</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Net Amount</TableHead>
                <TableHead>Bank (JSON)</TableHead>
                <TableHead>Requested At</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.data.map((w: any) => (
                <TableRow key={w.id}>
                  <TableCell>{w.user.username}</TableCell>
                  <TableCell>₹{w.amount}</TableCell>
                  <TableCell>₹{w.net_amount}</TableCell>
                  <TableCell><pre className="text-xs">{JSON.stringify(w.bank_details)}</pre></TableCell>
                  <TableCell>{new Date(w.created_at).toLocaleString()}</TableCell>
                  <TableCell>
                    <DialogTrigger asChild>
                      <Button onClick={() => setSelectedWithdrawal(w)}>Review</Button>
                    </DialogTrigger>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      
      {selectedWithdrawal && (
        <WithdrawalProcessModal 
          withdrawal={selectedWithdrawal} 
          onClose={() => {
            setSelectedWithdrawal(null);
            refetch(); // Refetch the queue
          }} 
        />
      )}
    </Dialog>
  );
}