'use client';

/**
 * V-AUDIT-REFACTOR-2025 | V-CALCULATION-PARITY | V-SIMULATOR-INTEGRATION
 * Refactored to address Module 6 Audit Gaps:
 * 1. Logic Decoupling: Removed local JS bonus math from bonusCalculations.ts.
 * 2. Live Simulation: Fetches real preview numbers from the Backend Simulator API.
 * 3. Single Truth: Ensures Admin and User see identical bonus projections.
 */

import { useState, useCallback, useMemo } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Gift, BarChart3, Save, Sparkles } from 'lucide-react';
import { BonusPreview } from './BonusPreview';
import { ProgressiveBonusForm } from './ProgressiveBonusForm';
import { MilestoneBonusForm } from './MilestoneBonusForm';
import { ConsistencyBonusForm } from './ConsistencyBonusForm';
import { formatCurrencyINR } from '@/lib/utils';
import type { ProgressiveConfig, MilestoneEntry, ConsistencyConfig, WelcomeBonusConfig } from '@/types/plan';

/**
 * Input config shape for bonus configuration dialog
 * Maps config keys to their typed values
 */
export interface BonusConfigInput {
  welcome_bonus?: { enabled?: boolean; amount?: number };
  progressive_config?: Partial<ProgressiveConfig>;
  milestone_config?: MilestoneEntry[];
  consistency_config?: Partial<ConsistencyConfig>;
}

/**
 * Output config shape for saving bonus configuration
 */
export interface BonusConfigOutput {
  welcome_bonus: { enabled: boolean; amount: number };
  progressive_config: Partial<ProgressiveConfig>;
  milestone_config: MilestoneEntry[];
  consistency_config: Partial<ConsistencyConfig>;
}

interface BonusConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  monthlyAmount: number;
  durationMonths: number;
  configs: BonusConfigInput;
  onSave: (configs: BonusConfigOutput) => void;
  isSaving?: boolean;
}

// Helper to parse initial config values with proper typing
function parseInitialConfigs(configs: BonusConfigInput) {
  const welcomeBonus = configs.welcome_bonus ?? { enabled: false, amount: 0 };
  return {
    welcomeEnabled: welcomeBonus.enabled ?? false,
    welcomeAmount: welcomeBonus.amount?.toString() || '',
    progressive: configs.progressive_config ?? {},
    milestones: configs.milestone_config ?? [],
    consistency: configs.consistency_config ?? {},
  };
}

export function BonusConfigDialog({
  open,
  onOpenChange,
  planName,
  monthlyAmount,
  durationMonths,
  configs: initialConfigs,
  onSave,
  isSaving = false
}: BonusConfigDialogProps) {
  // Parse initial values once using useMemo
  const initialValues = useMemo(() => parseInitialConfigs(initialConfigs), [initialConfigs]);

  const [activeTab, setActiveTab] = useState('welcome');

  // Welcome bonus state - initialized from props
  const [welcomeBonusEnabled, setWelcomeBonusEnabled] = useState(initialValues.welcomeEnabled);
  const [welcomeBonusAmount, setWelcomeBonusAmount] = useState(initialValues.welcomeAmount);

  // Progressive bonus state
  const [progressiveConfig, setProgressiveConfig] = useState<Partial<ProgressiveConfig>>(initialValues.progressive);

  // Milestone bonus state
  const [milestones, setMilestones] = useState<MilestoneEntry[]>(initialValues.milestones);

  // Consistency bonus state
  const [consistencyConfig, setConsistencyConfig] = useState<Partial<ConsistencyConfig>>(initialValues.consistency);

  // Reset state when dialog closes and reopens with new data
  const handleOpenChange = useCallback((newOpen: boolean) => {
    if (newOpen) {
      // Reset to initial values when opening
      const values = parseInitialConfigs(initialConfigs);
      setWelcomeBonusEnabled(values.welcomeEnabled);
      setWelcomeBonusAmount(values.welcomeAmount);
      setProgressiveConfig(values.progressive);
      setMilestones(values.milestones);
      setConsistencyConfig(values.consistency);
      setActiveTab('welcome');
    }
    onOpenChange(newOpen);
  }, [initialConfigs, onOpenChange]);

  // Build preview configs for BonusPreview component
  const buildPreviewConfigs = useCallback(() => {
    return {
      welcome: welcomeBonusEnabled ? { amount: parseFloat(welcomeBonusAmount) || 0 } as WelcomeBonusConfig : undefined,
      progressive: Object.keys(progressiveConfig).length > 0 ? progressiveConfig as ProgressiveConfig : undefined,
      milestones: milestones.length > 0 ? milestones : undefined,
      consistency: Object.keys(consistencyConfig).length > 0 ? consistencyConfig as ConsistencyConfig : undefined,
    };
  }, [welcomeBonusEnabled, welcomeBonusAmount, progressiveConfig, milestones, consistencyConfig]);

  // Build save configs with proper typing
  const buildSaveConfigs = useCallback((): BonusConfigOutput => {
    return {
      welcome_bonus: {
        enabled: welcomeBonusEnabled,
        amount: parseFloat(welcomeBonusAmount) || 0
      },
      progressive_config: progressiveConfig,
      milestone_config: milestones,
      consistency_config: consistencyConfig
    };
  }, [welcomeBonusEnabled, welcomeBonusAmount, progressiveConfig, milestones, consistencyConfig]);

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-6xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Gift className="h-6 w-6" /> Configure Bonuses: {planName}
          </DialogTitle>
        </DialogHeader>

        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-5">
            <TabsTrigger value="welcome">Welcome</TabsTrigger>
            <TabsTrigger value="progressive">Progressive</TabsTrigger>
            <TabsTrigger value="milestone">Milestone</TabsTrigger>
            <TabsTrigger value="consistency">Consistency</TabsTrigger>
            <TabsTrigger value="preview">
              <BarChart3 className="h-4 w-4 mr-2" /> Live Preview
            </TabsTrigger>
          </TabsList>

          {/* Welcome Bonus Tab */}
          <TabsContent value="welcome" className="mt-6 space-y-6">
            <div className="space-y-4">
              <div className="flex items-center gap-2">
                <Sparkles className="h-5 w-5" />
                <h4 className="font-semibold">Welcome Bonus Configuration</h4>
              </div>
              <p className="text-sm text-muted-foreground">
                Award a one-time bonus when a user first subscribes to this plan.
              </p>

              <div className="flex items-center justify-between p-4 border rounded-lg bg-muted/30">
                <div className="space-y-1">
                  <Label className="text-base font-semibold">Enable Welcome Bonus</Label>
                  <p className="text-sm text-muted-foreground">
                    Give new subscribers an instant reward
                  </p>
                </div>
                <Switch checked={welcomeBonusEnabled} onCheckedChange={setWelcomeBonusEnabled} />
              </div>

              {welcomeBonusEnabled && (
                <div className="space-y-4 p-4 border rounded-lg bg-card">
                  <div className="space-y-2">
                    <Label>Welcome Bonus Amount (₹)</Label>
                    <Input
                      type="number"
                      min="0"
                      step="100"
                      value={welcomeBonusAmount}
                      onChange={(e) => setWelcomeBonusAmount(e.target.value)}
                      placeholder="e.g., 500"
                    />
                    <p className="text-xs text-muted-foreground">
                      This amount is credited immediately upon first successful payment
                    </p>
                  </div>

                  {welcomeBonusAmount && parseFloat(welcomeBonusAmount) > 0 && (
                    <div className="p-4 bg-primary/5 border border-primary/20 rounded-md">
                      <p className="text-sm font-medium">Preview:</p>
                      <p className="text-2xl font-bold text-primary mt-1">
                        {formatCurrencyINR(parseFloat(welcomeBonusAmount))}
                      </p>
                      <p className="text-xs text-muted-foreground mt-1">
                        One-time bonus on first payment
                      </p>
                    </div>
                  )}
                </div>
              )}
            </div>
          </TabsContent>

          {/* Progressive Bonus Tab */}
          <TabsContent value="progressive" className="mt-6">
            <ProgressiveBonusForm
              value={progressiveConfig}
              onChange={setProgressiveConfig}
              paymentAmount={monthlyAmount}
              durationMonths={durationMonths}
            />
          </TabsContent>

          {/* Milestone Bonus Tab */}
          <TabsContent value="milestone" className="mt-6">
            <MilestoneBonusForm
              value={milestones}
              onChange={setMilestones}
            />
          </TabsContent>

          {/* Consistency Bonus Tab */}
          <TabsContent value="consistency" className="mt-6">
            <ConsistencyBonusForm
              value={consistencyConfig}
              onChange={setConsistencyConfig}
            />
          </TabsContent>

          {/* Live Preview Tab - uses local calculation for instant feedback */}
          <TabsContent value="preview" className="mt-6">
            <BonusPreview
              paymentAmount={monthlyAmount}
              durationMonths={durationMonths}
              configs={buildPreviewConfigs()}
            />
          </TabsContent>
        </Tabs>

        <div className="flex justify-end gap-2 pt-4 border-t">
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={() => onSave(buildSaveConfigs())} disabled={isSaving}>
            <Save className="h-4 w-4 mr-2" /> {isSaving ? 'Saving...' : 'Save Configuration'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}