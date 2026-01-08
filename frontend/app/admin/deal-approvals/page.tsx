'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import {
  Search,
  RefreshCw,
  Eye,
  Clock,
  CheckCircle2,
  XCircle,
  AlertCircle,
  TrendingUp,
  PlayCircle,
  FileCheck
} from "lucide-react";
import { PaginationControls } from '@/components/shared/PaginationControls';
import { format } from 'date-fns';
import { useToast } from '@/components/ui/use-toast';

/**
 * Deal Approval Queue Page (FIX 49)
 *
 * Comprehensive deal approval workflow management including:
 * - Approval queue with SLA priority sorting
 * - Submit, Review, Approve, Reject, Publish actions
 * - Analytics dashboard (approval rates, average times, SLA compliance)
 * - Status tracking through workflow states
 */
export default function DealApprovalsPage() {
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [view, setView] = useState<'all' | 'queue'>('queue');

  const queryClient = useQueryClient();
  const { toast } = useToast();

  // Fetch Deal Approvals
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['dealApprovals', page, statusFilter, searchQuery, view],
    queryFn: async () => {
      const endpoint = view === 'queue' ? '/admin/deal-approvals/queue' : '/admin/deal-approvals';
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: '50',
      });

      if (statusFilter !== 'all' && view === 'all') {
        params.append('status', statusFilter);
      }
      if (searchQuery) {
        params.append('search', searchQuery);
      }

      const res = await api.get(`${endpoint}?${params.toString()}`);
      return res.data;
    },
    placeholderData: (previousData) => previousData,
  });

  // Fetch Analytics
  const { data: analytics } = useQuery({
    queryKey: ['dealApprovalsAnalytics'],
    queryFn: async () => (await api.get('/admin/deal-approvals/analytics')).data.analytics,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  // Start Review Mutation
  const startReviewMutation = useMutation({
    mutationFn: async (approvalId: number) => {
      await api.post(`/admin/deal-approvals/${approvalId}/start-review`);
    },
    onSuccess: () => {
      toast({ title: 'Review started successfully' });
      refetch();
    },
    onError: (error: any) => {
      toast({
        title: 'Failed to start review',
        description: error.response?.data?.error || 'An error occurred',
        variant: 'destructive'
      });
    }
  });

  const stats = view === 'all' ? data?.stats : null;
  const approvals = view === 'queue' ? data?.queue?.data : data?.approvals?.data || [];
  const pagination = view === 'queue' ? data?.queue : data?.approvals;

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { className: string; text: string }> = {
      draft: { className: 'bg-gray-100 text-gray-800', text: 'Draft' },
      pending_review: { className: 'bg-yellow-100 text-yellow-800', text: 'Pending Review' },
      under_review: { className: 'bg-blue-100 text-blue-800', text: 'Under Review' },
      approved: { className: 'bg-green-100 text-green-800', text: 'Approved' },
      rejected: { className: 'bg-red-100 text-red-800', text: 'Rejected' },
      published: { className: 'bg-purple-100 text-purple-800', text: 'Published' },
    };

    const config = variants[status] || variants.draft;
    return <Badge className={config.className}>{config.text}</Badge>;
  };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Deal Approval Workflow</h1>
          <p className="text-muted-foreground">Manage deal approval queue and workflow</p>
        </div>
        <div className="flex gap-2">
          <Button
            variant={view === 'queue' ? 'default' : 'outline'}
            onClick={() => {
              setView('queue');
              setPage(1);
            }}
          >
            <Clock className="mr-2 h-4 w-4" /> Queue
          </Button>
          <Button
            variant={view === 'all' ? 'default' : 'outline'}
            onClick={() => {
              setView('all');
              setPage(1);
            }}
          >
            <FileCheck className="mr-2 h-4 w-4" /> All Approvals
          </Button>
          <Button variant="outline" onClick={() => refetch()}>
            <RefreshCw className="mr-2 h-4 w-4" /> Refresh
          </Button>
        </div>
      </div>

      {/* Stats Cards - Only show on "All Approvals" view */}
      {view === 'all' && stats && (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Total Approvals</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.total_approvals || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Pending Review</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-yellow-600">{stats.pending_review || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Under Review</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-600">{stats.under_review || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Approved</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">{stats.approved || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Overdue (SLA)</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-red-600">{stats.overdue || 0}</div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Analytics Cards */}
      {analytics && view === 'all' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Approval Rate</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <div className="text-2xl font-bold">{analytics.approval_rate || 0}%</div>
                <TrendingUp className="h-5 w-5 text-green-600" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Avg Approval Time</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {analytics.avg_approval_time_days ? `${Math.round(analytics.avg_approval_time_days)} days` : 'N/A'}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">SLA Compliance</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{analytics.sla_compliance?.rate || 0}%</div>
              <div className="text-xs text-muted-foreground mt-1">
                {analytics.sla_compliance?.within_sla || 0} of {analytics.sla_compliance?.total_completed || 0} within 7 days
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                placeholder="Search deal title..."
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setPage(1);
                }}
                className="pl-10"
              />
            </div>

            {view === 'all' && (
              <Select value={statusFilter} onValueChange={(val) => {
                setStatusFilter(val);
                setPage(1);
              }}>
                <SelectTrigger>
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="pending_review">Pending Review</SelectItem>
                  <SelectItem value="under_review">Under Review</SelectItem>
                  <SelectItem value="approved">Approved</SelectItem>
                  <SelectItem value="rejected">Rejected</SelectItem>
                  <SelectItem value="published">Published</SelectItem>
                </SelectContent>
              </Select>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Approvals Table */}
      <Card>
        <CardHeader>
          <CardTitle>{view === 'queue' ? 'Approval Queue' : 'All Approvals'}</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Loading approvals...</div>
          ) : approvals.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <CheckCircle2 className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No approvals found</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Deal</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Submitted</TableHead>
                    {view === 'queue' && <TableHead>Days Pending</TableHead>}
                    <TableHead>Submitted By</TableHead>
                    {view === 'all' && <TableHead>Reviewer</TableHead>}
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {approvals.map((approval: any) => (
                    <TableRow
                      key={approval.id}
                      className={approval.is_overdue ? 'bg-red-50' : ''}
                    >
                      <TableCell>
                        <div className="font-medium">{approval.deal?.title || 'N/A'}</div>
                        <div className="text-xs text-muted-foreground">
                          ID: {approval.deal?.id} • Valuation: ${approval.deal?.valuation?.toLocaleString()}
                        </div>
                      </TableCell>
                      <TableCell>
                        {getStatusBadge(approval.status)}
                        {approval.is_overdue && (
                          <Badge className="ml-2 bg-red-100 text-red-800">
                            <AlertCircle className="h-3 w-3 mr-1" /> Overdue
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-sm">
                        {approval.submitted_at ? format(new Date(approval.submitted_at), 'MMM dd, yyyy') : 'N/A'}
                      </TableCell>
                      {view === 'queue' && (
                        <TableCell>
                          <Badge variant="outline">{approval.days_pending || 0} days</Badge>
                        </TableCell>
                      )}
                      <TableCell>
                        <div className="text-sm">{approval.submitter?.name || 'N/A'}</div>
                        <div className="text-xs text-muted-foreground">{approval.submitter?.email}</div>
                      </TableCell>
                      {view === 'all' && (
                        <TableCell>
                          <div className="text-sm">{approval.reviewer?.name || '—'}</div>
                        </TableCell>
                      )}
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          {approval.status === 'pending_review' && (
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => startReviewMutation.mutate(approval.id)}
                              disabled={startReviewMutation.isPending}
                            >
                              <PlayCircle className="h-4 w-4 mr-1" /> Start Review
                            </Button>
                          )}
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => window.location.href = `/admin/deal-approvals/${approval.id}`}
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                        </div>
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
