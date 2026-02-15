// V-KYC-ENHANCEMENT-004 | KYC Statistics & Compliance Reports
// Created: 2025-12-10 | Purpose: KYC analytics dashboard and compliance report generation

'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts';
import {
  Download,
  TrendingUp,
  Clock,
  CheckCircle,
  XCircle,
  Users,
  FileText,
  BarChart3,
} from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useQuery, useMutation } from '@tanstack/react-query';

interface KycStats {
  total_submissions: number;
  pending: number;
  verified: number;
  rejected: number;
  avg_processing_time_hours: number;
  sla_compliance_percentage: number;
  auto_verified_count: number;
  manual_verified_count: number;
}

interface TimeSeriesData {
  date: string;
  submissions: number;
  verifications: number;
  rejections: number;
}

interface DocumentTypeStats {
  doc_type: string;
  total: number;
  verified: number;
  rejected: number;
  pending: number;
}

const COLORS = ['#667eea', '#22c55e', '#ef4444', '#f59e0b', '#3b82f6'];

export default function KycReportsPage() {
  const [dateRange, setDateRange] = useState('30');
  const [reportType, setReportType] = useState('summary');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  // Fetch KYC statistics
  const { data: stats, isLoading: statsLoading } = useQuery<KycStats>({
    queryKey: ['kycStats', dateRange],
    queryFn: async () => (await api.get(`/admin/kyc/statistics?days=${dateRange}`)).data,
  });

  // Fetch time series data
  const { data: timeSeries = [] } = useQuery<TimeSeriesData[]>({
    queryKey: ['kycTimeSeries', dateRange],
    queryFn: async () => (await api.get(`/admin/kyc/time-series?days=${dateRange}`)).data.data,
  });

  // Fetch document type stats
  const { data: docTypeStats = [] } = useQuery<DocumentTypeStats[]>({
    queryKey: ['kycDocTypeStats', dateRange],
    queryFn: async () =>
      (await api.get(`/admin/kyc/document-type-stats?days=${dateRange}`)).data.data,
  });

  // Generate report mutation
  const generateReportMutation = useMutation({
    mutationFn: async (params: any) => {
      const res = await api.post('/admin/kyc/generate-report', params, {
        responseType: 'blob',
      });
      return res.data;
    },
    onSuccess: (blob, variables) => {
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `kyc-report-${variables.type}-${Date.now()}.${variables.format}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      toast.success('Report downloaded successfully');
    },
    onError: () => {
      toast.error('Failed to generate report');
    },
  });

  const handleGenerateReport = (format: 'pdf' | 'csv' | 'xlsx') => {
    generateReportMutation.mutate({
      type: reportType,
      format,
      start_date: startDate,
      end_date: endDate,
      date_range: dateRange,
    });
  };

  const statusData = stats
    ? [
        { name: 'Verified', value: stats.verified, color: '#22c55e' },
        { name: 'Pending', value: stats.pending, color: '#f59e0b' },
        { name: 'Rejected', value: stats.rejected, color: '#ef4444' },
      ]
    : [];

  const verificationMethodData = stats
    ? [
        { name: 'Auto-Verified', value: stats.auto_verified_count },
        { name: 'Manual Verified', value: stats.manual_verified_count },
      ]
    : [];

  if (statsLoading) {
    return <div className="p-8">Loading KYC statistics...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">KYC Reports & Analytics</h1>
          <p className="text-muted-foreground mt-1">
            View KYC statistics and generate compliance reports
          </p>
        </div>
        <Select value={dateRange} onValueChange={setDateRange}>
          <SelectTrigger className="w-[180px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="7">Last 7 Days</SelectItem>
            <SelectItem value="30">Last 30 Days</SelectItem>
            <SelectItem value="90">Last 90 Days</SelectItem>
            <SelectItem value="365">Last Year</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Tabs defaultValue="statistics" className="space-y-4">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="statistics">
            <BarChart3 className="mr-2 h-4 w-4" />
            Statistics
          </TabsTrigger>
          <TabsTrigger value="trends">
            <TrendingUp className="mr-2 h-4 w-4" />
            Trends
          </TabsTrigger>
          <TabsTrigger value="reports">
            <FileText className="mr-2 h-4 w-4" />
            Generate Reports
          </TabsTrigger>
        </TabsList>

        {/* Statistics Tab */}
        <TabsContent value="statistics" className="space-y-4">
          {/* Key Metrics */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Total Submissions</p>
                    <p className="text-3xl font-bold">{stats?.total_submissions || 0}</p>
                  </div>
                  <Users className="h-10 w-10 text-primary opacity-20" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Pending Review</p>
                    <p className="text-3xl font-bold text-yellow-600">{stats?.pending || 0}</p>
                  </div>
                  <Clock className="h-10 w-10 text-yellow-600 opacity-20" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Verified</p>
                    <p className="text-3xl font-bold text-green-600">{stats?.verified || 0}</p>
                  </div>
                  <CheckCircle className="h-10 w-10 text-green-600 opacity-20" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">Rejected</p>
                    <p className="text-3xl font-bold text-red-600">{stats?.rejected || 0}</p>
                  </div>
                  <XCircle className="h-10 w-10 text-red-600 opacity-20" />
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Performance Metrics */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card>
              <CardHeader>
                <CardTitle>Average Processing Time</CardTitle>
                <CardDescription>Time from submission to verification</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-4xl font-bold">
                  {stats?.avg_processing_time_hours.toFixed(1) || 0}
                  <span className="text-lg text-muted-foreground ml-2">hours</span>
                </div>
                <p className="text-sm text-muted-foreground mt-2">
                  Target SLA: 24 hours
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>SLA Compliance</CardTitle>
                <CardDescription>Verifications completed within 24 hours</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-4xl font-bold">
                  {stats?.sla_compliance_percentage.toFixed(1) || 0}
                  <span className="text-lg text-muted-foreground ml-2">%</span>
                </div>
                <p className="text-sm text-muted-foreground mt-2">
                  Target: 95%
                </p>
              </CardContent>
            </Card>
          </div>

          {/* Charts */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card>
              <CardHeader>
                <CardTitle>Status Distribution</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={statusData}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, percent }) => `${name} ${((percent ?? 0) * 100).toFixed(0)}%`}
                      outerRadius={80}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {statusData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Verification Methods</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart data={verificationMethodData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="value" fill="#667eea" />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
          </div>

          {/* Document Type Statistics */}
          <Card>
            <CardHeader>
              <CardTitle>Document Type Statistics</CardTitle>
              <CardDescription>Verification status by document type</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Document Type</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                    <TableHead className="text-right">Verified</TableHead>
                    <TableHead className="text-right">Pending</TableHead>
                    <TableHead className="text-right">Rejected</TableHead>
                    <TableHead className="text-right">Success Rate</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {docTypeStats.map((stat) => (
                    <TableRow key={stat.doc_type}>
                      <TableCell className="font-medium capitalize">
                        {stat.doc_type.replace(/_/g, ' ')}
                      </TableCell>
                      <TableCell className="text-right">{stat.total}</TableCell>
                      <TableCell className="text-right text-green-600">{stat.verified}</TableCell>
                      <TableCell className="text-right text-yellow-600">{stat.pending}</TableCell>
                      <TableCell className="text-right text-red-600">{stat.rejected}</TableCell>
                      <TableCell className="text-right">
                        {stat.total > 0
                          ? ((stat.verified / stat.total) * 100).toFixed(1)
                          : 0}
                        %
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Trends Tab */}
        <TabsContent value="trends" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>KYC Submissions Over Time</CardTitle>
              <CardDescription>Daily submission and verification trends</CardDescription>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={400}>
                <LineChart data={timeSeries}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="date" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Line
                    type="monotone"
                    dataKey="submissions"
                    stroke="#667eea"
                    name="Submissions"
                    strokeWidth={2}
                  />
                  <Line
                    type="monotone"
                    dataKey="verifications"
                    stroke="#22c55e"
                    name="Verifications"
                    strokeWidth={2}
                  />
                  <Line
                    type="monotone"
                    dataKey="rejections"
                    stroke="#ef4444"
                    name="Rejections"
                    strokeWidth={2}
                  />
                </LineChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Verification Status Breakdown</CardTitle>
              <CardDescription>Stacked bar chart showing daily status</CardDescription>
            </CardHeader>
            <CardContent>
              <ResponsiveContainer width="100%" height={400}>
                <BarChart data={timeSeries}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="date" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Bar dataKey="submissions" stackId="a" fill="#667eea" name="Submissions" />
                  <Bar dataKey="verifications" stackId="a" fill="#22c55e" name="Verifications" />
                  <Bar dataKey="rejections" stackId="a" fill="#ef4444" name="Rejections" />
                </BarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Reports Tab */}
        <TabsContent value="reports" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Generate Compliance Reports</CardTitle>
              <CardDescription>
                Export KYC data and compliance reports in various formats
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Report Type</Label>
                  <Select value={reportType} onValueChange={setReportType}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="summary">Summary Report</SelectItem>
                      <SelectItem value="detailed">Detailed Report</SelectItem>
                      <SelectItem value="compliance">Compliance Report</SelectItem>
                      <SelectItem value="sla">SLA Performance</SelectItem>
                      <SelectItem value="rejection_analysis">Rejection Analysis</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Date Range</Label>
                  <Select value={dateRange} onValueChange={setDateRange}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="7">Last 7 Days</SelectItem>
                      <SelectItem value="30">Last 30 Days</SelectItem>
                      <SelectItem value="90">Last 90 Days</SelectItem>
                      <SelectItem value="365">Last Year</SelectItem>
                      <SelectItem value="custom">Custom Range</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                {dateRange === 'custom' && (
                  <>
                    <div className="space-y-2">
                      <Label>Start Date</Label>
                      <Input
                        type="date"
                        value={startDate}
                        onChange={(e) => setStartDate(e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>End Date</Label>
                      <Input
                        type="date"
                        value={endDate}
                        onChange={(e) => setEndDate(e.target.value)}
                      />
                    </div>
                  </>
                )}
              </div>

              <div className="flex gap-4">
                <Button
                  onClick={() => handleGenerateReport('pdf')}
                  disabled={generateReportMutation.isPending}
                >
                  <Download className="mr-2 h-4 w-4" />
                  Download PDF
                </Button>
                <Button
                  variant="outline"
                  onClick={() => handleGenerateReport('csv')}
                  disabled={generateReportMutation.isPending}
                >
                  <Download className="mr-2 h-4 w-4" />
                  Download CSV
                </Button>
                <Button
                  variant="outline"
                  onClick={() => handleGenerateReport('xlsx')}
                  disabled={generateReportMutation.isPending}
                >
                  <Download className="mr-2 h-4 w-4" />
                  Download Excel
                </Button>
              </div>

              <div className="border-t pt-4">
                <h4 className="font-semibold mb-2">Report Contents</h4>
                <ul className="list-disc list-inside space-y-1 text-sm text-muted-foreground">
                  <li>Total KYC submissions and status breakdown</li>
                  <li>Average processing time and SLA compliance</li>
                  <li>Verification success rates by document type</li>
                  <li>Rejection reasons and analysis</li>
                  <li>Auto-verification vs manual verification stats</li>
                  <li>Time series trends and patterns</li>
                </ul>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
