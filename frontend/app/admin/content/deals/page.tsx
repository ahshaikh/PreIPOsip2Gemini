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
import { Plus, Search, Edit, Trash2, TrendingUp, Eye } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function DealsManagementPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingDeal, setEditingDeal] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterDealType, setFilterDealType] = useState('all');
  const [filterStatus, setFilterStatus] = useState('all');

  // Form state - FIX: Changed company_name to company_id (foreign key)
  const [formData, setFormData] = useState({
    title: '',
    company_id: '',
    product_id: '',
    sector: '',
    deal_type: 'upcoming',
    description: '',
    min_investment: '',
    max_investment: '',
    valuation: '',
    share_price: '',
    total_shares: '',
    available_shares: '',
    deal_opens_at: '',
    deal_closes_at: '',
    video_url: '',
    status: 'draft',
    is_featured: false,
  });

  // Fetch deals
  const { data: deals, isLoading } = useQuery({
    queryKey: ['admin-deals', searchQuery, filterDealType, filterStatus],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      if (filterDealType !== 'all') params.append('deal_type', filterDealType);
      if (filterStatus !== 'all') params.append('status', filterStatus);

      const response = await api.get(`/admin/deals?${params.toString()}`);
      return response.data;
    },
  });

  // Fetch statistics
  const { data: stats } = useQuery({
    queryKey: ['admin-deals-stats'],
    queryFn: async () => {
      const response = await api.get('/admin/deals/statistics');
      return response.data;
    },
  });

  // FIX: Fetch companies list for dropdown
  const { data: companiesData } = useQuery({
    queryKey: ['companies-list'],
    queryFn: async () => {
      const response = await api.get('/admin/companies?per_page=100&status=active');
      return response.data;
    },
  });

  // FIX: Fetch products list for dropdown
  const { data: productsData } = useQuery({
    queryKey: ['products-list'],
    queryFn: async () => {
      const response = await api.get('/admin/products?per_page=100&status=active');
      return response.data;
    },
  });

  const companies = companiesData?.data || [];
  const products = productsData?.data || [];

  // Create/Update mutation
  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingDeal) {
        return api.put(`/admin/deals/${editingDeal.id}`, data);
      } else {
        return api.post('/admin/deals', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-deals'] });
      queryClient.invalidateQueries({ queryKey: ['admin-deals-stats'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingDeal ? 'Deal updated successfully' : 'Deal created successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to save deal');
    },
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      return api.delete(`/admin/deals/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-deals'] });
      queryClient.invalidateQueries({ queryKey: ['admin-deals-stats'] });
      toast.success('Deal deleted successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to delete deal');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (deal: any) => {
    setEditingDeal(deal);
    // FIX: Use company_id and product_id from the deal relationship
    setFormData({
      title: deal.title || '',
      company_id: deal.company_id?.toString() || '',
      product_id: deal.product_id?.toString() || '',
      sector: deal.sector || '',
      deal_type: deal.deal_type || 'upcoming',
      description: deal.description || '',
      min_investment: deal.min_investment || '',
      max_investment: deal.max_investment || '',
      valuation: deal.valuation || '',
      share_price: deal.share_price || '',
      total_shares: deal.total_shares || '',
      available_shares: deal.available_shares || '',
      deal_opens_at: deal.deal_opens_at ? deal.deal_opens_at.split('T')[0] : '',
      deal_closes_at: deal.deal_closes_at ? deal.deal_closes_at.split('T')[0] : '',
      video_url: deal.video_url || '',
      status: deal.status || 'draft',
      is_featured: deal.is_featured || false,
    });
    setIsDialogOpen(true);
  };

  const handleDelete = async (id: number) => {
    if (confirm('Are you sure you want to delete this deal?')) {
      deleteMutation.mutate(id);
    }
  };

  const resetForm = () => {
    setEditingDeal(null);
    setFormData({
      title: '',
      company_id: '',
      product_id: '',
      sector: '',
      deal_type: 'upcoming',
      description: '',
      min_investment: '',
      max_investment: '',
      valuation: '',
      share_price: '',
      total_shares: '',
      available_shares: '',
      deal_opens_at: '',
      deal_closes_at: '',
      video_url: '',
      status: 'draft',
      is_featured: false,
    });
  };

  const getDealTypeBadge = (type: string) => {
    const variants: any = {
      live: 'default',
      upcoming: 'secondary',
      closed: 'outline',
    };
    return <Badge variant={variants[type] || 'default'}>{type.toUpperCase()}</Badge>;
  };

  const getStatusBadge = (status: string) => {
    const variants: any = {
      active: 'default',
      draft: 'secondary',
      paused: 'outline',
      closed: 'destructive',
    };
    return <Badge variant={variants[status] || 'default'}>{status.toUpperCase()}</Badge>;
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Deals Management</h1>
          <p className="text-muted-foreground">Manage live deals, upcoming deals, and closed deals</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Add New Deal
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingDeal ? 'Edit Deal' : 'Create New Deal'}</DialogTitle>
              <DialogDescription>
                Fill in the details to {editingDeal ? 'update' : 'create'} a deal.
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="title">Deal Title *</Label>
                  <Input
                    id="title"
                    value={formData.title}
                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="company_id">Company *</Label>
                  <Select
                    value={formData.company_id}
                    onValueChange={(value) => setFormData({ ...formData, company_id: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select a company" />
                    </SelectTrigger>
                    <SelectContent>
                      {companies.map((company: any) => (
                        <SelectItem key={company.id} value={company.id.toString()}>
                          {company.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="product_id">Product *</Label>
                  <Select
                    value={formData.product_id}
                    onValueChange={(value) => setFormData({ ...formData, product_id: value })}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select a product" />
                    </SelectTrigger>
                    <SelectContent>
                      {products.map((product: any) => (
                        <SelectItem key={product.id} value={product.id.toString()}>
                          {product.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
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
                  <Label htmlFor="deal_type">Deal Type *</Label>
                  <Select
                    value={formData.deal_type}
                    onValueChange={(value) => setFormData({ ...formData, deal_type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="live">Live</SelectItem>
                      <SelectItem value="upcoming">Upcoming</SelectItem>
                      <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="min_investment">Min Investment (₹)</Label>
                  <Input
                    id="min_investment"
                    type="number"
                    value={formData.min_investment}
                    onChange={(e) => setFormData({ ...formData, min_investment: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="max_investment">Max Investment (₹)</Label>
                  <Input
                    id="max_investment"
                    type="number"
                    value={formData.max_investment}
                    onChange={(e) => setFormData({ ...formData, max_investment: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="valuation">Valuation (₹)</Label>
                  <Input
                    id="valuation"
                    type="number"
                    value={formData.valuation}
                    onChange={(e) => setFormData({ ...formData, valuation: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="share_price">Share Price (₹)</Label>
                  <Input
                    id="share_price"
                    type="number"
                    value={formData.share_price}
                    onChange={(e) => setFormData({ ...formData, share_price: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="total_shares">Total Shares</Label>
                  <Input
                    id="total_shares"
                    type="number"
                    value={formData.total_shares}
                    onChange={(e) => setFormData({ ...formData, total_shares: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="available_shares">Available Shares</Label>
                  <Input
                    id="available_shares"
                    type="number"
                    value={formData.available_shares}
                    onChange={(e) => setFormData({ ...formData, available_shares: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="deal_opens_at">Deal Opens At</Label>
                  <Input
                    id="deal_opens_at"
                    type="date"
                    value={formData.deal_opens_at}
                    onChange={(e) => setFormData({ ...formData, deal_opens_at: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="deal_closes_at">Deal Closes At</Label>
                  <Input
                    id="deal_closes_at"
                    type="date"
                    value={formData.deal_closes_at}
                    onChange={(e) => setFormData({ ...formData, deal_closes_at: e.target.value })}
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
                      <SelectItem value="draft">Draft</SelectItem>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="paused">Paused</SelectItem>
                      <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2 flex items-center pt-8">
                  <input
                    type="checkbox"
                    id="is_featured"
                    checked={formData.is_featured}
                    onChange={(e) => setFormData({ ...formData, is_featured: e.target.checked })}
                    className="mr-2"
                  />
                  <Label htmlFor="is_featured">Featured Deal</Label>
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
              <div className="space-y-2">
                <Label htmlFor="video_url">Video URL</Label>
                <Input
                  id="video_url"
                  type="url"
                  value={formData.video_url}
                  onChange={(e) => setFormData({ ...formData, video_url: e.target.value })}
                />
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={saveMutation.isPending}>
                  {saveMutation.isPending ? 'Saving...' : editingDeal ? 'Update Deal' : 'Create Deal'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">Total Deals</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.total_deals || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">Live Deals</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">{stats.live_deals || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">Upcoming Deals</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-600">{stats.upcoming_deals || 0}</div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium">Closed Deals</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-gray-600">{stats.closed_deals || 0}</div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search deals..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={filterDealType} onValueChange={setFilterDealType}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by deal type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Deal Types</SelectItem>
                <SelectItem value="live">Live</SelectItem>
                <SelectItem value="upcoming">Upcoming</SelectItem>
                <SelectItem value="closed">Closed</SelectItem>
              </SelectContent>
            </Select>
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger>
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="draft">Draft</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="paused">Paused</SelectItem>
                <SelectItem value="closed">Closed</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Deals Table */}
      <Card>
        <CardHeader>
          <CardTitle>All Deals</CardTitle>
          <CardDescription>
            {deals?.data?.length || 0} deals found
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading deals...</div>
          ) : deals?.data?.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              No deals found. Create your first deal to get started!
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Company</TableHead>
                    <TableHead>Sector</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Valuation</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Featured</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {deals?.data?.map((deal: any) => (
                    <TableRow key={deal.id}>
                      <TableCell className="font-medium">{deal.title}</TableCell>
                      <TableCell>{deal.company?.name || '-'}</TableCell>
                      <TableCell>{deal.sector}</TableCell>
                      <TableCell>{getDealTypeBadge(deal.deal_type)}</TableCell>
                      <TableCell>
                        {deal.valuation ? `₹${Number(deal.valuation).toLocaleString('en-IN')}` : '-'}
                      </TableCell>
                      <TableCell>{getStatusBadge(deal.status)}</TableCell>
                      <TableCell>
                        {deal.is_featured && <Badge variant="secondary">⭐ Featured</Badge>}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleEdit(deal)}
                          >
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => handleDelete(deal.id)}
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
