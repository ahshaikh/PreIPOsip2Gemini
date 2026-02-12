// V-REMEDIATE-1730-131 (Created - Revised) | V-FINAL-1730-484 (Scheduling UI) | V-ENHANCED-PLANS | V-BONUS-CONFIG-1208
// V-ARCH-2026: Typed with canonical Plan types
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { formatCurrencyINR } from "@/lib/utils";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, PlusCircle, Edit, Trash2, Copy, Users, IndianRupee, TrendingUp, Star, Calendar, Eye, MoreHorizontal, Gift, ShieldCheck, Sparkles, PartyPopper, Tag } from "lucide-react";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { BonusConfigDialog } from "@/components/admin/BonusConfigDialog";
import { EligibilityConfigDialog } from "@/components/admin/EligibilityConfigDialog";
import { AdvancedFeaturesDialog } from "@/components/admin/AdvancedFeaturesDialog";
import { ProfitSharingConfigDialog } from "@/components/admin/ProfitSharingConfigDialog";
import { CelebrationBonusConfigDialog } from "@/components/admin/CelebrationBonusConfigDialog";
import { AutoDebitConfigDialog } from "@/components/admin/AutoDebitConfigDialog";
import { DiscountConfigDialog } from "@/components/admin/DiscountConfigDialog";
import { PlanAnalyticsDashboard } from "@/components/admin/PlanAnalyticsDashboard";
import type { AdminPlan, BillingCycle, CreatePlanPayload, UpdatePlanPayload, PlanFeature, GenericPlanConfig } from "@/types/plan";

// Helper to format date for input
const formatDateForInput = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
};

export default function PlanManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPlan, setEditingPlan] = useState<AdminPlan | null>(null);
  const [deleteConfirmPlan, setDeleteConfirmPlan] = useState<AdminPlan | null>(null);
  const [activeTab, setActiveTab] = useState('all');

  // Bonus Configuration State
  const [bonusConfigOpen, setBonusConfigOpen] = useState(false);
  const [bonusConfigPlan, setBonusConfigPlan] = useState<AdminPlan | null>(null);

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
  const [displayOrder, setDisplayOrder] = useState('0');

  // Date States
  const [availableFrom, setAvailableFrom] = useState('');
  const [availableUntil, setAvailableUntil] = useState('');

  // Pause/Cancel Rules & Eligibility State
  const [allowPause, setAllowPause] = useState(true);
  const [maxPauseCount, setMaxPauseCount] = useState('3');
  const [maxPauseDuration, setMaxPauseDuration] = useState('3');
  const [maxSubscriptionsPerUser, setMaxSubscriptionsPerUser] = useState('1');

  // Eligibility Configuration State
  const [eligibilityConfigOpen, setEligibilityConfigOpen] = useState(false);
  const [eligibilityConfigPlan, setEligibilityConfigPlan] = useState<AdminPlan | null>(null);

  // Advanced Features Configuration State
  const [advancedFeaturesOpen, setAdvancedFeaturesOpen] = useState(false);
  const [advancedFeaturesPlan, setAdvancedFeaturesPlan] = useState<AdminPlan | null>(null);

  // Profit Sharing Configuration State
  const [profitSharingConfigOpen, setProfitSharingConfigOpen] = useState(false);
  const [profitSharingConfigPlan, setProfitSharingConfigPlan] = useState<AdminPlan | null>(null);

  // Celebration Bonus Configuration State
  const [celebrationBonusConfigOpen, setCelebrationBonusConfigOpen] = useState(false);
  const [celebrationBonusConfigPlan, setCelebrationBonusConfigPlan] = useState<AdminPlan | null>(null);

  // Auto-Debit Configuration State
  const [autoDebitConfigOpen, setAutoDebitConfigOpen] = useState(false);
  const [autoDebitConfigPlan, setAutoDebitConfigPlan] = useState<AdminPlan | null>(null);

  // Discount Configuration State
  const [discountConfigOpen, setDiscountConfigOpen] = useState(false);
  const [discountConfigPlan, setDiscountConfigPlan] = useState<AdminPlan | null>(null);

  // Features 4, 5, 14 Form State
  const [billingCycle, setBillingCycle] = useState<'weekly' | 'bi-weekly' | 'monthly' | 'quarterly' | 'yearly'>('monthly');
  const [trialPeriodDays, setTrialPeriodDays] = useState('0');
  const [metadata, setMetadata] = useState<Array<{key: string; value: string}>>([]);

  // Bulk Actions State
  const [selectedPlans, setSelectedPlans] = useState<number[]>([]);

  const { data: plans, isLoading } = useQuery<AdminPlan[]>({
    queryKey: ['adminPlans'],
    queryFn: async () => (await api.get('/admin/plans')).data,
  });

  // Plan statistics query
  const { data: planStats } = useQuery({
    queryKey: ['adminPlanStats'],
    queryFn: async () => (await api.get('/admin/plans/stats')).data,
  });

  const mutation = useMutation({
    mutationFn: (newPlan: CreatePlanPayload | UpdatePlanPayload) => {
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
    onError: (e: Error & { response?: { data?: { message?: string } } }) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/plans/${id}`),
    onSuccess: () => {
      toast.success("Plan deleted successfully");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      queryClient.invalidateQueries({ queryKey: ['adminPlanStats'] });
      setDeleteConfirmPlan(null);
    },
    onError: (e: Error & { response?: { data?: { message?: string } } }) => toast.error("Cannot delete plan", { description: e.response?.data?.message || "Plan may have active subscriptions" })
  });

  const duplicateMutation = useMutation({
    mutationFn: (plan: AdminPlan) => api.post('/admin/plans', {
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
    onError: (e: Error & { response?: { data?: { message?: string } } }) => toast.error("Error duplicating plan", { description: e.response?.data?.message })
  });

  // Type for API errors
  type ApiError = Error & { response?: { data?: { message?: string } } };

  // Bonus Configuration Mutation
  const bonusConfigMutation = useMutation({
    mutationFn: ({ planId, configs }: { planId: number; configs: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs }),
    onSuccess: () => {
      toast.success("Bonus configuration saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setBonusConfigOpen(false);
      setBonusConfigPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save bonus configuration", { description: e.response?.data?.message })
  });

  // Eligibility Configuration Mutation
  const eligibilityConfigMutation = useMutation({
    mutationFn: ({ planId, eligibilityConfig }: { planId: number; eligibilityConfig: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs: { eligibility_config: eligibilityConfig } }),
    onSuccess: () => {
      toast.success("Eligibility rules saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setEligibilityConfigOpen(false);
      setEligibilityConfigPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save eligibility rules", { description: e.response?.data?.message })
  });

  // Advanced Features Configuration Mutation
  const advancedFeaturesMutation = useMutation({
    mutationFn: ({ planId, advancedConfig }: { planId: number; advancedConfig: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs: advancedConfig }),
    onSuccess: () => {
      toast.success("Advanced features saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setAdvancedFeaturesOpen(false);
      setAdvancedFeaturesPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save advanced features", { description: e.response?.data?.message })
  });

  // Profit Sharing Configuration Mutation
  const profitSharingConfigMutation = useMutation({
    mutationFn: ({ planId, profitSharingConfig }: { planId: number; profitSharingConfig: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs: { profit_sharing_config: profitSharingConfig } }),
    onSuccess: () => {
      toast.success("Profit sharing configuration saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setProfitSharingConfigOpen(false);
      setProfitSharingConfigPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save profit sharing configuration", { description: e.response?.data?.message })
  });

  // Celebration Bonus Configuration Mutation
  const celebrationBonusConfigMutation = useMutation({
    mutationFn: ({ planId, celebrationBonusConfig }: { planId: number; celebrationBonusConfig: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs: { celebration_bonus_config: celebrationBonusConfig } }),
    onSuccess: () => {
      toast.success("Celebration bonus configuration saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setCelebrationBonusConfigOpen(false);
      setCelebrationBonusConfigPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save celebration bonus configuration", { description: e.response?.data?.message })
  });

  // Auto-Debit Configuration Mutation
  const autoDebitConfigMutation = useMutation({
    mutationFn: ({ planId, autoDebitConfig }: { planId: number; autoDebitConfig: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs: { auto_debit_config: autoDebitConfig } }),
    onSuccess: () => {
      toast.success("Auto-debit configuration saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setAutoDebitConfigOpen(false);
      setAutoDebitConfigPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save auto-debit configuration", { description: e.response?.data?.message })
  });

  // Discount Configuration Mutation
  const discountConfigMutation = useMutation({
    mutationFn: ({ planId, discountConfig }: { planId: number; discountConfig: Record<string, unknown> }) =>
      api.put(`/admin/plans/${planId}`, { configs: { discount_config: discountConfig } }),
    onSuccess: () => {
      toast.success("Discount configuration saved successfully!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setDiscountConfigOpen(false);
      setDiscountConfigPlan(null);
    },
    onError: (e: ApiError) => toast.error("Failed to save discount configuration", { description: e.response?.data?.message })
  });

  // Bulk Actions Mutations
  const bulkActivateMutation = useMutation({
    mutationFn: async (planIds: number[]) => {
      await Promise.all(planIds.map(id => api.put(`/admin/plans/${id}`, { is_active: true })));
    },
    onSuccess: () => {
      toast.success(`${selectedPlans.length} plan(s) activated successfully!`);
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setSelectedPlans([]);
    },
    onError: (e: ApiError) => toast.error("Failed to activate plans", { description: e.response?.data?.message })
  });

  const bulkDeactivateMutation = useMutation({
    mutationFn: async (planIds: number[]) => {
      await Promise.all(planIds.map(id => api.put(`/admin/plans/${id}`, { is_active: false })));
    },
    onSuccess: () => {
      toast.success(`${selectedPlans.length} plan(s) deactivated successfully!`);
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
      setSelectedPlans([]);
    },
    onError: (e: ApiError) => toast.error("Failed to deactivate plans", { description: e.response?.data?.message })
  });

  const resetForm = () => {
    setName(''); setMonthlyAmount(''); setDuration('36'); setDescription('');
    setIsActive(true); setIsFeatured(false); setEditingPlan(null);
    setAvailableFrom(''); setAvailableUntil('');
    setFeatures([]); setNewFeature('');
    setMinInvestment(''); setMaxInvestment('');
    setDisplayOrder('0');
    setAllowPause(true); setMaxPauseCount('3'); setMaxPauseDuration('3'); setMaxSubscriptionsPerUser('1');
    setBillingCycle('monthly'); setTrialPeriodDays('0'); setMetadata([]);
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

  const handleEdit = (plan: AdminPlan) => {
    setEditingPlan(plan);
    setName(plan.name);
    setMonthlyAmount(String(plan.monthly_amount));
    setDuration(String(plan.duration_months));
    setDescription(plan.description || '');
    setIsActive(plan.is_active);
    setIsFeatured(plan.is_featured);
    setAvailableFrom(formatDateForInput(plan.available_from));
    setAvailableUntil(formatDateForInput(plan.available_until));
    // Convert feature objects to strings
    setFeatures(Array.isArray(plan.features) ? plan.features.map((f: PlanFeature) => f.feature_text) : []);
    setMinInvestment(plan.min_investment?.toString() || '');
    setMaxInvestment(plan.max_investment?.toString() || '');
    setDisplayOrder(plan.display_order?.toString() || '0');
    setAllowPause(plan.allow_pause ?? true);
    setMaxPauseCount(plan.max_pause_count?.toString() || '3');
    setMaxPauseDuration(plan.max_pause_duration_months?.toString() || '3');
    setMaxSubscriptionsPerUser(plan.max_subscriptions_per_user?.toString() || '1');
    setBillingCycle(plan.billing_cycle || 'monthly');
    setTrialPeriodDays(plan.trial_period_days?.toString() || '0');
    setMetadata(plan.metadata ? Object.entries(plan.metadata).map(([key, value]) => ({ key, value: String(value) })) : []);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Convert metadata array to object
    const metadataObject = metadata.reduce((acc, { key, value }) => {
      if (key.trim()) acc[key.trim()] = value;
      return acc;
    }, {} as Record<string, string>);

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
        display_order: parseInt(displayOrder),
        allow_pause: allowPause,
        max_pause_count: parseInt(maxPauseCount),
        max_pause_duration_months: parseInt(maxPauseDuration),
        max_subscriptions_per_user: parseInt(maxSubscriptionsPerUser),
        billing_cycle: billingCycle,
        trial_period_days: parseInt(trialPeriodDays),
        metadata: Object.keys(metadataObject).length > 0 ? metadataObject : null,
    };
    mutation.mutate(payload);
  };

  // Helper to extract config from configs array
  const getConfigValue = (configs: GenericPlanConfig[], key: string): unknown => {
    return configs.find(c => c.config_key === key)?.value || {};
  };

  // Helper to convert configs array to object
  const configsArrayToObject = (configs: GenericPlanConfig[]): Record<string, unknown> => {
    return configs.reduce<Record<string, unknown>>((acc, config) => {
      acc[config.config_key] = config.value;
      return acc;
    }, {});
  };

  const handleBonusConfig = (plan: AdminPlan) => {
    // Transform configs array to object format for the dialog
    const configsObject = configsArrayToObject(plan.configs);
    setBonusConfigPlan({ ...plan, configs: configsObject } as AdminPlan);
    setBonusConfigOpen(true);
  };

  const handleSaveBonusConfig = (configs: Record<string, unknown>) => {
    if (!bonusConfigPlan) return;
    bonusConfigMutation.mutate({ planId: bonusConfigPlan.id, configs });
  };

  const handleEligibilityConfig = (plan: AdminPlan) => {
    const eligibilityConfig = getConfigValue(plan.configs, 'eligibility_config');
    setEligibilityConfigPlan({ ...plan, eligibilityConfig } as AdminPlan);
    setEligibilityConfigOpen(true);
  };

  const handleSaveEligibilityConfig = (eligibilityConfig: Record<string, unknown>) => {
    if (!eligibilityConfigPlan) return;
    eligibilityConfigMutation.mutate({ planId: eligibilityConfigPlan.id, eligibilityConfig });
  };

  const handleAdvancedFeatures = (plan: AdminPlan) => {
    const configsObject = configsArrayToObject(plan.configs);
    const advancedConfig = {
      lucky_draw_config: configsObject['lucky_draw_config'] || {},
      referral_config: configsObject['referral_config'] || {},
      plan_change_config: configsObject['plan_change_config'] || {}
    };
    setAdvancedFeaturesPlan({ ...plan, advancedConfig } as AdminPlan);
    setAdvancedFeaturesOpen(true);
  };

  const handleSaveAdvancedFeatures = (advancedConfig: Record<string, unknown>) => {
    if (!advancedFeaturesPlan) return;
    advancedFeaturesMutation.mutate({ planId: advancedFeaturesPlan.id, advancedConfig });
  };

  const handleProfitSharingConfig = (plan: AdminPlan) => {
    const profitSharingConfig = getConfigValue(plan.configs, 'profit_sharing_config');
    setProfitSharingConfigPlan({ ...plan, profitSharingConfig } as AdminPlan);
    setProfitSharingConfigOpen(true);
  };

  const handleSaveProfitSharingConfig = (profitSharingConfig: Record<string, unknown>) => {
    if (!profitSharingConfigPlan) return;
    profitSharingConfigMutation.mutate({ planId: profitSharingConfigPlan.id, profitSharingConfig });
  };

  const handleCelebrationBonusConfig = (plan: AdminPlan) => {
    const celebrationBonusConfig = getConfigValue(plan.configs, 'celebration_bonus_config');
    setCelebrationBonusConfigPlan({ ...plan, celebrationBonusConfig } as AdminPlan);
    setCelebrationBonusConfigOpen(true);
  };

  const handleSaveCelebrationBonusConfig = (celebrationBonusConfig: Record<string, unknown>) => {
    if (!celebrationBonusConfigPlan) return;
    celebrationBonusConfigMutation.mutate({ planId: celebrationBonusConfigPlan.id, celebrationBonusConfig });
  };

  const handleAutoDebitConfig = (plan: AdminPlan) => {
    const autoDebitConfig = getConfigValue(plan.configs, 'auto_debit_config');

    setAutoDebitConfigPlan({ ...plan, autoDebitConfig });
    setAutoDebitConfigOpen(true);
  };

  const handleSaveAutoDebitConfig = (autoDebitConfig: Record<string, unknown>) => {
    if (!autoDebitConfigPlan) return;
    autoDebitConfigMutation.mutate({ planId: autoDebitConfigPlan.id, autoDebitConfig });
  };

  const handleDiscountConfig = (plan: AdminPlan) => {
    const discountConfig = getConfigValue(plan.configs, 'discount_config');
    setDiscountConfigPlan({ ...plan, discountConfig } as AdminPlan);
    setDiscountConfigOpen(true);
  };

  const handleSaveDiscountConfig = (discountConfig: Record<string, unknown>) => {
    if (!discountConfigPlan) return;
    discountConfigMutation.mutate({ planId: discountConfigPlan.id, discountConfig });
  };

  // Bulk Actions Handlers
  const handleSelectPlan = (planId: number) => {
    setSelectedPlans(prev =>
      prev.includes(planId) ? prev.filter(id => id !== planId) : [...prev, planId]
    );
  };

  const handleSelectAll = () => {
    if (selectedPlans.length === filteredPlans?.length) {
      setSelectedPlans([]);
    } else {
      setSelectedPlans(filteredPlans?.map((p: AdminPlan) => p.id) || []);
    }
  };

  const handleBulkActivate = () => {
    if (selectedPlans.length === 0) return;
    bulkActivateMutation.mutate(selectedPlans);
  };

  const handleBulkDeactivate = () => {
    if (selectedPlans.length === 0) return;
    bulkDeactivateMutation.mutate(selectedPlans);
  };

  // Filter plans based on active tab
  const filteredPlans = plans?.filter((plan: AdminPlan) => {
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
                    <Input value={monthlyAmount && duration ? formatCurrencyINR(parseFloat(monthlyAmount) * parseInt(duration)) : '₹0'} disabled className="bg-muted" />
                  </div>
                </div>
              </div>

              {/* Investment Limits & Display */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Investment Limits & Display</h4>
                <div className="grid grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label>Minimum Investment (₹)</Label>
                    <Input type="number" value={minInvestment} onChange={(e) => setMinInvestment(e.target.value)} placeholder="No minimum" />
                  </div>
                  <div className="space-y-2">
                    <Label>Maximum Investment (₹)</Label>
                    <Input type="number" value={maxInvestment} onChange={(e) => setMaxInvestment(e.target.value)} placeholder="No maximum" />
                  </div>
                  <div className="space-y-2">
                    <Label>Display Order</Label>
                    <Input type="number" value={displayOrder} onChange={(e) => setDisplayOrder(e.target.value)} placeholder="0" />
                    <p className="text-xs text-muted-foreground">Lower numbers appear first</p>
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

              {/* Pause & Cancel Rules */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Pause & Cancel Rules</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Max Subscriptions Per User</Label>
                    <Input
                      type="number"
                      min="1"
                      value={maxSubscriptionsPerUser}
                      onChange={(e) => setMaxSubscriptionsPerUser(e.target.value)}
                      placeholder="1"
                    />
                    <p className="text-xs text-muted-foreground">How many times can a user subscribe to this plan?</p>
                  </div>
                  <div className="flex items-center space-x-2 pt-6">
                    <Switch id="allow_pause" checked={allowPause} onCheckedChange={setAllowPause} />
                    <Label htmlFor="allow_pause">Allow Pause</Label>
                  </div>
                </div>
                {allowPause && (
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Max Pause Count</Label>
                      <Input
                        type="number"
                        min="0"
                        value={maxPauseCount}
                        onChange={(e) => setMaxPauseCount(e.target.value)}
                        placeholder="3"
                      />
                      <p className="text-xs text-muted-foreground">How many times can user pause?</p>
                    </div>
                    <div className="space-y-2">
                      <Label>Max Pause Duration (Months)</Label>
                      <Input
                        type="number"
                        min="1"
                        value={maxPauseDuration}
                        onChange={(e) => setMaxPauseDuration(e.target.value)}
                        placeholder="3"
                      />
                      <p className="text-xs text-muted-foreground">Maximum months per pause</p>
                    </div>
                  </div>
                )}
              </div>

              {/* Billing & Trial (Features 4 & 5) */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Billing & Trial</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Billing Cycle</Label>
                    <Select value={billingCycle} onValueChange={(value: BillingCycle) => setBillingCycle(value)}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="weekly">Weekly</SelectItem>
                        <SelectItem value="bi-weekly">Bi-Weekly</SelectItem>
                        <SelectItem value="monthly">Monthly</SelectItem>
                        <SelectItem value="quarterly">Quarterly</SelectItem>
                        <SelectItem value="yearly">Yearly</SelectItem>
                      </SelectContent>
                    </Select>
                    <p className="text-xs text-muted-foreground">How often subscribers are billed</p>
                  </div>
                  <div className="space-y-2">
                    <Label>Trial Period (Days)</Label>
                    <Input
                      type="number"
                      min="0"
                      value={trialPeriodDays}
                      onChange={(e) => setTrialPeriodDays(e.target.value)}
                      placeholder="0"
                    />
                    <p className="text-xs text-muted-foreground">Days of free trial before first charge (0 = no trial)</p>
                  </div>
                </div>
              </div>

              {/* Custom Metadata (Feature 14) */}
              <div className="space-y-4">
                <h4 className="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Custom Metadata</h4>
                <div className="space-y-2">
                  {metadata.map((meta, index) => (
                    <div key={index} className="flex gap-2">
                      <Input
                        placeholder="Key"
                        value={meta.key}
                        onChange={(e) => {
                          const newMetadata = [...metadata];
                          newMetadata[index].key = e.target.value;
                          setMetadata(newMetadata);
                        }}
                        className="flex-1"
                      />
                      <Input
                        placeholder="Value"
                        value={meta.value}
                        onChange={(e) => {
                          const newMetadata = [...metadata];
                          newMetadata[index].value = e.target.value;
                          setMetadata(newMetadata);
                        }}
                        className="flex-1"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setMetadata(metadata.filter((_, i) => i !== index))}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setMetadata([...metadata, { key: '', value: '' }])}
                  >
                    <Plus className="h-4 w-4 mr-1" /> Add Metadata
                  </Button>
                  <p className="text-xs text-muted-foreground">Add custom key-value pairs for this plan</p>
                </div>
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
            <p className="text-xs text-muted-foreground">{plans?.filter((p: AdminPlan) => p.is_active).length || 0} active</p>
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
                <TabsTrigger value="analytics">📊 Analytics</TabsTrigger>
              </TabsList>
            </Tabs>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {activeTab === 'analytics' ? (
            <PlanAnalyticsDashboard plans={plans || []} />
          ) : (
            <>
              {/* Bulk Actions */}
              {selectedPlans.length > 0 && (
                <div className="flex items-center gap-2 p-3 bg-muted rounded-lg">
                  <span className="text-sm font-medium">{selectedPlans.length} plan(s) selected</span>
                  <Button size="sm" variant="outline" onClick={handleBulkActivate} disabled={bulkActivateMutation.isPending}>
                    Activate Selected
                  </Button>
                  <Button size="sm" variant="outline" onClick={handleBulkDeactivate} disabled={bulkDeactivateMutation.isPending}>
                    Deactivate Selected
                  </Button>
                  <Button size="sm" variant="ghost" onClick={() => setSelectedPlans([])}>
                    Clear Selection
                  </Button>
                </div>
              )}

              {isLoading ? <p>Loading...</p> : (
                <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[50px]">
                    <Checkbox
                      checked={selectedPlans.length === filteredPlans?.length && filteredPlans?.length > 0}
                      onCheckedChange={handleSelectAll}
                    />
                  </TableHead>
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
                {filteredPlans?.map((plan: AdminPlan) => (
                  <TableRow key={plan.id}>
                    <TableCell>
                      <Checkbox
                        checked={selectedPlans.includes(plan.id)}
                        onCheckedChange={() => handleSelectPlan(plan.id)}
                      />
                    </TableCell>
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
                      <div className="font-semibold">{formatCurrencyINR(plan.monthly_amount || 0)}/mo</div>
                      <div className="text-xs text-muted-foreground">Total: {formatCurrencyINR((plan.monthly_amount || 0) * (plan.duration_months || 0))}</div>
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
                          <DropdownMenuItem onClick={() => handleEligibilityConfig(plan)}>
                            <ShieldCheck className="h-4 w-4 mr-2" /> Configure Eligibility
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleAdvancedFeatures(plan)}>
                            <Sparkles className="h-4 w-4 mr-2" /> Advanced Features
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleProfitSharingConfig(plan)}>
                            <TrendingUp className="h-4 w-4 mr-2" /> Profit Sharing
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleCelebrationBonusConfig(plan)}>
                            <PartyPopper className="h-4 w-4 mr-2" /> Celebration Bonuses
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleAutoDebitConfig(plan)}>
                            <Calendar className="h-4 w-4 mr-2" /> Auto-Debit Scheduling
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleDiscountConfig(plan)}>
                            <Tag className="h-4 w-4 mr-2" /> Discounts & Offers
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
            </>
          )}
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmPlan} onOpenChange={() => setDeleteConfirmPlan(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Plan</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete &ldquo;{deleteConfirmPlan?.name}&rdquo;? This action cannot be undone.
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

      {/* Eligibility Configuration Dialog */}
      {eligibilityConfigPlan && (
        <EligibilityConfigDialog
          open={eligibilityConfigOpen}
          onOpenChange={setEligibilityConfigOpen}
          planName={eligibilityConfigPlan.name}
          eligibilityConfig={eligibilityConfigPlan.eligibilityConfig || {}}
          onSave={handleSaveEligibilityConfig}
          isSaving={eligibilityConfigMutation.isPending}
        />
      )}

      {/* Advanced Features Configuration Dialog */}
      {advancedFeaturesPlan && (
        <AdvancedFeaturesDialog
          open={advancedFeaturesOpen}
          onOpenChange={setAdvancedFeaturesOpen}
          planName={advancedFeaturesPlan.name}
          advancedConfig={advancedFeaturesPlan.advancedConfig || {}}
          onSave={handleSaveAdvancedFeatures}
          isSaving={advancedFeaturesMutation.isPending}
        />
      )}

      {/* Profit Sharing Configuration Dialog */}
      {profitSharingConfigPlan && (
        <ProfitSharingConfigDialog
          open={profitSharingConfigOpen}
          onOpenChange={setProfitSharingConfigOpen}
          planName={profitSharingConfigPlan.name}
          profitSharingConfig={profitSharingConfigPlan.profitSharingConfig || {}}
          onSave={handleSaveProfitSharingConfig}
          isSaving={profitSharingConfigMutation.isPending}
        />
      )}

      {/* Celebration Bonus Configuration Dialog */}
      {celebrationBonusConfigPlan && (
        <CelebrationBonusConfigDialog
          open={celebrationBonusConfigOpen}
          onOpenChange={setCelebrationBonusConfigOpen}
          planName={celebrationBonusConfigPlan.name}
          celebrationBonusConfig={celebrationBonusConfigPlan.celebrationBonusConfig || {}}
          onSave={handleSaveCelebrationBonusConfig}
          isSaving={celebrationBonusConfigMutation.isPending}
        />
      )}

      {/* Auto-Debit Configuration Dialog */}
      {autoDebitConfigPlan && (
        <AutoDebitConfigDialog
          open={autoDebitConfigOpen}
          onOpenChange={setAutoDebitConfigOpen}
          planName={autoDebitConfigPlan.name}
          autoDebitConfig={autoDebitConfigPlan.autoDebitConfig || {}}
          onSave={handleSaveAutoDebitConfig}
          isSaving={autoDebitConfigMutation.isPending}
        />
      )}

      {/* Discount Configuration Dialog */}
      {discountConfigPlan && (
        <DiscountConfigDialog
          open={discountConfigOpen}
          onOpenChange={setDiscountConfigOpen}
          planName={discountConfigPlan.name}
          discountConfig={discountConfigPlan.discountConfig || {}}
          monthlyAmount={discountConfigPlan.monthly_amount || 0}
          onSave={handleSaveDiscountConfig}
          isSaving={discountConfigMutation.isPending}
        />
      )}
    </div>
  );
}