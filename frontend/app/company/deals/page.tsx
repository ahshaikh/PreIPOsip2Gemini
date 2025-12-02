'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Plus, Edit, Trash2, TrendingUp, AlertCircle, CheckCircle } from 'lucide-react';

export default function DealsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingDeal, setEditingDeal] = useState<any>(null);
  const [highlightInput, setHighlightInput] = useState('');

  const [formData, setFormData] = useState({
    title: '',
    description: '',
    deal_type: 'upcoming',
    min_investment: '',
    max_investment: '',
    total_shares_available: '',
    price_per_share: '',
    valuation: '',
    deal_opens_at: '',
    deal_closes_at: '',
    highlights: [] as string[],
  });

  const { data: deals, isLoading } = useQuery({
    queryKey: ['company-deals'],
    queryFn: async () => {
      const response = await companyApi.get('/deals');
      return response.data;
    },
  });

  const { data: stats } = useQuery({
    queryKey: ['company-deals-stats'],
    queryFn: async () => {
      const response = await companyApi.get('/deals/statistics');
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingDeal) {
        return companyApi.put(`/deals/${editingDeal.id}`, data);
      } else {
        return companyApi.post('/deals', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-deals'] });
      queryClient.invalidateQueries({ queryKey: ['company-deals-stats'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingDeal ? 'Deal updated' : 'Deal created and pending admin approval');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Operation failed');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => companyApi.delete(`/deals/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-deals'] });
      queryClient.invalidateQueries({ queryKey: ['company-deals-stats'] });
      toast.success('Deal deleted');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Cannot delete live deals');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (deal: any) => {
    setEditingDeal(deal);
    setFormData({
      title: deal.title,
      description: deal.description,
      deal_type: deal.deal_type,
      min_investment: deal.min_investment || '',
      max_investment: deal.max_investment || '',
      total_shares_available: deal.total_shares_available || '',
      price_per_share: deal.price_per_share || '',
      valuation: deal.valuation || '',
      deal_opens_at: deal.deal_opens_at ? deal.deal_opens_at.split('T')[0] : '',
      deal_closes_at: deal.deal_closes_at ? deal.deal_closes_at.split('T')[0] : '',
      highlights: deal.highlights || [],
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingDeal(null);
    setHighlightInput('');
    setFormData({
      title: '',
      description: '',
      deal_type: 'upcoming',
      min_investment: '',
      max_investment: '',
      total_shares_available: '',
      price_per_share: '',
      valuation: '',
      deal_opens_at: '',
      deal_closes_at: '',
      highlights: [],
    });
  };

  const addHighlight = () => {
    if (highlightInput.trim()) {
      setFormData({
        ...formData,
        highlights: [...formData.highlights, highlightInput.trim()],
      });
      setHighlightInput('');
    }
  };

  const removeHighlight = (index: number) => {
    setFormData({
      ...formData,
      highlights: formData.highlights.filter((_, i) => i !== index),
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Share Offerings & Deals</h1>
          <p className="text-muted-foreground mt-2">
            Create and manage your pre-IPO share offerings
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Create Deal
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingDeal ? 'Edit Deal' : 'Create New Deal'}</DialogTitle>
              <DialogDescription>
                Create a share offering for investors. Your deal will be reviewed by admin before going live.
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Deal Title *</Label>
                <Input
                  id="title"
                  placeholder="e.g., Series B Pre-IPO Share Offering"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description *</Label>
                <Textarea
                  id="description"
                  rows={4}
                  placeholder="Describe your share offering, investment opportunity, and company growth story..."
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  required
                />
              </div>

              <div className="grid grid-cols-3 gap-4">
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
                      <SelectItem value="upcoming">Upcoming</SelectItem>
                      <SelectItem value="live">Live</SelectItem>
                      <SelectItem value="closed">Closed</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="deal_opens_at">Opening Date</Label>
                  <Input
                    id="deal_opens_at"
                    type="date"
                    value={formData.deal_opens_at}
                    onChange={(e) => setFormData({ ...formData, deal_opens_at: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="deal_closes_at">Closing Date</Label>
                  <Input
                    id="deal_closes_at"
                    type="date"
                    value={formData.deal_closes_at}
                    onChange={(e) => setFormData({ ...formData, deal_closes_at: e.target.value })}
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="min_investment">Minimum Investment (₹)</Label>
                  <Input
                    id="min_investment"
                    type="number"
                    placeholder="10000"
                    value={formData.min_investment}
                    onChange={(e) => setFormData({ ...formData, min_investment: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="max_investment">Maximum Investment (₹)</Label>
                  <Input
                    id="max_investment"
                    type="number"
                    placeholder="1000000"
                    value={formData.max_investment}
                    onChange={(e) => setFormData({ ...formData, max_investment: e.target.value })}
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="total_shares_available">Total Shares Available</Label>
                  <Input
                    id="total_shares_available"
                    type="number"
                    placeholder="100000"
                    value={formData.total_shares_available}
                    onChange={(e) => setFormData({ ...formData, total_shares_available: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="price_per_share">Price Per Share (₹)</Label>
                  <Input
                    id="price_per_share"
                    type="number"
                    placeholder="100"
                    value={formData.price_per_share}
                    onChange={(e) => setFormData({ ...formData, price_per_share: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="valuation">Company Valuation (₹)</Label>
                  <Input
                    id="valuation"
                    type="number"
                    placeholder="100000000"
                    value={formData.valuation}
                    onChange={(e) => setFormData({ ...formData, valuation: e.target.value })}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Key Highlights</Label>
                <div className="flex gap-2">
                  <Input
                    placeholder="Enter a highlight (e.g., 10x revenue growth)"
                    value={highlightInput}
                    onChange={(e) => setHighlightInput(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addHighlight())}
                  />
                  <Button type="button" variant="outline" onClick={addHighlight}>
                    Add
                  </Button>
                </div>
                {formData.highlights.length > 0 && (
                  <div className="flex flex-wrap gap-2 mt-2">
                    {formData.highlights.map((highlight, index) => (
                      <Badge key={index} variant="secondary" className="pr-1">
                        {highlight}
                        <button
                          type="button"
                          onClick={() => removeHighlight(index)}
                          className="ml-2 hover:text-destructive"
                        >
                          ×
                        </button>
                      </Badge>
                    ))}
                  </div>
                )}
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

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Deals</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.total_deals || 0}</p>
              </div>
              <TrendingUp className="h-8 w-8 text-blue-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Live Deals</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.live_deals || 0}</p>
              </div>
              <CheckCircle className="h-8 w-8 text-green-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Upcoming</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.upcoming_deals || 0}</p>
              </div>
              <AlertCircle className="h-8 w-8 text-orange-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Draft</p>
                <p className="text-2xl font-bold mt-1">{stats?.stats?.draft_deals || 0}</p>
              </div>
              <AlertCircle className="h-8 w-8 text-yellow-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Deals Table */}
      <Card>
        <CardHeader>
          <CardTitle>Your Deals</CardTitle>
          <CardDescription>
            {deals?.data?.length || 0} share offerings created
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading deals...</div>
          ) : deals?.data?.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <TrendingUp className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium mb-2">No deals created yet</p>
              <p className="text-sm mb-4">
                Create your first share offering to connect with investors
              </p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Min Investment</TableHead>
                  <TableHead>Opening Date</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {deals?.data?.map((deal: any) => (
                  <TableRow key={deal.id}>
                    <TableCell className="font-medium">{deal.title}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{deal.deal_type}</Badge>
                    </TableCell>
                    <TableCell>
                      {deal.min_investment ? `₹${parseFloat(deal.min_investment).toLocaleString()}` : 'N/A'}
                    </TableCell>
                    <TableCell>
                      {deal.deal_opens_at
                        ? new Date(deal.deal_opens_at).toLocaleDateString()
                        : 'Not set'}
                    </TableCell>
                    <TableCell>
                      <Badge variant={deal.status === 'active' ? 'default' : 'secondary'}>
                        {deal.status}
                      </Badge>
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
                          onClick={() => deleteMutation.mutate(deal.id)}
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

      {/* Info Card */}
      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <AlertCircle className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                Deal Creation Guidelines
              </h3>
              <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• All deals require admin approval before going live</li>
                <li>• Provide accurate financial information and valuations</li>
                <li>• Set realistic minimum and maximum investment amounts</li>
                <li>• Add compelling highlights to attract investors</li>
                <li>• Keep deal information updated throughout the offering period</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
