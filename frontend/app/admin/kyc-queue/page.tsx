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
import { Search, Filter, Eye, RefreshCw } from "lucide-react";
// [FIX] Named import matches the updated component
import { PaginationControls } from '@/components/shared/PaginationControls'; 
import { EnhancedKycVerificationModal } from '@/components/admin/EnhancedKycVerificationModal';

export default function KycQueuePage() {
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('submitted');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedKycId, setSelectedKycId] = useState<number | null>(null);

  // 1. Fetch KYC Queue Data
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['kycQueue', page, statusFilter, searchQuery],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        status: statusFilter,
      });
      
      if (searchQuery) {
        params.append('search', searchQuery);
      }

      const res = await api.get(`/admin/kyc-queue?${params.toString()}`);
      return res.data; 
    },
    placeholderData: (previousData) => previousData,
  });

  // 2. Fetch Stats
  const { data: stats } = useQuery({
    queryKey: ['kycStats'],
    queryFn: async () => (await api.get('/admin/kyc-queue/stats')).data,
    staleTime: 60 * 1000, 
  });

  const handleStatusChange = (val: string) => {
    setStatusFilter(val);
    setPage(1); 
  };

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchQuery(e.target.value);
    setPage(1); 
  };

  const handleVerificationComplete = () => {
    setSelectedKycId(null);
    refetch(); 
  };

  return (
    <div className="space-y-6 p-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold tracking-tight">KYC Verification Queue</h1>
        <Button variant="outline" onClick={() => refetch()}>
          <RefreshCw className="mr-2 h-4 w-4" /> Refresh
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Pending Review</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">{stats?.submitted || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Verified Users</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">{stats?.verified || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Rejected</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">{stats?.rejected || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Processed</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-4 items-center bg-card p-4 rounded-lg border shadow-sm">
        <div className="relative flex-1 w-full">
          <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
          <Input 
            placeholder="Search by Name, Email, or Phone..." 
            className="pl-8" 
            value={searchQuery} 
            onChange={handleSearch} 
          />
        </div>
        <div className="flex items-center gap-2 w-full sm:w-auto">
          <Filter className="h-4 w-4 text-muted-foreground" />
          <Select value={statusFilter} onValueChange={handleStatusChange}>
            <SelectTrigger className="w-[180px]">
              <SelectValue placeholder="Filter Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="submitted">Pending Review</SelectItem>
              <SelectItem value="verified">Verified</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
              <SelectItem value="resubmission_required">Resubmission Req</SelectItem>
              <SelectItem value="all">All Records</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Data Table */}
      <Card>
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User Details</TableHead>
                <TableHead>Date Submitted</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Mobile</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center h-24">Loading data...</TableCell>
                </TableRow>
              ) : data?.data?.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center h-24 text-muted-foreground">
                    No records found matching your filters.
                  </TableCell>
                </TableRow>
              ) : (
                data?.data?.map((item: any) => (
                  <TableRow key={item.id}>
                    <TableCell>
                      <div className="font-medium">{item.user?.username || 'Unknown User'}</div>
                      <div className="text-xs text-muted-foreground">{item.user?.email}</div>
                    </TableCell>
                    <TableCell>
                      {new Date(item.submitted_at).toLocaleDateString()}
                      <div className="text-xs text-muted-foreground">{new Date(item.submitted_at).toLocaleTimeString()}</div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={
                        item.status === 'verified' ? 'success' : 
                        item.status === 'rejected' ? 'destructive' : 
                        item.status === 'submitted' ? 'warning' : 'secondary'
                      }>
                        {item.status.toUpperCase().replace('_', ' ')}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {item.user?.mobile || 'N/A'}
                    </TableCell>
                    <TableCell className="text-right">
                      <Button size="sm" onClick={() => setSelectedKycId(item.id)}>
                        <Eye className="mr-2 h-3 w-3" /> Review
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* [FIX]: Passing exact range values from server to ensure text matches logic */}
      {data && (
        <PaginationControls
          currentPage={data.current_page}
          totalPages={data.last_page}
          onPageChange={setPage}
          totalItems={data.total}
          from={data.from} 
          to={data.to}    
        />
      )}

      {/* Verification Modal */}
      {selectedKycId && (
        <EnhancedKycVerificationModal 
          kycId={selectedKycId} 
          onClose={handleVerificationComplete} 
        />
      )}
    </div>
  );
}