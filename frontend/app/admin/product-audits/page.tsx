'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Search, Filter, Eye, RefreshCw, Download, FileText, TrendingUp, AlertTriangle } from "lucide-react";
import { PaginationControls } from '@/components/shared/PaginationControls';
import { format } from 'date-fns';

/**
 * Product Audit Trail Page (FIX 48)
 *
 * Displays comprehensive audit log of all product changes including:
 * - Price changes
 * - Status updates
 * - Compliance field changes (SEBI approval, etc.)
 * - Complete change history with old/new values
 */
export default function ProductAuditsPage() {
  const [page, setPage] = useState(1);
  const [actionFilter, setActionFilter] = useState('all');
  const [productFilter, setProductFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [criticalOnly, setCriticalOnly] = useState(false);

  // Fetch Product Audits
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['productAudits', page, actionFilter, productFilter, searchQuery, criticalOnly],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: '50',
      });

      if (actionFilter !== 'all') {
        params.append('action', actionFilter);
      }
      if (productFilter) {
        params.append('product_id', productFilter);
      }
      if (searchQuery) {
        params.append('search', searchQuery);
      }
      if (criticalOnly) {
        params.append('critical_only', 'true');
      }

      const res = await api.get(`/admin/product-audits?${params.toString()}`);
      return res.data;
    },
    placeholderData: (previousData) => previousData,
  });

  // Export audits to CSV
  const handleExport = async () => {
    try {
      const params = new URLSearchParams();
      if (actionFilter !== 'all') params.append('action', actionFilter);
      if (productFilter) params.append('product_id', productFilter);
      if (searchQuery) params.append('search', searchQuery);
      if (criticalOnly) params.append('critical_only', 'true');

      const res = await api.get(`/admin/product-audits/export?${params.toString()}`);

      // Convert CSV data to downloadable file
      const csvContent = res.data.data.map((row: string[]) => row.join(',')).join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = res.data.filename;
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Export failed:', error);
    }
  };

  const stats = data?.stats || {};
  const audits = data?.audits?.data || [];
  const pagination = data?.audits;

  const getActionBadge = (action: string) => {
    const variants: Record<string, { variant: string; className: string }> = {
      created: { variant: 'default', className: 'bg-blue-100 text-blue-800' },
      updated: { variant: 'secondary', className: 'bg-gray-100 text-gray-800' },
      activated: { variant: 'default', className: 'bg-green-100 text-green-800' },
      deactivated: { variant: 'destructive', className: 'bg-red-100 text-red-800' },
      price_updated: { variant: 'default', className: 'bg-yellow-100 text-yellow-800' },
    };

    const config = variants[action] || variants.updated;
    return (
      <Badge className={config.className}>
        {action.replace('_', ' ').toUpperCase()}
      </Badge>
    );
  };

  const isCriticalChange = (audit: any) => {
    const criticalFields = ['status', 'current_market_price', 'face_value_per_unit', 'sebi_approval_number'];
    return audit.changed_fields?.some((field: string) => criticalFields.includes(field));
  };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Product Audit Trail</h1>
          <p className="text-muted-foreground">Complete change history for all products</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={handleExport}>
            <Download className="mr-2 h-4 w-4" /> Export CSV
          </Button>
          <Button variant="outline" onClick={() => refetch()}>
            <RefreshCw className="mr-2 h-4 w-4" /> Refresh
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Audits</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total_audits || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Critical Changes</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-orange-600">{stats.critical_changes || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Price Changes (30d)</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-blue-600">{stats.recent_price_changes || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Products Tracked</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.unique_products || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                placeholder="Search product name..."
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setPage(1);
                }}
                className="pl-10"
              />
            </div>

            <Select value={actionFilter} onValueChange={(val) => {
              setActionFilter(val);
              setPage(1);
            }}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by action" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Actions</SelectItem>
                <SelectItem value="created">Created</SelectItem>
                <SelectItem value="updated">Updated</SelectItem>
                <SelectItem value="activated">Activated</SelectItem>
                <SelectItem value="deactivated">Deactivated</SelectItem>
                <SelectItem value="price_updated">Price Updated</SelectItem>
              </SelectContent>
            </Select>

            <div className="flex items-center space-x-2">
              <input
                type="checkbox"
                id="criticalOnly"
                checked={criticalOnly}
                onChange={(e) => {
                  setCriticalOnly(e.target.checked);
                  setPage(1);
                }}
                className="rounded border-gray-300"
              />
              <label htmlFor="criticalOnly" className="text-sm font-medium cursor-pointer">
                Critical changes only
              </label>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Audit Table */}
      <Card>
        <CardHeader>
          <CardTitle>Audit Logs</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Loading audits...</div>
          ) : audits.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <FileText className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No audit logs found</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Timestamp</TableHead>
                    <TableHead>Product</TableHead>
                    <TableHead>Action</TableHead>
                    <TableHead>Changed Fields</TableHead>
                    <TableHead>Performed By</TableHead>
                    <TableHead>IP Address</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {audits.map((audit: any) => (
                    <TableRow key={audit.id} className={isCriticalChange(audit) ? 'bg-orange-50' : ''}>
                      <TableCell className="font-mono text-xs">
                        {format(new Date(audit.created_at), 'MMM dd, yyyy HH:mm')}
                      </TableCell>
                      <TableCell>
                        <div className="font-medium">{audit.product?.name || 'N/A'}</div>
                        <div className="text-xs text-muted-foreground">{audit.product?.slug}</div>
                      </TableCell>
                      <TableCell>
                        {getActionBadge(audit.action)}
                        {isCriticalChange(audit) && (
                          <AlertTriangle className="inline-block ml-2 h-4 w-4 text-orange-600" />
                        )}
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          {audit.changed_fields?.map((field: string) => (
                            <Badge key={field} variant="outline" className="text-xs">
                              {field.replace('_', ' ')}
                            </Badge>
                          ))}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="font-medium">{audit.performed_by?.name || 'System'}</div>
                        <div className="text-xs text-muted-foreground">{audit.performed_by?.email}</div>
                      </TableCell>
                      <TableCell className="font-mono text-xs">{audit.ip_address}</TableCell>
                      <TableCell className="text-right">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => window.location.href = `/admin/product-audits/${audit.id}`}
                        >
                          <Eye className="h-4 w-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}

          {pagination && (
            <div className="mt-4">
              <PaginationControls
                currentPage={pagination.current_page}
                totalPages={pagination.last_page}
                onPageChange={setPage}
              />
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
