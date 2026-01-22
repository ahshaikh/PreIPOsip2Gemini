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
import { Plus, Search, Edit, Trash2, Building2 } from 'lucide-react';

export default function CompaniesManagementPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCompany, setEditingCompany] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');

  const [formData, setFormData] = useState({
    name: '',
    sector: '',
    description: '',
    website: '',
    founded_year: '',
    headquarters: '',
    ceo_name: '',
    latest_valuation: '',
    funding_stage: '',
    total_funding: '',
    linkedin_url: '',
    twitter_url: '',
    is_featured: false,
    is_verified: false, // FIX: Added is_verified for admin to verify companies
    status: 'active',
  });

  const { data: companies, isLoading } = useQuery({
    queryKey: ['admin-companies', searchQuery],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      const response = await api.get(`/admin/companies?${params.toString()}`);
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingCompany) {
        return api.put(`/admin/companies/${editingCompany.id}`, data);
      } else {
        return api.post('/admin/companies', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-companies'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingCompany ? 'Company updated successfully' : 'Company created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to save company');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => api.delete(`/admin/companies/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-companies'] });
      toast.success('Company deleted successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to delete company');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (company: any) => {
    setEditingCompany(company);
    setFormData({
      name: company.name || '',
      sector: company.sector || '',
      description: company.description || '',
      website: company.website || '',
      founded_year: company.founded_year || '',
      headquarters: company.headquarters || '',
      ceo_name: company.ceo_name || '',
      latest_valuation: company.latest_valuation || '',
      funding_stage: company.funding_stage || '',
      total_funding: company.total_funding || '',
      linkedin_url: company.linkedin_url || '',
      twitter_url: company.twitter_url || '',
      is_featured: company.is_featured || false,
      is_verified: company.is_verified || false, // FIX: Include is_verified when editing
      status: company.status || 'active',
    });
    setIsDialogOpen(true);
  };

  const handleDelete = async (id: number) => {
    if (confirm('Are you sure you want to delete this company?')) {
      deleteMutation.mutate(id);
    }
  };

  const resetForm = () => {
    setEditingCompany(null);
    setFormData({
      name: '',
      sector: '',
      description: '',
      website: '',
      founded_year: '',
      headquarters: '',
      ceo_name: '',
      latest_valuation: '',
      funding_stage: '',
      total_funding: '',
      linkedin_url: '',
      twitter_url: '',
      is_featured: false,
      is_verified: false, // FIX: Reset is_verified to false
      status: 'active',
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Companies Directory</h1>
          <p className="text-muted-foreground">Manage your companies directory and profiles</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Add Company
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingCompany ? 'Edit Company' : 'Add New Company'}</DialogTitle>
              <DialogDescription>
                Fill in the company details
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="name">Company Name *</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="sector">Sector *</Label>
                  <Input
                    id="sector"
                    value={formData.sector}
                    onChange={(e) => setFormData({ ...formData, sector: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="website">Website</Label>
                  <Input
                    id="website"
                    type="url"
                    value={formData.website}
                    onChange={(e) => setFormData({ ...formData, website: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="founded_year">Founded Year</Label>
                  <Input
                    id="founded_year"
                    value={formData.founded_year}
                    onChange={(e) => setFormData({ ...formData, founded_year: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="headquarters">Headquarters</Label>
                  <Input
                    id="headquarters"
                    value={formData.headquarters}
                    onChange={(e) => setFormData({ ...formData, headquarters: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="ceo_name">CEO Name</Label>
                  <Input
                    id="ceo_name"
                    value={formData.ceo_name}
                    onChange={(e) => setFormData({ ...formData, ceo_name: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="latest_valuation">Latest Valuation (₹)</Label>
                  <Input
                    id="latest_valuation"
                    type="number"
                    value={formData.latest_valuation}
                    onChange={(e) => setFormData({ ...formData, latest_valuation: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="total_funding">Total Funding (₹)</Label>
                  <Input
                    id="total_funding"
                    type="number"
                    value={formData.total_funding}
                    onChange={(e) => setFormData({ ...formData, total_funding: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="funding_stage">Funding Stage</Label>
                  <Input
                    id="funding_stage"
                    value={formData.funding_stage}
                    placeholder="e.g., Series A, Series B"
                    onChange={(e) => setFormData({ ...formData, funding_stage: e.target.value })}
                  />
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
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="linkedin_url">LinkedIn URL</Label>
                  <Input
                    id="linkedin_url"
                    type="url"
                    value={formData.linkedin_url}
                    onChange={(e) => setFormData({ ...formData, linkedin_url: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="twitter_url">Twitter URL</Label>
                  <Input
                    id="twitter_url"
                    type="url"
                    value={formData.twitter_url}
                    onChange={(e) => setFormData({ ...formData, twitter_url: e.target.value })}
                  />
                </div>
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
                  id="is_featured"
                  checked={formData.is_featured}
                  onChange={(e) => setFormData({ ...formData, is_featured: e.target.checked })}
                  className="mr-2"
                />
                <Label htmlFor="is_featured">Featured Company</Label>
              </div>
              <div className="space-y-2 flex items-center">
                <input
                  type="checkbox"
                  id="is_verified"
                  checked={formData.is_verified}
                  onChange={(e) => setFormData({ ...formData, is_verified: e.target.checked })}
                  className="mr-2"
                />
                <Label htmlFor="is_verified">Verified Company (Required for public visibility)</Label>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={saveMutation.isPending}>
                  {saveMutation.isPending ? 'Saving...' : editingCompany ? 'Update' : 'Create'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>All Companies</CardTitle>
            <div className="relative w-64">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search companies..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
          <CardDescription>
            {companies?.data?.length || 0} companies found
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading companies...</div>
          ) : companies?.data?.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              No companies found. Add your first company to get started!
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Sector</TableHead>
                    <TableHead>Headquarters</TableHead>
                    <TableHead>Funding Stage</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Featured</TableHead>
                    <TableHead>Verified</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {companies?.data?.map((company: any) => (
                    <TableRow key={company.id}>
                      <TableCell className="font-medium">{company.name}</TableCell>
                      <TableCell>{company.sector}</TableCell>
                      <TableCell>{company.headquarters || '-'}</TableCell>
                      <TableCell>{company.funding_stage || '-'}</TableCell>
                      <TableCell>
                        <Badge variant={company.status === 'active' ? 'default' : 'secondary'}>
                          {company.status}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {company.is_featured && <Badge variant="secondary">⭐ Featured</Badge>}
                      </TableCell>
                      <TableCell>
                        {company.is_verified ? (
                          <Badge variant="default" className="bg-green-600">✓ Verified</Badge>
                        ) : (
                          <Badge variant="destructive">✗ Not Verified</Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleEdit(company)}
                          >
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleDelete(company.id)}
                            disabled={deleteMutation.isPending}
                          >
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
