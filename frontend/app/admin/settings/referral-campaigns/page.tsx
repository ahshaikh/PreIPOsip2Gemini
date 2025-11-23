// V-FINAL-1730-274 | V-ENHANCED-CAMPAIGNS
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Trash2, Zap, Edit, Users, IndianRupee, TrendingUp, Calendar, Gift, BarChart3, Clock, Copy } from "lucide-react";

// Helper to format date for input
const formatDateForInput = (date: string | null) => {
  if (!date) return '';
  return new Date(date).toISOString().split('T')[0];
};

// Helper to check campaign status
const getCampaignStatus = (campaign: any) => {
  const now = new Date();
  const start = new Date(campaign.start_date);
  const end = new Date(campaign.end_date);

  if (!campaign.is_active) return { label: 'Paused', color: 'bg-gray-100 text-gray-800' };
  if (now < start) return { label: 'Scheduled', color: 'bg-blue-100 text-blue-800' };
  if (now > end) return { label: 'Ended', color: 'bg-red-100 text-red-800' };
  return { label: 'Active', color: 'bg-green-100 text-green-800' };
};

// Helper to get days remaining
const getDaysRemaining = (endDate: string) => {
  const now = new Date();
  const end = new Date(endDate);
  const diff = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  return diff > 0 ? diff : 0;
};

export default function ReferralCampaignsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCampaign, setEditingCampaign] = useState<any>(null);
  const [deleteConfirmCampaign, setDeleteConfirmCampaign] = useState<any>(null);

  // Form State
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [multiplier, setMultiplier] = useState('1.5');
  const [bonusAmount, setBonusAmount] = useState('0');
  const [maxUses, setMaxUses] = useState('');
  const [minReferrals, setMinReferrals] = useState('1');
  const [isActive, setIsActive] = useState(true);

  const { data: campaigns, isLoading } = useQuery({
    queryKey: ['adminCampaigns'],
    queryFn: async () => (await api.get('/admin/referral-campaigns')).data,
  });

  // Campaign statistics query
  const { data: campaignStats } = useQuery({
    queryKey: ['adminCampaignStats'],
    queryFn: async () => (await api.get('/admin/referral-campaigns/stats')).data,
  });

  const resetForm = () => {
    setName(''); setDescription(''); setStartDate(''); setEndDate('');
    setMultiplier('1.5'); setBonusAmount('0'); setMaxUses(''); setMinReferrals('1');
    setIsActive(true); setEditingCampaign(null);
  };

  const handleEdit = (campaign: any) => {
    setEditingCampaign(campaign);
    setName(campaign.name);
    setDescription(campaign.description || '');
    setStartDate(formatDateForInput(campaign.start_date));
    setEndDate(formatDateForInput(campaign.end_date));
    setMultiplier(campaign.multiplier?.toString() || '1.5');
    setBonusAmount(campaign.bonus_amount?.toString() || '0');
    setMaxUses(campaign.max_uses?.toString() || '');
    setMinReferrals(campaign.min_referrals?.toString() || '1');
    setIsActive(campaign.is_active);
    setIsDialogOpen(true);
  };

  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingCampaign) {
        return api.put(`/admin/referral-campaigns/${editingCampaign.id}`, data);
      }
      return api.post('/admin/referral-campaigns', data);
    },
    onSuccess: () => {
      toast.success(editingCampaign ? "Campaign Updated" : "Campaign Created");
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      queryClient.invalidateQueries({ queryKey: ['adminCampaignStats'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/referral-campaigns/${id}`),
    onSuccess: () => {
      toast.success("Campaign deleted");
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      queryClient.invalidateQueries({ queryKey: ['adminCampaignStats'] });
      setDeleteConfirmCampaign(null);
    },
    onError: (e: any) => toast.error("Cannot delete campaign", { description: e.response?.data?.message })
  });

  const toggleMutation = useMutation({
    mutationFn: (c: any) => api.put(`/admin/referral-campaigns/${c.id}`, { is_active: !c.is_active }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.success("Campaign status updated");
    }
  });

  const duplicateMutation = useMutation({
    mutationFn: (campaign: any) => api.post('/admin/referral-campaigns', {
      name: `${campaign.name} (Copy)`,
      description: campaign.description,
      start_date: campaign.start_date,
      end_date: campaign.end_date,
      multiplier: campaign.multiplier,
      bonus_amount: campaign.bonus_amount,
      max_uses: campaign.max_uses,
      min_referrals: campaign.min_referrals,
      is_active: false,
    }),
    onSuccess: () => {
      toast.success("Campaign duplicated");
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({
      name,
      description,
      start_date: startDate,
      end_date: endDate,
      multiplier: parseFloat(multiplier),
      bonus_amount: parseFloat(bonusAmount),
      max_uses: maxUses ? parseInt(maxUses) : null,
      min_referrals: parseInt(minReferrals),
      is_active: isActive
    });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Referral Campaigns</h1>
          <p className="text-muted-foreground">Create and manage promotional referral campaigns.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><Plus className="mr-2 h-4 w-4" /> Create Campaign</Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>{editingCampaign ? 'Edit Campaign' : 'New Referral Campaign'}</DialogTitle>
              <DialogDescription>
                {editingCampaign ? 'Update campaign settings and incentives.' : 'Create a time-limited campaign to boost referrals.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Basic Info */}
              <div className="space-y-4">
                <div className="space-y-2">
                  <Label>Campaign Name</Label>
                  <Input value={name} onChange={e => setName(e.target.value)} placeholder="e.g. Diwali Dhamaka 2024" required />
                </div>
                <div className="space-y-2">
                  <Label>Description (Optional)</Label>
                  <Textarea value={description} onChange={e => setDescription(e.target.value)} placeholder="Describe the campaign..." rows={2} />
                </div>
              </div>

              {/* Duration */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Campaign Duration</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Start Date</Label>
                    <Input type="date" value={startDate} onChange={e => setStartDate(e.target.value)} required />
                  </div>
                  <div className="space-y-2">
                    <Label>End Date</Label>
                    <Input type="date" value={endDate} onChange={e => setEndDate(e.target.value)} required />
                  </div>
                </div>
              </div>

              {/* Incentives */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Incentives</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Bonus Multiplier (x)</Label>
                    <Input type="number" step="0.1" min="1" value={multiplier} onChange={e => setMultiplier(e.target.value)} />
                    <p className="text-xs text-muted-foreground">Multiply base referral bonus by this factor</p>
                  </div>
                  <div className="space-y-2">
                    <Label>Extra Flat Bonus (₹)</Label>
                    <Input type="number" min="0" value={bonusAmount} onChange={e => setBonusAmount(e.target.value)} />
                    <p className="text-xs text-muted-foreground">Additional bonus per successful referral</p>
                  </div>
                </div>
              </div>

              {/* Limits */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Limits</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Max Uses (Optional)</Label>
                    <Input type="number" min="1" value={maxUses} onChange={e => setMaxUses(e.target.value)} placeholder="Unlimited" />
                    <p className="text-xs text-muted-foreground">Maximum total referrals for this campaign</p>
                  </div>
                  <div className="space-y-2">
                    <Label>Min Referrals Required</Label>
                    <Input type="number" min="1" value={minReferrals} onChange={e => setMinReferrals(e.target.value)} />
                    <p className="text-xs text-muted-foreground">Minimum referrals needed to qualify</p>
                  </div>
                </div>
              </div>

              {/* Active Toggle */}
              <div className="flex items-center space-x-2 p-4 bg-muted/50 rounded-lg">
                <Switch checked={isActive} onCheckedChange={setIsActive} />
                <Label>Activate campaign immediately when dates match</Label>
              </div>

              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingCampaign ? "Save Changes" : "Create Campaign")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics Cards */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Campaigns</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{campaigns?.length || 0}</div>
            <p className="text-xs text-muted-foreground">
              {campaigns?.filter((c: any) => getCampaignStatus(c).label === 'Active').length || 0} currently active
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Referrals</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{campaignStats?.total_referrals?.toLocaleString() || '0'}</div>
            <p className="text-xs text-muted-foreground">From all campaigns</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Bonuses Paid</CardTitle>
            <IndianRupee className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{campaignStats?.total_bonuses_paid?.toLocaleString() || '0'}</div>
            <p className="text-xs text-muted-foreground">Campaign incentives</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Conversion Rate</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{campaignStats?.conversion_rate || '0'}%</div>
            <p className="text-xs text-muted-foreground">Referrals to signups</p>
          </CardContent>
        </Card>
      </div>

      {/* Campaigns Table */}
      <Card>
        <CardHeader>
          <CardTitle>All Campaigns</CardTitle>
          <CardDescription>Manage your referral promotion campaigns.</CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Campaign</TableHead>
                  <TableHead>Duration</TableHead>
                  <TableHead>Incentives</TableHead>
                  <TableHead>Performance</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Active</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {campaigns?.map((c: any) => {
                  const status = getCampaignStatus(c);
                  const daysLeft = getDaysRemaining(c.end_date);
                  const usagePercent = c.max_uses ? Math.min((c.uses_count || 0) / c.max_uses * 100, 100) : 0;

                  return (
                    <TableRow key={c.id}>
                      <TableCell>
                        <div>
                          <div className="font-medium flex items-center gap-2">
                            <Zap className="h-4 w-4 text-yellow-500" />
                            {c.name}
                          </div>
                          {c.description && (
                            <div className="text-xs text-muted-foreground truncate max-w-[200px]">{c.description}</div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="text-sm">
                          <div>{new Date(c.start_date).toLocaleDateString()}</div>
                          <div className="text-muted-foreground">to {new Date(c.end_date).toLocaleDateString()}</div>
                        </div>
                        {status.label === 'Active' && daysLeft > 0 && (
                          <div className="flex items-center gap-1 text-xs text-orange-600 mt-1">
                            <Clock className="h-3 w-3" /> {daysLeft} days left
                          </div>
                        )}
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          <Badge variant="secondary" className="bg-purple-100 text-purple-800">
                            {c.multiplier}x Bonus
                          </Badge>
                          {c.bonus_amount > 0 && (
                            <Badge variant="secondary" className="bg-green-100 text-green-800">
                              +₹{c.bonus_amount}
                            </Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="space-y-1">
                          <div className="flex items-center gap-2 text-sm">
                            <Users className="h-3 w-3" />
                            <span>{c.uses_count || 0} referrals</span>
                          </div>
                          {c.max_uses && (
                            <div className="w-24">
                              <Progress value={usagePercent} className="h-1.5" />
                              <p className="text-xs text-muted-foreground mt-0.5">
                                {c.uses_count || 0}/{c.max_uses} used
                              </p>
                            </div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <span className={`px-2 py-1 rounded-full text-xs font-semibold ${status.color}`}>
                          {status.label}
                        </span>
                      </TableCell>
                      <TableCell>
                        <Switch
                          checked={c.is_active}
                          onCheckedChange={() => toggleMutation.mutate(c)}
                          disabled={toggleMutation.isPending}
                        />
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => handleEdit(c)}>
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="sm" onClick={() => duplicateMutation.mutate(c)}>
                            <Copy className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setDeleteConfirmCampaign(c)}
                            className="text-destructive hover:text-destructive"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  );
                })}
                {campaigns?.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      No campaigns created yet. Create your first referral campaign!
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmCampaign} onOpenChange={() => setDeleteConfirmCampaign(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Campaign</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirmCampaign?.name}"? This action cannot be undone.
              {deleteConfirmCampaign?.uses_count > 0 && (
                <span className="block mt-2 text-orange-600 font-medium">
                  Note: This campaign has {deleteConfirmCampaign.uses_count} recorded referrals.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmCampaign.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Campaign"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}