// V-REMEDIATE-1730-168 (Created) | V-FINAL-1730-476 (36-Month Override Table) | V-FIX-1730-552 (Array Safety)
// V-ARCH-2026: Typed with canonical Plan types
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { Plus, Trash2, Save } from "lucide-react";
import type {
  AdminPlan,
  GenericPlanConfig,
  ProgressiveConfig,
  MilestoneEntryEditable,
  ReferralTierSimple,
  CelebrationBonusConfig,
  LuckyDrawConfig,
} from "@/types/plan";

// API error type for mutations
type ApiError = Error & { response?: { data?: { message?: string } } };

/**
 * Bonus configs map - local state structure for editing
 * Keys correspond to config_key in plan_configs table
 */
interface BonusConfigsMap {
  progressive_config?: ProgressiveConfig;
  milestone_config?: MilestoneEntryEditable[];
  referral_tiers?: ReferralTierSimple[];
  celebration_bonus_config?: CelebrationBonusConfig;
  lucky_draw_entries?: LuckyDrawConfig;
  [key: string]: unknown; // Allow other keys from backend
}

/**
 * NEW: Progressive Bonus Table Component
 * This renders the 36-month override table as per FSD-PLAN-006
 */
interface ProgressiveBonusTableProps {
  config: ProgressiveConfig | undefined;
  onChange: (key: string, value: ProgressiveConfig) => void;
}

function ProgressiveBonusTable({ config, onChange }: ProgressiveBonusTableProps) {
  const [months, setMonths] = useState<number[]>([]);
  const [baseRate, setBaseRate] = useState(0.5);
  const [startMonth, setStartMonth] = useState(4);
  const [maxPercent, setMaxPercent] = useState(20);

  // De-serialize the config object into a 36-month array
  useEffect(() => {
    const overrides = config?.overrides || {};
    const rate = config?.rate || 0.5;
    const start = config?.start_month || 4;
    const max = config?.max_percentage || 20;

    setBaseRate(rate);
    setStartMonth(start);
    setMaxPercent(max);

    const newMonths = Array.from({ length: 36 }, (_, i) => {
      const month = i + 1;
      // If an override is set for this month, use it.
      // Otherwise, calculate the linear rate.
      if (overrides[month]) {
        return overrides[month];
      }
      if (month < start) {
        return 0;
      }
      const val = (month - start + 1) * rate;
      return Math.min(val, max);
    });
    setMonths(newMonths);
  }, [config]);

  const handleMonthChange = (index: number, value: string) => {
    const newMonths = [...months];
    newMonths[index] = parseFloat(value) || 0;
    setMonths(newMonths);

    // Serialize back into the 'overrides' object
    const newOverrides: { [key: number]: number } = {};
    newMonths.forEach((rate, i) => {
      newOverrides[i + 1] = rate;
    });

    onChange('progressive_config', {
      rate: config?.rate ?? 0.5,
      start_month: config?.start_month ?? 4,
      max_percentage: config?.max_percentage ?? 20,
      overrides: newOverrides
    });
  };

  return (
    <div className="space-y-4">
      <CardDescription>
        Set a specific bonus percentage for each month. This overrides the linear formula.
      </CardDescription>
      <div className="grid grid-cols-3 gap-4">
        <div className="space-y-2">
            <Label>Base Rate (%) (Used for linear calc)</Label>
            <Input
                type="number"
                step="0.1"
                value={baseRate}
                onChange={(e) => {
                    setBaseRate(parseFloat(e.target.value));
                    onChange('progressive_config', {
                      rate: parseFloat(e.target.value),
                      start_month: config?.start_month ?? 4,
                      max_percentage: config?.max_percentage ?? 20,
                      overrides: config?.overrides
                    });
                }}
            />
        </div>
        <div className="space-y-2">
            <Label>Start Month (For linear calc)</Label>
            <Input
                type="number"
                value={startMonth}
                onChange={(e) => {
                    setStartMonth(parseInt(e.target.value));
                    onChange('progressive_config', {
                      rate: config?.rate ?? 0.5,
                      start_month: parseInt(e.target.value),
                      max_percentage: config?.max_percentage ?? 20,
                      overrides: config?.overrides
                    });
                }}
            />
        </div>
        <div className="space-y-2">
            <Label>Max % Cap (For linear calc)</Label>
            <Input
                type="number"
                value={maxPercent}
                onChange={(e) => {
                    setMaxPercent(parseInt(e.target.value));
                    onChange('progressive_config', {
                      rate: config?.rate ?? 0.5,
                      start_month: config?.start_month ?? 4,
                      max_percentage: parseInt(e.target.value),
                      overrides: config?.overrides
                    });
                }}
            />
        </div>
      </div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Month</TableHead>
            <TableHead>Override Rate (%)</TableHead>
            <TableHead>Month</TableHead>
            <TableHead>Override Rate (%)</TableHead>
            <TableHead>Month</TableHead>
            <TableHead>Override Rate (%)</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {Array.from({ length: 12 }).map((_, i) => (
            <TableRow key={i}>
              <TableCell className="font-medium">{i + 1}</TableCell>
              <TableCell>
                <Input 
                  type="number" 
                  step="0.1" 
                  value={months[i] || 0} 
                  onChange={(e) => handleMonthChange(i, e.target.value)}
                  className="h-8"
                />
              </TableCell>
              <TableCell className="font-medium">{i + 13}</TableCell>
              <TableCell>
                <Input 
                  type="number" 
                  step="0.1" 
                  value={months[i + 12] || 0} 
                  onChange={(e) => handleMonthChange(i + 12, e.target.value)}
                  className="h-8"
                />
              </TableCell>
              <TableCell className="font-medium">{i + 25}</TableCell>
              <TableCell>
                <Input 
                  type="number" 
                  step="0.1" 
                  value={months[i + 24] || 0} 
                  onChange={(e) => handleMonthChange(i + 24, e.target.value)}
                  className="h-8"
                />
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
// --- END NEW COMPONENT ---


export default function BonusSettingsPage() {
  const queryClient = useQueryClient();
  const [selectedPlanId, setSelectedPlanId] = useState<string>("");

  // Local state for the configs of the selected plan
  const [configs, setConfigs] = useState<BonusConfigsMap>({});

  const { data: plans, isLoading } = useQuery<AdminPlan[]>({
    queryKey: ['adminPlans'],
    queryFn: async () => (await api.get('/admin/plans')).data,
  });

  // Helper to safely extract array from potentially wrapped config value
  const getSafeArray = <T,>(data: T[] | { value?: T[] } | undefined): T[] => {
    if (Array.isArray(data)) return data;
    if (data && typeof data === 'object' && 'value' in data && Array.isArray(data.value)) return data.value;
    return [];
  };

  // When plans load, select the first one automatically
  useEffect(() => {
    if (plans && plans.length > 0 && !selectedPlanId) {
      setSelectedPlanId(plans[0].id.toString());
    }
  }, [plans, selectedPlanId]);

  // When selection changes, load configs into state
  useEffect(() => {
    if (selectedPlanId && plans) {
      const plan = plans.find((p: AdminPlan) => p.id.toString() === selectedPlanId);
      if (plan) {
        // Transform the array of config objects into a key-value map
        const configMap: BonusConfigsMap = {};
        plan.configs.forEach((c: GenericPlanConfig) => {
          // Normalize array configs on load to prevent rendering errors
          if (c.config_key === 'milestone_config') {
            configMap[c.config_key] = getSafeArray(c.value as MilestoneEntryEditable[]);
          } else if (c.config_key === 'referral_tiers') {
            configMap[c.config_key] = getSafeArray(c.value as ReferralTierSimple[]);
          } else {
            configMap[c.config_key] = c.value;
          }
        });
        setConfigs(configMap);
      }
    }
  }, [selectedPlanId, plans]);

  const mutation = useMutation({
    mutationFn: (data: BonusConfigsMap) => api.put(`/admin/plans/${selectedPlanId}`, { configs: data }),
    onSuccess: () => {
      toast.success("Bonus Configuration Saved!");
      queryClient.invalidateQueries({ queryKey: ['adminPlans'] });
    },
    onError: (error: ApiError) => {
      toast.error("Save Failed", { description: error.response?.data?.message });
    }
  });

  const handleSave = () => {
    mutation.mutate(configs);
  };

  // Generic handler for simple fields (celebration_bonus_config, lucky_draw_entries)
  const updateConfig = (key: keyof BonusConfigsMap, field: string, value: number) => {
    setConfigs((prev: BonusConfigsMap) => ({
      ...prev,
      [key]: {
        ...(prev[key] as Record<string, unknown> || {}),
        [field]: value
      }
    }));
  };

  // Handler for array-based configs (Milestones, Tiers)
  const updateArrayConfig = (key: 'milestone_config' | 'referral_tiers', index: number, field: string, value: number) => {
    // V-FIX: Safely extract array before spreading to avoid "object is not iterable"
    const currentArray = key === 'milestone_config'
      ? getSafeArray<MilestoneEntryEditable>(configs.milestone_config)
      : getSafeArray<ReferralTierSimple>(configs.referral_tiers);
    const newArray = [...currentArray];
    newArray[index] = { ...newArray[index], [field]: value };

    setConfigs((prev: BonusConfigsMap) => ({
      ...prev,
      [key]: newArray
    }));
  };

  const addArrayItem = (key: 'milestone_config' | 'referral_tiers', template: MilestoneEntryEditable | ReferralTierSimple) => {
    // V-FIX: Use safe extraction
    const current = key === 'milestone_config'
      ? getSafeArray<MilestoneEntryEditable>(configs.milestone_config)
      : getSafeArray<ReferralTierSimple>(configs.referral_tiers);
    setConfigs((prev: BonusConfigsMap) => ({
      ...prev,
      [key]: [...current, { ...template, _uid: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}` }]
    }));
  };

  const removeArrayItem = (key: 'milestone_config' | 'referral_tiers', index: number) => {
    // V-FIX: Use safe extraction
    const current = key === 'milestone_config'
      ? getSafeArray<MilestoneEntryEditable>(configs.milestone_config)
      : getSafeArray<ReferralTierSimple>(configs.referral_tiers);
    setConfigs((prev: BonusConfigsMap) => ({
      ...prev,
      [key]: current.filter((_, i: number) => i !== index)
    }));
  };

  // Handler for the Progressive Config
  const handleProgressiveChange = (_key: string, value: ProgressiveConfig) => {
    setConfigs((prev: BonusConfigsMap) => ({
        ...prev,
        progressive_config: value
    }));
  };

  if (isLoading) return <div>Loading plans...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Bonus Configuration</h1>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          <Save className="mr-2 h-4 w-4" />
          {mutation.isPending ? "Saving..." : "Save Changes"}
        </Button>
      </div>

      {/* Plan Selector */}
      <div className="flex items-center gap-4 p-4 bg-muted rounded-lg">
        <Label>Select Plan to Configure:</Label>
        <Select value={selectedPlanId} onValueChange={setSelectedPlanId}>
          <SelectTrigger className="w-[300px]">
            <SelectValue placeholder="Select a plan" />
          </SelectTrigger>
          <SelectContent>
            {plans?.map((plan: AdminPlan) => (
              <SelectItem key={plan.id} value={plan.id.toString()}>
                {plan.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Configuration Tabs */}
      <Tabs defaultValue="progressive" className="w-full">
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="progressive">Progressive</TabsTrigger>
          <TabsTrigger value="milestone">Milestone</TabsTrigger>
          <TabsTrigger value="referral">Referral</TabsTrigger>
          <TabsTrigger value="celebration">Celebration</TabsTrigger>
          <TabsTrigger value="lucky_draw">Lucky Draw</TabsTrigger>
        </TabsList>

        {/* 1. Progressive Bonus (REPLACED) */}
        <TabsContent value="progressive">
          <Card>
            <CardHeader>
              <CardTitle>Progressive Monthly Bonus</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <ProgressiveBonusTable 
                config={configs.progressive_config} 
                onChange={handleProgressiveChange} 
              />
            </CardContent>
          </Card>
        </TabsContent>

        {/* 2. Milestone Bonus */}
        <TabsContent value="milestone">
          <Card>
            <CardHeader>
              <CardTitle>Milestone Bonuses</CardTitle>
              <CardDescription>One-time bonuses awarded at specific months.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* V-FIX: Use getSafeArray to prevent .map crashes on wrapped objects */}
              {getSafeArray<MilestoneEntryEditable>(configs.milestone_config).map((item: MilestoneEntryEditable, index: number) => (
                <div key={item.id || item._uid || `milestone-${index}`} className="flex gap-4 items-end border-b pb-4">
                  <div className="flex-1 space-y-2">
                    <Label>Month</Label>
                    <Input 
                      type="number" 
                      value={item.month} 
                      onChange={(e) => updateArrayConfig('milestone_config', index, 'month', parseInt(e.target.value))}
                    />
                  </div>
                  <div className="flex-1 space-y-2">
                    <Label>Bonus Amount (₹)</Label>
                    <Input 
                      type="number" 
                      value={item.amount} 
                      onChange={(e) => updateArrayConfig('milestone_config', index, 'amount', parseFloat(e.target.value))}
                    />
                  </div>
                  <Button variant="destructive" size="icon" onClick={() => removeArrayItem('milestone_config', index)}>
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              ))}
              <Button variant="outline" onClick={() => addArrayItem('milestone_config', { month: 0, amount: 0 })}>
                <Plus className="mr-2 h-4 w-4" /> Add Milestone
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* 3. Referral Tiers */}
        <TabsContent value="referral">
          <Card>
            <CardHeader>
              <CardTitle>Referral Multipliers</CardTitle>
              <CardDescription>Multipliers applied to bonuses based on active referrals.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* V-FIX: Use getSafeArray to prevent .map crashes */}
              {getSafeArray<ReferralTierSimple>(configs.referral_tiers).map((item: ReferralTierSimple, index: number) => (
                <div key={item.id || item._uid || `referral-${index}`} className="flex gap-4 items-end border-b pb-4">
                  <div className="flex-1 space-y-2">
                    <Label>Min Referrals</Label>
                    <Input 
                      type="number" 
                      value={item.count} 
                      onChange={(e) => updateArrayConfig('referral_tiers', index, 'count', parseInt(e.target.value))}
                    />
                  </div>
                  <div className="flex-1 space-y-2">
                    <Label>Multiplier (x)</Label>
                    <Input 
                      type="number" 
                      step="0.1"
                      value={item.multiplier} 
                      onChange={(e) => updateArrayConfig('referral_tiers', index, 'multiplier', parseFloat(e.target.value))}
                    />
                  </div>
                  <Button variant="destructive" size="icon" onClick={() => removeArrayItem('referral_tiers', index)}>
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              ))}
              <Button variant="outline" onClick={() => addArrayItem('referral_tiers', { count: 0, multiplier: 1.0 })}>
                <Plus className="mr-2 h-4 w-4" /> Add Tier
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* 4. Celebration Bonus */}
        <TabsContent value="celebration">
          <Card>
            <CardHeader>
              <CardTitle>Celebration Bonuses</CardTitle>
              <CardDescription>Automatic bonuses for personal events.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Birthday Bonus (₹)</Label>
                  <Input 
                    type="number" 
                    value={configs.celebration_bonus_config?.birthday_amount || 0} 
                    onChange={(e) => updateConfig('celebration_bonus_config', 'birthday_amount', parseFloat(e.target.value))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Anniversary Bonus (₹)</Label>
                  <Input 
                    type="number" 
                    value={configs.celebration_bonus_config?.anniversary_amount || 0} 
                    onChange={(e) => updateConfig('celebration_bonus_config', 'anniversary_amount', parseFloat(e.target.value))}
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* 5. Lucky Draw */}
        <TabsContent value="lucky_draw">
          <Card>
            <CardHeader>
              <CardTitle>Lucky Draw Entries</CardTitle>
              <CardDescription>Number of entries granted per on-time payment.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Entries per Payment</Label>
                <Input 
                  type="number" 
                  value={configs.lucky_draw_entries?.count || 1} 
                  onChange={(e) => updateConfig('lucky_draw_entries', 'count', parseInt(e.target.value))}
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

      </Tabs>
    </div>
  );
}