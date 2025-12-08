'use client';

import { useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Gift, TrendingUp, Trophy, Zap, BarChart3, Save } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ProgressiveBonusForm } from './ProgressiveBonusForm';
import { MilestoneBonusForm } from './MilestoneBonusForm';
import { ConsistencyBonusForm } from './ConsistencyBonusForm';
import { BonusPreview } from './BonusPreview';
import type { ProgressiveConfig, MilestoneConfig, ConsistencyConfig, WelcomeBonusConfig } from '@/lib/bonusCalculations';

interface BonusConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  monthlyAmount: number;
  durationMonths: number;
  configs: {
    welcome_bonus_config?: WelcomeBonusConfig;
    progressive_config?: ProgressiveConfig;
    milestone_config?: MilestoneConfig[];
    consistency_config?: ConsistencyConfig;
  };
  onSave: (configs: any) => void;
  isSaving?: boolean;
}

export function BonusConfigDialog({
  open,
  onOpenChange,
  planName,
  monthlyAmount,
  durationMonths,
  configs: initialConfigs,
  onSave,
  isSaving = false,
}: BonusConfigDialogProps) {
  const [activeTab, setActiveTab] = useState('welcome');

  // State for each bonus type
  const [welcomeConfig, setWelcomeConfig] = useState<WelcomeBonusConfig>(
    initialConfigs.welcome_bonus_config || { amount: 500 }
  );

  const [progressiveConfig, setProgressiveConfig] = useState<Partial<ProgressiveConfig>>(
    initialConfigs.progressive_config || {
      rate: 0.5,
      start_month: 4,
      max_percentage: 20,
      overrides: {},
    }
  );

  const [milestoneConfig, setMilestoneConfig] = useState<MilestoneConfig[]>(
    initialConfigs.milestone_config || []
  );

  const [consistencyConfig, setConsistencyConfig] = useState<Partial<ConsistencyConfig>>(
    initialConfigs.consistency_config || {
      amount_per_payment: 50,
      streaks: [],
    }
  );

  const handleSave = () => {
    const configsToSave = {
      welcome_bonus_config: welcomeConfig,
      progressive_config: progressiveConfig,
      milestone_config: milestoneConfig,
      consistency_config: consistencyConfig,
    };

    onSave(configsToSave);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-6xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-2xl flex items-center gap-2">
            <Gift className="h-6 w-6" />
            Configure Bonuses: {planName}
          </DialogTitle>
          <DialogDescription>
            Set up welcome bonuses, progressive growth, milestones, and consistency rewards for this plan.
            <div className="flex gap-2 mt-2">
              <span className="text-xs px-2 py-1 bg-muted rounded">
                Monthly: ₹{monthlyAmount?.toLocaleString()}
              </span>
              <span className="text-xs px-2 py-1 bg-muted rounded">
                Duration: {durationMonths} months
              </span>
            </div>
          </DialogDescription>
        </DialogHeader>

        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-5">
            <TabsTrigger value="welcome">
              <Gift className="h-4 w-4 mr-2" />
              Welcome
            </TabsTrigger>
            <TabsTrigger value="progressive">
              <TrendingUp className="h-4 w-4 mr-2" />
              Progressive
            </TabsTrigger>
            <TabsTrigger value="milestone">
              <Trophy className="h-4 w-4 mr-2" />
              Milestone
            </TabsTrigger>
            <TabsTrigger value="consistency">
              <Zap className="h-4 w-4 mr-2" />
              Consistency
            </TabsTrigger>
            <TabsTrigger value="preview">
              <BarChart3 className="h-4 w-4 mr-2" />
              Preview
            </TabsTrigger>
          </TabsList>

          {/* Welcome Bonus Tab */}
          <TabsContent value="welcome" className="space-y-4 mt-6">
            <div className="space-y-4">
              <h4 className="font-semibold flex items-center gap-2">
                <Gift className="h-4 w-4" />
                Welcome Bonus Configuration
              </h4>
              <p className="text-sm text-muted-foreground">
                Award a one-time bonus to new subscribers on their first payment. This encourages initial sign-ups and creates a positive first impression.
              </p>

              <div className="space-y-4 p-6 bg-muted/50 rounded-lg">
                <div className="space-y-2">
                  <Label>Welcome Bonus Amount (₹)</Label>
                  <Input
                    type="number"
                    min="0"
                    step="100"
                    value={welcomeConfig.amount}
                    onChange={(e) => setWelcomeConfig({ amount: parseFloat(e.target.value) || 0 })}
                    placeholder="500"
                    className="max-w-xs"
                  />
                  <p className="text-xs text-muted-foreground">
                    One-time bonus awarded on the first successful payment
                  </p>
                </div>

                {welcomeConfig.amount > 0 && (
                  <div className="p-4 bg-primary/10 border border-primary rounded-lg">
                    <p className="text-sm font-medium mb-1">Preview:</p>
                    <p className="text-xs text-muted-foreground mb-2">
                      New subscriber makes first payment of ₹{monthlyAmount?.toLocaleString()}
                    </p>
                    <div className="flex items-center gap-2">
                      <span className="text-lg">💰</span>
                      <div>
                        <p className="font-semibold">Welcome Bonus: ₹{welcomeConfig.amount.toLocaleString()}</p>
                        <p className="text-xs text-muted-foreground">
                          Credited immediately after first payment
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Best Practices */}
                <div className="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-md">
                  <p className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">💡 Welcome Bonus Tips:</p>
                  <ul className="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Typical range: ₹250 - ₹1000</li>
                    <li>• Make it meaningful but not excessive</li>
                    <li>• Consider 5-10% of monthly investment</li>
                    <li>• Round to psychological prices (₹499, ₹999)</li>
                  </ul>
                </div>
              </div>
            </div>
          </TabsContent>

          {/* Progressive Bonus Tab */}
          <TabsContent value="progressive" className="space-y-4 mt-6">
            <ProgressiveBonusForm
              value={progressiveConfig}
              onChange={setProgressiveConfig}
              paymentAmount={monthlyAmount}
              durationMonths={durationMonths}
            />
          </TabsContent>

          {/* Milestone Bonus Tab */}
          <TabsContent value="milestone" className="space-y-4 mt-6">
            <MilestoneBonusForm value={milestoneConfig} onChange={setMilestoneConfig} />
          </TabsContent>

          {/* Consistency Bonus Tab */}
          <TabsContent value="consistency" className="space-y-4 mt-6">
            <ConsistencyBonusForm value={consistencyConfig} onChange={setConsistencyConfig} />
          </TabsContent>

          {/* Preview Tab */}
          <TabsContent value="preview" className="space-y-4 mt-6">
            <BonusPreview
              paymentAmount={monthlyAmount}
              durationMonths={durationMonths}
              configs={{
                welcome: welcomeConfig,
                progressive: progressiveConfig as ProgressiveConfig,
                milestones: milestoneConfig,
                consistency: consistencyConfig as ConsistencyConfig,
              }}
              multiplier={1.0}
            />
          </TabsContent>
        </Tabs>

        {/* Footer Actions */}
        <div className="flex items-center justify-between pt-4 border-t">
          <div className="text-sm text-muted-foreground">
            Changes will apply to new subscriptions and future payments
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button onClick={handleSave} disabled={isSaving}>
              <Save className="h-4 w-4 mr-2" />
              {isSaving ? 'Saving...' : 'Save Configuration'}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
