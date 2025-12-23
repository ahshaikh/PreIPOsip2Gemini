// V-PHASE6-1730-129 | V-PROTOCOL-7-PAGINATION
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogTrigger } from "@/components/ui/dialog";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { WithdrawalProcessModal } from "@/components/admin/WithdrawalProcessModal";
import { PaginationControls } from "@/components/shared/PaginationControls";

export default function WithdrawalQueuePage() {
  // [PROTOCOL 7] State for pagination
  const [page, setPage] = useState(1);
  const [selectedWithdrawal, setSelectedWithdrawal] = useState<any>(null);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['withdrawalQueue', page],
    queryFn: async () => {
      // [PROTOCOL 7] Pass page parameter to backend
      const res = await api.get(`/admin/withdrawal-queue?status=pending&page=${page}`);
      return res.data;
    },
    // Keep previous data while fetching new page for smoother UX
    placeholderData: (previousData) => previousData,
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
              {isLoading ? (
                <TableRow><TableCell colSpan={6} className="text-center h-24">Loading queue...</TableCell></TableRow>
              ) : data?.data?.length === 0 ? (
                <TableRow><TableCell colSpan={6} className="text-center h-24 text-muted-foreground">No pending withdrawals.</TableCell></TableRow>
              ) : (
                data?.data.map((w: any) => (
                  <TableRow key={w.id}>
                    <TableCell>{w.user?.username || 'Unknown'}</TableCell>
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
                ))
              )}
            </TableBody>
          </Table>

          {/* [PROTOCOL 7] Dynamic Pagination Controls */}
          {data && (
            <PaginationControls
              currentPage={data.current_page}
              totalPages={data.last_page}
              onPageChange={setPage}
              totalItems={data.total}
              from={data.from}
              to={data.to}
            />
          )}

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