// V-PHASE6-1730-127
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogTrigger } from "@/components/ui/dialog";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { KycVerificationModal } from "@/components/admin/KycVerificationModal";
import { useState } from "react";

export default function KycQueuePage() {
  const [selectedKycId, setSelectedKycId] = useState<number | null>(null);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['kycQueue'],
    queryFn: async () => (await api.get('/admin/kyc-queue?status=submitted')).data,
  });

  if (isLoading) return <div>Loading KYC queue...</div>;

  return (
    <Dialog open={!!selectedKycId} onOpenChange={(open) => !open && setSelectedKycId(null)}>
      <Card>
        <CardHeader>
          <CardTitle>Pending KYC Queue</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User ID</TableHead>
                <TableHead>Username</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Submitted At</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.data.map((kyc: any) => (
                <TableRow key={kyc.id}>
                  <TableCell>{kyc.user.id}</TableCell>
                  <TableCell>{kyc.user.username}</TableCell>
                  <TableCell>{kyc.user.email}</TableCell>
                  <TableCell>{new Date(kyc.submitted_at).toLocaleString()}</TableCell>
                  <TableCell>
                    <DialogTrigger asChild>
                      <Button onClick={() => setSelectedKycId(kyc.id)}>Review</Button>
                    </DialogTrigger>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      
      {selectedKycId && (
        <KycVerificationModal 
          kycId={selectedKycId} 
          onClose={() => {
            setSelectedKycId(null);
            refetch(); // Refetch the queue after closing the modal
          }} 
        />
      )}
    </Dialog>
  );
}