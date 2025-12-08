// V-PHASE5-PROFIT-1208 (Created)
'use client';

import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { TrendingUp, Info, Calculator, Users } from 'lucide-react';

interface ProfitSharingConfig {
  enabled?: boolean;
  percentage?: number;
  distribution_frequency?: 'monthly' | 'quarterly' | 'annually';
  min_subscription_months?: number;
  allocation_method?: 'equal' | 'proportional_to_investment' | 'proportional_to_tenure' | 'weighted';
  pool_description?: string;
}

interface ProfitSharingConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  profitSharingConfig: ProfitSharingConfig;
  onSave: (config: ProfitSharingConfig) => void;
  isSaving: boolean;
}

export function ProfitSharingConfigDialog({
  open,
  onOpenChange,
  planName,
  profitSharingConfig,
  onSave,
  isSaving
}: ProfitSharingConfigDialogProps) {
  const [enabled, setEnabled] = useState(false);
  const [percentage, setPercentage] = useState('10');
  const [distributionFrequency, setDistributionFrequency] = useState<'monthly' | 'quarterly' | 'annually'>('quarterly');
  const [minSubscriptionMonths, setMinSubscriptionMonths] = useState('3');
  const [allocationMethod, setAllocationMethod] = useState<'equal' | 'proportional_to_investment' | 'proportional_to_tenure' | 'weighted'>('proportional_to_investment');
  const [poolDescription, setPoolDescription] = useState('');

  // Load existing config
  useEffect(() => {
    if (open) {
      setEnabled(profitSharingConfig.enabled ?? false);
      setPercentage(profitSharingConfig.percentage?.toString() || '10');
      setDistributionFrequency(profitSharingConfig.distribution_frequency || 'quarterly');
      setMinSubscriptionMonths(profitSharingConfig.min_subscription_months?.toString() || '3');
      setAllocationMethod(profitSharingConfig.allocation_method || 'proportional_to_investment');
      setPoolDescription(profitSharingConfig.pool_description || '');
    }
  }, [open, profitSharingConfig]);

  const handleSave = () => {
    const config: ProfitSharingConfig = {
      enabled,
      percentage: parseFloat(percentage),
      distribution_frequency: distributionFrequency,
      min_subscription_months: parseInt(minSubscriptionMonths),
      allocation_method: allocationMethod,
      pool_description: poolDescription.trim()
    };
    onSave(config);
  };

  const allocationMethods = [
    {
      value: 'equal',
      label: 'Equal Distribution',
      description: 'Every eligible subscriber gets an equal share'
    },
    {
      value: 'proportional_to_investment',
      label: 'Investment-Based',
      description: 'Share proportional to investment amount'
    },
    {
      value: 'proportional_to_tenure',
      label: 'Tenure-Based',
      description: 'Share proportional to months subscribed'
    },
    {
      value: 'weighted',
      label: 'Weighted (70% Investment, 30% Tenure)',
      description: 'Balanced approach combining both factors'
    }
  ];

  const percentageNum = parseFloat(percentage) || 0;
  const isValidPercentage = percentageNum > 0 && percentageNum <= 100;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <TrendingUp className="h-5 w-5" />
            Profit Sharing Configuration - {planName}
          </DialogTitle>
          <DialogDescription>
            Configure how company profits are distributed to plan subscribers
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Enable/Disable */}
          <div className="flex items-center justify-between p-4 border rounded-lg bg-muted/30">
            <div className="space-y-1">
              <Label className="text-base font-semibold">Enable Profit Sharing</Label>
              <p className="text-sm text-muted-foreground">
                Share company profits with subscribers on this plan
              </p>
            </div>
            <Switch checked={enabled} onCheckedChange={setEnabled} />
          </div>

          {enabled && (
            <>
              {/* Sharing Percentage */}
              <div className="space-y-4">
                <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                  <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                  <div className="text-sm text-muted-foreground">
                    <p className="font-medium mb-1">How It Works:</p>
                    <p>A percentage of total profits is distributed to eligible subscribers based on the allocation method you choose.</p>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Profit Sharing Percentage</Label>
                  <div className="flex items-center gap-2">
                    <Input
                      type="number"
                      min="0"
                      max="100"
                      step="0.1"
                      value={percentage}
                      onChange={(e) => setPercentage(e.target.value)}
                      placeholder="10"
                      className="flex-1"
                    />
                    <span className="text-sm text-muted-foreground">%</span>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    Percentage of total profit pool to distribute to this plan's subscribers
                  </p>
                  {!isValidPercentage && (
                    <p className="text-xs text-destructive">
                      Must be between 0 and 100
                    </p>
                  )}
                </div>

                {/* Distribution Frequency */}
                <div className="space-y-2">
                  <Label>Distribution Frequency</Label>
                  <Select value={distributionFrequency} onValueChange={(value: any) => setDistributionFrequency(value)}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="monthly">Monthly</SelectItem>
                      <SelectItem value="quarterly">Quarterly (Every 3 months)</SelectItem>
                      <SelectItem value="annually">Annually (Once per year)</SelectItem>
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-muted-foreground">
                    How often profits are distributed to subscribers
                  </p>
                </div>

                {/* Minimum Subscription Period */}
                <div className="space-y-2">
                  <Label>Minimum Subscription Months</Label>
                  <Input
                    type="number"
                    min="0"
                    value={minSubscriptionMonths}
                    onChange={(e) => setMinSubscriptionMonths(e.target.value)}
                    placeholder="3"
                  />
                  <p className="text-xs text-muted-foreground">
                    Subscribers must be active for at least this many months to be eligible
                  </p>
                </div>

                {/* Allocation Method */}
                <div className="space-y-3">
                  <Label className="flex items-center gap-2">
                    <Calculator className="h-4 w-4" />
                    Allocation Method
                  </Label>
                  <Select value={allocationMethod} onValueChange={(value: any) => setAllocationMethod(value)}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {allocationMethods.map((method) => (
                        <SelectItem key={method.value} value={method.value}>
                          {method.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <div className="p-3 bg-muted/50 rounded-lg text-sm">
                    <p className="font-medium mb-1">
                      {allocationMethods.find(m => m.value === allocationMethod)?.label}
                    </p>
                    <p className="text-muted-foreground">
                      {allocationMethods.find(m => m.value === allocationMethod)?.description}
                    </p>
                  </div>
                </div>

                {/* Profit Pool Description */}
                <div className="space-y-2">
                  <Label>Profit Pool Description (Optional)</Label>
                  <Textarea
                    value={poolDescription}
                    onChange={(e) => setPoolDescription(e.target.value)}
                    placeholder="Describe what profits are included (e.g., 'Net profits from Pre-IPO investments after operational costs')"
                    rows={3}
                  />
                  <p className="text-xs text-muted-foreground">
                    Explain to subscribers what profit sources are included
                  </p>
                </div>

                {/* Example Calculation */}
                <Alert>
                  <Calculator className="h-4 w-4" />
                  <AlertDescription>
                    <strong>Example Calculation:</strong>
                    <div className="mt-2 space-y-1 text-sm">
                      <p>• Total quarterly profit: ₹10,00,000</p>
                      <p>• Sharing percentage: {percentage}%</p>
                      <p>• Distributable amount: ₹{((1000000 * percentageNum) / 100).toLocaleString('en-IN')}</p>
                      <p>• Method: {allocationMethods.find(m => m.value === allocationMethod)?.label}</p>
                      <p className="text-muted-foreground mt-2">
                        {allocationMethod === 'equal' && '→ Split equally among all eligible subscribers'}
                        {allocationMethod === 'proportional_to_investment' && '→ Higher investment = Higher share'}
                        {allocationMethod === 'proportional_to_tenure' && '→ Longer tenure = Higher share'}
                        {allocationMethod === 'weighted' && '→ 70% based on investment, 30% based on tenure'}
                      </p>
                    </div>
                  </AlertDescription>
                </Alert>
              </div>
            </>
          )}
        </div>

        <div className="flex gap-2 pt-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="flex-1" disabled={isSaving}>
            Cancel
          </Button>
          <Button
            onClick={handleSave}
            className="flex-1"
            disabled={isSaving || (enabled && !isValidPercentage)}
          >
            {isSaving ? 'Saving...' : 'Save Profit Sharing Config'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
