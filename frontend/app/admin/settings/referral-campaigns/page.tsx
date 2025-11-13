// V-FINAL-1730-274
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Trash2, Zap } from "lucide-react";

export default function ReferralCampaignsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  // Form State
  const [name, setName] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [multiplier, setMultiplier] = useState('1.5');
  const [bonusAmount, setBonusAmount] = useState('0');
  const [isActive, setIsActive] = useState(true);

  const { data: campaigns, isLoading } = useQuery({
    queryKey: ['adminCampaigns'],
    queryFn: async () => (await api.get('/admin/referral-campaigns')).data,
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/referral-campaigns', data),
    onSuccess: () => {
      toast.success("Campaign Created");
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      setIsDialogOpen(false);
      setName('');
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/referral-campaigns/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] })
  });

  const toggleMutation = useMutation({
    mutationFn: (c: any) => api.put(`/admin/referral-campaigns/${c.id}`, { is_active: !c.is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] })
  });

  const handleSubmit = () => {
    createMutation.mutate({
      name,
      start_date: startDate,
      end_date: endDate,
      multiplier: parseFloat(multiplier),
      bonus_amount: parseFloat(bonusAmount),
      is_active: isActive
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Referral Campaigns</h1>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild><Button><Plus className="mr-2 h-4 w-4" /> Create Campaign</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>New Referral Campaign</DialogTitle></DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2"><Label>Campaign Name</Label><Input value={name} onChange={e => setName(e.target.value)} placeholder="e.g. Diwali Dhamaka" /></div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2"><Label>Start Date</Label><Input type="date" value={startDate} onChange={e => setStartDate(e.target.value)} /></div>
                <div className="space-y-2"><Label>End Date</Label><Input type="date" value={endDate} onChange={e => setEndDate(e.target.value)} /></div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2"><Label>Multiplier (x)</Label><Input type="number" step="0.1" value={multiplier} onChange={e => setMultiplier(e.target.value)} /></div>
                <div className="space-y-2"><Label>Extra Bonus (₹)</Label><Input type="number" value={bonusAmount} onChange={e => setBonusAmount(e.target.value)} /></div>
              </div>
              <div className="flex items-center space-x-2">
                <Switch checked={isActive} onCheckedChange={setIsActive} />
                <Label>Active Immediately?</Label>
              </div>
              <Button onClick={handleSubmit} className="w-full" disabled={createMutation.isPending}>Create Campaign</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="pt-6">
          <Table>
            <TableHeader><TableRow><TableHead>Name</TableHead><TableHead>Duration</TableHead><TableHead>Boosts</TableHead><TableHead>Status</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
            <TableBody>
              {campaigns?.map((c: any) => (
                <TableRow key={c.id}>
                  <TableCell className="font-medium">{c.name}</TableCell>
                  <TableCell>{new Date(c.start_date).toLocaleDateString()} - {new Date(c.end_date).toLocaleDateString()}</TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                        <span className="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded font-bold">{c.multiplier}x Multiplier</span>
                        {c.bonus_amount > 0 && <span className="bg-green-100 text-green-800 text-xs px-2 py-1 rounded font-bold">+₹{c.bonus_amount}</span>}
                    </div>
                  </TableCell>
                  <TableCell>
                    <Switch checked={c.is_active} onCheckedChange={() => toggleMutation.mutate(c)} />
                  </TableCell>
                  <TableCell>
                    <Button variant="ghost" size="sm" onClick={() => deleteMutation.mutate(c.id)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}