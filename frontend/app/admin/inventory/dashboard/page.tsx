// V-BULK-PURCHASE-002 | Inventory Dashboard & Analytics
// Created: 2025-12-10 | Purpose: Product-wise inventory tracking and reorder suggestions

'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from 'recharts';
import {
  Package,
  AlertTriangle,
  TrendingUp,
  ShoppingCart,
  Bell,
  Settings,
  Calendar,
} from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

interface ProductInventory {
  product_id: number;
  product_name: string;
  total_inventory: string;
  allocated: string;
  available: string;
  allocation_percentage: number;
  purchase_count: number;
  average_daily_allocation: string;
  days_remaining: number;
  reorder_suggestion: boolean;
  low_stock_alert: boolean;
}

interface AllocationTrend {
  date: string;
  allocated: number;
  product_name: string;
}

interface LowStockConfig {
  threshold_percentage: number;
  days_remaining_threshold: number;
  enable_email_alerts: boolean;
  enable_system_alerts: boolean;
}

const COLORS = ['#667eea', '#22c55e', '#ef4444', '#f59e0b', '#3b82f6'];

export default function InventoryDashboardPage() {
  const queryClient = useQueryClient();
  const [configDialogOpen, setConfigDialogOpen] = useState(false);
  const [selectedPeriod, setSelectedPeriod] = useState('30');

  const [alertConfig, setAlertConfig] = useState<LowStockConfig>({
    threshold_percentage: 20,
    days_remaining_threshold: 30,
    enable_email_alerts: true,
    enable_system_alerts: true,
  });

  // Fetch inventory summary
  const { data: inventory = [], isLoading } = useQuery<ProductInventory[]>({
    queryKey: ['inventorySummary'],
    queryFn: async () => (await api.get('/admin/inventory/summary')).data.data,
  });

  // Fetch allocation trends
  const { data: trends = [] } = useQuery<AllocationTrend[]>({
    queryKey: ['allocationTrends', selectedPeriod],
    queryFn: async () =>
      (await api.get(`/admin/inventory/trends?days=${selectedPeriod}`)).data.data,
  });

  // Fetch low stock config
  const { data: configData } = useQuery({
    queryKey: ['lowStockConfig'],
    queryFn: async () => {
      const res = await api.get('/admin/inventory/low-stock-config');
      setAlertConfig(res.data.config || alertConfig);
      return res.data;
    },
  });

  // Save config mutation
  const saveConfigMutation = useMutation({
    mutationFn: (config: LowStockConfig) =>
      api.post('/admin/inventory/low-stock-config', { config }),
    onSuccess: () => {
      toast.success('Low stock alert configuration saved');
      queryClient.invalidateQueries({ queryKey: ['lowStockConfig'] });
      setConfigDialogOpen(false);
    },
  });

  const handleSaveConfig = () => {
    saveConfigMutation.mutate(alertConfig);
  };

  if (isLoading) {
    return <div className="p-8">Loading inventory dashboard...</div>;
  }

  const lowStockProducts = inventory.filter((p) => p.low_stock_alert);
  const reorderSuggestions = inventory.filter((p) => p.reorder_suggestion);

  const totalInventoryValue = inventory.reduce(
    (sum, p) => sum + parseFloat(p.total_inventory),
    0
  );
  const totalAllocated = inventory.reduce((sum, p) => sum + parseFloat(p.allocated), 0);
  const totalAvailable = inventory.reduce((sum, p) => sum + parseFloat(p.available), 0);

  const statusDistribution = [
    {
      name: 'Available',
      value: totalAvailable,
      color: '#22c55e',
    },
    {
      name: 'Allocated',
      value: totalAllocated,
      color: '#667eea',
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Inventory Dashboard</h1>
          <p className="text-muted-foreground mt-1">
            Real-time inventory tracking and reorder management
          </p>
        </div>
        <Button onClick={() => setConfigDialogOpen(true)}>
          <Settings className="mr-2 h-4 w-4" />
          Alert Settings
        </Button>
      </div>

      {/* Alert Banners */}
      {lowStockProducts.length > 0 && (
        <Card className="border-yellow-500 bg-yellow-50 dark:bg-yellow-950/10">
          <CardContent className="pt-6">
            <div className="flex items-start gap-3">
              <AlertTriangle className="h-5 w-5 text-yellow-600 mt-0.5" />
              <div className="flex-1">
                <h3 className="font-semibold text-yellow-900 dark:text-yellow-100">
                  Low Stock Alert
                </h3>
                <p className="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                  {lowStockProducts.length} product(s) running low on inventory
                </p>
                <div className="flex gap-2 mt-2 flex-wrap">
                  {lowStockProducts.map((p) => (
                    <Badge key={p.product_id} variant="outline" className="border-yellow-600">
                      {p.product_name}
                    </Badge>
                  ))}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Inventory</p>
                <p className="text-3xl font-bold">
                  ₹{totalInventoryValue.toLocaleString('en-IN', { maximumFractionDigits: 0 })}
                </p>
              </div>
              <Package className="h-10 w-10 text-primary opacity-20" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Available</p>
                <p className="text-3xl font-bold text-green-600">
                  ₹{totalAvailable.toLocaleString('en-IN', { maximumFractionDigits: 0 })}
                </p>
              </div>
              <TrendingUp className="h-10 w-10 text-green-600 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Low Stock Alerts</p>
                <p className="text-3xl font-bold text-yellow-600">{lowStockProducts.length}</p>
              </div>
              <AlertTriangle className="h-10 w-10 text-yellow-600 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Reorder Suggestions</p>
                <p className="text-3xl font-bold text-blue-600">{reorderSuggestions.length}</p>
              </div>
              <ShoppingCart className="h-10 w-10 text-blue-600 opacity-20" />
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="products" className="space-y-4">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="products">
            <Package className="mr-2 h-4 w-4" />
            Product Inventory
          </TabsTrigger>
          <TabsTrigger value="trends">
            <TrendingUp className="mr-2 h-4 w-4" />
            Allocation Trends
          </TabsTrigger>
          <TabsTrigger value="reorder">
            <ShoppingCart className="mr-2 h-4 w-4" />
            Reorder Suggestions
          </TabsTrigger>
        </TabsList>

        {/* Product Inventory Tab */}
        <TabsContent value="products" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Product-wise Inventory Status</CardTitle>
              <CardDescription>Real-time inventory levels per product</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>Total Inventory</TableHead>
                    <TableHead>Allocation Status</TableHead>
                    <TableHead>Available</TableHead>
                    <TableHead>Avg Daily Usage</TableHead>
                    <TableHead>Days Remaining</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {inventory.map((item) => (
                    <TableRow key={item.product_id}>
                      <TableCell>
                        <div className="font-medium">{item.product_name}</div>
                        <div className="text-xs text-muted-foreground">
                          {item.purchase_count} purchase(s)
                        </div>
                      </TableCell>
                      <TableCell>
                        ₹{parseFloat(item.total_inventory).toLocaleString('en-IN')}
                      </TableCell>
                      <TableCell>
                        <div className="space-y-2">
                          <Progress value={item.allocation_percentage} className="h-2" />
                          <div className="text-xs">
                            {item.allocation_percentage.toFixed(1)}% allocated
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="font-medium text-green-600">
                        ₹{parseFloat(item.available).toLocaleString('en-IN')}
                      </TableCell>
                      <TableCell>
                        ₹{parseFloat(item.average_daily_allocation).toLocaleString('en-IN')}
                      </TableCell>
                      <TableCell>
                        <Badge
                          variant={item.days_remaining < 30 ? 'destructive' : 'outline'}
                          className="font-mono"
                        >
                          {item.days_remaining > 365
                            ? '365+'
                            : item.days_remaining < 0
                            ? '0'
                            : item.days_remaining}{' '}
                          days
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-col gap-1">
                          {item.low_stock_alert && (
                            <Badge variant="destructive" className="w-fit">
                              <AlertTriangle className="mr-1 h-3 w-3" />
                              Low Stock
                            </Badge>
                          )}
                          {item.reorder_suggestion && (
                            <Badge variant="outline" className="w-fit">
                              <ShoppingCart className="mr-1 h-3 w-3" />
                              Reorder
                            </Badge>
                          )}
                          {!item.low_stock_alert && !item.reorder_suggestion && (
                            <Badge variant="default" className="w-fit">
                              Healthy
                            </Badge>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {inventory.length === 0 && (
                <div className="text-center py-12 text-muted-foreground">
                  No inventory data available
                </div>
              )}
            </CardContent>
          </Card>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card>
              <CardHeader>
                <CardTitle>Inventory Distribution</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={statusDistribution}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, value }) =>
                        `${name}: ₹${value.toLocaleString('en-IN', {
                          maximumFractionDigits: 0,
                        })}`
                      }
                      outerRadius={80}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {statusDistribution.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Allocation by Product</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart data={inventory.slice(0, 5)}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="product_name" angle={-45} textAnchor="end" height={100} />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="allocation_percentage" fill="#667eea" name="Allocated %" />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Allocation Trends Tab */}
        <TabsContent value="trends" className="space-y-4">
          <Card>
            <CardHeader>
              <div className="flex justify-between items-center">
                <div>
                  <CardTitle>Allocation Trends</CardTitle>
                  <CardDescription>Daily allocation patterns over time</CardDescription>
                </div>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant={selectedPeriod === '7' ? 'default' : 'outline'}
                    onClick={() => setSelectedPeriod('7')}
                  >
                    7D
                  </Button>
                  <Button
                    size="sm"
                    variant={selectedPeriod === '30' ? 'default' : 'outline'}
                    onClick={() => setSelectedPeriod('30')}
                  >
                    30D
                  </Button>
                  <Button
                    size="sm"
                    variant={selectedPeriod === '90' ? 'default' : 'outline'}
                    onClick={() => setSelectedPeriod('90')}
                  >
                    90D
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={400}>
                <LineChart data={trends}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="date" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Line
                    type="monotone"
                    dataKey="allocated"
                    stroke="#667eea"
                    name="Allocated Amount (₹)"
                    strokeWidth={2}
                  />
                </LineChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Reorder Suggestions Tab */}
        <TabsContent value="reorder" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Reorder Suggestions</CardTitle>
              <CardDescription>
                Products that need restocking based on allocation rate
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>Available Inventory</TableHead>
                    <TableHead>Daily Usage Rate</TableHead>
                    <TableHead>Days Remaining</TableHead>
                    <TableHead>Suggested Reorder Amount</TableHead>
                    <TableHead>Priority</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {reorderSuggestions.map((item) => {
                    const suggestedAmount =
                      parseFloat(item.average_daily_allocation) * 90; // 90 days supply
                    const priority =
                      item.days_remaining < 15
                        ? 'High'
                        : item.days_remaining < 30
                        ? 'Medium'
                        : 'Low';

                    return (
                      <TableRow key={item.product_id}>
                        <TableCell className="font-medium">{item.product_name}</TableCell>
                        <TableCell>
                          ₹{parseFloat(item.available).toLocaleString('en-IN')}
                        </TableCell>
                        <TableCell>
                          ₹{parseFloat(item.average_daily_allocation).toLocaleString('en-IN')}/day
                        </TableCell>
                        <TableCell>
                          <Badge variant={item.days_remaining < 15 ? 'destructive' : 'outline'}>
                            {item.days_remaining} days
                          </Badge>
                        </TableCell>
                        <TableCell className="font-medium text-blue-600">
                          ₹{suggestedAmount.toLocaleString('en-IN', { maximumFractionDigits: 0 })}
                        </TableCell>
                        <TableCell>
                          <Badge
                            variant={
                              priority === 'High'
                                ? 'destructive'
                                : priority === 'Medium'
                                ? 'outline'
                                : 'secondary'
                            }
                          >
                            {priority}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    );
                  })}
                </TableBody>
              </Table>

              {reorderSuggestions.length === 0 && (
                <div className="text-center py-12 text-muted-foreground">
                  No reorder suggestions at this time. All products have sufficient inventory.
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Low Stock Alert Configuration Dialog */}
      <Dialog open={configDialogOpen} onOpenChange={setConfigDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Low Stock Alert Configuration</DialogTitle>
            <DialogDescription>
              Configure thresholds and notification settings for low stock alerts
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Stock Threshold Percentage (%)</Label>
              <Input
                type="number"
                step="1"
                min="0"
                max="100"
                value={alertConfig.threshold_percentage}
                onChange={(e) =>
                  setAlertConfig({
                    ...alertConfig,
                    threshold_percentage: parseFloat(e.target.value),
                  })
                }
              />
              <p className="text-xs text-muted-foreground">
                Alert when remaining inventory falls below this percentage
              </p>
            </div>

            <div className="space-y-2">
              <Label>Days Remaining Threshold</Label>
              <Input
                type="number"
                step="1"
                min="0"
                value={alertConfig.days_remaining_threshold}
                onChange={(e) =>
                  setAlertConfig({
                    ...alertConfig,
                    days_remaining_threshold: parseInt(e.target.value),
                  })
                }
              />
              <p className="text-xs text-muted-foreground">
                Suggest reorder when days remaining is below this value
              </p>
            </div>

            <div className="space-y-4 border-t pt-4">
              <div className="flex items-center justify-between">
                <div>
                  <Label>Email Alerts</Label>
                  <p className="text-xs text-muted-foreground">
                    Send email notifications for low stock
                  </p>
                </div>
                <Input
                  type="checkbox"
                  className="w-4 h-4"
                  checked={alertConfig.enable_email_alerts}
                  onChange={(e) =>
                    setAlertConfig({ ...alertConfig, enable_email_alerts: e.target.checked })
                  }
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <Label>System Alerts</Label>
                  <p className="text-xs text-muted-foreground">
                    Show alerts in admin dashboard
                  </p>
                </div>
                <Input
                  type="checkbox"
                  className="w-4 h-4"
                  checked={alertConfig.enable_system_alerts}
                  onChange={(e) =>
                    setAlertConfig({ ...alertConfig, enable_system_alerts: e.target.checked })
                  }
                />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setConfigDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={handleSaveConfig} disabled={saveConfigMutation.isPending}>
              Save Configuration
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
