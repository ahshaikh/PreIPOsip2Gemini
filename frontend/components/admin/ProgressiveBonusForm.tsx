'use client';

import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2, TrendingUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { validateProgressiveConfig, getProgressiveBonusSchedule, formatCurrency } from '@/lib/bonusCalculations';
import type { ProgressiveConfig } from '@/types/plan';

interface ProgressiveBonusFormProps {
  value: Partial<ProgressiveConfig>;
  onChange: (config: Partial<ProgressiveConfig>) => void;
  paymentAmount?: number;
  durationMonths?: number;
}

export function ProgressiveBonusForm({ value, onChange, paymentAmount = 5000, durationMonths = 36 }: ProgressiveBonusFormProps) {
  const [overrideMonth, setOverrideMonth] = useState('');
  const [overrideRate, setOverrideRate] = useState('');

  const config: Partial<ProgressiveConfig> = {
    rate: value.rate ?? 0.5,
    start_month: value.start_month ?? 4,
    max_percentage: value.max_percentage ?? 20,
    overrides: value.overrides ?? {},
  };

  const errors = validateProgressiveConfig(config);

  const handleChange = <K extends keyof ProgressiveConfig>(field: K, val: ProgressiveConfig[K]) => {
    onChange({ ...config, [field]: val });
  };

  const addOverride = () => {
    const month = parseInt(overrideMonth);
    const rate = parseFloat(overrideRate);

    if (!month || !rate || month < 1) return;

    const newOverrides = { ...config.overrides, [month]: rate };
    handleChange('overrides', newOverrides);
    setOverrideMonth('');
    setOverrideRate('');
  };

  const removeOverride = (month: number) => {
    const newOverrides = { ...config.overrides };
    delete newOverrides[month];
    handleChange('overrides', newOverrides);
  };

  // Generate preview schedule
  const schedule = config.rate !== undefined && config.start_month !== undefined && config.max_percentage !== undefined
    ? getProgressiveBonusSchedule(paymentAmount, Math.min(durationMonths, 12), config as ProgressiveConfig, 1.0)
    : [];

  return (
    <div className="space-y-6">
      {/* Basic Configuration */}
      <div className="space-y-4">
        <h4 className="font-semibold flex items-center gap-2">
          <TrendingUp className="h-4 w-4" />
          Progressive Bonus Configuration
        </h4>
        <p className="text-sm text-muted-foreground">
          Progressive bonuses increase over time based on subscription tenure. Set the base growth rate and configure month-specific overrides for custom bonus curves.
        </p>

        <div className="grid grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label>Growth Rate (%)</Label>
            <Input
              type="number"
              step="0.1"
              min="0"
              max="100"
              value={config.rate}
              onChange={(e) => handleChange('rate', parseFloat(e.target.value) || 0)}
              placeholder="0.5"
            />
            <p className="text-xs text-muted-foreground">% increase per month</p>
          </div>

          <div className="space-y-2">
            <Label>Start Month</Label>
            <Input
              type="number"
              min="1"
              value={config.start_month}
              onChange={(e) => handleChange('start_month', parseInt(e.target.value) || 1)}
              placeholder="4"
            />
            <p className="text-xs text-muted-foreground">When bonus starts</p>
          </div>

          <div className="space-y-2">
            <Label>Max Percentage (%)</Label>
            <Input
              type="number"
              min="0"
              max="100"
              value={config.max_percentage}
              onChange={(e) => handleChange('max_percentage', parseFloat(e.target.value) || 0)}
              placeholder="20"
            />
            <p className="text-xs text-muted-foreground">Maximum cap</p>
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

        {/* Formula Display */}
        <div className="p-4 bg-muted rounded-md">
          <p className="text-sm font-medium mb-2">Formula:</p>
          <code className="text-xs">
            Bonus = ((Month - {config.start_month} + 1) × {config.rate}%) × Payment Amount × Multiplier
          </code>
          <p className="text-xs text-muted-foreground mt-2">
            Maximum: {config.max_percentage}% of payment amount
          </p>
        </div>
      </div>

      {/* Month Overrides */}
      <div className="space-y-4">
        <h4 className="font-semibold">Month-Specific Overrides</h4>
        <p className="text-sm text-muted-foreground">
          Override the progressive calculation for specific months to create custom bonus curves.
        </p>

        {/* Add Override Form */}
        <div className="flex gap-2">
          <Input
            type="number"
            placeholder="Month"
            min="1"
            value={overrideMonth}
            onChange={(e) => setOverrideMonth(e.target.value)}
            className="w-32"
          />
          <Input
            type="number"
            placeholder="Rate %"
            step="0.1"
            min="0"
            max="100"
            value={overrideRate}
            onChange={(e) => setOverrideRate(e.target.value)}
            className="w-32"
          />
          <Button type="button" variant="outline" onClick={addOverride} disabled={!overrideMonth || !overrideRate}>
            <Plus className="h-4 w-4 mr-2" />
            Add Override
          </Button>
        </div>

        {/* Overrides Table */}
        {config.overrides && Object.keys(config.overrides).length > 0 ? (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Month</TableHead>
                <TableHead>Rate (%)</TableHead>
                <TableHead>Example Bonus</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {Object.entries(config.overrides)
                .sort(([a], [b]) => parseInt(a) - parseInt(b))
                .map(([month, rate]) => (
                  <TableRow key={month}>
                    <TableCell className="font-medium">Month {month}</TableCell>
                    <TableCell>{rate}%</TableCell>
                    <TableCell className="text-muted-foreground">
                      {formatCurrency((rate / 100) * paymentAmount)}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeOverride(parseInt(month))}
                      >
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
            </TableBody>
          </Table>
        ) : (
          <p className="text-sm text-muted-foreground text-center py-4">
            No overrides configured. Progressive formula will apply to all months.
          </p>
        )}
      </div>

      {/* Preview */}
      {schedule.length > 0 && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h4 className="font-semibold">Preview (First 12 Months)</h4>
            <Badge variant="outline">Payment: {formatCurrency(paymentAmount)}</Badge>
          </div>

          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Month</TableHead>
                  <TableHead>Rate</TableHead>
                  <TableHead>Bonus</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {schedule.map(({ month, rate, bonus }) => (
                  <TableRow key={month}>
                    <TableCell className="font-medium">Month {month}</TableCell>
                    <TableCell>
                      {rate > 0 ? `${rate.toFixed(2)}%` : '-'}
                      {config.overrides?.[month] && (
                        <Badge variant="secondary" className="ml-2 text-xs">Override</Badge>
                      )}
                    </TableCell>
                    <TableCell className="font-semibold">
                      {bonus > 0 ? formatCurrency(bonus) : '-'}
                    </TableCell>
                    <TableCell>
                      {month < (config.start_month || 4) ? (
                        <Badge variant="outline" className="text-xs">Not started</Badge>
                      ) : (
                        <Badge variant="default" className="text-xs">Active</Badge>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          <div className="p-4 bg-primary/5 border border-primary/20 rounded-md">
            <p className="text-sm font-medium">Total Progressive Bonus (12 months):</p>
            <p className="text-2xl font-bold text-primary mt-1">
              {formatCurrency(schedule.reduce((sum, { bonus }) => sum + bonus, 0))}
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
