// V-REMEDIATE-1730-159 (Created) | V-REMEDIATE-1730-163 | V-FINAL-1730-576 (V2.0 UI)

'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Settings, AlertTriangle, Undo, UserPlus } from "lucide-react";

// --- Manage Period Component ---
function ManagePeriodModal({ period, onClose }: { period: any, onClose: () => void }) {
  const queryClient = useQueryClient();
  const [reason, setReason] = useState('');
  const [adjUser, setAdjUser] = useState('');
  const [adjAmount, setAdjAmount] = useState('');
  
  // Fetch the full details, including distributions
  const { data: detail, isLoading } = useQuery({
    queryKey: ['profitShareDetail', period.id],
    queryFn: async () => (await api.get(`/admin/profit-sharing/${period.id}`)).data,
  });

  // --- Mutations ---
  const calculateMutation = useMutation({
    mutationFn: () => api.post(`/admin/profit-sharing/${period.id}/calculate`),
    onSuccess: () => {
      toast.success("Calculation Complete!");
      queryClient.invalidateQueries({ queryKey: ['profitShareDetail', period.id] });
    }
  });
  
  const distributeMutation = useMutation({
    mutationFn: () => api.post(`/admin/profit-sharing/${period.id}/distribute`),
    onSuccess: ()_ => {
      toast.success("Distribution Started!");
      queryClient.invalidateQueries({ queryKey: ['profitShareDetail', period.id] });
      queryClient.invalidateQueries({ queryKey: ['adminProfitSharing'] });
      onClose();
    }
  });
  
  const adjustMutation = useMutation({
    mutationFn: (data: any) => api.post(`/admin/profit-sharing/${period.id}/adjust`, data),
    onSuccess: () => {
      toast.success("Adjustment Saved!");
      queryClient.invalidateQueries({ queryKey: ['profitShareDetail', period.id] });
      setAdjUser(''); setAdjAmount('');
    }
  });

  const reverseMutation = useMutation({
    mutationFn: (data: any) => api.post(`/admin/profit-sharing/${period.id}/reverse`, data),
    onSuccess: () => {
      toast.success("Distribution Reversed!");
      queryClient.invalidateQueries({ queryKey: ['profitShareDetail', period.id] });
      queryClient.invalidateQueries({ queryKey: ['adminProfitSharing'] });
      onClose();
    }
  });

  const handleAdjust = () => {
    adjustMutation.mutate({ user_id: adjUser, amount: adjAmount, reason: "Admin adjustment" });
  };

  return (
    <Dialog open={true} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Manage Period: {period.period_name}</DialogTitle>
          <DialogDescription>Status: <Badge>{period.status}</Badge></DialogDescription>
        </DialogHeader>
        <div className="max-h-[70vh] overflow-y-auto space-y-6 p-1">
          {/* 1. Actions */}
          <div className="flex gap-4">
            <Button onClick={() => calculateMutation.mutate()} disabled={period.status !== 'pending' || calculateMutation.isPending}>
              {calculateMutation.isPending ? "Calculating..." : "1. Calculate Distribution"}
            </Button>
            <Button onClick={() => distributeMutation.mutate()} disabled={period.status !== 'calculated' || distributeMutation.isPending}>
              {distributeMutation.isPending ? "Distributing..." : "2. Distribute Prizes"}
            </Button>
          </div>
          
          {/* 2. Manual Adjustment */}
          {period.status === 'calculated' && (
            <Card>
              <CardHeader><CardTitle className="text-lg">Manual Adjustment</CardTitle></CardHeader>
              <CardContent className="flex gap-2">
                <Input placeholder="User ID" value={adjUser} onChange={e => setAdjUser(e.target.value)} />
                <Input placeholder="Amount (₹)" value={adjAmount} onChange={e => setAdjAmount(e.target.value)} />
                <Button onClick={handleAdjust} disabled={adjustMutation.isPending}><UserPlus className="h-4 w-4" /></Button>
              </CardContent>
            </Card>
          )}

          {/* 3. Distribution List */}
          <Card>
            <CardHeader><CardTitle>Calculated Distributions</CardTitle></CardHeader>
            <CardContent>
              {isLoading ? <p>Loading...</p> : (
                <Table>
                  <TableHeader><TableRow><TableHead>User</TableHead><TableHead>Amount</TableHead></TableRow></TableHeader>
                  <TableBody>
                    {detail?.distributions.map((d: any) => (
                      <TableRow key={d.id}>
                        <TableCell>{d.user.username} ({d.user.email})</TableCell>
                        <TableCell>₹{d.amount}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
          
          {/* 4. Reversal (Danger Zone) */}
          {period.status === 'distributed' && (
            <Card className="border-destructive/50">
              <CardHeader><CardTitle className="text-destructive flex items-center"><AlertTriangle className="mr-2"/> Danger Zone</CardTitle></CardHeader>
              <CardContent className="space-y-2">
                <Label>Reason for Reversal</Label>
                <Input value={reason} onChange={e => setReason(e.target.value)} placeholder="e.g., Calculation error" />
                <Button variant="destructive" onClick={()_ => reverseMutation.mutate({ reason })} disabled={reverseMutation.isPending || reason.length < 10}>
                  <Undo className="mr-2 h-4 w-4" /> Reverse this Distribution
                </Button>
              </CardContent>
            </Card>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}

// --- Main Page Component ---
export default function ProfitSharingPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [selectedPeriod, setSelectedPeriod] = useState<any>(null);

  // Form State
  const [periodName, setPeriodName] = useState('');
  const [netProfit, setNetProfit] = useState('');
  const [totalPool, setTotalPool] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['adminProfitSharing'],
    queryFn: async () => (await api.get('/admin/profit-sharing')).data.data,
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/profit-sharing', data),
    onSuccess: () => {
      toast.success("Period Created");
      queryClient.invalidateQueries({ queryKey: ['adminProfitSharing'] });
      setIsDialogOpen(false);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const handleSubmit = () => {
    createMutation.mutate({
      period_name: periodName,
      net_profit: parseFloat(netProfit),
      total_pool: parseFloat(totalPool),
      start_date: startDate,
      end_date: endDate
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Profit Sharing</h1>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild><Button><Plus className="mr-2 h-4 w-4" /> Create Period</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>New Profit Share Period</DialogTitle></DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2"><Label>Period Name</Label><Input value={periodName} onChange={e => setPeriodName(e.target.value)} placeholder="e.g. Q4 2025" /></div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2"><Label>Start Date</Label><Input type="date" value={startDate} onChange={e => setStartDate(e.target.value)} /></div>
                <div className="space-y-2"><Label>End Date</Label><Input type="date" value={endDate} onChange={e => setEndDate(e.target.value)} /></div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2"><Label>Total Net Profit (₹)</Label><Input type="number" value={netProfit} onChange={e => setNetProfit(e.target.value)} /></div>
                <div className="space-y-2"><Label>Distribution Pool (₹)</Label><Input type="number" value={totalPool} onChange={e => setTotalPool(e.target.value)} /></div>
              </div>
              <Button onClick={handleSubmit} className="w-full" disabled={createMutation.isPending}>Create Period</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="pt-6">
          <Table>
            <TableHeader><TableRow>
              <TableHead>Period</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Total Pool</TableHead>
              <TableHead>Net Profit</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow></TableHeader>
            <TableBody>
              {isLoading ? <TableRow><TableCell colSpan={5}>Loading...</TableCell></TableRow> :
                data?.map((p: any) => (
                  <TableRow key={p.id}>
                    <TableCell className="font-medium">{p.period_name}</TableCell>
                    <TableCell><Badge variant="outline">{p.status}</Badge></TableCell>
                    <TableCell>₹{p.total_pool}</TableCell>
                    <TableCell>₹{p.net_profit}</TableCell>
                    <TableCell>
                      <Button variant="outline" size="sm" onClick={() => setSelectedPeriod(p)}>
                        <Settings className="mr-2 h-4 w-4" /> Manage
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              }
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      
      {selectedPeriod && (
        <ManagePeriodModal 
          period={selectedPeriod}
          onClose={() => setSelectedPeriod(null)}
        />
      )}
    </div>
  );
}