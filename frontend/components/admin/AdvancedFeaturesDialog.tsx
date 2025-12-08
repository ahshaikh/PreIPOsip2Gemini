// V-PHASE3-ADVANCED-1208 (Created)
'use client';

import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Sparkles, Users, TrendingUp, Plus, Trash2, Info } from 'lucide-react';

interface LuckyDrawConfig {
  entries_per_payment?: number;
}

interface ReferralTier {
  min_referrals: number;
  multiplier: number;
  name: string;
}

interface ReferralConfig {
  tiers?: ReferralTier[];
}

interface PlanChangeConfig {
  allow_upgrade?: boolean;
  allow_downgrade?: boolean;
  min_months_before_change?: number;
  upgrade_fee?: number;
  downgrade_fee?: number;
  forfeit_bonuses_on_downgrade?: boolean;
}

interface AdvancedConfig {
  lucky_draw_config?: LuckyDrawConfig;
  referral_config?: ReferralConfig;
  plan_change_config?: PlanChangeConfig;
}

interface AdvancedFeaturesDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  advancedConfig: AdvancedConfig;
  onSave: (config: AdvancedConfig) => void;
  isSaving: boolean;
}

export function AdvancedFeaturesDialog({
  open,
  onOpenChange,
  planName,
  advancedConfig,
  onSave,
  isSaving
}: AdvancedFeaturesDialogProps) {
  // Lucky Draw State
  const [entriesPerPayment, setEntriesPerPayment] = useState('1');

  // Referral Tiers State
  const [referralTiers, setReferralTiers] = useState<ReferralTier[]>([
    { min_referrals: 1, multiplier: 1.0, name: 'Bronze' },
    { min_referrals: 5, multiplier: 1.5, name: 'Silver' },
    { min_referrals: 10, multiplier: 2.0, name: 'Gold' }
  ]);
  const [newTierMin, setNewTierMin] = useState('');
  const [newTierMultiplier, setNewTierMultiplier] = useState('');
  const [newTierName, setNewTierName] = useState('');

  // Plan Change Rules State
  const [allowUpgrade, setAllowUpgrade] = useState(true);
  const [allowDowngrade, setAllowDowngrade] = useState(true);
  const [minMonthsBeforeChange, setMinMonthsBeforeChange] = useState('6');
  const [upgradeFee, setUpgradeFee] = useState('0');
  const [downgradeFee, setDowngradeFee] = useState('0');
  const [forfeitBonusesOnDowngrade, setForfeitBonusesOnDowngrade] = useState(false);

  // Load existing config
  useEffect(() => {
    if (open) {
      // Lucky Draw
      setEntriesPerPayment(advancedConfig.lucky_draw_config?.entries_per_payment?.toString() || '1');

      // Referral Tiers
      if (advancedConfig.referral_config?.tiers && advancedConfig.referral_config.tiers.length > 0) {
        setReferralTiers(advancedConfig.referral_config.tiers);
      }

      // Plan Change Rules
      const changeConfig = advancedConfig.plan_change_config || {};
      setAllowUpgrade(changeConfig.allow_upgrade ?? true);
      setAllowDowngrade(changeConfig.allow_downgrade ?? true);
      setMinMonthsBeforeChange(changeConfig.min_months_before_change?.toString() || '6');
      setUpgradeFee(changeConfig.upgrade_fee?.toString() || '0');
      setDowngradeFee(changeConfig.downgrade_fee?.toString() || '0');
      setForfeitBonusesOnDowngrade(changeConfig.forfeit_bonuses_on_downgrade ?? false);
    }
  }, [open, advancedConfig]);

  const handleSave = () => {
    const config: AdvancedConfig = {
      lucky_draw_config: {
        entries_per_payment: parseInt(entriesPerPayment)
      },
      referral_config: {
        tiers: referralTiers
      },
      plan_change_config: {
        allow_upgrade: allowUpgrade,
        allow_downgrade: allowDowngrade,
        min_months_before_change: parseInt(minMonthsBeforeChange),
        upgrade_fee: parseFloat(upgradeFee),
        downgrade_fee: parseFloat(downgradeFee),
        forfeit_bonuses_on_downgrade: forfeitBonusesOnDowngrade
      }
    };
    onSave(config);
  };

  const addReferralTier = () => {
    if (!newTierMin || !newTierMultiplier || !newTierName) return;

    const newTier: ReferralTier = {
      min_referrals: parseInt(newTierMin),
      multiplier: parseFloat(newTierMultiplier),
      name: newTierName.trim()
    };

    setReferralTiers([...referralTiers, newTier].sort((a, b) => a.min_referrals - b.min_referrals));
    setNewTierMin('');
    setNewTierMultiplier('');
    setNewTierName('');
  };

  const removeReferralTier = (index: number) => {
    setReferralTiers(referralTiers.filter((_, i) => i !== index));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Sparkles className="h-5 w-5" />
            Advanced Features - {planName}
          </DialogTitle>
          <DialogDescription>
            Configure lucky draw entries, referral tiers, and plan change rules for this plan.
          </DialogDescription>
        </DialogHeader>

        <Tabs defaultValue="lucky_draw" className="w-full">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="lucky_draw">Lucky Draw</TabsTrigger>
            <TabsTrigger value="referral">Referral Tiers</TabsTrigger>
            <TabsTrigger value="plan_changes">Plan Changes</TabsTrigger>
          </TabsList>

          {/* Lucky Draw Tab */}
          <TabsContent value="lucky_draw" className="space-y-6">
            <div className="space-y-4">
              <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div className="text-sm text-muted-foreground">
                  <p className="font-medium mb-1">How Lucky Draw Works:</p>
                  <p>Each successful payment gives users entries into monthly lucky draws. More entries = higher chance of winning!</p>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Base Entries Per Payment</Label>
                <Input
                  type="number"
                  min="0"
                  step="1"
                  value={entriesPerPayment}
                  onChange={(e) => setEntriesPerPayment(e.target.value)}
                  placeholder="1"
                />
                <p className="text-xs text-muted-foreground">
                  Number of lucky draw entries awarded for each successful payment on this plan
                </p>
              </div>

              <Alert>
                <AlertDescription>
                  <strong>Bonus Entries:</strong> Users automatically receive bonus entries for:
                  <ul className="list-disc list-inside mt-2 space-y-1">
                    <li>On-time payments: +1 entry</li>
                    <li>Every 6-month streak: +5 entries</li>
                  </ul>
                </AlertDescription>
              </Alert>

              <div className="p-4 border rounded-lg bg-card">
                <h4 className="font-semibold mb-2">Example Calculation:</h4>
                <div className="text-sm space-y-1">
                  <p>• Base entries from plan: {entriesPerPayment}</p>
                  <p>• On-time payment bonus: +1</p>
                  <p>• After 12 months (two 6-month streaks): +10</p>
                  <p className="font-semibold mt-2">
                    Total: {parseInt(entriesPerPayment) + 11} entries
                  </p>
                </div>
              </div>
            </div>
          </TabsContent>

          {/* Referral Tiers Tab */}
          <TabsContent value="referral" className="space-y-6">
            <div className="space-y-4">
              <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div className="text-sm text-muted-foreground">
                  <p className="font-medium mb-1">How Referral Tiers Work:</p>
                  <p>Referrers earn multiplied bonuses based on their total successful referral count. Higher tiers = bigger rewards!</p>
                </div>
              </div>

              <div className="space-y-3">
                <Label>Add Referral Tier</Label>
                <div className="grid grid-cols-4 gap-2">
                  <Input
                    type="number"
                    min="1"
                    value={newTierMin}
                    onChange={(e) => setNewTierMin(e.target.value)}
                    placeholder="Min referrals"
                  />
                  <Input
                    type="number"
                    min="1"
                    step="0.1"
                    value={newTierMultiplier}
                    onChange={(e) => setNewTierMultiplier(e.target.value)}
                    placeholder="Multiplier"
                  />
                  <Input
                    value={newTierName}
                    onChange={(e) => setNewTierName(e.target.value)}
                    placeholder="Tier name"
                  />
                  <Button onClick={addReferralTier} className="w-full">
                    <Plus className="h-4 w-4 mr-1" /> Add
                  </Button>
                </div>
              </div>

              <div className="border rounded-lg overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Tier Name</TableHead>
                      <TableHead>Min Referrals</TableHead>
                      <TableHead>Multiplier</TableHead>
                      <TableHead className="w-[100px]">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {referralTiers.map((tier, index) => (
                      <TableRow key={index}>
                        <TableCell className="font-medium">{tier.name}</TableCell>
                        <TableCell>{tier.min_referrals}+</TableCell>
                        <TableCell>{tier.multiplier}x</TableCell>
                        <TableCell>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => removeReferralTier(index)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))}
                    {referralTiers.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={4} className="text-center text-muted-foreground">
                          No tiers configured. Add at least one tier.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>

              <Alert>
                <AlertDescription>
                  <strong>Example:</strong> If base referral bonus is ₹1,000 and a user has 10 successful referrals (Gold tier, 2.0x multiplier), they will earn ₹2,000 per new referral.
                </AlertDescription>
              </Alert>
            </div>
          </TabsContent>

          {/* Plan Changes Tab */}
          <TabsContent value="plan_changes" className="space-y-6">
            <div className="space-y-4">
              <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div className="text-sm text-muted-foreground">
                  <p className="font-medium mb-1">Plan Change Rules:</p>
                  <p>Control when and how users can switch between plans. Set fees, lock-in periods, and penalty rules.</p>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-semibold">Change Permissions</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                      <Label>Allow Upgrades</Label>
                      <p className="text-xs text-muted-foreground">Users can switch to higher-value plans</p>
                    </div>
                    <Switch checked={allowUpgrade} onCheckedChange={setAllowUpgrade} />
                  </div>
                  <div className="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                      <Label>Allow Downgrades</Label>
                      <p className="text-xs text-muted-foreground">Users can switch to lower-value plans</p>
                    </div>
                    <Switch checked={allowDowngrade} onCheckedChange={setAllowDowngrade} />
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-semibold">Lock-in Period</h4>
                <div className="space-y-2">
                  <Label>Minimum Months Before Change</Label>
                  <Input
                    type="number"
                    min="0"
                    value={minMonthsBeforeChange}
                    onChange={(e) => setMinMonthsBeforeChange(e.target.value)}
                    placeholder="6"
                  />
                  <p className="text-xs text-muted-foreground">
                    Users must stay on this plan for at least this many months before changing
                  </p>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-semibold">Change Fees</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Upgrade Fee (₹)</Label>
                    <Input
                      type="number"
                      min="0"
                      step="100"
                      value={upgradeFee}
                      onChange={(e) => setUpgradeFee(e.target.value)}
                      placeholder="0"
                    />
                    <p className="text-xs text-muted-foreground">One-time fee for upgrading</p>
                  </div>
                  <div className="space-y-2">
                    <Label>Downgrade Fee (₹)</Label>
                    <Input
                      type="number"
                      min="0"
                      step="100"
                      value={downgradeFee}
                      onChange={(e) => setDowngradeFee(e.target.value)}
                      placeholder="0"
                    />
                    <p className="text-xs text-muted-foreground">One-time fee for downgrading</p>
                  </div>
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="font-semibold">Downgrade Penalties</h4>
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <Label>Forfeit Bonuses on Downgrade</Label>
                    <p className="text-xs text-muted-foreground">Remove all accrued bonuses when user downgrades</p>
                  </div>
                  <Switch
                    checked={forfeitBonusesOnDowngrade}
                    onCheckedChange={setForfeitBonusesOnDowngrade}
                    disabled={!allowDowngrade}
                  />
                </div>
              </div>

              <Alert>
                <AlertDescription>
                  <strong>Note:</strong> These rules apply when users try to change from this plan to another. Separate rules may exist on the destination plan.
                </AlertDescription>
              </Alert>
            </div>
          </TabsContent>
        </Tabs>

        <div className="flex gap-2 pt-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="flex-1" disabled={isSaving}>
            Cancel
          </Button>
          <Button onClick={handleSave} className="flex-1" disabled={isSaving}>
            {isSaving ? 'Saving...' : 'Save Advanced Features'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
