// V-PHASE6-1730-127 | V-KYC-ENHANCEMENT-003 (Enhanced Queue with Filters)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { Search, Filter, Clock, CheckCircle, XCircle, AlertCircle } from "lucide-react";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { EnhancedKycVerificationModal } from "@/components/admin/EnhancedKycVerificationModal";
import { useState } from "react";

export default function KycQueuePage() {
  const [selectedKycId, setSelectedKycId] = useState<number | null>(null);
  const [statusFilter, setStatusFilter] = useState('submitted');
  const [searchQuery, setSearchQuery] = useState('');
  const [priorityFilter, setPriorityFilter] = useState('all');

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['kycQueue', statusFilter, searchQuery, priorityFilter],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.append('status', statusFilter);
      if (searchQuery) params.append('search', searchQuery);
      if (priorityFilter !== 'all') params.append('priority', priorityFilter);

      return (await api.get(`/admin/kyc-queue?${params.toString()}`)).data;
    },
  });

  const getStatusBadge = (status: string) => {
    const variants: Record<string, any> = {
      submitted: { variant: 'secondary', icon: Clock, label: 'Pending' },
      under_review: { variant: 'outline', icon: AlertCircle, label: 'Under Review' },
      verified: { variant: 'default', icon: CheckCircle, label: 'Verified' },
      rejected: { variant: 'destructive', icon: XCircle, label: 'Rejected' },
    };

    const config = variants[status] || variants.submitted;
    const Icon = config.icon;

    return (
      <Badge variant={config.variant} className="flex items-center gap-1 w-fit">
        <Icon className="h-3 w-3" />
        {config.label}
      </Badge>
    );
  };

  const getWaitingTime = (submittedAt: string) => {
    const hours = Math.floor((Date.now() - new Date(submittedAt).getTime()) / (1000 * 60 * 60));
    if (hours < 24) return `${hours}h`;
    const days = Math.floor(hours / 24);
    return `${days}d ${hours % 24}h`;
  };

  if (isLoading) return <div className="p-8">Loading KYC queue...</div>;

  const stats = data?.stats || {
    total: 0,
    pending: 0,
    verified: 0,
    rejected: 0,
  };

  return (
    <Dialog open={!!selectedKycId} onOpenChange={(open) => !open && setSelectedKycId(null)}>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">KYC Verification Queue</h1>
          <p className="text-muted-foreground mt-1">
            Review and verify user KYC submissions
          </p>
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold">{stats.total}</div>
              <p className="text-xs text-muted-foreground">Total Submissions</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-yellow-600">{stats.pending}</div>
              <p className="text-xs text-muted-foreground">Pending Review</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-green-600">{stats.verified}</div>
              <p className="text-xs text-muted-foreground">Verified</p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-2xl font-bold text-red-600">{stats.rejected}</div>
              <p className="text-xs text-muted-foreground">Rejected</p>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <Filter className="mr-2 h-5 w-5" />
              Filters
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex gap-4">
              <div className="flex-1">
                <div className="relative">
                  <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input
                    placeholder="Search by username, email, or ID..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    className="pl-10"
                  />
                </div>
              </div>
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="submitted">Pending</SelectItem>
                  <SelectItem value="under_review">Under Review</SelectItem>
                  <SelectItem value="verified">Verified</SelectItem>
                  <SelectItem value="rejected">Rejected</SelectItem>
                </SelectContent>
              </Select>
              <Select value={priorityFilter} onValueChange={setPriorityFilter}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Priority" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Priority</SelectItem>
                  <SelectItem value="high">High Priority</SelectItem>
                  <SelectItem value="normal">Normal</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Queue Table */}
        <Card>
          <CardHeader>
            <CardTitle>KYC Submissions</CardTitle>
            <CardDescription>
              {data?.data?.length || 0} submissions found
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>User</TableHead>
                  <TableHead>Contact</TableHead>
                  <TableHead>Submitted</TableHead>
                  <TableHead>Waiting Time</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data?.map((kyc: any) => (
                  <TableRow key={kyc.id}>
                    <TableCell>
                      <div>
                        <div className="font-medium">{kyc.user.username}</div>
                        <div className="text-xs text-muted-foreground">ID: {kyc.user.id}</div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="text-sm">{kyc.user.email}</div>
                      {kyc.user.phone && (
                        <div className="text-xs text-muted-foreground">{kyc.user.phone}</div>
                      )}
                    </TableCell>
                    <TableCell>
                      {new Date(kyc.submitted_at).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                      })}
                      <div className="text-xs text-muted-foreground">
                        {new Date(kyc.submitted_at).toLocaleTimeString('en-IN', {
                          hour: '2-digit',
                          minute: '2-digit',
                        })}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">{getWaitingTime(kyc.submitted_at)}</Badge>
                    </TableCell>
                    <TableCell>{getStatusBadge(kyc.status)}</TableCell>
                    <TableCell className="text-right">
                      <DialogTrigger asChild>
                        <Button onClick={() => setSelectedKycId(kyc.id)} size="sm">
                          Review
                        </Button>
                      </DialogTrigger>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>

            {!data?.data?.length && (
              <div className="text-center py-12 text-muted-foreground">
                No KYC submissions found matching your filters
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {selectedKycId && (
        <EnhancedKycVerificationModal
          kycId={selectedKycId}
          onClose={() => {
            setSelectedKycId(null);
            refetch();
          }}
        />
      )}
    </Dialog>
  );
}