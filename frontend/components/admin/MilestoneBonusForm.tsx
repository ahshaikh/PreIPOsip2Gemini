'use client';

import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Trash2, Edit, Trophy, Check, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { validateMilestoneConfig, formatCurrency } from '@/lib/bonusCalculations';
import type { MilestoneConfig } from '@/lib/bonusCalculations';

interface MilestoneBonusFormProps {
  value: MilestoneConfig[];
  onChange: (milestones: MilestoneConfig[]) => void;
}

export function MilestoneBonusForm({ value = [], onChange }: MilestoneBonusFormProps) {
  const [newMonth, setNewMonth] = useState('');
  const [newAmount, setNewAmount] = useState('');
  const [editingIndex, setEditingIndex] = useState<number | null>(null);
  const [editMonth, setEditMonth] = useState('');
  const [editAmount, setEditAmount] = useState('');

  const errors = validateMilestoneConfig(value);

  const addMilestone = () => {
    const month = parseInt(newMonth);
    const amount = parseFloat(newAmount);

    if (!month || !amount || month < 1 || amount < 0) return;

    // Check if month already exists
    if (value.some(m => m.month === month)) {
      alert(`Milestone for month ${month} already exists!`);
      return;
    }

    const newMilestones = [...value, { month, amount }].sort((a, b) => a.month - b.month);
    onChange(newMilestones);
    setNewMonth('');
    setNewAmount('');
  };

  const startEdit = (index: number) => {
    setEditingIndex(index);
    setEditMonth(value[index].month.toString());
    setEditAmount(value[index].amount.toString());
  };

  const saveEdit = () => {
    if (editingIndex === null) return;

    const month = parseInt(editMonth);
    const amount = parseFloat(editAmount);

    if (!month || !amount || month < 1 || amount < 0) return;

    // Check if new month conflicts with other milestones
    const conflictIndex = value.findIndex((m, i) => m.month === month && i !== editingIndex);
    if (conflictIndex !== -1) {
      alert(`Milestone for month ${month} already exists!`);
      return;
    }

    const newMilestones = [...value];
    newMilestones[editingIndex] = { month, amount };
    onChange(newMilestones.sort((a, b) => a.month - b.month));
    setEditingIndex(null);
  };

  const cancelEdit = () => {
    setEditingIndex(null);
  };

  const removeMilestone = (index: number) => {
    const newMilestones = value.filter((_, i) => i !== index);
    onChange(newMilestones);
  };

  const totalMilestoneBonus = value.reduce((sum, m) => sum + m.amount, 0);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="space-y-4">
        <h4 className="font-semibold flex items-center gap-2">
          <Trophy className="h-4 w-4" />
          Milestone Bonus Configuration
        </h4>
        <p className="text-sm text-muted-foreground">
          Award one-time bonuses when users reach specific payment milestones. Perfect for celebrating commitment and encouraging long-term subscriptions.
        </p>
      </div>

      {/* Add Milestone Form */}
      <div className="space-y-4 p-4 bg-muted/50 rounded-lg">
        <Label className="text-sm font-medium">Add New Milestone</Label>
        <div className="flex gap-2">
          <div className="flex-1">
            <Input
              type="number"
              placeholder="Month (e.g., 6, 12, 24)"
              min="1"
              value={newMonth}
              onChange={(e) => setNewMonth(e.target.value)}
            />
          </div>
          <div className="flex-1">
            <Input
              type="number"
              placeholder="Bonus Amount (₹)"
              min="0"
              step="100"
              value={newAmount}
              onChange={(e) => setNewAmount(e.target.value)}
            />
          </div>
          <Button
            type="button"
            onClick={addMilestone}
            disabled={!newMonth || !newAmount}
          >
            <Plus className="h-4 w-4 mr-2" />
            Add
          </Button>
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

      {/* Milestones Table */}
      {value.length > 0 ? (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <h5 className="font-medium">Configured Milestones</h5>
            <Badge variant="outline">
              Total: {formatCurrency(totalMilestoneBonus)}
            </Badge>
          </div>

          <div className="border rounded-lg overflow-hidden">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-[100px]">Month</TableHead>
                  <TableHead>Bonus Amount</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead className="text-right w-[120px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {value.map((milestone, index) => (
                  <TableRow key={index}>
                    {editingIndex === index ? (
                      // Edit Mode
                      <>
                        <TableCell>
                          <Input
                            type="number"
                            min="1"
                            value={editMonth}
                            onChange={(e) => setEditMonth(e.target.value)}
                            className="w-20"
                          />
                        </TableCell>
                        <TableCell>
                          <Input
                            type="number"
                            min="0"
                            step="100"
                            value={editAmount}
                            onChange={(e) => setEditAmount(e.target.value)}
                            className="w-32"
                          />
                        </TableCell>
                        <TableCell className="text-muted-foreground">
                          <em>Editing...</em>
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex gap-1 justify-end">
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={saveEdit}
                            >
                              <Check className="h-4 w-4 text-green-600" />
                            </Button>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={cancelEdit}
                            >
                              <X className="h-4 w-4 text-muted-foreground" />
                            </Button>
                          </div>
                        </TableCell>
                      </>
                    ) : (
                      // View Mode
                      <>
                        <TableCell className="font-bold">
                          <Badge variant="secondary">
                            Month {milestone.month}
                          </Badge>
                        </TableCell>
                        <TableCell className="font-semibold text-primary">
                          {formatCurrency(milestone.amount)}
                        </TableCell>
                        <TableCell className="text-sm text-muted-foreground">
                          {getMilestoneDescription(milestone.month)}
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex gap-1 justify-end">
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => startEdit(index)}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => removeMilestone(index)}
                            >
                              <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                          </div>
                        </TableCell>
                      </>
                    )}
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          {/* Milestone Timeline Visual */}
          <div className="p-4 bg-gradient-to-r from-primary/5 to-primary/10 border border-primary/20 rounded-lg">
            <p className="text-sm font-medium mb-3">Milestone Timeline:</p>
            <div className="flex items-center gap-2 overflow-x-auto pb-2">
              {value.map((milestone, index) => (
                <div key={index} className="flex items-center gap-2">
                  <div className="flex flex-col items-center min-w-[100px]">
                    <div className="w-12 h-12 rounded-full bg-primary text-primary-foreground flex items-center justify-center font-bold">
                      {milestone.month}
                    </div>
                    <p className="text-xs font-medium mt-1">Month {milestone.month}</p>
                    <p className="text-xs text-muted-foreground">{formatCurrency(milestone.amount)}</p>
                  </div>
                  {index < value.length - 1 && (
                    <div className="w-8 h-0.5 bg-primary/30" />
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Summary */}
          <div className="grid md:grid-cols-3 gap-4">
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-sm text-muted-foreground">Total Milestones</p>
              <p className="text-2xl font-bold">{value.length}</p>
            </div>
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-sm text-muted-foreground">First Milestone</p>
              <p className="text-2xl font-bold">Month {value[0]?.month || '-'}</p>
            </div>
            <div className="p-4 bg-muted rounded-lg">
              <p className="text-sm text-muted-foreground">Total Bonus Value</p>
              <p className="text-2xl font-bold text-primary">{formatCurrency(totalMilestoneBonus)}</p>
            </div>
          </div>
        </div>
      ) : (
        <div className="text-center py-12 bg-muted/30 rounded-lg border-2 border-dashed">
          <Trophy className="h-12 w-12 mx-auto text-muted-foreground mb-3" />
          <p className="text-sm text-muted-foreground mb-1">No milestones configured</p>
          <p className="text-xs text-muted-foreground">
            Add your first milestone above to reward long-term subscribers
          </p>
        </div>
      )}

      {/* Best Practices */}
      <div className="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-md">
        <p className="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">💡 Milestone Best Practices:</p>
        <ul className="text-xs text-blue-700 dark:text-blue-300 space-y-1">
          <li>• Common milestones: 6, 12, 18, 24, 36 months</li>
          <li>• Increase bonus amounts for later milestones to encourage retention</li>
          <li>• Consider quarter/half/full year achievements (3, 6, 12 months)</li>
          <li>• Use psychological pricing (₹999, ₹4999, ₹9999)</li>
        </ul>
      </div>
    </div>
  );
}

/**
 * Get friendly description for common milestone months
 */
function getMilestoneDescription(month: number): string {
  const descriptions: Record<number, string> = {
    3: 'Quarter milestone',
    6: 'Half-year achievement',
    9: 'Three-quarter milestone',
    12: '1 year anniversary',
    18: '1.5 years milestone',
    24: '2 years anniversary',
    30: '2.5 years milestone',
    36: '3 years anniversary',
  };

  return descriptions[month] || `${month} month milestone`;
}
