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
import {
  Search,
  RefreshCw,
  Eye,
  Download,
  GitCommit,
  Lock,
  Unlock,
  History,
  Star,
  Calendar
} from "lucide-react";
import { PaginationControls } from '@/components/shared/PaginationControls';
import { format } from 'date-fns';

/**
 * Company Version History Page (FIX 33, 34, 35)
 *
 * Displays comprehensive version history for companies including:
 * - All version snapshots with changed fields
 * - Approval snapshots (immutability markers)
 * - Data protection status
 * - Field change history
 * - Version comparison capability
 */
export default function CompanyVersionsPage() {
  const [page, setPage] = useState(1);
  const [companyFilter, setCompanyFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');
  const [approvalSnapshotsOnly, setApprovalSnapshotsOnly] = useState(false);

  // Fetch Company Versions
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['companyVersions', page, companyFilter, searchQuery, approvalSnapshotsOnly],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: '50',
      });

      if (companyFilter) {
        params.append('company_id', companyFilter);
      }
      if (searchQuery) {
        params.append('search', searchQuery);
      }
      if (approvalSnapshotsOnly) {
        params.append('approval_snapshots', 'true');
      }

      const res = await api.get(`/admin/company-versions?${params.toString()}`);
      return res.data;
    },
    placeholderData: (previousData) => previousData,
  });

  // Fetch Stats
  const { data: statsData } = useQuery({
    queryKey: ['companyVersionsStats'],
    queryFn: async () => (await api.get('/admin/company-versions/stats')).data.stats,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  const stats = data?.stats || statsData || {};
  const versions = data?.versions?.data || [];
  const pagination = data?.versions;

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Company Version History</h1>
          <p className="text-muted-foreground">Track changes and data immutability for companies</p>
        </div>
        <Button variant="outline" onClick={() => refetch()}>
          <RefreshCw className="mr-2 h-4 w-4" /> Refresh
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Versions</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.total_versions || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Companies Versioned</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-blue-600">{stats.companies_with_versions || 0}</div>
            <div className="text-xs text-muted-foreground mt-1">
              of {stats.total_companies || 0} total
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Approval Snapshots</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">{stats.approval_snapshots || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Protected Companies</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Lock className="h-5 w-5 text-orange-600" />
              <div className="text-2xl font-bold text-orange-600">{stats.protected_companies || 0}</div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Activity */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              <Calendar className="inline-block h-4 w-4 mr-1" /> Versions Today
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.versions_today || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              <Calendar className="inline-block h-4 w-4 mr-1" /> This Week
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.versions_this_week || 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              <Calendar className="inline-block h-4 w-4 mr-1" /> This Month
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.versions_this_month || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                placeholder="Search company name..."
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setPage(1);
                }}
                className="pl-10"
              />
            </div>

            <div className="flex items-center space-x-2">
              <input
                type="checkbox"
                id="approvalSnapshots"
                checked={approvalSnapshotsOnly}
                onChange={(e) => {
                  setApprovalSnapshotsOnly(e.target.checked);
                  setPage(1);
                }}
                className="rounded border-gray-300"
              />
              <label htmlFor="approvalSnapshots" className="text-sm font-medium cursor-pointer flex items-center gap-2">
                <Star className="h-4 w-4 text-yellow-600" />
                Approval snapshots only
              </label>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Versions Table */}
      <Card>
        <CardHeader>
          <CardTitle>Version History</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8 text-muted-foreground">Loading versions...</div>
          ) : versions.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <History className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No version history found</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Version #</TableHead>
                    <TableHead>Company</TableHead>
                    <TableHead>Changed Fields</TableHead>
                    <TableHead>Summary</TableHead>
                    <TableHead>Created By</TableHead>
                    <TableHead>Timestamp</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {versions.map((version: any) => (
                    <TableRow
                      key={version.id}
                      className={version.is_approval_snapshot ? 'bg-yellow-50' : ''}
                    >
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <GitCommit className="h-4 w-4 text-muted-foreground" />
                          <span className="font-mono font-bold">v{version.version_number}</span>
                          {version.is_approval_snapshot && (
                            <Star className="h-4 w-4 text-yellow-600 fill-yellow-600" />
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="font-medium">{version.company?.name || 'N/A'}</div>
                        <div className="text-xs text-muted-foreground">{version.company?.slug}</div>
                      </TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          {version.changed_fields?.slice(0, 3).map((field: string) => (
                            <Badge key={field} variant="outline" className="text-xs">
                              {field.replace('_', ' ')}
                            </Badge>
                          ))}
                          {version.changed_fields?.length > 3 && (
                            <Badge variant="outline" className="text-xs">
                              +{version.changed_fields.length - 3} more
                            </Badge>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="max-w-xs">
                        <div className="text-sm text-muted-foreground truncate">
                          {version.change_summary || 'No summary'}
                        </div>
                        {version.is_approval_snapshot && (
                          <Badge className="mt-1 bg-yellow-100 text-yellow-800 text-xs">
                            Approval Snapshot
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        <div className="text-sm">{version.creator?.name || 'System'}</div>
                        <div className="text-xs text-muted-foreground">{version.creator?.email}</div>
                      </TableCell>
                      <TableCell className="text-sm">
                        {format(new Date(version.created_at), 'MMM dd, yyyy HH:mm')}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => window.location.href = `/admin/company-versions/${version.id}`}
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

      {/* Most Versioned Companies */}
      {stats.most_versioned_companies && stats.most_versioned_companies.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Most Versioned Companies</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {stats.most_versioned_companies.slice(0, 10).map((item: any) => (
                <div key={item.company_id} className="flex justify-between items-center p-2 border rounded">
                  <div className="font-medium">{item.company?.name || `Company #${item.company_id}`}</div>
                  <Badge variant="secondary">{item.version_count} versions</Badge>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
