'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import {
  AlertCircle,
  CheckCircle2,
  Clock,
  XCircle,
  RefreshCw,
  Eye,
  AlertTriangle,
  TrendingUp,
  TrendingDown,
  Activity,
} from 'lucide-react';
import api from '@/lib/api';

interface SagaStats {
  by_status: {
    processing: number;
    completed: number;
    failed: number;
    compensated: number;
    compensation_failed: number;
    requires_manual_resolution: number;
    manually_resolved: number;
  };
  time_periods: {
    last_24h: {
      total: number;
      completed: number;
      failed: number;
    };
    last_7d: {
      total: number;
      completed: number;
      failed: number;
    };
    last_30d: {
      total: number;
      completed: number;
      failed: number;
    };
  };
  success_rate: {
    last_24h: number;
    last_7d: number;
    last_30d: number;
  };
  needs_attention: number;
}

interface Saga {
  id: number;
  saga_id: string;
  status: string;
  steps_completed: number;
  steps_total: number;
  failure_reason?: string;
  initiated_at: string;
  completed_at?: string;
  failed_at?: string;
  compensated_at?: string;
  resolved_at?: string;
  payment?: {
    id: number;
    amount: number;
    status: string;
  };
  user?: {
    id: number;
    username: string;
    email: string;
  };
  duration?: string;
  needs_attention: boolean;
}

export default function SagaManagementPage() {
  const [stats, setStats] = useState<SagaStats | null>(null);
  const [sagas, setSagas] = useState<Saga[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('failed,compensation_failed,requires_manual_resolution');
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const router = useRouter();

  useEffect(() => {
    fetchStats();
    fetchSagas();
  }, [statusFilter, searchTerm, currentPage]);

  const fetchStats = async () => {
    try {
      const response = await api.get('/admin/sagas/stats');
      setStats(response.data.data);
    } catch (error) {
      console.error('Failed to fetch saga stats:', error);
    }
  };

  const fetchSagas = async () => {
    setLoading(true);
    try {
      const response = await api.get('/admin/sagas', {
        params: {
          status: statusFilter,
          search: searchTerm,
          page: currentPage,
          per_page: 20,
        },
      });
      setSagas(response.data.data.data);
      setTotalPages(response.data.data.last_page);
    } catch (error) {
      console.error('Failed to fetch sagas:', error);
    } finally {
      setLoading(false);
    }
  };

  const runRecovery = async () => {
    try {
      const response = await api.post('/admin/sagas/recovery/run');
      alert(`Recovery completed: ${response.data.data.sagas_recovered} sagas flagged`);
      fetchSagas();
      fetchStats();
    } catch (error) {
      alert('Recovery failed');
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { color: string; icon: any }> = {
      processing: { color: 'bg-blue-100 text-blue-800', icon: Clock },
      completed: { color: 'bg-green-100 text-green-800', icon: CheckCircle2 },
      failed: { color: 'bg-red-100 text-red-800', icon: XCircle },
      compensated: { color: 'bg-yellow-100 text-yellow-800', icon: RefreshCw },
      compensation_failed: { color: 'bg-orange-100 text-orange-800', icon: AlertCircle },
      requires_manual_resolution: { color: 'bg-purple-100 text-purple-800', icon: AlertTriangle },
      manually_resolved: { color: 'bg-gray-100 text-gray-800', icon: CheckCircle2 },
    };

    const variant = variants[status] || variants.failed;
    const Icon = variant.icon;

    return (
      <Badge className={variant.color}>
        <Icon className="w-3 h-3 mr-1" />
        {status.replace(/_/g, ' ').toUpperCase()}
      </Badge>
    );
  };

  const getSuccessRateTrend = (rate: number) => {
    if (rate >= 95) return { color: 'text-green-600', icon: TrendingUp };
    if (rate >= 80) return { color: 'text-yellow-600', icon: Activity };
    return { color: 'text-red-600', icon: TrendingDown };
  };

  return (
    <div className="container mx-auto p-6 space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Saga Management</h1>
          <p className="text-gray-600">Monitor and resolve payment allocation sagas</p>
        </div>
        <Button onClick={runRecovery} variant="outline">
          <RefreshCw className="w-4 h-4 mr-2" />
          Run Recovery
        </Button>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                Needs Attention
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="text-3xl font-bold text-red-600">
                  {stats.needs_attention}
                </div>
                <AlertCircle className="w-8 h-8 text-red-600" />
              </div>
              <p className="text-xs text-gray-500 mt-2">
                Failed or stuck sagas
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                24h Success Rate
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="text-3xl font-bold">
                  {stats.success_rate.last_24h}%
                </div>
                {(() => {
                  const trend = getSuccessRateTrend(stats.success_rate.last_24h);
                  const Icon = trend.icon;
                  return <Icon className={`w-8 h-8 ${trend.color}`} />;
                })()}
              </div>
              <p className="text-xs text-gray-500 mt-2">
                {stats.time_periods.last_24h.completed} / {stats.time_periods.last_24h.total} completed
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                7d Success Rate
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="text-3xl font-bold">
                  {stats.success_rate.last_7d}%
                </div>
                {(() => {
                  const trend = getSuccessRateTrend(stats.success_rate.last_7d);
                  const Icon = trend.icon;
                  return <Icon className={`w-8 h-8 ${trend.color}`} />;
                })()}
              </div>
              <p className="text-xs text-gray-500 mt-2">
                {stats.time_periods.last_7d.completed} / {stats.time_periods.last_7d.total} completed
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                Compensation Failed
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="text-3xl font-bold text-orange-600">
                  {stats.by_status.compensation_failed}
                </div>
                <AlertTriangle className="w-8 h-8 text-orange-600" />
              </div>
              <p className="text-xs text-gray-500 mt-2">
                Requires immediate attention
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle>Saga List</CardTitle>
          <CardDescription>Filter and view saga executions</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-4">
            <div className="flex-1">
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger>
                  <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="failed,compensation_failed,requires_manual_resolution">
                    Needs Attention
                  </SelectItem>
                  <SelectItem value="processing">Processing</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                  <SelectItem value="failed">Failed</SelectItem>
                  <SelectItem value="compensated">Compensated</SelectItem>
                  <SelectItem value="compensation_failed">Compensation Failed</SelectItem>
                  <SelectItem value="requires_manual_resolution">Manual Resolution</SelectItem>
                  <SelectItem value="manually_resolved">Manually Resolved</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="flex-1">
              <Input
                placeholder="Search by saga ID..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>

          {/* Table */}
          <div className="border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Saga ID</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Progress</TableHead>
                  <TableHead>User</TableHead>
                  <TableHead>Payment</TableHead>
                  <TableHead>Duration</TableHead>
                  <TableHead>Initiated</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center py-8">
                      <RefreshCw className="w-6 h-6 animate-spin mx-auto mb-2" />
                      Loading sagas...
                    </TableCell>
                  </TableRow>
                ) : sagas.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} className="text-center py-8 text-gray-500">
                      No sagas found
                    </TableCell>
                  </TableRow>
                ) : (
                  sagas.map((saga) => (
                    <TableRow key={saga.id} className={saga.needs_attention ? 'bg-red-50' : ''}>
                      <TableCell className="font-mono text-xs">
                        {saga.saga_id.substring(0, 24)}...
                      </TableCell>
                      <TableCell>{getStatusBadge(saga.status)}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <div className="w-full bg-gray-200 rounded-full h-2 max-w-[100px]">
                            <div
                              className={`h-2 rounded-full ${
                                saga.status === 'completed'
                                  ? 'bg-green-500'
                                  : saga.status === 'failed'
                                  ? 'bg-red-500'
                                  : 'bg-blue-500'
                              }`}
                              style={{
                                width: `${(saga.steps_completed / saga.steps_total) * 100}%`,
                              }}
                            />
                          </div>
                          <span className="text-xs text-gray-600">
                            {saga.steps_completed}/{saga.steps_total}
                          </span>
                        </div>
                      </TableCell>
                      <TableCell>
                        {saga.user ? (
                          <div>
                            <div className="font-medium">{saga.user.username}</div>
                            <div className="text-xs text-gray-500">{saga.user.email}</div>
                          </div>
                        ) : (
                          <span className="text-gray-400">N/A</span>
                        )}
                      </TableCell>
                      <TableCell>
                        {saga.payment ? (
                          <div>
                            <div className="font-medium">₹{saga.payment.amount}</div>
                            <div className="text-xs text-gray-500">ID: {saga.payment.id}</div>
                          </div>
                        ) : (
                          <span className="text-gray-400">N/A</span>
                        )}
                      </TableCell>
                      <TableCell>{saga.duration || 'N/A'}</TableCell>
                      <TableCell className="text-xs text-gray-600">
                        {new Date(saga.initiated_at).toLocaleString()}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => router.push(`/admin/sagas/${saga.id}`)}
                        >
                          <Eye className="w-4 h-4 mr-1" />
                          View
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex justify-center gap-2 mt-4">
              <Button
                size="sm"
                variant="outline"
                disabled={currentPage === 1}
                onClick={() => setCurrentPage(currentPage - 1)}
              >
                Previous
              </Button>
              <span className="flex items-center px-4 text-sm">
                Page {currentPage} of {totalPages}
              </span>
              <Button
                size="sm"
                variant="outline"
                disabled={currentPage === totalPages}
                onClick={() => setCurrentPage(currentPage + 1)}
              >
                Next
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
