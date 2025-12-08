// V-PHASE6-DISCOUNT-1208 (Created)
'use client';

import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Percent, Plus, Trash2, Info, Tag } from 'lucide-react';

interface Discount {
  name: string;
  type: 'percentage' | 'fixed';
  value: number;
  duration_months?: number;
  valid_from?: string;
  valid_until?: string;
  is_permanent?: boolean;
}

interface DiscountConfig {
  enabled?: boolean;
  discounts?: Discount[];
}

interface DiscountConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planName: string;
  discountConfig: DiscountConfig;
  monthlyAmount: number;
  onSave: (config: DiscountConfig) => void;
  isSaving: boolean;
}

export function DiscountConfigDialog({
  open,
  onOpenChange,
  planName,
  discountConfig,
  monthlyAmount,
  onSave,
  isSaving
}: DiscountConfigDialogProps) {
  const [enabled, setEnabled] = useState(false);
  const [discounts, setDiscounts] = useState<Discount[]>([]);

  // New discount form state
  const [newName, setNewName] = useState('');
  const [newType, setNewType] = useState<'percentage' | 'fixed'>('percentage');
  const [newValue, setNewValue] = useState('');
  const [newDurationMonths, setNewDurationMonths] = useState('');
  const [newValidFrom, setNewValidFrom] = useState('');
  const [newValidUntil, setNewValidUntil] = useState('');
  const [newIsPermanent, setNewIsPermanent] = useState(false);

  useEffect(() => {
    if (open) {
      setEnabled(discountConfig.enabled ?? false);
      setDiscounts(discountConfig.discounts || []);
    }
  }, [open, discountConfig]);

  const handleSave = () => {
    const config: DiscountConfig = {
      enabled,
      discounts
    };
    onSave(config);
  };

  const addDiscount = () => {
    if (!newName.trim() || !newValue) return;

    const discount: Discount = {
      name: newName.trim(),
      type: newType,
      value: parseFloat(newValue),
      duration_months: newDurationMonths ? parseInt(newDurationMonths) : undefined,
      valid_from: newValidFrom || undefined,
      valid_until: newValidUntil || undefined,
      is_permanent: newIsPermanent
    };

    setDiscounts([...discounts, discount]);

    // Reset form
    setNewName('');
    setNewValue('');
    setNewDurationMonths('');
    setNewValidFrom('');
    setNewValidUntil('');
    setNewIsPermanent(false);
  };

  const removeDiscount = (index: number) => {
    setDiscounts(discounts.filter((_, i) => i !== index));
  };

  const calculateDiscountedAmount = (discount: Discount): number => {
    if (discount.type === 'percentage') {
      return monthlyAmount * (1 - discount.value / 100);
    } else {
      return Math.max(0, monthlyAmount - discount.value);
    }
  };

  const formatDateForInput = (date?: string) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
  };

  const getQuickDiscounts = () => {
    return [
      { name: 'Early Bird 20%', type: 'percentage', value: 20, duration: 3 },
      { name: 'First Month 50% Off', type: 'percentage', value: 50, duration: 1 },
      { name: 'Loyalty Discount', type: 'percentage', value: 10, permanent: true },
      { name: '₹500 Off First 6 Months', type: 'fixed', value: 500, duration: 6 }
    ];
  };

  const addQuickDiscount = (preset: any) => {
    const discount: Discount = {
      name: preset.name,
      type: preset.type as 'percentage' | 'fixed',
      value: preset.value,
      duration_months: preset.duration,
      is_permanent: preset.permanent || false
    };
    setDiscounts([...discounts, discount]);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Tag className="h-5 w-5" />
            Discount Configuration - {planName}
          </DialogTitle>
          <DialogDescription>
            Configure promotional discounts and offers for this plan
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Enable/Disable */}
          <div className="flex items-center justify-between p-4 border rounded-lg bg-muted/30">
            <div className="space-y-1">
              <Label className="text-base font-semibold">Enable Discounts</Label>
              <p className="text-sm text-muted-foreground">
                Offer discounted pricing to attract subscribers
              </p>
            </div>
            <Switch checked={enabled} onCheckedChange={setEnabled} />
          </div>

          {enabled && (
            <>
              <div className="flex items-start gap-3 p-4 bg-muted/50 rounded-lg">
                <Info className="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div className="text-sm text-muted-foreground">
                  <p className="font-medium mb-1">Discount Types:</p>
                  <p>• <strong>Percentage:</strong> X% off the monthly amount</p>
                  <p>• <strong>Fixed:</strong> ₹X off the monthly amount</p>
                  <p>• <strong>Duration:</strong> Apply for first X months only</p>
                  <p>• <strong>Date Range:</strong> Valid only between specific dates</p>
                  <p>• <strong>Permanent:</strong> Apply for the entire subscription period</p>
                </div>
              </div>

              {/* Quick Presets */}
              <div className="space-y-3">
                <Label>Quick Add Presets</Label>
                <div className="flex flex-wrap gap-2">
                  {getQuickDiscounts().map((preset, idx) => (
                    <Button
                      key={idx}
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => addQuickDiscount(preset)}
                    >
                      <Plus className="h-3 w-3 mr-1" />
                      {preset.name}
                    </Button>
                  ))}
                </div>
              </div>

              {/* Add Discount Form */}
              <div className="space-y-4 p-4 border rounded-lg bg-card">
                <h4 className="font-semibold">Add Custom Discount</h4>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Discount Name</Label>
                    <Input
                      value={newName}
                      onChange={(e) => setNewName(e.target.value)}
                      placeholder="e.g., Summer Sale 2024"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Discount Type & Value</Label>
                    <div className="flex gap-2">
                      <Input
                        type="number"
                        min="0"
                        step="0.01"
                        value={newValue}
                        onChange={(e) => setNewValue(e.target.value)}
                        placeholder="10"
                        className="flex-1"
                      />
                      <Select value={newType} onValueChange={(value: any) => setNewType(value)}>
                        <SelectTrigger className="w-[100px]">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="percentage">%</SelectItem>
                          <SelectItem value="fixed">₹</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Duration (Months)</Label>
                    <Input
                      type="number"
                      min="1"
                      value={newDurationMonths}
                      onChange={(e) => setNewDurationMonths(e.target.value)}
                      placeholder="Leave empty for permanent"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Permanent Discount</Label>
                    <div className="flex items-center h-10">
                      <Switch checked={newIsPermanent} onCheckedChange={setNewIsPermanent} />
                      <span className="ml-2 text-sm text-muted-foreground">
                        Apply to all months
                      </span>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Valid From (Optional)</Label>
                    <Input
                      type="date"
                      value={newValidFrom}
                      onChange={(e) => setNewValidFrom(e.target.value)}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Valid Until (Optional)</Label>
                    <Input
                      type="date"
                      value={newValidUntil}
                      onChange={(e) => setNewValidUntil(e.target.value)}
                    />
                  </div>
                </div>
                <div className="flex justify-end">
                  <Button onClick={addDiscount} disabled={!newName.trim() || !newValue}>
                    <Plus className="h-4 w-4 mr-1" /> Add Discount
                  </Button>
                </div>
              </div>

              {/* Discounts Table */}
              <div className="space-y-3">
                <Label>Configured Discounts ({discounts.length})</Label>
                {discounts.length > 0 ? (
                  <div className="border rounded-lg overflow-hidden">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Discount Name</TableHead>
                          <TableHead>Value</TableHead>
                          <TableHead>Duration</TableHead>
                          <TableHead>Valid Period</TableHead>
                          <TableHead>Discounted Price</TableHead>
                          <TableHead className="w-[80px]">Actions</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {discounts.map((discount, index) => (
                          <TableRow key={index}>
                            <TableCell className="font-medium">{discount.name}</TableCell>
                            <TableCell>
                              {discount.type === 'percentage'
                                ? `${discount.value}%`
                                : `₹${discount.value}`
                              }
                            </TableCell>
                            <TableCell>
                              {discount.is_permanent ? (
                                <Badge>Permanent</Badge>
                              ) : discount.duration_months ? (
                                `${discount.duration_months} months`
                              ) : (
                                'Not set'
                              )}
                            </TableCell>
                            <TableCell className="text-xs">
                              {discount.valid_from || discount.valid_until ? (
                                <>
                                  {discount.valid_from && formatDateForInput(discount.valid_from)}
                                  {discount.valid_from && discount.valid_until && ' → '}
                                  {discount.valid_until && formatDateForInput(discount.valid_until)}
                                </>
                              ) : (
                                <span className="text-muted-foreground">No date limit</span>
                              )}
                            </TableCell>
                            <TableCell>
                              <span className="line-through text-muted-foreground">
                                ₹{monthlyAmount}
                              </span>
                              {' → '}
                              <span className="font-semibold text-green-600">
                                ₹{calculateDiscountedAmount(discount).toFixed(2)}
                              </span>
                            </TableCell>
                            <TableCell>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => removeDiscount(index)}
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
                      No discounts configured. Add discounts using the form above or quick presets.
                    </AlertDescription>
                  </Alert>
                )}
              </div>
            </>
          )}
        </div>

        <div className="flex gap-2 pt-4">
          <Button variant="outline" onClick={() => onOpenChange(false)} className="flex-1" disabled={isSaving}>
            Cancel
          </Button>
          <Button onClick={handleSave} className="flex-1" disabled={isSaving}>
            {isSaving ? 'Saving...' : 'Save Discount Config'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
