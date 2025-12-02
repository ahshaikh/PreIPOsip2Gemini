'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { CheckCircle, XCircle, Ban, RotateCcw, Eye, Building2, AlertCircle, Users } from 'lucide-react';

export default function CompanyUsersPage() {
  const queryClient = useQueryClient();
  const [searchQuery, setSearchQuery] = useState('');
  const [filterStatus, setFilterStatus] = useState('all');
  const [selectedCompany, setSelectedCompany] = useState<any>(null);
  const [actionDialog, setActionDialog] = useState<{ type: string; company: any } | null>(null);
  const [actionReason, setActionReason] = useState('');

  const { data: companies, isLoading } = useQuery({
    queryKey: ['admin-company-users', searchQuery, filterStatus],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      if (filterStatus !== 'all') params.append('status', filterStatus);
      const response = await api.get(`/admin/company-users?${params.toString()}`);
      return response.data;
    },
  });

  const { data: stats } = useQuery({
    queryKey: ['admin-company-users-stats'],
    queryFn: async () => {
      const response = await api.get('/admin/company-users/statistics');
      return response.data;
    },
  });

  const approveMutation = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/company-users/${id}/approve`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-company-users'] });
      queryClient.invalidateQueries({ queryKey: ['admin-company-users-stats'] });
      toast.success('Company approved successfully');
      setActionDialog(null);
    },
  });

  const rejectMutation = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) => {
      return api.post(`/admin/company-users/${id}/reject`, { rejection_reason: reason });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-company-users'] });
      queryClient.invalidateQueries({ queryKey: ['admin-company-users-stats'] });
      toast.success('Company rejected');
      setActionDialog(null);
      setActionReason('');
    },
  });

  const suspendMutation = useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) => {
      return api.post(`/admin/company-users/${id}/suspend`, { suspension_reason: reason });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-company-users'] });
      queryClient.invalidateQueries({ queryKey: ['admin-company-users-stats'] });
      toast.success('Company suspended');
      setActionDialog(null);
      setActionReason('');
    },
  });

  const reactivateMutation = useMutation({
    mutationFn: async (id: number) => api.post(`/admin/company-users/${id}/reactivate`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-company-users'] });
      queryClient.invalidateQueries({ queryKey: ['admin-company-users-stats'] });
      toast.success('Company reactivated');
      setActionDialog(null);
    },
  });

  const handleAction = (type: string, company: any) => {
    setActionDialog({ type, company });
  };

  const executeAction = () => {
    if (!actionDialog) return;

    const { type, company } = actionDialog;

    switch (type) {
      case 'approve':
        approveMutation.mutate(company.id);
        break;
      case 'reject':
        if (!actionReason.trim()) {
          toast.error('Please provide a rejection reason');
          return;
        }
        rejectMutation.mutate({ id: company.id, reason: actionReason });
        break;
      case 'suspend':
        if (!actionReason.trim()) {
          toast.error('Please provide a suspension reason');
          return;
        }
        suspendMutation.mutate({ id: company.id, reason: actionReason });
        break;
      case 'reactivate':
        reactivateMutation.mutate(company.id);
        break;
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; class: string }> = {
      pending: { variant: 'secondary', class: 'bg-yellow-100 text-yellow-800' },
      active: { variant: 'default', class: 'bg-green-100 text-green-800' },
      suspended: { variant: 'destructive', class: 'bg-red-100 text-red-800' },
      rejected: { variant: 'destructive', class: 'bg-gray-100 text-gray-800' },
    };
    return variants[status] || variants.pending;
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Company Users Management</h1>
        <p className="text-muted-foreground mt-2">
          Approve, manage, and monitor company registrations
        </p>
      </div>

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Companies</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.total_companies || 0}</p>
              </div>
              <Building2 className="h-8 w-8 text-blue-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Pending Approval</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.pending_approval || 0}</p>
              </div>
              <AlertCircle className="h-8 w-8 text-yellow-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Active</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.active_companies || 0}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-green-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Verified</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.verified_companies || 0}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-blue-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Suspended</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.suspended_companies || 0}</p>
              </div>
              <Ban className="h-8 w-8 text-red-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1">
              <Input
                placeholder="Search companies, emails, or contact persons..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-full md:w-[200px]">
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Companies Table */}
      <Card>
        <CardHeader>
          <CardTitle>Company Users</CardTitle>
          <CardDescription>
            {companies?.data?.length || 0} companies found
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading companies...</div>
          ) : companies?.data?.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <Users className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium">No companies found</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Company</TableHead>
                  <TableHead>Contact Person</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Registered</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {companies?.data?.map((company: any) => (
                  <TableRow key={company.id}>
                    <TableCell>
                      <div>
                        <p className="font-medium">{company.company?.name || 'N/A'}</p>
                        <p className="text-sm text-muted-foreground">{company.company?.sector || 'N/A'}</p>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        <p className="font-medium">{company.contact_person_name}</p>
                        <p className="text-sm text-muted-foreground">{company.contact_person_designation || 'N/A'}</p>
                      </div>
                    </TableCell>
                    <TableCell>{company.email}</TableCell>
                    <TableCell>
                      {new Date(company.created_at).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                      })}
                    </TableCell>
                    <TableCell>
                      <Badge className={getStatusBadge(company.status).class}>
                        {company.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        {company.status === 'pending' && (
                          <>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleAction('approve', company)}
                            >
                              <CheckCircle className="h-4 w-4 text-green-600" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleAction('reject', company)}
                            >
                              <XCircle className="h-4 w-4 text-red-600" />
                            </Button>
                          </>
                        )}
                        {company.status === 'active' && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleAction('suspend', company)}
                          >
                            <Ban className="h-4 w-4 text-orange-600" />
                          </Button>
                        )}
                        {company.status === 'suspended' && (
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleAction('reactivate', company)}
                          >
                            <RotateCcw className="h-4 w-4 text-blue-600" />
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Action Dialog */}
      <Dialog open={!!actionDialog} onOpenChange={(open) => !open && setActionDialog(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {actionDialog?.type === 'approve' && 'Approve Company'}
              {actionDialog?.type === 'reject' && 'Reject Company'}
              {actionDialog?.type === 'suspend' && 'Suspend Company'}
              {actionDialog?.type === 'reactivate' && 'Reactivate Company'}
            </DialogTitle>
            <DialogDescription>
              {actionDialog?.type === 'approve' &&
                'This will activate the company account and allow them to create deals.'}
              {actionDialog?.type === 'reject' &&
                'This will reject the company registration. Please provide a reason.'}
              {actionDialog?.type === 'suspend' &&
                'This will suspend the company account. Please provide a reason.'}
              {actionDialog?.type === 'reactivate' &&
                'This will reactivate the suspended company account.'}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            {(actionDialog?.type === 'reject' || actionDialog?.type === 'suspend') && (
              <div className="space-y-2">
                <Label>Reason *</Label>
                <Textarea
                  rows={4}
                  placeholder="Provide a clear reason for this action..."
                  value={actionReason}
                  onChange={(e) => setActionReason(e.target.value)}
                />
              </div>
            )}
            <div className="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
              <p className="text-sm font-medium">Company: {actionDialog?.company?.company?.name}</p>
              <p className="text-sm text-muted-foreground">Contact: {actionDialog?.company?.contact_person_name}</p>
              <p className="text-sm text-muted-foreground">Email: {actionDialog?.company?.email}</p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setActionDialog(null)}>
              Cancel
            </Button>
            <Button
              onClick={executeAction}
              disabled={
                approveMutation.isPending ||
                rejectMutation.isPending ||
                suspendMutation.isPending ||
                reactivateMutation.isPending
              }
            >
              Confirm
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
