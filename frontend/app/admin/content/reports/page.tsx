'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
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
import { Plus, Search, Edit, Trash2, FileText } from 'lucide-react';

export default function ReportsManagementPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingReport, setEditingReport] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');

  const [formData, setFormData] = useState({
    title: '',
    description: '',
    type: 'market_analysis',
    file_path: '',
    author: '',
    access_level: 'registered',
    requires_subscription: false,
    status: 'draft',
  });

  const { data: reports, isLoading } = useQuery({
    queryKey: ['admin-reports', searchQuery],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      const response = await api.get(`/admin/content-reports?${params.toString()}`);
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingReport) {
        return api.put(`/admin/content-reports/${editingReport.id}`, data);
      } else {
        return api.post('/admin/content-reports', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-reports'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingReport ? 'Report updated' : 'Report created');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to save report');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => api.delete(`/admin/content-reports/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-reports'] });
      toast.success('Report deleted');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (report: any) => {
    setEditingReport(report);
    setFormData({
      title: report.title || '',
      description: report.description || '',
      type: report.type || 'market_analysis',
      file_path: report.file_path || '',
      author: report.author || '',
      access_level: report.access_level || 'registered',
      requires_subscription: report.requires_subscription || false,
      status: report.status || 'draft',
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingReport(null);
    setFormData({
      title: '',
      description: '',
      type: 'market_analysis',
      file_path: '',
      author: '',
      access_level: 'registered',
      requires_subscription: false,
      status: 'draft',
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Reports & Market Analysis</h1>
          <p className="text-muted-foreground">Manage research reports and market analysis</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Add Report
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingReport ? 'Edit Report' : 'Add Report'}</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Title *</Label>
                <Input
                  id="title"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  required
                />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="type">Type *</Label>
                  <Select
                    value={formData.type}
                    onValueChange={(value) => setFormData({ ...formData, type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="market_analysis">Market Analysis</SelectItem>
                      <SelectItem value="research">Research</SelectItem>
                      <SelectItem value="white_paper">White Paper</SelectItem>
                      <SelectItem value="case_study">Case Study</SelectItem>
                      <SelectItem value="guide">Guide</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="author">Author</Label>
                  <Input
                    id="author"
                    value={formData.author}
                    onChange={(e) => setFormData({ ...formData, author: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="access_level">Access Level *</Label>
                  <Select
                    value={formData.access_level}
                    onValueChange={(value) => setFormData({ ...formData, access_level: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="public">Public</SelectItem>
                      <SelectItem value="registered">Registered Users</SelectItem>
                      <SelectItem value="premium">Premium Only</SelectItem>
                      <SelectItem value="admin">Admin Only</SelectItem>
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
                <Label htmlFor="file_path">File Path (PDF) *</Label>
                <Input
                  id="file_path"
                  value={formData.file_path}
                  onChange={(e) => setFormData({ ...formData, file_path: e.target.value })}
                  placeholder="/uploads/reports/analysis.pdf"
                  required
                />
                <p className="text-xs text-muted-foreground">Upload the file first, then paste the path here</p>
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={4}
                />
              </div>
              <div className="space-y-2 flex items-center">
                <input
                  type="checkbox"
                  id="requires_subscription"
                  checked={formData.requires_subscription}
                  onChange={(e) => setFormData({ ...formData, requires_subscription: e.target.checked })}
                  className="mr-2"
                />
                <Label htmlFor="requires_subscription">Requires Subscription</Label>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={saveMutation.isPending}>
                  {saveMutation.isPending ? 'Saving...' : 'Save'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>All Reports</CardTitle>
            <div className="relative w-64">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search reports..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading reports...</div>
          ) : reports?.data?.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              No reports found. Add your first report!
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Author</TableHead>
                  <TableHead>Access</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {reports?.data?.map((report: any) => (
                  <TableRow key={report.id}>
                    <TableCell className="font-medium">{report.title}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{report.type.replace('_', ' ')}</Badge>
                    </TableCell>
                    <TableCell>{report.author || '-'}</TableCell>
                    <TableCell>
                      <Badge variant="secondary">{report.access_level}</Badge>
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
