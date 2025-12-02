'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Plus, FileText, Download, Edit, Trash2, Upload } from 'lucide-react';

export default function FinancialReportsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingReport, setEditingReport] = useState<any>(null);
  const [uploadFile, setUploadFile] = useState<File | null>(null);

  const [formData, setFormData] = useState({
    year: new Date().getFullYear().toString(),
    quarter: 'Annual',
    report_type: 'annual_report',
    title: '',
    description: '',
    status: 'draft',
  });

  const { data: reports, isLoading } = useQuery({
    queryKey: ['financial-reports'],
    queryFn: async () => {
      const response = await companyApi.get('/financial-reports');
      return response.data;
    },
  });

  const uploadMutation = useMutation({
    mutationFn: async (data: FormData) => {
      return companyApi.post('/financial-reports', data, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['financial-reports'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success('Financial report uploaded successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to upload report');
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: any }) => {
      return companyApi.put(`/financial-reports/${id}`, data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['financial-reports'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success('Report updated successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to update report');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => companyApi.delete(`/financial-reports/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['financial-reports'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      toast.success('Report deleted successfully');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (editingReport) {
      updateMutation.mutate({ id: editingReport.id, data: formData });
    } else {
      if (!uploadFile) {
        toast.error('Please select a file to upload');
        return;
      }

      const data = new FormData();
      data.append('file', uploadFile);
      data.append('year', formData.year);
      data.append('quarter', formData.quarter);
      data.append('report_type', formData.report_type);
      data.append('title', formData.title);
      data.append('description', formData.description);
      data.append('status', formData.status);

      uploadMutation.mutate(data);
    }
  };

  const handleEdit = (report: any) => {
    setEditingReport(report);
    setFormData({
      year: report.year.toString(),
      quarter: report.quarter,
      report_type: report.report_type,
      title: report.title,
      description: report.description || '',
      status: report.status,
    });
    setIsDialogOpen(true);
  };

  const handleDownload = async (id: number) => {
    try {
      const response = await companyApi.get(`/financial-reports/${id}/download`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `financial-report-${id}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success('Download started');
    } catch (error) {
      toast.error('Download failed');
    }
  };

  const resetForm = () => {
    setEditingReport(null);
    setUploadFile(null);
    setFormData({
      year: new Date().getFullYear().toString(),
      quarter: 'Annual',
      report_type: 'annual_report',
      title: '',
      description: '',
      status: 'draft',
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Financial Reports</h1>
          <p className="text-muted-foreground mt-2">Upload and manage your company's financial reports</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Upload Report
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingReport ? 'Edit Report' : 'Upload Financial Report'}</DialogTitle>
              <DialogDescription>
                {editingReport ? 'Update report details' : 'Upload a PDF file with your financial report'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              {!editingReport && (
                <div className="space-y-2">
                  <Label htmlFor="file">PDF File *</Label>
                  <Input
                    id="file"
                    type="file"
                    accept=".pdf"
                    onChange={(e) => setUploadFile(e.target.files?.[0] || null)}
                    required
                  />
                  <p className="text-xs text-muted-foreground">Maximum file size: 10MB</p>
                </div>
              )}
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="year">Year *</Label>
                  <Input
                    id="year"
                    type="number"
                    min="2000"
                    max={new Date().getFullYear() + 1}
                    value={formData.year}
                    onChange={(e) => setFormData({ ...formData, year: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="quarter">Quarter *</Label>
                  <Select
                    value={formData.quarter}
                    onValueChange={(value) => setFormData({ ...formData, quarter: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Q1">Q1</SelectItem>
                      <SelectItem value="Q2">Q2</SelectItem>
                      <SelectItem value="Q3">Q3</SelectItem>
                      <SelectItem value="Q4">Q4</SelectItem>
                      <SelectItem value="Annual">Annual</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="report_type">Report Type *</Label>
                  <Select
                    value={formData.report_type}
                    onValueChange={(value) => setFormData({ ...formData, report_type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="financial_statement">Financial Statement</SelectItem>
                      <SelectItem value="balance_sheet">Balance Sheet</SelectItem>
                      <SelectItem value="cash_flow">Cash Flow</SelectItem>
                      <SelectItem value="income_statement">Income Statement</SelectItem>
                      <SelectItem value="annual_report">Annual Report</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="status">Status *</Label>
                  <Select
                    value={formData.status}
                    onValueChange={(value) => setFormData({ ...formData, status: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="draft">Draft</SelectItem>
                      <SelectItem value="published">Published</SelectItem>
                      <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="title">Title *</Label>
                <Input
                  id="title"
                  placeholder="e.g., Annual Report 2024"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  rows={3}
                  placeholder="Brief description of the report"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={uploadMutation.isPending || updateMutation.isPending}>
                  <Upload className="mr-2 h-4 w-4" />
                  {uploadMutation.isPending || updateMutation.isPending ? 'Saving...' : editingReport ? 'Update' : 'Upload'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Financial Reports</CardTitle>
          <CardDescription>
            {reports?.data?.length || 0} reports uploaded
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading reports...</div>
          ) : reports?.data?.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              No financial reports uploaded yet. Upload your first report to get started!
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Year</TableHead>
                  <TableHead>Quarter</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {reports?.data?.map((report: any) => (
                  <TableRow key={report.id}>
                    <TableCell className="font-medium">{report.title}</TableCell>
                    <TableCell>{report.year}</TableCell>
                    <TableCell>{report.quarter}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{report.report_type.replace('_', ' ')}</Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant={report.status === 'published' ? 'default' : 'secondary'}>
                        {report.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleDownload(report.id)}
                        >
                          <Download className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(report)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => deleteMutation.mutate(report.id)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
