// V-PHASE5-CELEBRATION-1208 (Created)
'use client';

import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PartyPopper, Plus, Trash2, Info, Target } from 'lucide-react';

interface Milestone {
  name: string;
  type: 'payment_count' | 'tenure_months' | 'total_invested' | 'referral_count' | 'streak_months' | 'zero_missed_payments';
  threshold: number;
  bonus_amount: number;
  bonus_type: 'fixed' | 'percentage';
  one_time: boolean;
}

interface CelebrationBonusConfig {
  enabled?: boolean;
  milestones?: Milestone[];
}

interface CelebrationBonusConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  celebrationBonusConfig: CelebrationBonusConfig;
  onSave: (config: CelebrationBonusConfig) => void;
  isSaving: boolean;
}

export function CelebrationBonusConfigDialog({
  open,
  onOpenChange,
  planName,
  celebrationBonusConfig,
  onSave,
  isSaving
}: CelebrationBonusConfigDialogProps) {
  const [enabled, setEnabled] = useState(false);
  const [milestones, setMilestones] = useState<Milestone[]>([]);

  // New milestone form state
  const [newName, setNewName] = useState('');
  const [newType, setNewType] = useState<Milestone['type']>('payment_count');
  const [newThreshold, setNewThreshold] = useState('');
  const [newBonusAmount, setNewBonusAmount] = useState('');
  const [newBonusType, setNewBonusType] = useState<'fixed' | 'percentage'>('fixed');
  const [newOneTime, setNewOneTime] = useState(true);

  // Load existing config
  useEffect(() => {
    if (open) {
      setEnabled(celebrationBonusConfig.enabled ?? false);
      setMilestones(celebrationBonusConfig.milestones || []);
    }
  }, [open, celebrationBonusConfig]);

  const handleSave = () => {
    const config: CelebrationBonusConfig = {
      enabled,
      milestones: milestones
    };
    onSave(config);
  };

  const addMilestone = () => {
    if (!newName.trim() || !newThreshold || !newBonusAmount) return;

    const milestone: Milestone = {
      name: newName.trim(),
      type: newType,
      threshold: parseFloat(newThreshold),
      bonus_amount: parseFloat(newBonusAmount),
      bonus_type: newBonusType,
      one_time: newOneTime
    };

    setMilestones([...milestones, milestone]);

    // Reset form
    setNewName('');
    setNewThreshold('');
    setNewBonusAmount('');
    setNewBonusType('fixed');
    setNewOneTime(true);
  };

  const removeMilestone = (index: number) => {
    setMilestones(milestones.filter((_, i) => i !== index));
  };

  const milestoneTypes = [
    { value: 'payment_count', label: 'Payment Count', unit: 'payments' },
    { value: 'tenure_months', label: 'Tenure', unit: 'months' },
    { value: 'total_invested', label: 'Total Invested', unit: '₹' },
    { value: 'referral_count', label: 'Referrals', unit: 'referrals' },
    { value: 'streak_months', label: 'On-time Streak', unit: 'months' },
    { value: 'zero_missed_payments', label: 'Perfect Record', unit: 'payments' }
  ];

  const getMilestoneTypeLabel = (type: string) => {
    return milestoneTypes.find(t => t.value === type)?.label || type;
  };

  const getMilestoneUnit = (type: string) => {
    return milestoneTypes.find(t => t.value === type)?.unit || '';
  };

  const getPresetMilestones = () => {
    return [
      { name: 'First Payment', type: 'payment_count', threshold: 1, bonus: 500 },
      { name: '6-Month Anniversary', type: 'tenure_months', threshold: 6, bonus: 1000 },
      { name: '1-Year Champion', type: 'tenure_months', threshold: 12, bonus: 5000 },
      { name: 'Perfect Year', type: 'zero_missed_payments', threshold: 12, bonus: 2000 },
      { name: '10 Successful Referrals', type: 'referral_count', threshold: 10, bonus: 3000 }
    ];
  };

  const addPresetMilestone = (preset: any) => {
    const milestone: Milestone = {
      name: preset.name,
      type: preset.type as Milestone['type'],
      threshold: preset.threshold,
      bonus_amount: preset.bonus,
      bonus_type: 'fixed',
      one_time: true
    };
    setMilestones([...milestones, milestone]);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <PartyPopper className="h-5 w-5" />
            Celebration Bonuses - {planName}
          </DialogTitle>
          <DialogDescription>
            Configure milestone-based bonuses to celebrate subscriber achievements
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Enable/Disable */}
          <div className="flex items-center justify-between p-4 border rounded-lg bg-muted/30">
            <div className="space-y-1">
              <Label className="text-base font-semibold">Enable Celebration Bonuses</Label>
              <p className="text-sm text-muted-foreground">
                Reward subscribers when they reach important milestones
              </p>
            </div>
            <Switch checked={enabled} onCheckedChange={setEnabled} />
          </div>

          {enabled && (
            <>
              {/* Info Alert */}
              <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div className="text-sm text-muted-foreground">
                  <p className="font-medium mb-1">How Celebration Bonuses Work:</p>
                  <p>Set up milestones based on payments, tenure, investments, or referrals. When subscribers hit these milestones, they automatically receive bonus rewards!</p>
                </div>
              </div>

              {/* Quick Presets */}
              <div className="space-y-3">
                <Label className="flex items-center gap-2">
                  <Target className="h-4 w-4" />
                  Quick Add Presets
                </Label>
                <div className="flex flex-wrap gap-2">
                  {getPresetMilestones().map((preset, idx) => (
                    <Button
                      key={idx}
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => addPresetMilestone(preset)}
                    >
                      <Plus className="h-3 w-3 mr-1" />
                      {preset.name}
                    </Button>
                  ))}
                </div>
              </div>

              {/* Add Milestone Form */}
              <div className="space-y-4 p-4 border rounded-lg bg-card">
                <h4 className="font-semibold">Add Custom Milestone</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Milestone Name</Label>
                    <Input
                      value={newName}
                      onChange={(e) => setNewName(e.target.value)}
                      placeholder="e.g., First Payment Champion"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Milestone Type</Label>
                    <Select value={newType} onValueChange={(value: any) => setNewType(value)}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {milestoneTypes.map((type) => (
                          <SelectItem key={type.value} value={type.value}>
                            {type.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Threshold ({getMilestoneUnit(newType)})</Label>
                    <Input
                      type="number"
                      min="1"
                      value={newThreshold}
                      onChange={(e) => setNewThreshold(e.target.value)}
                      placeholder="e.g., 1, 6, 12"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Bonus Amount</Label>
                    <div className="flex gap-2">
                      <Input
                        type="number"
                        min="0"
                        step="100"
                        value={newBonusAmount}
                        onChange={(e) => setNewBonusAmount(e.target.value)}
                        placeholder="500"
                        className="flex-1"
                      />
                      <Select value={newBonusType} onValueChange={(value: any) => setNewBonusType(value)}>
                        <SelectTrigger className="w-[100px]">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="fixed">₹</SelectItem>
                          <SelectItem value="percentage">%</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {newBonusType === 'percentage' ? 'Percentage of investment amount' : 'Fixed rupee amount'}
                    </p>
                  </div>
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Switch checked={newOneTime} onCheckedChange={setNewOneTime} />
                    <Label>One-time bonus (awarded only once)</Label>
                  </div>
                  <Button onClick={addMilestone} disabled={!newName.trim() || !newThreshold || !newBonusAmount}>
                    <Plus className="h-4 w-4 mr-1" /> Add Milestone
                  </Button>
                </div>
              </div>

              {/* Milestones Table */}
              <div className="space-y-3">
                <Label>Configured Milestones ({milestones.length})</Label>
                {milestones.length > 0 ? (
                  <div className="border rounded-lg overflow-hidden">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Milestone Name</TableHead>
                          <TableHead>Type</TableHead>
                          <TableHead>Threshold</TableHead>
                          <TableHead>Bonus</TableHead>
                          <TableHead>Frequency</TableHead>
                          <TableHead className="w-[80px]">Actions</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {milestones.map((milestone, index) => (
                          <TableRow key={index}>
                            <TableCell className="font-medium">{milestone.name}</TableCell>
                            <TableCell>
                              <Badge variant="outline">{getMilestoneTypeLabel(milestone.type)}</Badge>
                            </TableCell>
                            <TableCell>
                              {milestone.threshold} {getMilestoneUnit(milestone.type)}
                            </TableCell>
                            <TableCell>
                              {milestone.bonus_type === 'fixed'
                                ? `₹${milestone.bonus_amount.toLocaleString('en-IN')}`
                                : `${milestone.bonus_amount}%`
                              }
                            </TableCell>
                            <TableCell>
                              {milestone.one_time ? (
                                <Badge variant="secondary">One-time</Badge>
                              ) : (
                                <Badge>Recurring</Badge>
                              )}
                            </TableCell>
                            <TableCell>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => removeMilestone(index)}
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                ) : (
                  <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                      No milestones configured yet. Add milestones using the form above or quick presets.
                    </AlertDescription>
                  </Alert>
                )}
              </div>

              {/* Example */}
              {milestones.length > 0 && (
                <Alert>
                  <PartyPopper className="h-4 w-4" />
                  <AlertDescription>
                    <strong>Example User Journey:</strong>
                    <div className="mt-2 space-y-1 text-sm">
                      {milestones.slice(0, 3).map((m, i) => (
                        <p key={i}>
                          • {m.name}: Earn {m.bonus_type === 'fixed' ? `₹${m.bonus_amount}` : `${m.bonus_amount}%`} after {m.threshold} {getMilestoneUnit(m.type)}
                        </p>
                      ))}
                      {milestones.length > 3 && (
                        <p className="text-muted-foreground">...and {milestones.length - 3} more milestones</p>
                      )}
                    </div>
                  </AlertDescription>
                </Alert>
              )}
            </>
          )}
        </div>

        <div className="flex gap-2 pt-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="flex-1" disabled={isSaving}>
            Cancel
          </Button>
          <Button onClick={handleSave} className="flex-1" disabled={isSaving}>
            {isSaving ? 'Saving...' : 'Save Celebration Bonuses'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
