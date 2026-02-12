// Admin Bonus Management Page - Comprehensive Bonus Configuration & Monitoring
// V-ARCH-2026: Typed with canonical Plan types
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import type { AdminPlan } from "@/types/plan";

// API error type for mutations
type ApiError = Error & { response?: { data?: { message?: string } } };

// Bonus setting item from backend
interface BonusSetting {
  key: string;
  value: string | number | boolean;
}

// Bonus settings response structure
interface BonusSettingsResponse {
  settings: {
    bonus_controls?: BonusSetting[];
    bonus_config?: BonusSetting[];
    referral_config?: BonusSetting[];
    bonus_processing?: BonusSetting[];
  };
}

// Bonus transaction record
interface BonusTransaction {
  id: number;
  type: string;
  amount: number;
  tds_deducted: number | null;
  description: string;
  created_at: string;
  user?: {
    username: string;
  };
}

// Bonus stats summary
interface BonusStats {
  total_amount: number;
  total_tds: number;
  net_amount: number;
  total_count: number;
}

// Bonus data response structure
interface BonusDataResponse {
  bonuses: {
    data: BonusTransaction[];
  };
  stats?: BonusStats;
}

// Calculator test result
interface CalculatorBonus {
  type: string;
  amount: number;
  calculation: string;
}

interface CalculatorTestResponse {
  data: {
    total_bonus: number;
    bonuses: CalculatorBonus[];
  };
}

// Calculator input for test mutation
interface CalculatorInput {
  payment_amount: string;
  payment_month: number;
  is_on_time: boolean;
  plan_id: string;
  bonus_multiplier: number;
  consecutive_payments: number;
}

// Bonus type color map
const BONUS_TYPE_COLORS: Record<string, string> = {
  'loyalty_bonus': 'bg-blue-500',
  'milestone_bonus': 'bg-purple-500',
  'cashback': 'bg-green-500',
  'welcome_bonus': 'bg-yellow-500',
  'referral_bonus': 'bg-pink-500',
  'celebration': 'bg-orange-500',
  'lucky_draw': 'bg-indigo-500',
  'profit_share': 'bg-teal-500',
  'special_bonus': 'bg-red-500',
  'reversal': 'bg-gray-500',
};
import {
  Download,
  Upload,
  Filter,
  RefreshCw,
  Calculator,
  FileDown,
  Undo2,
  Save,
  Search,
  AlertCircle
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";

export default function BonusManagementPage() {
  const queryClient = useQueryClient();

  // State for filters
  const [filters, setFilters] = useState({
    type: 'all',
    user_id: '',
    date_from: '',
    date_to: '',
    amount_min: '',
    amount_max: '',
    search: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 25,
  });

  // State for reversal dialog
  const [reversalDialog, setReversalDialog] = useState<{ open: boolean; bonusId: number | null; reason: string }>({
    open: false,
    bonusId: null,
    reason: '',
  });

  // State for CSV upload
  const [csvFile, setCsvFile] = useState<File | null>(null);

  // State for bonus calculator
  const [calculator, setCalculator] = useState({
    payment_amount: '',
    payment_month: 1,
    is_on_time: true,
    plan_id: '',
    bonus_multiplier: 1.0,
    consecutive_payments: 1,
  });

  // Fetch bonus settings
  const { data: settings, isLoading: settingsLoading } = useQuery<BonusSettingsResponse>({
    queryKey: ['bonusSettings'],
    queryFn: async () => (await api.get('/admin/bonuses/settings')).data,
  });

  // Fetch bonus transactions
  const { data: bonusData, isLoading: bonusesLoading, refetch } = useQuery<BonusDataResponse>({
    queryKey: ['adminBonuses', filters],
    queryFn: async () => {
      const params = new URLSearchParams();
      Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value.toString());
      });
      return (await api.get(`/admin/bonuses?${params}`)).data;
    },
  });

  // Fetch plans for calculator
  const { data: plans } = useQuery<AdminPlan[]>({
    queryKey: ['adminPlans'],
    queryFn: async () => (await api.get('/admin/plans')).data,
  });

  // Update settings mutation
  const updateSettingsMutation = useMutation({
    mutationFn: (settingsData: Array<{ key: string; value: string | number | boolean }>) =>
      api.put('/admin/bonuses/settings', { settings: settingsData }),
    onSuccess: () => {
      toast.success("Settings updated successfully!");
      queryClient.invalidateQueries({ queryKey: ['bonusSettings'] });
    },
    onError: (error: ApiError) => {
      toast.error("Failed to update settings", { description: error.response?.data?.message });
    }
  });

  // Reverse bonus mutation
  const reverseBonusMutation = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      api.post(`/admin/bonuses/${id}/reverse`, { reason }),
    onSuccess: () => {
      toast.success("Bonus reversed successfully!");
      setReversalDialog({ open: false, bonusId: null, reason: '' });
      refetch();
    },
    onError: (error: ApiError) => {
      toast.error("Failed to reverse bonus", { description: error.response?.data?.message });
    }
  });

  // CSV upload mutation
  const csvUploadMutation = useMutation({
    mutationFn: (formData: FormData) => api.post('/admin/bonuses/upload-csv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
    onSuccess: (response) => {
      toast.success(`Bulk bonuses processed!`, {
        description: `${response.data.awarded_count} successful, ${response.data.failed_count} failed`
      });
      setCsvFile(null);
      refetch();
    },
    onError: (error: ApiError) => {
      toast.error("Failed to upload CSV", { description: error.response?.data?.message });
    }
  });

  // Calculate test mutation
  const calculateTestMutation = useMutation<CalculatorTestResponse, ApiError, CalculatorInput>({
    mutationFn: (data: CalculatorInput) => api.post('/admin/bonuses/calculate-test', data),
  });

  const handleSettingChange = (key: string, value: string | number | boolean) => {
    const settingsArray = Object.values(settings?.settings || {})
      .flat()
      .filter((s): s is BonusSetting => s !== undefined)
      .map((s: BonusSetting) => ({ key: s.key, value: s.key === key ? value : s.value }));

    updateSettingsMutation.mutate(settingsArray);
  };

  const handleCsvUpload = () => {
    if (!csvFile) {
      toast.error("Please select a CSV file");
      return;
    }

    const formData = new FormData();
    formData.append('csv_file', csvFile);
    csvUploadMutation.mutate(formData);
  };

  const handleCalculateTest = () => {
    if (!calculator.payment_amount || !calculator.plan_id) {
      toast.error("Please fill in payment amount and plan");
      return;
    }

    calculateTestMutation.mutate(calculator);
  };

  const getBonusTypeColor = (type: string): string => {
    return BONUS_TYPE_COLORS[type] || 'bg-gray-400';
  };

  return (
    <div className="space-y-6 p-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Bonus Management</h1>
        <Button onClick={() => refetch()} variant="outline">
          <RefreshCw className="mr-2 h-4 w-4" />
          Refresh
        </Button>
      </div>

      <Tabs defaultValue="transactions" className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="transactions">Transactions</TabsTrigger>
          <TabsTrigger value="settings">Global Settings</TabsTrigger>
          <TabsTrigger value="bulk">Bulk Operations</TabsTrigger>
          <TabsTrigger value="calculator">Calculator</TabsTrigger>
        </TabsList>

        {/* Transactions Tab */}
        <TabsContent value="transactions">
          <Card>
            <CardHeader>
              <CardTitle>Bonus Transactions</CardTitle>
              <CardDescription>View and manage all bonus transactions with advanced filters</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Filters */}
              <div className="grid grid-cols-4 gap-4 p-4 bg-muted rounded-lg">
                <div className="space-y-2">
                  <Label>Type</Label>
                  <Select value={filters.type} onValueChange={(v) => setFilters({ ...filters, type: v })}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Types</SelectItem>
                      <SelectItem value="loyalty_bonus">Progressive</SelectItem>
                      <SelectItem value="milestone_bonus">Milestone</SelectItem>
                      <SelectItem value="cashback">Consistency</SelectItem>
                      <SelectItem value="welcome_bonus">Welcome</SelectItem>
                      <SelectItem value="referral_bonus">Referral</SelectItem>
                      <SelectItem value="celebration">Celebration</SelectItem>
                      <SelectItem value="special_bonus">Special</SelectItem>
                      <SelectItem value="reversal">Reversal</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Date From</Label>
                  <Input
                    type="date"
                    value={filters.date_from}
                    onChange={(e) => setFilters({ ...filters, date_from: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Date To</Label>
                  <Input
                    type="date"
                    value={filters.date_to}
                    onChange={(e) => setFilters({ ...filters, date_to: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Search</Label>
                  <Input
                    placeholder="Search description..."
                    value={filters.search}
                    onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                  />
                </div>
              </div>

              {/* Stats Summary */}
              {bonusData?.stats && (
                <div className="grid grid-cols-4 gap-4">
                  <Card>
                    <CardContent className="pt-6">
                      <div className="text-2xl font-bold">₹{bonusData.stats.total_amount?.toLocaleString() || 0}</div>
                      <p className="text-xs text-muted-foreground">Total Amount</p>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent className="pt-6">
                      <div className="text-2xl font-bold">₹{bonusData.stats.total_tds?.toLocaleString() || 0}</div>
                      <p className="text-xs text-muted-foreground">Total TDS</p>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent className="pt-6">
                      <div className="text-2xl font-bold">₹{bonusData.stats.net_amount?.toLocaleString() || 0}</div>
                      <p className="text-xs text-muted-foreground">Net Amount</p>
                    </CardContent>
                  </Card>
                  <Card>
                    <CardContent className="pt-6">
                      <div className="text-2xl font-bold">{bonusData.stats.total_count || 0}</div>
                      <p className="text-xs text-muted-foreground">Total Count</p>
                    </CardContent>
                  </Card>
                </div>
              )}

              {/* Transactions Table */}
              <div className="border rounded-lg">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>ID</TableHead>
                      <TableHead>User</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Amount</TableHead>
                      <TableHead>TDS</TableHead>
                      <TableHead>Net</TableHead>
                      <TableHead>Description</TableHead>
                      <TableHead>Date</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {bonusesLoading ? (
                      <TableRow>
                        <TableCell colSpan={9} className="text-center">Loading...</TableCell>
                      </TableRow>
                    ) : bonusData?.bonuses?.data?.length > 0 ? (
                      bonusData.bonuses.data.map((bonus: BonusTransaction) => (
                        <TableRow key={bonus.id}>
                          <TableCell className="font-medium">{bonus.id}</TableCell>
                          <TableCell>{bonus.user?.username || 'N/A'}</TableCell>
                          <TableCell>
                            <Badge className={getBonusTypeColor(bonus.type)}>
                              {bonus.type}
                            </Badge>
                          </TableCell>
                          <TableCell>₹{bonus.amount}</TableCell>
                          <TableCell>₹{bonus.tds_deducted || 0}</TableCell>
                          <TableCell className="font-semibold">₹{(bonus.amount - (bonus.tds_deducted || 0)).toFixed(2)}</TableCell>
                          <TableCell className="max-w-xs truncate">{bonus.description}</TableCell>
                          <TableCell>{new Date(bonus.created_at).toLocaleDateString()}</TableCell>
                          <TableCell>
                            {bonus.type !== 'reversal' && (
                              <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setReversalDialog({ open: true, bonusId: bonus.id, reason: '' })}
                              >
                                <Undo2 className="h-3 w-3" />
                              </Button>
                            )}
                          </TableCell>
                        </TableRow>
                      ))
                    ) : (
                      <TableRow>
                        <TableCell colSpan={9} className="text-center">No bonuses found</TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Settings Tab */}
        <TabsContent value="settings">
          <div className="grid grid-cols-2 gap-6">
            {/* Global Controls */}
            <Card>
              <CardHeader>
                <CardTitle>Global Bonus Controls</CardTitle>
                <CardDescription>Enable or disable bonus types globally</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {settingsLoading ? (
                  <div>Loading settings...</div>
                ) : (
                  settings?.settings?.bonus_controls?.map((setting: BonusSetting) => (
                    <div key={setting.key} className="flex items-center justify-between">
                      <Label htmlFor={setting.key}>{setting.key.replace(/_/g, ' ').replace(/bonus/gi, '').trim()}</Label>
                      <Switch
                        id={setting.key}
                        checked={setting.value === true || setting.value === 'true'}
                        onCheckedChange={(checked) => handleSettingChange(setting.key, checked)}
                      />
                    </div>
                  ))
                )}
              </CardContent>
            </Card>

            {/* Bonus Configuration */}
            <Card>
              <CardHeader>
                <CardTitle>Bonus Configuration</CardTitle>
                <CardDescription>Configure bonus calculation settings</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {settings?.settings?.bonus_config?.map((setting: BonusSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <Label>{setting.key.replace(/_/g, ' ')}</Label>
                    {setting.key === 'bonus_rounding_mode' ? (
                      <Select
                        value={setting.value}
                        onValueChange={(v) => handleSettingChange(setting.key, v)}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="round">Round</SelectItem>
                          <SelectItem value="floor">Floor</SelectItem>
                          <SelectItem value="ceil">Ceil</SelectItem>
                        </SelectContent>
                      </Select>
                    ) : setting.key === 'bonus_allocation_source' ? (
                      <Select
                        value={setting.value}
                        onValueChange={(v) => handleSettingChange(setting.key, v)}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="company_reserves">Company Reserves</SelectItem>
                          <SelectItem value="profit_pool">Profit Pool</SelectItem>
                          <SelectItem value="marketing_budget">Marketing Budget</SelectItem>
                        </SelectContent>
                      </Select>
                    ) : (
                      <Input
                        type="number"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                      />
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>

            {/* Referral Configuration */}
            <Card>
              <CardHeader>
                <CardTitle>Referral Configuration</CardTitle>
                <CardDescription>Configure referral bonus settings</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {settings?.settings?.referral_config?.map((setting: BonusSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <Label>{setting.key.replace(/_/g, ' ')}</Label>
                    {setting.key === 'referral_completion_criteria' ? (
                      <Select
                        value={setting.value}
                        onValueChange={(v) => handleSettingChange(setting.key, v)}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="first_payment">First Payment</SelectItem>
                          <SelectItem value="nth_payment">Nth Payment</SelectItem>
                          <SelectItem value="total_amount">Total Amount</SelectItem>
                        </SelectContent>
                      </Select>
                    ) : (
                      <Input
                        type="number"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                      />
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>

            {/* Processing Configuration */}
            <Card>
              <CardHeader>
                <CardTitle>Bonus Processing</CardTitle>
                <CardDescription>Configure when bonuses are processed</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {settings?.settings?.bonus_processing?.map((setting: BonusSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <Label>{setting.key.replace(/_/g, ' ')}</Label>
                    {setting.key === 'bonus_processing_mode' ? (
                      <Select
                        value={setting.value}
                        onValueChange={(v) => handleSettingChange(setting.key, v)}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="immediate">Immediate</SelectItem>
                          <SelectItem value="daily">Daily</SelectItem>
                          <SelectItem value="weekly">Weekly</SelectItem>
                          <SelectItem value="monthly">Monthly</SelectItem>
                        </SelectContent>
                      </Select>
                    ) : (
                      <Input
                        type="time"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                      />
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Bulk Operations Tab */}
        <TabsContent value="bulk">
          <Card>
            <CardHeader>
              <CardTitle>Bulk Bonus Processing</CardTitle>
              <CardDescription>Upload CSV file to award bonuses to multiple users</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="border-2 border-dashed rounded-lg p-8 text-center">
                <Input
                  type="file"
                  accept=".csv"
                  onChange={(e) => setCsvFile(e.target.files?.[0] || null)}
                  className="max-w-md mx-auto"
                />
                <p className="text-sm text-muted-foreground mt-4">
                  CSV format: <code>user_id,amount,reason</code>
                </p>
                <p className="text-xs text-muted-foreground mt-2">
                  Example: <code>1,1000,Birthday Bonus</code>
                </p>
              </div>

              {csvFile && (
                <div className="flex items-center justify-between p-4 bg-muted rounded-lg">
                  <span className="text-sm">Selected: {csvFile.name}</span>
                  <Button onClick={handleCsvUpload} disabled={csvUploadMutation.isPending}>
                    <Upload className="mr-2 h-4 w-4" />
                    {csvUploadMutation.isPending ? "Uploading..." : "Upload & Process"}
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Calculator Tab */}
        <TabsContent value="calculator">
          <Card>
            <CardHeader>
              <CardTitle>Bonus Testing Calculator</CardTitle>
              <CardDescription>Test bonus calculations for hypothetical scenarios</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>Payment Amount</Label>
                  <Input
                    type="number"
                    placeholder="10000"
                    value={calculator.payment_amount}
                    onChange={(e) => setCalculator({ ...calculator, payment_amount: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Payment Month</Label>
                  <Input
                    type="number"
                    min="1"
                    value={calculator.payment_month}
                    onChange={(e) => setCalculator({ ...calculator, payment_month: parseInt(e.target.value) })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Plan</Label>
                  <Select value={calculator.plan_id} onValueChange={(v) => setCalculator({ ...calculator, plan_id: v })}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select plan" />
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
                <div className="space-y-2">
                  <Label>Bonus Multiplier</Label>
                  <Input
                    type="number"
                    step="0.1"
                    value={calculator.bonus_multiplier}
                    onChange={(e) => setCalculator({ ...calculator, bonus_multiplier: parseFloat(e.target.value) })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Consecutive Payments</Label>
                  <Input
                    type="number"
                    min="0"
                    value={calculator.consecutive_payments}
                    onChange={(e) => setCalculator({ ...calculator, consecutive_payments: parseInt(e.target.value) })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>On-Time Payment</Label>
                  <Switch
                    checked={calculator.is_on_time}
                    onCheckedChange={(checked) => setCalculator({ ...calculator, is_on_time: checked })}
                  />
                </div>
              </div>

              <Button onClick={handleCalculateTest} disabled={calculateTestMutation.isPending}>
                <Calculator className="mr-2 h-4 w-4" />
                {calculateTestMutation.isPending ? "Calculating..." : "Calculate"}
              </Button>

              {calculateTestMutation.data && (
                <div className="mt-6 space-y-4">
                  <div className="p-4 bg-primary/10 rounded-lg">
                    <h3 className="text-lg font-semibold">Total Bonus: ₹{calculateTestMutation.data.data.total_bonus}</h3>
                  </div>

                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Type</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Calculation</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {calculateTestMutation.data.data.bonuses.map((bonus: CalculatorBonus, idx: number) => (
                        <TableRow key={idx}>
                          <TableCell>
                            <Badge className={getBonusTypeColor(bonus.type)}>
                              {bonus.type}
                            </Badge>
                          </TableCell>
                          <TableCell className="font-semibold">₹{bonus.amount}</TableCell>
                          <TableCell className="text-sm text-muted-foreground">{bonus.calculation}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Reversal Dialog */}
      <Dialog open={reversalDialog.open} onOpenChange={(open) => setReversalDialog({ ...reversalDialog, open })}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reverse Bonus</DialogTitle>
            <DialogDescription>
              This will create a reversal transaction. Please provide a reason.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label>Reason for Reversal</Label>
              <Textarea
                placeholder="e.g., Incorrect calculation, duplicate bonus..."
                value={reversalDialog.reason}
                onChange={(e) => setReversalDialog({ ...reversalDialog, reason: e.target.value })}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setReversalDialog({ open: false, bonusId: null, reason: '' })}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={() => {
                if (reversalDialog.bonusId && reversalDialog.reason) {
                  reverseBonusMutation.mutate({ id: reversalDialog.bonusId, reason: reversalDialog.reason });
                }
              }}
              disabled={!reversalDialog.reason || reverseBonusMutation.isPending}
            >
              <Undo2 className="mr-2 h-4 w-4" />
              {reverseBonusMutation.isPending ? "Reversing..." : "Reverse Bonus"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
