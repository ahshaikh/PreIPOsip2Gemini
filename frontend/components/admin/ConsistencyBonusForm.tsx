'use client';

import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2, Zap, Award } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { validateConsistencyConfig, calculateConsistencyBonus, formatCurrency } from '@/lib/bonusCalculations';
import type { ConsistencyConfig } from '@/lib/bonusCalculations';

interface ConsistencyBonusFormProps {
  value: Partial<ConsistencyConfig>;
  onChange: (config: Partial<ConsistencyConfig>) => void;
}

export function ConsistencyBonusForm({ value, onChange }: ConsistencyBonusFormProps) {
  const [streakMonths, setStreakMonths] = useState('');
  const [streakMultiplier, setStreakMultiplier] = useState('');

  const config: Partial<ConsistencyConfig> = {
    amount_per_payment: value.amount_per_payment ?? 50,
    streaks: value.streaks ?? [],
  };

  const errors = validateConsistencyConfig(config);

  const handleChange = (field: keyof ConsistencyConfig, val: any) => {
    onChange({ ...config, [field]: val });
  };

  const addStreak = () => {
    const months = parseInt(streakMonths);
    const multiplier = parseFloat(streakMultiplier);

    if (!months || !multiplier || months < 1 || multiplier < 1) return;

    // Check if streak already exists
    if (config.streaks?.some(s => s.months === months)) {
      alert(`Streak for ${months} months already exists!`);
      return;
    }

    const newStreaks = [...(config.streaks || []), { months, multiplier }].sort((a, b) => a.months - b.months);
    handleChange('streaks', newStreaks);
    setStreakMonths('');
    setStreakMultiplier('');
  };

  const removeStreak = (index: number) => {
    const newStreaks = config.streaks?.filter((_, i) => i !== index) || [];
    handleChange('streaks', newStreaks);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="space-y-4">
        <h4 className="font-semibold flex items-center gap-2">
          <Zap className="h-4 w-4" />
          Consistency Bonus Configuration
        </h4>
        <p className="text-sm text-muted-foreground">
          Reward users for making on-time payments consistently. Bonus is only awarded if payment is made before the due date. Configure streak multipliers to incentivize longer payment streaks.
        </p>
      </div>

      {/* Base Amount */}
      <div className="space-y-4 p-4 bg-muted/50 rounded-lg">
        <div className="space-y-2">
          <Label>Base Consistency Bonus (₹)</Label>
          <Input
            type="number"
            min="0"
            step="10"
            value={config.amount_per_payment}
            onChange={(e) => handleChange('amount_per_payment', parseFloat(e.target.value) || 0)}
            placeholder="50"
          />
          <p className="text-xs text-muted-foreground">
            Amount awarded for each on-time payment (before streak multipliers)
          </p>
        </div>

        {/* Formula Display */}
        <div className="p-3 bg-background rounded border">
          <p className="text-xs font-medium mb-1">Calculation Formula:</p>
          <code className="text-xs">
            Final Bonus = Base Amount × Streak Multiplier
          </code>
          <p className="text-xs text-muted-foreground mt-2">
            Example: ₹{config.amount_per_payment ?? 0} × 2.0 = ₹{(config.amount_per_payment ?? 0) * 2} (for 2x streak multiplier)
          </p>
        </div>
      </div>

      {/* Validation Errors */}
      {errors.length > 0 && (
        <div className="p-3 bg-destructive/10 border border-destructive/30 rounded-md">
          <p className="text-sm font-medium text-destructive mb-1">Configuration Errors:</p>
          <ul className="text-sm text-destructive space-y-1">
            {errors.map((error, i) => (
              <li key={i}>• {error}</li>
            ))}
          </ul>
        </div>
      )}

      {/* Streak Multipliers */}
      <div className="space-y-4">
        <div>
          <h5 className="font-medium mb-1">Streak Multipliers</h5>
          <p className="text-sm text-muted-foreground">
            Boost the consistency bonus when users maintain payment streaks
          </p>
        </div>

        {/* Add Streak Form */}
        <div className="flex gap-2 p-4 bg-muted/50 rounded-lg">
          <div className="flex-1">
            <Input
              type="number"
              placeholder="Streak (months)"
              min="1"
              value={streakMonths}
              onChange={(e) => setStreakMonths(e.target.value)}
            />
          </div>
          <div className="flex-1">
            <Input
              type="number"
              placeholder="Multiplier (e.g., 1.5)"
              min="1"
              step="0.1"
              value={streakMultiplier}
              onChange={(e) => setStreakMultiplier(e.target.value)}
            />
          </div>
          <Button
            type="button"
            onClick={addStreak}
            disabled={!streakMonths || !streakMultiplier}
          >
            <Plus className="h-4 w-4 mr-2" />
            Add
          </Button>
        </div>

        {/* Streaks Table */}
        {config.streaks && config.streaks.length > 0 ? (
          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Streak Length</TableHead>
                  <TableHead>Multiplier</TableHead>
                  <TableHead>Bonus Amount</TableHead>
                  <TableHead>Example Value</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {config.streaks.map((streak, index) => {
                  const bonusAmount = (config.amount_per_payment || 0) * streak.multiplier;
                  return (
                    <TableRow key={index}>
                      <TableCell className="font-medium">
                        <Badge variant="secondary">
                          {streak.months} months
                        </Badge>
                      </TableCell>
                      <TableCell className="font-bold text-primary">
                        {streak.multiplier}x
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        ₹{config.amount_per_payment} × {streak.multiplier}
                      </TableCell>
                      <TableCell className="font-semibold">
                        = {formatCurrency(bonusAmount)}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => removeStreak(index)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </div>
        ) : (
          <div className="text-center py-8 bg-muted/30 rounded-lg border-2 border-dashed">
            <Award className="h-10 w-10 mx-auto text-muted-foreground mb-2" />
            <p className="text-sm text-muted-foreground">No streak multipliers configured</p>
            <p className="text-xs text-muted-foreground mt-1">
              Add streak multipliers to reward consecutive on-time payments
            </p>
          </div>
        )}
      </div>

      {/* Preview Examples */}
      {config.amount_per_payment && config.amount_per_payment > 0 && (
        <div className="space-y-4">
          <h5 className="font-medium">Example Scenarios</h5>
          <div className="grid md:grid-cols-3 gap-4">
            {/* No Streak */}
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-xs text-muted-foreground mb-1">New User (No Streak)</p>
              <p className="text-2xl font-bold">
                {formatCurrency(calculateConsistencyBonus(1, config as ConsistencyConfig))}
              </p>
              <p className="text-xs text-muted-foreground mt-1">Base amount only</p>
            </div>

            {/* With best streak */}
            {config.streaks && config.streaks.length > 0 && (
              <div className="p-4 bg-primary/10 border border-primary rounded-lg">
                <p className="text-xs text-muted-foreground mb-1">
                  {config.streaks[config.streaks.length - 1].months} Month Streak
                </p>
                <p className="text-2xl font-bold text-primary">
                  {formatCurrency(calculateConsistencyBonus(config.streaks[config.streaks.length - 1].months, config as ConsistencyConfig))}
                </p>
                <p className="text-xs text-muted-foreground mt-1">
                  {config.streaks[config.streaks.length - 1].multiplier}x multiplier
                </p>
              </div>
            )}

            {/* Mid-tier streak if exists */}
            {config.streaks && config.streaks.length > 1 && (
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-xs text-muted-foreground mb-1">
                  {config.streaks[Math.floor(config.streaks.length / 2)].months} Month Streak
                </p>
                <p className="text-2xl font-bold">
                  {formatCurrency(calculateConsistencyBonus(config.streaks[Math.floor(config.streaks.length / 2)].months, config as ConsistencyConfig))}
                </p>
                <p className="text-xs text-muted-foreground mt-1">
                  {config.streaks[Math.floor(config.streaks.length / 2)].multiplier}x multiplier
                </p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Important Notes */}
      <div className="p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-md">
        <p className="text-sm font-medium text-amber-900 dark:text-amber-100 mb-2">⚠️ Important Notes:</p>
        <ul className="text-xs text-amber-700 dark:text-amber-300 space-y-1">
          <li>• Consistency bonus is <strong>only awarded for on-time payments</strong></li>
          <li>• Late payments do not qualify, even if eventually paid</li>
          <li>• Streak resets if user misses a due date or pays late</li>
          <li>• Higher streak multipliers encourage long-term commitment</li>
          <li>• Recommended streaks: 3, 6, 12 months with 1.5x, 2x, 3x multipliers</li>
        </ul>
      </div>

      {/* Best Practices */}
      <div className="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-md">
        <p className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">💡 Streak Configuration Tips:</p>
        <ul className="text-xs text-blue-700 dark:text-blue-300 space-y-1">
          <li>• Start with modest base amount (₹25-₹100)</li>
          <li>• Use incremental multipliers (1.5x → 2x → 3x)</li>
          <li>• Common streaks: 3 months (quarter), 6 months (half-year), 12 months (annual)</li>
          <li>• Higher multipliers for longer streaks incentivize retention</li>
        </ul>
      </div>
    </div>
  );
}
