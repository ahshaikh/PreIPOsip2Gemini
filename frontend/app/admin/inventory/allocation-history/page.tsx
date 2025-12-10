// V-BULK-PURCHASE-ENHANCEMENT-004 | Allocation History Viewer
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import {
  History,
  Search,
  Filter,
  Download,
  User,
  Package,
  TrendingUp,
  RotateCcw,
  CheckCircle,
  AlertCircle,
  Clock,
  Calendar
} from "lucide-react";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";

interface AllocationRecord {
  id: number;
  user_investment_id: number;
  bulk_purchase_id: number;
  user_id: number;
  username: string;
  email: string;
  product_name: string;
  allocated_value: string;
  allocation_type: 'automatic' | 'manual';
  allocated_by_admin_id?: number;
  allocated_by_admin_name?: string;
  payment_id?: number;
  subscription_id?: number;
  is_reversed: boolean;
  reversed_at?: string;
  reversal_reason?: string;
  created_at: string;
  notes?: string;
}

interface BulkPurchase {
  id: number;
  product_name: string;
  purchase_date: string;
  face_value_purchased: string;
  total_value_received: string;
  value_remaining: string;
}

interface Product {
  id: number;
  name: string;
}

export default function AllocationHistoryPage() {
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [productFilter, setProductFilter] = useState('all');
  const [purchaseFilter, setPurchaseFilter] = useState('all');
  const [allocationTypeFilter, setAllocationTypeFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('active');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedRecord, setSelectedRecord] = useState<AllocationRecord | null>(null);

  // Fetch allocation history
  const { data: historyData, isLoading } = useQuery({
    queryKey: ['allocationHistory', dateFrom, dateTo, productFilter, purchaseFilter, allocationTypeFilter, statusFilter, searchQuery],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (dateFrom) params.append('date_from', dateFrom);
      if (dateTo) params.append('date_to', dateTo);
      if (productFilter !== 'all') params.append('product_id', productFilter);
      if (purchaseFilter !== 'all') params.append('bulk_purchase_id', purchaseFilter);
      if (allocationTypeFilter !== 'all') params.append('allocation_type', allocationTypeFilter);
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (searchQuery) params.append('search', searchQuery);

      return (await api.get(`/admin/bulk-purchases/allocation-history?${params.toString()}`)).data;
    },
  });

  // Fetch products for filter
  const { data: productsData } = useQuery({
    queryKey: ['products'],
    queryFn: async () => (await api.get('/admin/products')).data,
  });

  // Fetch bulk purchases for filter
  const { data: purchasesData } = useQuery({
    queryKey: ['bulkPurchasesFilter'],
    queryFn: async () => (await api.get('/admin/bulk-purchases?filter=active')).data,
  });

  const getAllocationTypeBadge = (type: string) => {
    if (type === 'automatic') {
      return (
        <Badge variant="default" className="flex items-center gap-1 w-fit">
          <TrendingUp className="h-3 w-3" />
          Automatic
        </Badge>
      );
    }
    return (
      <Badge variant="secondary" className="flex items-center gap-1 w-fit">
        <User className="h-3 w-3" />
        Manual
      </Badge>
    );
  };

  const getStatusBadge = (isReversed: boolean) => {
    if (isReversed) {
      return (
        <Badge variant="destructive" className="flex items-center gap-1 w-fit">
          <RotateCcw className="h-3 w-3" />
          Reversed
        </Badge>
      );
    }
    return (
      <Badge variant="default" className="flex items-center gap-1 w-fit">
        <CheckCircle className="h-3 w-3" />
        Active
      </Badge>
    );
  };

  const handleExport = async (format: 'csv' | 'excel' | 'pdf') => {
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (productFilter !== 'all') params.append('product_id', productFilter);
    if (purchaseFilter !== 'all') params.append('bulk_purchase_id', purchaseFilter);
    if (allocationTypeFilter !== 'all') params.append('allocation_type', allocationTypeFilter);
    if (statusFilter !== 'all') params.append('status', statusFilter);
    params.append('format', format);

    const response = await api.get(`/admin/bulk-purchases/allocation-history/export?${params.toString()}`, {
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `allocation-history-${new Date().toISOString().split('T')[0]}.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  };

  if (isLoading) {
    return (
      <div className="p-8">
        <div className="flex items-center gap-2">
          <Clock className="h-5 w-5 animate-spin" />
          <span>Loading allocation history...</span>
        </div>
      </div>
    );
  }

  const records = historyData?.data || [];
  const summary = historyData?.summary || {
    total_allocations: 0,
    total_value_allocated: '0',
    active_allocations: 0,
    reversed_allocations: 0,
    automatic_allocations: 0,
    manual_allocations: 0,
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <History className="h-8 w-8" />
            Allocation History
          </h1>
          <p className="text-muted-foreground mt-1">
            Track all allocations from bulk purchases
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => handleExport('csv')}>
            <Download className="h-4 w-4 mr-2" />
            Export CSV
          </Button>
          <Button variant="outline" onClick={() => handleExport('excel')}>
            <Download className="h-4 w-4 mr-2" />
            Export Excel
          </Button>
          <Button variant="outline" onClick={() => handleExport('pdf')}>
            <Download className="h-4 w-4 mr-2" />
            Export PDF
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{summary.total_allocations}</div>
            <p className="text-xs text-muted-foreground">Total Allocations</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">
              ₹{parseFloat(summary.total_value_allocated).toLocaleString('en-IN')}
            </div>
            <p className="text-xs text-muted-foreground">Total Value</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-green-600">{summary.active_allocations}</div>
            <p className="text-xs text-muted-foreground">Active</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-red-600">{summary.reversed_allocations}</div>
            <p className="text-xs text-muted-foreground">Reversed</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-blue-600">{summary.automatic_allocations}</div>
            <p className="text-xs text-muted-foreground">Automatic</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-purple-600">{summary.manual_allocations}</div>
            <p className="text-xs text-muted-foreground">Manual</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="h-5 w-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Date Range */}
            <div className="space-y-2">
              <Label htmlFor="dateFrom">From Date</Label>
              <Input
                id="dateFrom"
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="dateTo">To Date</Label>
              <Input
                id="dateTo"
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
              />
            </div>

            {/* Product Filter */}
            <div className="space-y-2">
              <Label>Product</Label>
              <Select value={productFilter} onValueChange={setProductFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="All Products" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Products</SelectItem>
                  {productsData?.data?.map((product: Product) => (
                    <SelectItem key={product.id} value={product.id.toString()}>
                      {product.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Bulk Purchase Filter */}
            <div className="space-y-2">
              <Label>Bulk Purchase</Label>
              <Select value={purchaseFilter} onValueChange={setPurchaseFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="All Purchases" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Purchases</SelectItem>
                  {purchasesData?.data?.map((purchase: BulkPurchase) => (
                    <SelectItem key={purchase.id} value={purchase.id.toString()}>
                      {purchase.product_name} - {new Date(purchase.purchase_date).toLocaleDateString()}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Allocation Type Filter */}
            <div className="space-y-2">
              <Label>Allocation Type</Label>
              <Select value={allocationTypeFilter} onValueChange={setAllocationTypeFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="All Types" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Types</SelectItem>
                  <SelectItem value="automatic">Automatic</SelectItem>
                  <SelectItem value="manual">Manual</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Status Filter */}
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="All Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="reversed">Reversed</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {/* Search */}
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="search">Search User</Label>
              <div className="relative">
                <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  id="search"
                  placeholder="Search by username or email..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Allocation History Table */}
      <Card>
        <CardHeader>
          <CardTitle>Allocation Records</CardTitle>
          <CardDescription>
            {records.length} record{records.length !== 1 ? 's' : ''} found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date & Time</TableHead>
                <TableHead>User</TableHead>
                <TableHead>Product</TableHead>
                <TableHead>Allocated Value</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Allocated By</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {records.map((record: AllocationRecord) => (
                <TableRow key={record.id}>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-muted-foreground" />
                      <div>
                        <div className="font-medium">
                          {new Date(record.created_at).toLocaleDateString('en-IN', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                          })}
                        </div>
                        <div className="text-xs text-muted-foreground">
                          {new Date(record.created_at).toLocaleTimeString('en-IN', {
                            hour: '2-digit',
                            minute: '2-digit',
                          })}
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{record.username}</div>
                      <div className="text-xs text-muted-foreground">{record.email}</div>
                      <div className="text-xs text-muted-foreground">ID: {record.user_id}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Package className="h-4 w-4 text-muted-foreground" />
                      <span className="font-medium">{record.product_name}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="font-semibold text-green-600">
                      ₹{parseFloat(record.allocated_value).toLocaleString('en-IN')}
                    </div>
                  </TableCell>
                  <TableCell>{getAllocationTypeBadge(record.allocation_type)}</TableCell>
                  <TableCell>
                    {record.allocation_type === 'manual' && record.allocated_by_admin_name ? (
                      <div className="flex items-center gap-2">
                        <User className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm">{record.allocated_by_admin_name}</span>
                      </div>
                    ) : (
                      <Badge variant="outline">System</Badge>
                    )}
                  </TableCell>
                  <TableCell>{getStatusBadge(record.is_reversed)}</TableCell>
                  <TableCell className="text-right">
                    <Dialog>
                      <DialogTrigger asChild>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setSelectedRecord(record)}
                        >
                          View Details
                        </Button>
                      </DialogTrigger>
                      <DialogContent className="max-w-2xl">
                        <DialogHeader>
                          <DialogTitle>Allocation Details</DialogTitle>
                        </DialogHeader>
                        {selectedRecord && (
                          <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                              <div>
                                <Label className="text-muted-foreground">Allocation ID</Label>
                                <p className="font-medium">#{selectedRecord.id}</p>
                              </div>
                              <div>
                                <Label className="text-muted-foreground">Investment ID</Label>
                                <p className="font-medium">#{selectedRecord.user_investment_id}</p>
                              </div>
                              <div>
                                <Label className="text-muted-foreground">Bulk Purchase ID</Label>
                                <p className="font-medium">#{selectedRecord.bulk_purchase_id}</p>
                              </div>
                              <div>
                                <Label className="text-muted-foreground">Payment ID</Label>
                                <p className="font-medium">
                                  {selectedRecord.payment_id ? `#${selectedRecord.payment_id}` : 'N/A'}
                                </p>
                              </div>
                              <div>
                                <Label className="text-muted-foreground">Subscription ID</Label>
                                <p className="font-medium">
                                  {selectedRecord.subscription_id ? `#${selectedRecord.subscription_id}` : 'N/A'}
                                </p>
                              </div>
                              <div>
                                <Label className="text-muted-foreground">Allocation Date</Label>
                                <p className="font-medium">
                                  {new Date(selectedRecord.created_at).toLocaleString('en-IN')}
                                </p>
                              </div>
                            </div>

                            {selectedRecord.is_reversed && (
                              <Card className="border-red-200 bg-red-50">
                                <CardContent className="pt-6">
                                  <div className="flex items-start gap-2">
                                    <AlertCircle className="h-5 w-5 text-red-600 mt-0.5" />
                                    <div>
                                      <p className="font-semibold text-red-900">This allocation was reversed</p>
                                      <p className="text-sm text-red-700 mt-1">
                                        <strong>Date:</strong>{' '}
                                        {selectedRecord.reversed_at
                                          ? new Date(selectedRecord.reversed_at).toLocaleString('en-IN')
                                          : 'N/A'}
                                      </p>
                                      {selectedRecord.reversal_reason && (
                                        <p className="text-sm text-red-700 mt-1">
                                          <strong>Reason:</strong> {selectedRecord.reversal_reason}
                                        </p>
                                      )}
                                    </div>
                                  </div>
                                </CardContent>
                              </Card>
                            )}

                            {selectedRecord.notes && (
                              <div>
                                <Label className="text-muted-foreground">Notes</Label>
                                <p className="text-sm mt-1">{selectedRecord.notes}</p>
                              </div>
                            )}
                          </div>
                        )}
                      </DialogContent>
                    </Dialog>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>

          {!records.length && (
            <div className="text-center py-12 text-muted-foreground">
              <History className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No allocation records found matching your filters</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
