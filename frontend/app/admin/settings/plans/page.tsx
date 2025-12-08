// V-REMEDIATE-1730-131 (Created - Revised) | V-FINAL-1730-484 (Scheduling UI) | V-ENHANCED-PLANS | V-BONUS-CONFIG-1208
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, PlusCircle, Edit, Trash2, Copy, Users, IndianRupee, TrendingUp, Star, Calendar, Eye, MoreHorizontal, Gift } from "lucide-react";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { BonusConfigDialog } from "@/components/admin/BonusConfigDialog";

// Helper to format date for input
const formatDateForInput = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
};

export default function PlanManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPlan, setEditingPlan] = useState<any>(null);
  const [deleteConfirmPlan, setDeleteConfirmPlan] = useState<any>(null);
  const [activeTab, setActiveTab] = useState('all');

  // Bonus Configuration State
  const [bonusConfigOpen, setBonusConfigOpen] = useState(false);
  const [bonusConfigPlan, setBonusConfigPlan] = useState<any>(null);

  // Form State
  const [name, setName] = useState('');
  const [monthlyAmount, setMonthlyAmount] = useState('');
  const [duration, setDuration] = useState('36');
  const [description, setDescription] = useState('');
  const [isActive, setIsActive] = useState(true);
  const [isFeatured, setIsFeatured] = useState(false);
  const [features, setFeatures] = useState<string[]>([]);
  const [newFeature, setNewFeature] = useState('');
  const [minInvestment, setMinInvestment] = useState('');
  const [maxInvestment, setMaxInvestment] = useState('');

  // Date States
  const [availableFrom, setAvailableFrom] = useState('');
  const [availableUntil, setAvailableUntil] = useState('');

  const { data: plans, isLoading } = useQuery({
    queryKey: ['adminPlans'],
    queryFn: async () => (await api.get('/admin/plans')).data,
  });

  // Plan statistics query
  const { data: planStats } = useQuery({
    queryKey: ['adminPlanStats'],
    queryFn: async () => (await api.get('/admin/plans/stats')).data,
  });

  const mutation = useMutation({
    mutationFn: (newPlan: any) => {
      if (editingPlan) {
        return api.put(`/admin/plans/${editingPlan.id}`, newPlan);
      }
      return api.post('/admin/plans', newPlan);
    },
    onSuccess: () => {
      toast.success(editingPlan ? "Plan Updated" : "Plan Created");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      queryClient.invalidateQueries({ queryKey: ['adminPlanStats'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/plans/${id}`),
    onSuccess: () => {
      toast.success("Plan deleted successfully");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      queryClient.invalidateQueries({ queryKey: ['adminPlanStats'] });
      setDeleteConfirmPlan(null);
    },
    onError: (e: any) => toast.error("Cannot delete plan", { description: e.response?.data?.message || "Plan may have active subscriptions" })
  });

  const duplicateMutation = useMutation({
    mutationFn: (plan: any) => api.post('/admin/plans', {
      name: `${plan.name} (Copy)`,
      monthly_amount: plan.monthly_amount,
      duration_months: plan.duration_months,
      description: plan.description,
      is_active: false,
      is_featured: false,
      features: plan.features,
      min_investment: plan.min_investment,
      max_investment: plan.max_investment,
    }),
    onSuccess: () => {
      toast.success("Plan duplicated successfully");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
    },
    onError: (e: any) => toast.error("Error duplicating plan", { description: e.response?.data?.message })
  });

  // Bonus Configuration Mutation
  const bonusConfigMutation = useMutation({
    mutationFn: ({ planId, configs }: { planId: number; configs: any }) =>
      api.put(`/admin/plans/${planId}`, { configs }),
    onSuccess: () => {
      toast.success("Bonus configuration saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setBonusConfigOpen(false);
      setBonusConfigPlan(null);
    },
    onError: (e: any) => toast.error("Failed to save bonus configuration", { description: e.response?.data?.message })
  });

  const resetForm = () => {
    setName(''); setMonthlyAmount(''); setDuration('36'); setDescription('');
    setIsActive(true); setIsFeatured(false); setEditingPlan(null);
    setAvailableFrom(''); setAvailableUntil('');
    setFeatures([]); setNewFeature('');
    setMinInvestment(''); setMaxInvestment('');
  };

  const addFeature = () => {
    if (newFeature.trim()) {
      setFeatures([...features, newFeature.trim()]);
      setNewFeature('');
    }
  };

  const removeFeature = (index: number) => {
    setFeatures(features.filter((_, i) => i !== index));
  };

  const handleEdit = (plan: any) => {
    setEditingPlan(plan);
    setName(plan.name);
    setMonthlyAmount(plan.monthly_amount);
    setDuration(plan.duration_months);
    setDescription(plan.description);
    setIsActive(plan.is_active);
    setIsFeatured(plan.is_featured);
    setAvailableFrom(formatDateForInput(plan.available_from));
    setAvailableUntil(formatDateForInput(plan.available_until));
    setFeatures(Array.isArray(plan.features) ? plan.features : []);
    setMinInvestment(plan.min_investment || '');
    setMaxInvestment(plan.max_investment || '');
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
        name,
        monthly_amount: parseFloat(monthlyAmount),
        duration_months: parseInt(duration),
        description,
        is_active: isActive,
        is_featured: isFeatured,
        available_from: availableFrom || null,
        available_until: availableUntil || null,
        features,
        min_investment: minInvestment ? parseFloat(minInvestment) : null,
        max_investment: maxInvestment ? parseFloat(maxInvestment) : null,
    };
    mutation.mutate(payload);
  };

  const handleBonusConfig = (plan: any) => {
    setBonusConfigPlan(plan);
    setBonusConfigOpen(true);
  };

  const handleSaveBonusConfig = (configs: any) => {
    if (!bonusConfigPlan) return;
    bonusConfigMutation.mutate({ planId: bonusConfigPlan.id, configs });
  };

  // Filter plans based on active tab
  const filteredPlans = plans?.filter((plan: any) => {
    if (activeTab === 'all') return true;
    if (activeTab === 'active') return plan.is_active;
    if (activeTab === 'inactive') return !plan.is_active;
    if (activeTab === 'featured') return plan.is_featured;
    return true;
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Plan Management</h1>
          <p className="text-muted-foreground">Create and manage all investment plans.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> Create Plan</Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingPlan ? 'Edit Plan' : 'Create New Plan'}</DialogTitle>
              <DialogDescription>
                {editingPlan ? 'Update plan details and settings.' : 'Create a new investment plan for your users.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Basic Info */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Basic Information</h4>
                <div className="space-y-2">
                  <Label>Plan Name</Label>
                  <Input value={name} onChange={(e) => setName(e.target.value)} required placeholder="e.g., Premium SIP Plan" />
                </div>
                <div className="grid grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label>Monthly Amount (₹)</Label>
                    <Input type="number" value={monthlyAmount} onChange={(e) => setMonthlyAmount(e.target.value)} required />
                  </div>
                  <div className="space-y-2">
                    <Label>Duration (Months)</Label>
                    <Input type="number" value={duration} onChange={(e) => setDuration(e.target.value)} required />
                  </div>
                  <div className="space-y-2">
                    <Label>Total Investment</Label>
                    <Input value={monthlyAmount && duration ? `₹${(parseFloat(monthlyAmount) * parseInt(duration)).toLocaleString()}` : '₹0'} disabled className="bg-muted" />
                  </div>
                </div>
              </div>

              {/* Investment Limits */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Investment Limits (Optional)</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Minimum Investment (₹)</Label>
                    <Input type="number" value={minInvestment} onChange={(e) => setMinInvestment(e.target.value)} placeholder="No minimum" />
                  </div>
                  <div className="space-y-2">
                    <Label>Maximum Investment (₹)</Label>
                    <Input type="number" value={maxInvestment} onChange={(e) => setMaxInvestment(e.target.value)} placeholder="No maximum" />
                  </div>
                </div>
              </div>

              {/* Availability Schedule */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Availability Schedule</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Available From</Label>
                    <Input type="date" value={availableFrom} onChange={(e) => setAvailableFrom(e.target.value)} />
                  </div>
                  <div className="space-y-2">
                    <Label>Available Until</Label>
                    <Input type="date" value={availableUntil} onChange={(e) => setAvailableUntil(e.target.value)} />
                  </div>
                </div>
                <p className="text-xs text-muted-foreground">Leave empty for always available.</p>
              </div>

              {/* Features */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Plan Features</h4>
                <div className="flex gap-2">
                  <Input
                    value={newFeature}
                    onChange={(e) => setNewFeature(e.target.value)}
                    placeholder="Add a feature (e.g., 'Priority Support')"
                    onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addFeature())}
                  />
                  <Button type="button" variant="outline" onClick={addFeature}>
                    <Plus className="h-4 w-4" />
                  </Button>
                </div>
                <div className="flex flex-wrap gap-2">
                  {features.map((feature, index) => (
                    <Badge key={`feature-${index}`} variant="secondary" className="px-3 py-1">
                      {feature}
                      <button type="button" onClick={() => removeFeature(index)} className="ml-2 hover:text-destructive">×</button>
                    </Badge>
                  ))}
                  {features.length === 0 && <p className="text-xs text-muted-foreground">No features added yet.</p>}
                </div>
              </div>

              {/* Description */}
              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea value={description} onChange={(e) => setDescription(e.target.value)} rows={3} placeholder="Describe what makes this plan special..." />
              </div>

              {/* Toggles */}
              <div className="flex items-center gap-6 p-4 bg-muted/50 rounded-lg">
                <div className="flex items-center space-x-2">
                  <Switch id="is_active" checked={isActive} onCheckedChange={setIsActive} />
                  <Label htmlFor="is_active">Active</Label>
                </div>
                <div className="flex items-center space-x-2">
                  <Switch id="is_featured" checked={isFeatured} onCheckedChange={setIsFeatured} />
                  <Label htmlFor="is_featured" className="flex items-center gap-1">
                    <Star className="h-3 w-3" /> Featured
                  </Label>
                </div>
              </div>

              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingPlan ? "Save Changes" : "Create Plan")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics Cards */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Plans</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{plans?.length || 0}</div>
            <p className="text-xs text-muted-foreground">{plans?.filter((p: any) => p.is_active).length || 0} active</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Subscribers</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{planStats?.total_subscribers?.toLocaleString() || '0'}</div>
            <p className="text-xs text-muted-foreground">Across all plans</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Monthly Revenue</CardTitle>
            <IndianRupee className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{planStats?.monthly_revenue?.toLocaleString() || '0'}</div>
            <p className="text-xs text-muted-foreground">From active subscriptions</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Avg. Plan Value</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{planStats?.avg_plan_value?.toLocaleString() || '0'}</div>
            <p className="text-xs text-muted-foreground">Per subscription</p>
          </CardContent>
        </Card>
      </div>

      {/* Plans Table with Tabs */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Investment Plans</CardTitle>
            <Tabs value={activeTab} onValueChange={setActiveTab}>
              <TabsList>
                <TabsTrigger value="all">All ({plans?.length || 0})</TabsTrigger>
                <TabsTrigger value="active">Active</TabsTrigger>
                <TabsTrigger value="inactive">Inactive</TabsTrigger>
                <TabsTrigger value="featured">Featured</TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Plan</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Duration</TableHead>
                  <TableHead>Subscribers</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Availability</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredPlans?.map((plan: any) => (
                  <TableRow key={plan.id}>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        {plan.is_featured && <Star className="h-4 w-4 text-yellow-500 fill-yellow-500" />}
                        <div>
                          <div className="font-medium">{plan.name}</div>
                          {plan.description && <div className="text-xs text-muted-foreground truncate max-w-[200px]">{plan.description}</div>}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="font-semibold">₹{plan.monthly_amount?.toLocaleString()}/mo</div>
                      <div className="text-xs text-muted-foreground">Total: ₹{(plan.monthly_amount * plan.duration_months).toLocaleString()}</div>
                    </TableCell>
                    <TableCell>{plan.duration_months} months</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1">
                        <Users className="h-3 w-3 text-muted-foreground" />
                        <span>{plan.subscribers_count || 0}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={plan.is_active ? 'default' : 'secondary'}>
                        {plan.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground">
                      {plan.available_from ? (
                        <div>
                          <div>{formatDateForInput(plan.available_from)}</div>
                          <div>to {formatDateForInput(plan.available_until) || '∞'}</div>
                        </div>
                      ) : 'Always'}
                    </TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem onClick={() => handleEdit(plan)}>
                            <Edit className="h-4 w-4 mr-2" /> Edit Plan
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleBonusConfig(plan)}>
                            <Gift className="h-4 w-4 mr-2" /> Configure Bonuses
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => duplicateMutation.mutate(plan)}>
                            <Copy className="h-4 w-4 mr-2" /> Duplicate
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => window.open(`/plans/${plan.id}`, '_blank')}>
                            <Eye className="h-4 w-4 mr-2" /> Preview
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            onClick={() => setDeleteConfirmPlan(plan)}
                            className="text-destructive focus:text-destructive"
                            disabled={plan.subscribers_count > 0}
                          >
                            <Trash2 className="h-4 w-4 mr-2" /> Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
                {filteredPlans?.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                      No plans found in this category.
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmPlan} onOpenChange={() => setDeleteConfirmPlan(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Plan</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirmPlan?.name}"? This action cannot be undone.
              {deleteConfirmPlan?.subscribers_count > 0 && (
                <span className="block mt-2 text-destructive font-medium">
                  Warning: This plan has {deleteConfirmPlan.subscribers_count} active subscribers.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmPlan.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Plan"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Bonus Configuration Dialog */}
      {bonusConfigPlan && (
        <BonusConfigDialog
          open={bonusConfigOpen}
          onOpenChange={setBonusConfigOpen}
          planName={bonusConfigPlan.name}
          monthlyAmount={bonusConfigPlan.monthly_amount}
          durationMonths={bonusConfigPlan.duration_months}
          configs={bonusConfigPlan.configs || {}}
          onSave={handleSaveBonusConfig}
          isSaving={bonusConfigMutation.isPending}
        />
      )}
    </div>
  );
}