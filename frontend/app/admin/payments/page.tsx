// V-FINAL-1730-260 (Fraud UI + Manual Approval + Invoices)
'use client';

import { Card, CardContent } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription, DialogTrigger } from "@/components/ui/dialog";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
// --- UPDATED IMPORTS ---
import { Plus, RefreshCcw, Download, CheckCircle, XCircle, Eye, AlertTriangle } from "lucide-react";
import { SearchInput } from "@/components/shared/SearchInput";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { useSearchParams } from "next/navigation";
import { getStorageUrl } from "@/lib/utils";

export default function PaymentManagerPage() {
  const queryClient = useQueryClient();
  const searchParams = useSearchParams();
  const [isOfflineOpen, setIsOfflineOpen] = useState(false);
  const [refundData, setRefundData] = useState<{id: number, amount: number} | null>(null);
  const [refundReason, setRefundReason] = useState('');
  const [isDownloading, setIsDownloading] = useState<number | null>(null);

  // URL Params
  const page = searchParams.get('page') || '1';
  const search = searchParams.get('search') || '';
  const statusFilter = searchParams.get('status') || 'all';

  // Form State (Offline)
  const [userId, setUserId] = useState('');
  const [amount, setAmount] = useState('');
  const [date, setDate] = useState('');
  const [reference, setReference] = useState('');
  const [method, setMethod] = useState('NEFT');

  // Fetch Payments
  const { data: queryData, isLoading } = useQuery({
    queryKey: ['adminPayments', page, search, statusFilter],
    queryFn: async () => (await api.get(`/admin/payments?page=${page}&search=${search}&status=${statusFilter}`)).data,
  });

  // Offline Payment Mutation
  const offlineMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/payments/offline', data),
    onSuccess: () => {
      toast.success("Payment Recorded", { description: "Bonuses have been calculated." });
      queryClient.invalidateQueries({ queryKey: ['adminPayments'] });
      setIsOfflineOpen(false);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Refund Mutation
  const refundMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/payments/${id}/refund`, { reason: refundReason }),
    onSuccess: () => {
      toast.success("Payment Refunded", { description: "Amount returned to user wallet." });
      queryClient.invalidateQueries({ queryKey: ['adminPayments'] });
      setRefundData(null);
      setRefundReason('');
    },
    onError: (e: any) => toast.error("Refund Failed", { description: e.response?.data?.message })
  });

  // Approve Mutation (Handles Manual Proofs AND Fraud Flags)
  const approveMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/payments/${id}/approve`),
    onSuccess: () => {
      toast.success("Payment Approved");
      queryClient.invalidateQueries({ queryKey: ['adminPayments'] });
    },
    onError: (e: any) => toast.error("Approval Failed", { description: e.response?.data?.message })
  });

  // Reject Mutation
  const rejectMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/payments/${id}/reject`, { reason: 'Rejected by admin' }),
    onSuccess: () => {
      toast.success("Payment Rejected");
      queryClient.invalidateQueries({ queryKey: ['adminPayments'] });
    },
    onError: (e: any) => toast.error("Rejection Failed", { description: e.response?.data?.message })
  });

  const handleOfflineSubmit = () => {
    offlineMutation.mutate({
      user_id: userId,
      amount: parseFloat(amount),
      payment_date: date,
      reference_id: reference,
      method: method
    });
  };

  // PDF Download Handler
  const handleDownloadInvoice = async (paymentId: number) => {
    setIsDownloading(paymentId);
    try {
      const response = await api.get(`/admin/payments/${paymentId}/invoice`, {
        responseType: 'blob',
      });
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `receipt-${paymentId}.pdf`);
      document.body.appendChild(link);
      link.click();
      window.URL.revokeObjectURL(url);
      link.remove();
    } catch (error) {
      toast.error("Download Failed", { description: "Could not generate receipt." });
    } finally {
      setIsDownloading(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Payment Transactions</h1>
        <div className="flex items-center gap-2">
          <SearchInput placeholder="Search User/Trans ID..." />
          
          <Dialog open={isOfflineOpen} onOpenChange={setIsOfflineOpen}>
            <DialogTrigger asChild><Button><Plus className="mr-2 h-4 w-4" /> Record Offline</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Record Offline Payment</DialogTitle></DialogHeader>
              <div className="space-y-4">
                <div className="space-y-2"><Label>User ID</Label><Input value={userId} onChange={e => setUserId(e.target.value)} placeholder="e.g., 15" /></div>
                <div className="space-y-2"><Label>Amount (₹)</Label><Input type="number" value={amount} onChange={e => setAmount(e.target.value)} /></div>
                <div className="space-y-2"><Label>Date</Label><Input type="date" value={date} onChange={e => setDate(e.target.value)} /></div>
                <div className="space-y-2"><Label>Reference / UTR</Label><Input value={reference} onChange={e => setReference(e.target.value)} /></div>
                <div className="space-y-2">
                  <Label>Method</Label>
                  <Select value={method} onValueChange={setMethod}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="NEFT">NEFT</SelectItem>
                      <SelectItem value="IMPS">IMPS</SelectItem>
                      <SelectItem value="CASH">Cash</SelectItem>
                      <SelectItem value="CHEQUE">Cheque</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Button className="w-full" onClick={handleOfflineSubmit} disabled={offlineMutation.isPending}>Record Payment</Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? <p>Loading...</p> : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>User</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Ref / UTR</TableHead>
                    <TableHead>Proof</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {queryData?.data.map((pay: any) => (
                    <TableRow key={pay.id}>
                      <TableCell>{pay.id}</TableCell>
                      <TableCell>
                        <div>{pay.user.username}</div>
                        <div className="text-xs text-muted-foreground">{pay.user.email}</div>
                      </TableCell>
                      <TableCell>₹{pay.amount}</TableCell>
                      <TableCell>
                        <div className="text-xs font-mono">{pay.gateway_payment_id}</div>
                        <div className="uppercase text-[10px] text-muted-foreground">{pay.gateway}</div>
                      </TableCell>
                      <TableCell>
                        {pay.payment_proof_path ? (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => window.open(getStorageUrl(pay.payment_proof_path), '_blank')}
                          >
                            <Eye className="h-4 w-4 text-blue-600" />
                          </Button>
                        ) : (
                          <span className="text-muted-foreground text-xs">-</span>
                        )}
                      </TableCell>
                      <TableCell>{pay.paid_at ? new Date(pay.paid_at).toLocaleDateString() : '-'}</TableCell>
                      <TableCell>
                        {/* --- FRAUD ALERT UI --- */}
                        {pay.is_flagged && (
                          <div className="mb-1">
                            <span className="bg-red-100 text-red-800 text-[10px] font-bold px-2 py-0.5 rounded border border-red-200 flex items-center gap-1 w-fit">
                              <AlertTriangle className="h-3 w-3" /> FRAUD ALERT
                            </span>
                            <span className="text-[10px] text-destructive block mt-0.5 max-w-[150px] truncate" title={pay.flag_reason}>
                              {pay.flag_reason}
                            </span>
                          </div>
                        )}
                        {/* -------------------- */}
                        
                        <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                          pay.status === 'paid' ? 'bg-green-100 text-green-800' :
                          pay.status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' :
                          pay.status === 'refunded' ? 'bg-gray-100 text-gray-800' :
                          'bg-red-100 text-red-800'
                        }`}>
                          {pay.status.replace('_', ' ')}
                        </span>
                      </TableCell>
                      <TableCell className="flex gap-1 items-center">
                        {/* APPROVAL ACTIONS (Manual or Fraud Flagged) */}
                        {pay.status === 'pending_approval' && (
                          <>
                            <Button size="sm" className="h-8 px-2 bg-green-600 hover:bg-green-700" onClick={() => approveMutation.mutate(pay.id)} disabled={approveMutation.isPending}>
                              <CheckCircle className="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="destructive" className="h-8 px-2" onClick={() => rejectMutation.mutate(pay.id)} disabled={rejectMutation.isPending}>
                              <XCircle className="h-4 w-4" />
                            </Button>
                          </>
                        )}

                        {/* PAID ACTIONS */}
                        {pay.status === 'paid' && (
                          <>
                            <Button 
                              variant="ghost" 
                              size="sm" 
                              onClick={() => handleDownloadInvoice(pay.id)}
                              disabled={isDownloading === pay.id}
                            >
                              <Download className="h-4 w-4 text-muted-foreground" />
                            </Button>
                            <Dialog open={refundData?.id === pay.id} onOpenChange={(open) => { if(!open) setRefundData(null); }}>
                              <DialogTrigger asChild><Button variant="ghost" size="sm" onClick={() => setRefundData({id: pay.id, amount: pay.amount})}><RefreshCcw className="h-4 w-4 text-muted-foreground" /></Button></DialogTrigger>
                              <DialogContent>
                                <DialogHeader>
                                  <DialogTitle>Refund Payment #{pay.id}</DialogTitle>
                                  <DialogDescription>User: {pay.user.username}</DialogDescription>
                                </DialogHeader>
                                <div className="space-y-4">
                                  <p className="text-destructive font-bold text-sm">Warning: Refunds credit the wallet. Bonuses are NOT automatically reversed.</p>
                                  <Input value={refundReason} onChange={e => setRefundReason(e.target.value)} placeholder="Reason for refund" />
                                  <Button variant="destructive" className="w-full" onClick={() => refundMutation.mutate(pay.id)} disabled={refundMutation.isPending || !refundReason}>
                                    Confirm Refund
                                  </Button>
                                </div>
                              </DialogContent>
                            </Dialog>
                          </>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {queryData?.meta && <PaginationControls meta={queryData.meta} />}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}