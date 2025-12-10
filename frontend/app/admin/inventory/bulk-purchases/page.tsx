// V-BULK-PURCHASE-001 | Bulk Purchase Management System
// Created: 2025-12-10 | Purpose: Comprehensive bulk purchase and inventory management

'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Plus,
  Pencil,
  Trash2,
  Package,
  TrendingDown,
  AlertTriangle,
  CheckCircle,
  History,
  ArrowRight,
} from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

interface Product {
  id: number;
  name: string;
  face_value_per_unit: number;
  status: string;
}

interface BulkPurchase {
  id: number;
  product_id: number;
  product: Product;
  admin_id: number;
  face_value_purchased: string;
  actual_cost_paid: string;
  discount_percentage: string;
  extra_allocation_percentage: string;
  total_value_received: string;
  value_remaining: string;
  seller_name?: string;
  purchase_date: string;
  notes?: string;
  allocated_amount?: string;
  gross_margin?: string;
  gross_margin_percentage?: string;
  created_at: string;
}

export default function BulkPurchasePage() {
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingPurchase, setEditingPurchase] = useState<BulkPurchase | null>(null);
  const [manualAllocDialogOpen, setManualAllocDialogOpen] = useState(false);
  const [selectedPurchaseForAlloc, setSelectedPurchaseForAlloc] = useState<BulkPurchase | null>(null);

  const [purchaseForm, setPurchaseForm] = useState({
    product_id: '',
    face_value_purchased: '',
    actual_cost_paid: '',
    extra_allocation_percentage: '0',
    seller_name: '',
    purchase_date: new Date().toISOString().split('T')[0],
    notes: '',
  });

  const [manualAllocForm, setManualAllocForm] = useState({
    user_id: '',
    amount: '',
    notes: '',
  });

  // Fetch products
  const { data: products = [] } = useQuery<Product[]>({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/admin/products')).data.data,
  });

  // Fetch bulk purchases
  const { data: purchases = [], isLoading } = useQuery<BulkPurchase[]>({
    queryKey: ['bulkPurchases'],
    queryFn: async () => (await api.get('/admin/bulk-purchases')).data.data,
  });

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/bulk-purchases', data),
    onSuccess: () => {
      toast.success('Bulk purchase created successfully');
      queryClient.invalidateQueries({ queryKey: ['bulkPurchases'] });
      queryClient.invalidateQueries({ queryKey: ['inventory'] });
      setDialogOpen(false);
      resetForm();
    },
    onError: (error: any) => {
      toast.error('Failed to create bulk purchase', {
        description: error.response?.data?.message,
      });
    },
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      api.put(`/admin/bulk-purchases/${id}`, data),
    onSuccess: () => {
      toast.success('Bulk purchase updated successfully');
      queryClient.invalidateQueries({ queryKey: ['bulkPurchases'] });
      setDialogOpen(false);
      resetForm();
    },
    onError: (error: any) => {
      toast.error('Failed to update bulk purchase', {
        description: error.response?.data?.message,
      });
    },
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/bulk-purchases/${id}`),
    onSuccess: () => {
      toast.success('Bulk purchase deleted successfully');
      queryClient.invalidateQueries({ queryKey: ['bulkPurchases'] });
    },
  });

  // Manual allocation mutation
  const manualAllocMutation = useMutation({
    mutationFn: ({ purchaseId, data }: { purchaseId: number; data: any }) =>
      api.post(`/admin/bulk-purchases/${purchaseId}/manual-allocate`, data),
    onSuccess: () => {
      toast.success('Manual allocation successful');
      queryClient.invalidateQueries({ queryKey: ['bulkPurchases'] });
      queryClient.invalidateQueries({ queryKey: ['allocationHistory'] });
      setManualAllocDialogOpen(false);
      setManualAllocForm({ user_id: '', amount: '', notes: '' });
    },
    onError: (error: any) => {
      toast.error('Manual allocation failed', {
        description: error.response?.data?.message,
      });
    },
  });

  const resetForm = () => {
    setPurchaseForm({
      product_id: '',
      face_value_purchased: '',
      actual_cost_paid: '',
      extra_allocation_percentage: '0',
      seller_name: '',
      purchase_date: new Date().toISOString().split('T')[0],
      notes: '',
    });
    setEditingPurchase(null);
  };

  const handleSubmit = () => {
    if (editingPurchase) {
      updateMutation.mutate({ id: editingPurchase.id, data: purchaseForm });
    } else {
      createMutation.mutate(purchaseForm);
    }
  };

  const openEditDialog = (purchase: BulkPurchase) => {
    setEditingPurchase(purchase);
    setPurchaseForm({
      product_id: purchase.product_id.toString(),
      face_value_purchased: purchase.face_value_purchased,
      actual_cost_paid: purchase.actual_cost_paid,
      extra_allocation_percentage: purchase.extra_allocation_percentage,
      seller_name: purchase.seller_name || '',
      purchase_date: purchase.purchase_date,
      notes: purchase.notes || '',
    });
    setDialogOpen(true);
  };

  const openNewDialog = () => {
    resetForm();
    setDialogOpen(true);
  };

  const openManualAllocation = (purchase: BulkPurchase) => {
    setSelectedPurchaseForAlloc(purchase);
    setManualAllocDialogOpen(true);
  };

  const handleManualAllocation = () => {
    if (!selectedPurchaseForAlloc) return;
    manualAllocMutation.mutate({
      purchaseId: selectedPurchaseForAlloc.id,
      data: manualAllocForm,
    });
  };

  const calculateAllocationPercentage = (purchase: BulkPurchase) => {
    const total = parseFloat(purchase.total_value_received);
    const remaining = parseFloat(purchase.value_remaining);
    if (total === 0) return 0;
    return ((total - remaining) / total) * 100;
  };

  const getStockStatus = (purchase: BulkPurchase) => {
    const percentage = calculateAllocationPercentage(purchase);
    if (percentage >= 90)
      return { label: 'Critical', variant: 'destructive' as const, icon: AlertTriangle };
    if (percentage >= 70)
      return { label: 'Low', variant: 'outline' as const, icon: TrendingDown };
    return { label: 'Available', variant: 'default' as const, icon: CheckCircle };
  };

  if (isLoading) {
    return <div className="p-8">Loading bulk purchases...</div>;
  }

  const totalInventoryValue = purchases.reduce(
    (sum, p) => sum + parseFloat(p.value_remaining),
    0
  );
  const totalPurchased = purchases.reduce(
    (sum, p) => sum + parseFloat(p.total_value_received),
    0
  );
  const totalAllocated = totalPurchased - totalInventoryValue;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Bulk Purchase Management</h1>
          <p className="text-muted-foreground mt-1">
            Manage bulk purchases, inventory, and allocations
          </p>
        </div>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button onClick={openNewDialog}>
              <Plus className="mr-2 h-4 w-4" />
              Add Bulk Purchase
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>
                {editingPurchase ? 'Edit Bulk Purchase' : 'New Bulk Purchase'}
              </DialogTitle>
              <DialogDescription>
                Record a new bulk purchase or edit existing purchase details
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 max-h-[60vh] overflow-y-auto">
              <div className="space-y-2">
                <Label>Product</Label>
                <Select value={purchaseForm.product_id} onValueChange={(value) => setPurchaseForm({ ...purchaseForm, product_id: value })}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select product" />
                  </SelectTrigger>
                  <SelectContent>
                    {products.map((product) => (
                      <SelectItem key={product.id} value={product.id.toString()}>
                        {product.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Face Value Purchased (₹)</Label>
                  <Input
                    type="number"
                    step="0.01"
                    value={purchaseForm.face_value_purchased}
                    onChange={(e) =>
                      setPurchaseForm({ ...purchaseForm, face_value_purchased: e.target.value })
                    }
                    placeholder="1000000"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Actual Cost Paid (₹)</Label>
                  <Input
                    type="number"
                    step="0.01"
                    value={purchaseForm.actual_cost_paid}
                    onChange={(e) =>
                      setPurchaseForm({ ...purchaseForm, actual_cost_paid: e.target.value })
                    }
                    placeholder="950000"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Extra Allocation Percentage (%)</Label>
                <Input
                  type="number"
                  step="0.01"
                  value={purchaseForm.extra_allocation_percentage}
                  onChange={(e) =>
                    setPurchaseForm({
                      ...purchaseForm,
                      extra_allocation_percentage: e.target.value,
                    })
                  }
                  placeholder="5"
                />
                <p className="text-xs text-muted-foreground">
                  Bonus allocation percentage (e.g., 5% extra shares from seller)
                </p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Seller Name</Label>
                  <Input
                    value={purchaseForm.seller_name}
                    onChange={(e) =>
                      setPurchaseForm({ ...purchaseForm, seller_name: e.target.value })
                    }
                    placeholder="ABC Ventures Ltd"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Purchase Date</Label>
                  <Input
                    type="date"
                    value={purchaseForm.purchase_date}
                    onChange={(e) =>
                      setPurchaseForm({ ...purchaseForm, purchase_date: e.target.value })
                    }
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Notes (Optional)</Label>
                <Textarea
                  value={purchaseForm.notes}
                  onChange={(e) => setPurchaseForm({ ...purchaseForm, notes: e.target.value })}
                  rows={3}
                  placeholder="Additional purchase details..."
                />
              </div>

              {purchaseForm.face_value_purchased && purchaseForm.actual_cost_paid && (
                <div className="p-4 bg-muted rounded-lg space-y-2">
                  <h4 className="font-semibold">Calculated Values:</h4>
                  <div className="grid grid-cols-2 gap-2 text-sm">
                    <div>
                      <span className="text-muted-foreground">Discount:</span>
                      <span className="ml-2 font-medium">
                        {(
                          ((parseFloat(purchaseForm.face_value_purchased) -
                            parseFloat(purchaseForm.actual_cost_paid)) /
                            parseFloat(purchaseForm.face_value_purchased)) *
                          100
                        ).toFixed(2)}
                        %
                      </span>
                    </div>
                    <div>
                      <span className="text-muted-foreground">Total Value Received:</span>
                      <span className="ml-2 font-medium">
                        ₹
                        {(
                          parseFloat(purchaseForm.face_value_purchased) *
                          (1 + parseFloat(purchaseForm.extra_allocation_percentage || '0') / 100)
                        ).toLocaleString('en-IN', { maximumFractionDigits: 2 })}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setDialogOpen(false)}>
                Cancel
              </Button>
              <Button
                onClick={handleSubmit}
                disabled={createMutation.isPending || updateMutation.isPending}
              >
                {editingPurchase ? 'Update' : 'Create'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Inventory Value</p>
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
                <p className="text-sm text-muted-foreground">Total Allocated</p>
                <p className="text-3xl font-bold text-green-600">
                  ₹{totalAllocated.toLocaleString('en-IN', { maximumFractionDigits: 0 })}
                </p>
              </div>
              <CheckCircle className="h-10 w-10 text-green-600 opacity-20" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Allocation Rate</p>
                <p className="text-3xl font-bold">
                  {totalPurchased > 0 ? ((totalAllocated / totalPurchased) * 100).toFixed(1) : 0}%
                </p>
              </div>
              <TrendingDown className="h-10 w-10 text-blue-600 opacity-20" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Bulk Purchases Table */}
      <Card>
        <CardHeader>
          <CardTitle>Bulk Purchases</CardTitle>
          <CardDescription>{purchases.length} purchases on record</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Product</TableHead>
                <TableHead>Purchase Details</TableHead>
                <TableHead>Allocation Status</TableHead>
                <TableHead>Stock Status</TableHead>
                <TableHead>Financials</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {purchases.map((purchase) => {
                const status = getStockStatus(purchase);
                const StatusIcon = status.icon;
                const allocationPercent = calculateAllocationPercentage(purchase);

                return (
                  <TableRow key={purchase.id}>
                    <TableCell>
                      <div>
                        <div className="font-medium">{purchase.product.name}</div>
                        <div className="text-xs text-muted-foreground">
                          {new Date(purchase.purchase_date).toLocaleDateString('en-IN')}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="text-sm space-y-1">
                        <div>
                          <span className="text-muted-foreground">Face Value:</span>{' '}
                          ₹{parseFloat(purchase.face_value_purchased).toLocaleString('en-IN')}
                        </div>
                        <div>
                          <span className="text-muted-foreground">Cost Paid:</span>{' '}
                          ₹{parseFloat(purchase.actual_cost_paid).toLocaleString('en-IN')}
                        </div>
                        <div>
                          <span className="text-muted-foreground">Discount:</span>{' '}
                          {purchase.discount_percentage}%
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-2">
                        <Progress value={allocationPercent} className="h-2" />
                        <div className="text-xs space-y-1">
                          <div>
                            <span className="text-muted-foreground">Allocated:</span>{' '}
                            ₹
                            {(
                              parseFloat(purchase.total_value_received) -
                              parseFloat(purchase.value_remaining)
                            ).toLocaleString('en-IN', { maximumFractionDigits: 0 })}
                          </div>
                          <div>
                            <span className="text-muted-foreground">Remaining:</span>{' '}
                            ₹
                            {parseFloat(purchase.value_remaining).toLocaleString('en-IN', {
                              maximumFractionDigits: 0,
                            })}
                          </div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={status.variant} className="flex items-center gap-1 w-fit">
                        <StatusIcon className="h-3 w-3" />
                        {status.label}
                      </Badge>
                      <div className="text-xs text-muted-foreground mt-1">
                        {allocationPercent.toFixed(1)}% used
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="text-sm space-y-1">
                        <div>
                          <span className="text-muted-foreground">Total Received:</span>{' '}
                          ₹{parseFloat(purchase.total_value_received).toLocaleString('en-IN')}
                        </div>
                        <div>
                          <span className="text-muted-foreground">Extra Alloc:</span>{' '}
                          {purchase.extra_allocation_percentage}%
                        </div>
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openManualAllocation(purchase)}
                          disabled={parseFloat(purchase.value_remaining) <= 0}
                        >
                          <ArrowRight className="h-3 w-3" />
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => openEditDialog(purchase)}>
                          <Pencil className="h-3 w-3" />
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => deleteMutation.mutate(purchase.id)}
                        >
                          <Trash2 className="h-3 w-3" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>

          {purchases.length === 0 && (
            <div className="text-center py-12 text-muted-foreground">
              No bulk purchases found. Create your first bulk purchase to start managing inventory.
            </div>
          )}
        </CardContent>
      </Card>

      {/* Manual Allocation Dialog */}
      <Dialog open={manualAllocDialogOpen} onOpenChange={setManualAllocDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Manual Allocation</DialogTitle>
            <DialogDescription>
              Allocate inventory from {selectedPurchaseForAlloc?.product.name} to a specific user
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            {selectedPurchaseForAlloc && (
              <div className="p-4 bg-muted rounded-lg text-sm">
                <div className="font-semibold mb-2">Available Inventory</div>
                <div>
                  ₹
                  {parseFloat(selectedPurchaseForAlloc.value_remaining).toLocaleString('en-IN', {
                    maximumFractionDigits: 2,
                  })}
                </div>
              </div>
            )}

            <div className="space-y-2">
              <Label>User ID</Label>
              <Input
                type="number"
                value={manualAllocForm.user_id}
                onChange={(e) => setManualAllocForm({ ...manualAllocForm, user_id: e.target.value })}
                placeholder="Enter user ID"
              />
            </div>

            <div className="space-y-2">
              <Label>Allocation Amount (₹)</Label>
              <Input
                type="number"
                step="0.01"
                value={manualAllocForm.amount}
                onChange={(e) => setManualAllocForm({ ...manualAllocForm, amount: e.target.value })}
                placeholder="10000"
              />
            </div>

            <div className="space-y-2">
              <Label>Notes</Label>
              <Textarea
                value={manualAllocForm.notes}
                onChange={(e) => setManualAllocForm({ ...manualAllocForm, notes: e.target.value })}
                rows={3}
                placeholder="Reason for manual allocation..."
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setManualAllocDialogOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={handleManualAllocation}
              disabled={
                manualAllocMutation.isPending ||
                !manualAllocForm.user_id ||
                !manualAllocForm.amount
              }
            >
              Allocate
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
