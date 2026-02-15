'use client';

import { useState } from "react";
import companyApi from "@/lib/companyApi";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import {
  Users,
  Mail,
  Phone,
  TrendingUp,
  Calendar,
  DollarSign,
  MessageSquare,
  CheckCircle2,
  XCircle,
  Clock,
  ChevronLeft,
  ChevronRight
} from "lucide-react";
import { toast } from "sonner";

interface InvestorInterest {
  id: number;
  investor_name: string | null;
  investor_email: string | null;
  investor_phone: string | null;
  interest_level: 'low' | 'medium' | 'high';
  investment_range_min: number | null;
  investment_range_max: number | null;
  message: string | null;
  status: 'pending' | 'contacted' | 'qualified' | 'not_interested';
  admin_notes: string | null;
  created_at: string;
}

interface Statistics {
  total: number;
  pending: number;
  contacted: number;
  qualified: number;
  not_interested: number;
}

interface InvestorInterestResponse {
  data: InvestorInterest[];
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
}

export default function InvestorInterestsPage() {
  const [currentPage, setCurrentPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [interestFilter, setInterestFilter] = useState('');
  const [selectedInterest, setSelectedInterest] = useState<InvestorInterest | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [newStatus, setNewStatus] = useState('');
  const [adminNotes, setAdminNotes] = useState('');

  const queryClient = useQueryClient();

  // Fetch statistics
  const { data: statsData } = useQuery({
    queryKey: ['investorInterestStats'],
    queryFn: async () => {
      const { data } = await companyApi.get('/investor-interests/statistics');
      return data;
    },
  });

  const stats: Statistics = statsData?.stats || {
    total: 0,
    pending: 0,
    contacted: 0,
    qualified: 0,
    not_interested: 0,
  };

  // Fetch interests
  const { data: response, isLoading } = useQuery<InvestorInterestResponse>({
    queryKey: ['investorInterests', currentPage, statusFilter, interestFilter],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        ...(statusFilter && { status: statusFilter }),
        ...(interestFilter && { interest_level: interestFilter }),
      });
      const { data } = await companyApi.get(`/investor-interests?${params}`);
      return data;
    },
  });

  const interests: InvestorInterest[] = response?.data || [];
  const pagination = response?.pagination || {
    total: 0,
    per_page: 20,
    current_page: 1,
    last_page: 1,
  };

  // Update status mutation
  const updateStatusMutation = useMutation({
    mutationFn: async ({ id, status, notes }: { id: number; status: string; notes: string }) => {
      const { data } = await companyApi.put(`/investor-interests/${id}/status`, {
        status,
        admin_notes: notes,
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['investorInterests'] });
      queryClient.invalidateQueries({ queryKey: ['investorInterestStats'] });
      toast.success('Status updated successfully');
      setDialogOpen(false);
      setSelectedInterest(null);
    },
    onError: () => {
      toast.error('Failed to update status');
    },
  });

  const handleViewDetails = (interest: InvestorInterest) => {
    setSelectedInterest(interest);
    setNewStatus(interest.status);
    setAdminNotes(interest.admin_notes || '');
    setDialogOpen(true);
  };

  const handleUpdateStatus = () => {
    if (selectedInterest) {
      updateStatusMutation.mutate({
        id: selectedInterest.id,
        status: newStatus,
        notes: adminNotes,
      });
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      notation: 'compact',
      maximumFractionDigits: 2,
    }).format(amount);
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; icon: any; text: string }> = {
      pending: { variant: 'secondary', icon: Clock, text: 'Pending' },
      contacted: { variant: 'default', icon: Mail, text: 'Contacted' },
      qualified: { variant: 'default', icon: CheckCircle2, text: 'Qualified' },
      not_interested: { variant: 'destructive', icon: XCircle, text: 'Not Interested' },
    };
    const config = variants[status] || variants.pending;
    const Icon = config.icon;
    return (
      <Badge variant={config.variant} className="flex items-center gap-1 w-fit">
        <Icon className="w-3 h-3" />
        {config.text}
      </Badge>
    );
  };

  const getInterestLevelBadge = (level: string) => {
    const colors: Record<string, string> = {
      low: 'bg-gray-100 text-gray-800',
      medium: 'bg-blue-100 text-blue-800',
      high: 'bg-green-100 text-green-800',
    };
    return (
      <Badge className={colors[level] || colors.medium}>
        {level.charAt(0).toUpperCase() + level.slice(1)}
      </Badge>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">Investor Interests</h1>
        <p className="text-muted-foreground">
          Manage and track investor inquiries and interest in your company
        </p>
      </div>

      {/* Statistics */}
      <div className="grid gap-4 md:grid-cols-5">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{stats.total}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Pending</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-orange-600">{stats.pending}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Contacted</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{stats.contacted}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Qualified</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{stats.qualified}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Not Interested</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-red-600">{stats.not_interested}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="text-sm font-medium mb-2 block">Status</label>
              <Select value={statusFilter} onValueChange={(value) => {
                setStatusFilter(value === 'all' ? '' : value);
                setCurrentPage(1);
              }}>
                <SelectTrigger>
                  <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="contacted">Contacted</SelectItem>
                  <SelectItem value="qualified">Qualified</SelectItem>
                  <SelectItem value="not_interested">Not Interested</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <label className="text-sm font-medium mb-2 block">Interest Level</label>
              <Select value={interestFilter} onValueChange={(value) => {
                setInterestFilter(value === 'all' ? '' : value);
                setCurrentPage(1);
              }}>
                <SelectTrigger>
                  <SelectValue placeholder="All Levels" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Levels</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                </SelectContent>
              </Select>
            </div>
            {(statusFilter || interestFilter) && (
              <div className="flex items-end">
                <Button variant="outline" onClick={() => {
                  setStatusFilter('');
                  setInterestFilter('');
                  setCurrentPage(1);
                }}>
                  Clear Filters
                </Button>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Interests List */}
      <Card>
        <CardHeader>
          <CardTitle>All Interests ({pagination.total})</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-3">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="animate-pulse h-24 bg-gray-100 rounded"></div>
              ))}
            </div>
          ) : interests.length > 0 ? (
            <>
              <div className="space-y-3">
                {interests.map((interest) => (
                  <div
                    key={interest.id}
                    className="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                    onClick={() => handleViewDetails(interest)}
                  >
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <h4 className="font-semibold text-lg">
                            {interest.investor_name || 'Anonymous Investor'}
                          </h4>
                          {getInterestLevelBadge(interest.interest_level)}
                          {getStatusBadge(interest.status)}
                        </div>
                        <div className="flex items-center gap-4 text-sm text-muted-foreground">
                          <span className="flex items-center gap-1">
                            <Calendar className="w-4 h-4" />
                            {formatDate(interest.created_at)}
                          </span>
                          {interest.investor_email && (
                            <span className="flex items-center gap-1">
                              <Mail className="w-4 h-4" />
                              {interest.investor_email}
                            </span>
                          )}
                          {interest.investor_phone && (
                            <span className="flex items-center gap-1">
                              <Phone className="w-4 h-4" />
                              {interest.investor_phone}
                            </span>
                          )}
                        </div>
                      </div>
                    </div>

                    {(interest.investment_range_min || interest.investment_range_max) && (
                      <div className="flex items-center gap-2 text-sm mb-2">
                        <DollarSign className="w-4 h-4 text-green-600" />
                        <span className="font-medium">Investment Range:</span>
                        <span>
                          {interest.investment_range_min && formatCurrency(interest.investment_range_min)}
                          {interest.investment_range_min && interest.investment_range_max && ' - '}
                          {interest.investment_range_max && formatCurrency(interest.investment_range_max)}
                        </span>
                      </div>
                    )}

                    {interest.message && (
                      <div className="flex gap-2 text-sm bg-gray-50 p-3 rounded mt-2">
                        <MessageSquare className="w-4 h-4 text-muted-foreground mt-0.5" />
                        <p className="text-muted-foreground line-clamp-2">{interest.message}</p>
                      </div>
                    )}
                  </div>
                ))}
              </div>

              {/* Pagination */}
              {pagination.last_page > 1 && (
                <div className="mt-6 flex justify-center items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                    disabled={pagination.current_page === 1}
                  >
                    <ChevronLeft className="w-4 h-4 mr-1" />
                    Previous
                  </Button>

                  <span className="text-sm text-muted-foreground px-4">
                    Page {pagination.current_page} of {pagination.last_page}
                  </span>

                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setCurrentPage(p => Math.min(pagination.last_page, p + 1))}
                    disabled={pagination.current_page === pagination.last_page}
                  >
                    Next
                    <ChevronRight className="w-4 h-4 ml-1" />
                  </Button>
                </div>
              )}
            </>
          ) : (
            <div className="py-12 text-center text-muted-foreground">
              <Users className="w-16 h-16 mx-auto mb-4 text-gray-300" />
              <p>No investor interests found</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Details Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Investor Interest Details</DialogTitle>
            <DialogDescription>
              Manage status and add notes for this investor interest
            </DialogDescription>
          </DialogHeader>

          {selectedInterest && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Investor Name</label>
                  <p className="text-lg font-semibold">
                    {selectedInterest.investor_name || 'Anonymous'}
                  </p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Interest Level</label>
                  <div className="mt-1">
                    {getInterestLevelBadge(selectedInterest.interest_level)}
                  </div>
                </div>
              </div>

              {selectedInterest.investor_email && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Email</label>
                  <p>{selectedInterest.investor_email}</p>
                </div>
              )}

              {selectedInterest.investor_phone && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Phone</label>
                  <p>{selectedInterest.investor_phone}</p>
                </div>
              )}

              {(selectedInterest.investment_range_min || selectedInterest.investment_range_max) && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Investment Range</label>
                  <p className="text-lg font-semibold text-green-600">
                    {selectedInterest.investment_range_min && formatCurrency(selectedInterest.investment_range_min)}
                    {selectedInterest.investment_range_min && selectedInterest.investment_range_max && ' - '}
                    {selectedInterest.investment_range_max && formatCurrency(selectedInterest.investment_range_max)}
                  </p>
                </div>
              )}

              {selectedInterest.message && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Message</label>
                  <p className="p-3 bg-gray-50 rounded mt-1">{selectedInterest.message}</p>
                </div>
              )}

              <div>
                <label className="text-sm font-medium text-muted-foreground">Received At</label>
                <p>{formatDate(selectedInterest.created_at)}</p>
              </div>

              <div>
                <label className="text-sm font-medium mb-2 block">Update Status</label>
                <Select value={newStatus} onValueChange={setNewStatus}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="contacted">Contacted</SelectItem>
                    <SelectItem value="qualified">Qualified</SelectItem>
                    <SelectItem value="not_interested">Not Interested</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <label className="text-sm font-medium mb-2 block">Admin Notes</label>
                <Textarea
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder="Add notes about this investor interest..."
                  rows={4}
                />
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={handleUpdateStatus}
              disabled={updateStatusMutation.isPending}
            >
              {updateStatusMutation.isPending ? 'Updating...' : 'Update Status'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
