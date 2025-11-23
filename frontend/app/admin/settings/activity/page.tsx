// V-FINAL-1730-228 (Created) | V-FINAL-1730-450 | V-ENHANCED-AUDIT
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import api from "@/lib/api";
import { useQuery, useMutation } from "@tanstack/react-query";
import { SearchInput } from "@/components/shared/SearchInput";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { useSearchParams, useRouter, usePathname } from "next/navigation";
import { useState, useCallback } from "react";
import { Search, Download, Filter, Eye, Clock, User, Activity, Shield, AlertTriangle, CheckCircle, XCircle, RefreshCcw, Calendar, MapPin, Monitor } from "lucide-react";
import { toast } from "sonner";

// Action categories for filtering
const ACTION_CATEGORIES = {
  all: { label: 'All Actions', icon: Activity },
  auth: { label: 'Authentication', icon: Shield, actions: ['login', 'logout', 'password_reset', 'password_change', '2fa_enabled', '2fa_disabled'] },
  kyc: { label: 'KYC & Verification', icon: CheckCircle, actions: ['kyc_submitted', 'kyc_approved', 'kyc_rejected', 'bank_verified'] },
  payment: { label: 'Payments', icon: Activity, actions: ['payment_initiated', 'payment_completed', 'payment_failed', 'withdrawal_requested', 'withdrawal_approved'] },
  admin: { label: 'Admin Actions', icon: Shield, actions: ['user_created', 'user_updated', 'user_deleted', 'settings_changed', 'role_assigned'] },
  security: { label: 'Security Events', icon: AlertTriangle, actions: ['suspicious_login', 'account_locked', 'ip_blocked', 'failed_login'] },
};

// Get action badge color
const getActionBadgeColor = (action: string) => {
  if (action.includes('approved') || action.includes('completed') || action.includes('success')) {
    return 'bg-green-100 text-green-800';
  }
  if (action.includes('rejected') || action.includes('failed') || action.includes('blocked')) {
    return 'bg-red-100 text-red-800';
  }
  if (action.includes('pending') || action.includes('requested') || action.includes('initiated')) {
    return 'bg-yellow-100 text-yellow-800';
  }
  return 'bg-blue-100 text-blue-800';
};

export default function ActivityLogPage() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const pathname = usePathname();

  const page = searchParams.get('page') || '1';
  const search = searchParams.get('search') || '';
  const actionFilter = searchParams.get('action') || '';
  const dateFrom = searchParams.get('from') || '';
  const dateTo = searchParams.get('to') || '';

  const [selectedLog, setSelectedLog] = useState<any>(null);
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [localSearch, setLocalSearch] = useState(search);
  const [localActionFilter, setLocalActionFilter] = useState(actionFilter);
  const [localDateFrom, setLocalDateFrom] = useState(dateFrom);
  const [localDateTo, setLocalDateTo] = useState(dateTo);

  // Update URL params
  const updateParams = useCallback((updates: Record<string, string>) => {
    const params = new URLSearchParams(searchParams.toString());
    Object.entries(updates).forEach(([key, value]) => {
      if (value) params.set(key, value);
      else params.delete(key);
    });
    params.set('page', '1'); // Reset to page 1 on filter change
    router.push(`${pathname}?${params.toString()}`);
  }, [searchParams, router, pathname]);

  const { data: logData, isLoading, refetch, isFetching } = useQuery({
    queryKey: ['adminActivityLogs', page, search, actionFilter, dateFrom, dateTo],
    queryFn: async () => {
      const params = new URLSearchParams({ page });
      if (search) params.append('search', search);
      if (actionFilter) params.append('action', actionFilter);
      if (dateFrom) params.append('from', dateFrom);
      if (dateTo) params.append('to', dateTo);
      return (await api.get(`/admin/system/activity-logs?${params.toString()}`)).data;
    },
  });

  // Export mutation
  const exportMutation = useMutation({
    mutationFn: async (format: string) => {
      const params = new URLSearchParams();
      if (search) params.append('search', search);
      if (actionFilter) params.append('action', actionFilter);
      if (dateFrom) params.append('from', dateFrom);
      if (dateTo) params.append('to', dateTo);
      params.append('format', format);

      const response = await api.get(`/admin/system/activity-logs/export?${params.toString()}`, {
        responseType: 'blob'
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.download = `activity-logs-${new Date().toISOString().split('T')[0]}.${format}`;
      link.click();
      window.URL.revokeObjectURL(url);
    },
    onSuccess: () => toast.success("Export started, download will begin shortly"),
    onError: () => toast.error("Failed to export logs")
  });

  const applyFilters = () => {
    updateParams({
      search: localSearch,
      action: localActionFilter,
      from: localDateFrom,
      to: localDateTo
    });
    setIsFilterOpen(false);
  };

  const clearFilters = () => {
    setLocalSearch('');
    setLocalActionFilter('');
    setLocalDateFrom('');
    setLocalDateTo('');
    router.push(pathname);
    setIsFilterOpen(false);
  };

  const activeFiltersCount = [search, actionFilter, dateFrom, dateTo].filter(Boolean).length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Global Activity Audit</h1>
          <p className="text-muted-foreground">Track all system and user activities across the platform.</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" onClick={() => refetch()} disabled={isFetching}>
            <RefreshCcw className={`h-4 w-4 mr-2 ${isFetching ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
          <Button variant="outline" size="sm" onClick={() => exportMutation.mutate('csv')} disabled={exportMutation.isPending}>
            <Download className="h-4 w-4 mr-2" />
            Export CSV
          </Button>
        </div>
      </div>

      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Events</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{logData?.meta?.total?.toLocaleString() || 0}</div>
            <p className="text-xs text-muted-foreground">All time</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Today's Events</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{logData?.stats?.today || 0}</div>
            <p className="text-xs text-muted-foreground">Last 24 hours</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Security Events</CardTitle>
            <Shield className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{logData?.stats?.security_events || 0}</div>
            <p className="text-xs text-muted-foreground">Requires attention</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Failed Actions</CardTitle>
            <AlertTriangle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-500">{logData?.stats?.failed_actions || 0}</div>
            <p className="text-xs text-muted-foreground">Last 7 days</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters Card */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Activity Logs</CardTitle>
            <div className="flex items-center gap-2">
              {/* Quick Search */}
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search logs..."
                  value={localSearch}
                  onChange={e => setLocalSearch(e.target.value)}
                  onKeyDown={e => e.key === 'Enter' && applyFilters()}
                  className="pl-10 w-64"
                />
              </div>

              {/* Filter Button */}
              <Button
                variant="outline"
                size="sm"
                onClick={() => setIsFilterOpen(!isFilterOpen)}
                className={activeFiltersCount > 0 ? 'border-primary' : ''}
              >
                <Filter className="h-4 w-4 mr-2" />
                Filters
                {activeFiltersCount > 0 && (
                  <Badge variant="secondary" className="ml-2">{activeFiltersCount}</Badge>
                )}
              </Button>
            </div>
          </div>

          {/* Expanded Filters */}
          {isFilterOpen && (
            <div className="mt-4 p-4 border rounded-lg bg-muted/30 space-y-4">
              <div className="grid md:grid-cols-4 gap-4">
                <div className="space-y-2">
                  <Label>Action Type</Label>
                  <Select value={localActionFilter} onValueChange={setLocalActionFilter}>
                    <SelectTrigger><SelectValue placeholder="All actions" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">All Actions</SelectItem>
                      {Object.entries(ACTION_CATEGORIES).filter(([key]) => key !== 'all').map(([key, cat]) => (
                        <SelectItem key={key} value={key}>{cat.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>From Date</Label>
                  <Input type="date" value={localDateFrom} onChange={e => setLocalDateFrom(e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>To Date</Label>
                  <Input type="date" value={localDateTo} onChange={e => setLocalDateTo(e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>&nbsp;</Label>
                  <div className="flex gap-2">
                    <Button onClick={applyFilters} size="sm">Apply</Button>
                    <Button variant="outline" onClick={clearFilters} size="sm">Clear</Button>
                  </div>
                </div>
              </div>
            </div>
          )}
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <RefreshCcw className="h-6 w-6 animate-spin text-muted-foreground" />
              <span className="ml-2">Loading logs...</span>
            </div>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Time</TableHead>
                    <TableHead>User / Actor</TableHead>
                    <TableHead>Action</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>IP / Device</TableHead>
                    <TableHead className="text-right">Details</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {logData?.data?.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-12 text-muted-foreground">
                        No activity logs found matching your criteria.
                      </TableCell>
                    </TableRow>
                  ) : (
                    logData?.data?.map((log: any) => (
                      <TableRow key={log.id} className="cursor-pointer hover:bg-muted/50" onClick={() => setSelectedLog(log)}>
                        <TableCell>
                          <div className="text-sm">{new Date(log.created_at).toLocaleDateString()}</div>
                          <div className="text-xs text-muted-foreground">{new Date(log.created_at).toLocaleTimeString()}</div>
                        </TableCell>
                        <TableCell>
                          {log.user ? (
                            <div>
                              <div className="font-medium">{log.user.username || log.user.name}</div>
                              <div className="text-xs text-muted-foreground">{log.user.email}</div>
                            </div>
                          ) : (
                            <span className="text-muted-foreground flex items-center gap-1">
                              <Monitor className="h-3 w-3" /> System
                            </span>
                          )}
                        </TableCell>
                        <TableCell>
                          <span className={`px-2 py-1 rounded text-xs font-mono ${getActionBadgeColor(log.action)}`}>
                            {log.action}
                          </span>
                        </TableCell>
                        <TableCell className="max-w-[300px] truncate">{log.description}</TableCell>
                        <TableCell>
                          <div className="text-xs">
                            <div className="font-mono">{log.ip_address || 'N/A'}</div>
                            {log.user_agent && (
                              <div className="text-muted-foreground truncate max-w-[120px]" title={log.user_agent}>
                                {log.user_agent.split(' ')[0]}
                              </div>
                            )}
                          </div>
                        </TableCell>
                        <TableCell className="text-right">
                          <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); setSelectedLog(log); }}>
                            <Eye className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
              {logData?.meta && <PaginationControls meta={logData.meta} />}
            </>
          )}
        </CardContent>
      </Card>

      {/* Log Details Dialog */}
      <Dialog open={!!selectedLog} onOpenChange={() => setSelectedLog(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Activity Log Details</DialogTitle>
            <DialogDescription>
              Full details of the selected activity event.
            </DialogDescription>
          </DialogHeader>
          {selectedLog && (
            <div className="space-y-4">
              {/* Header Info */}
              <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                <div className="flex items-center gap-3">
                  <div className={`p-2 rounded-full ${getActionBadgeColor(selectedLog.action)}`}>
                    <Activity className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="font-mono font-medium">{selectedLog.action}</p>
                    <p className="text-sm text-muted-foreground">{new Date(selectedLog.created_at).toLocaleString()}</p>
                  </div>
                </div>
                <Badge className={getActionBadgeColor(selectedLog.action)}>
                  {selectedLog.action.includes('failed') || selectedLog.action.includes('rejected') ? 'Failed' : 'Success'}
                </Badge>
              </div>

              {/* Details Grid */}
              <div className="grid md:grid-cols-2 gap-4">
                <div className="space-y-3">
                  <div className="flex items-start gap-2">
                    <User className="h-4 w-4 text-muted-foreground mt-1" />
                    <div>
                      <p className="text-sm text-muted-foreground">Actor</p>
                      {selectedLog.user ? (
                        <>
                          <p className="font-medium">{selectedLog.user.username || selectedLog.user.name}</p>
                          <p className="text-sm">{selectedLog.user.email}</p>
                        </>
                      ) : (
                        <p className="font-medium">System</p>
                      )}
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <MapPin className="h-4 w-4 text-muted-foreground mt-1" />
                    <div>
                      <p className="text-sm text-muted-foreground">IP Address</p>
                      <p className="font-mono">{selectedLog.ip_address || 'N/A'}</p>
                    </div>
                  </div>
                </div>
                <div className="space-y-3">
                  <div className="flex items-start gap-2">
                    <Clock className="h-4 w-4 text-muted-foreground mt-1" />
                    <div>
                      <p className="text-sm text-muted-foreground">Timestamp</p>
                      <p className="font-medium">{new Date(selectedLog.created_at).toLocaleString()}</p>
                    </div>
                  </div>
                  <div className="flex items-start gap-2">
                    <Monitor className="h-4 w-4 text-muted-foreground mt-1" />
                    <div>
                      <p className="text-sm text-muted-foreground">User Agent</p>
                      <p className="text-xs break-all">{selectedLog.user_agent || 'N/A'}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Description */}
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">Description</p>
                <p className="p-3 bg-muted rounded-lg">{selectedLog.description}</p>
              </div>

              {/* Additional Data */}
              {selectedLog.properties && Object.keys(selectedLog.properties).length > 0 && (
                <div className="space-y-2">
                  <p className="text-sm text-muted-foreground">Additional Data</p>
                  <pre className="p-3 bg-muted rounded-lg text-xs overflow-auto max-h-40">
                    {JSON.stringify(selectedLog.properties, null, 2)}
                  </pre>
                </div>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}