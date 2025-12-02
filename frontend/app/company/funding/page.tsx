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
import { Plus, Edit, Trash2, TrendingUp, DollarSign, Calendar, Users as Investors } from 'lucide-react';

export default function FundingRoundsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingRound, setEditingRound] = useState<any>(null);
  const [investorInput, setInvestorInput] = useState('');

  const [formData, setFormData] = useState({
    round_name: '',
    amount_raised: '',
    currency: 'INR',
    valuation: '',
    round_date: '',
    investors: [] as string[],
    description: '',
  });

  const { data: fundingRounds, isLoading } = useQuery({
    queryKey: ['funding-rounds'],
    queryFn: async () => {
      const response = await companyApi.get('/funding-rounds');
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingRound) {
        return companyApi.put(`/funding-rounds/${editingRound.id}`, data);
      } else {
        return companyApi.post('/funding-rounds', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['funding-rounds'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      queryClient.invalidateQueries({ queryKey: ['company-profile'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingRound ? 'Funding round updated' : 'Funding round added');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Operation failed');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => companyApi.delete(`/funding-rounds/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['funding-rounds'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      queryClient.invalidateQueries({ queryKey: ['company-profile'] });
      toast.success('Funding round removed');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (round: any) => {
    setEditingRound(round);
    setFormData({
      round_name: round.round_name,
      amount_raised: round.amount_raised || '',
      currency: round.currency || 'INR',
      valuation: round.valuation || '',
      round_date: round.round_date || '',
      investors: round.investors || [],
      description: round.description || '',
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingRound(null);
    setInvestorInput('');
    setFormData({
      round_name: '',
      amount_raised: '',
      currency: 'INR',
      valuation: '',
      round_date: '',
      investors: [],
      description: '',
    });
  };

  const addInvestor = () => {
    if (investorInput.trim()) {
      setFormData({
        ...formData,
        investors: [...formData.investors, investorInput.trim()],
      });
      setInvestorInput('');
    }
  };

  const removeInvestor = (index: number) => {
    setFormData({
      ...formData,
      investors: formData.investors.filter((_, i) => i !== index),
    });
  };

  const formatCurrency = (amount: number, currency: string) => {
    const symbol = currency === 'INR' ? '₹' : '$';
    if (amount >= 10000000) {
      return `${symbol}${(amount / 10000000).toFixed(2)} Cr`;
    } else if (amount >= 100000) {
      return `${symbol}${(amount / 100000).toFixed(2)} L`;
    } else {
      return `${symbol}${amount.toLocaleString()}`;
    }
  };

  const calculateTotalFunding = () => {
    if (!fundingRounds?.data) return 0;
    return fundingRounds.data.reduce((sum: number, round: any) => {
      return sum + (parseFloat(round.amount_raised) || 0);
    }, 0);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Funding Rounds</h1>
          <p className="text-muted-foreground mt-2">
            Track your company's investment history and valuation growth
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Add Funding Round
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingRound ? 'Edit Funding Round' : 'Add Funding Round'}</DialogTitle>
              <DialogDescription>
                Record details about your funding rounds and investors
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="round_name">Round Name *</Label>
                <Input
                  id="round_name"
                  placeholder="Series A, Series B, Pre-IPO, Seed"
                  value={formData.round_name}
                  onChange={(e) => setFormData({ ...formData, round_name: e.target.value })}
                  required
                />
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2 col-span-2">
                  <Label htmlFor="amount_raised">Amount Raised</Label>
                  <Input
                    id="amount_raised"
                    type="number"
                    placeholder="10000000"
                    value={formData.amount_raised}
                    onChange={(e) => setFormData({ ...formData, amount_raised: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="currency">Currency</Label>
                  <Select
                    value={formData.currency}
                    onValueChange={(value) => setFormData({ ...formData, currency: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="INR">INR (₹)</SelectItem>
                      <SelectItem value="USD">USD ($)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="valuation">Post-Money Valuation</Label>
                  <Input
                    id="valuation"
                    type="number"
                    placeholder="100000000"
                    value={formData.valuation}
                    onChange={(e) => setFormData({ ...formData, valuation: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="round_date">Round Date</Label>
                  <Input
                    id="round_date"
                    type="date"
                    value={formData.round_date}
                    onChange={(e) => setFormData({ ...formData, round_date: e.target.value })}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Investors</Label>
                <div className="flex gap-2">
                  <Input
                    placeholder="Enter investor name"
                    value={investorInput}
                    onChange={(e) => setInvestorInput(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addInvestor())}
                  />
                  <Button type="button" variant="outline" onClick={addInvestor}>
                    Add
                  </Button>
                </div>
                {formData.investors.length > 0 && (
                  <div className="flex flex-wrap gap-2 mt-2">
                    {formData.investors.map((investor, index) => (
                      <Badge key={index} variant="secondary" className="pr-1">
                        {investor}
                        <button
                          type="button"
                          onClick={() => removeInvestor(index)}
                          className="ml-2 hover:text-destructive"
                        >
                          ×
                        </button>
                      </Badge>
                    ))}
                  </div>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  rows={3}
                  placeholder="Additional details about this funding round..."
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                />
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={saveMutation.isPending}>
                  {saveMutation.isPending ? 'Saving...' : editingRound ? 'Update' : 'Add Round'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Summary Cards */}
      {fundingRounds?.data && fundingRounds.data.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-muted-foreground">Total Funding</p>
                  <p className="text-2xl font-bold mt-1">
                    {formatCurrency(calculateTotalFunding(), 'INR')}
                  </p>
                </div>
                <DollarSign className="h-8 w-8 text-green-600" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-muted-foreground">Funding Rounds</p>
                  <p className="text-2xl font-bold mt-1">{fundingRounds.data.length}</p>
                </div>
                <TrendingUp className="h-8 w-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-muted-foreground">Latest Valuation</p>
                  <p className="text-2xl font-bold mt-1">
                    {fundingRounds.data[0]?.valuation
                      ? formatCurrency(parseFloat(fundingRounds.data[0].valuation), fundingRounds.data[0].currency)
                      : 'N/A'}
                  </p>
                </div>
                <TrendingUp className="h-8 w-8 text-purple-600" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Funding History</CardTitle>
          <CardDescription>
            {fundingRounds?.data?.length || 0} funding rounds recorded
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading funding rounds...</div>
          ) : fundingRounds?.data?.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <TrendingUp className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium mb-2">No funding rounds added yet</p>
              <p className="text-sm mb-4">Track your fundraising journey to showcase growth to investors</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Round Name</TableHead>
                  <TableHead>Amount Raised</TableHead>
                  <TableHead>Valuation</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Investors</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {fundingRounds?.data?.map((round: any) => (
                  <TableRow key={round.id}>
                    <TableCell className="font-medium">{round.round_name}</TableCell>
                    <TableCell>
                      {round.amount_raised
                        ? formatCurrency(parseFloat(round.amount_raised), round.currency)
                        : 'Undisclosed'}
                    </TableCell>
                    <TableCell>
                      {round.valuation
                        ? formatCurrency(parseFloat(round.valuation), round.currency)
                        : 'N/A'}
                    </TableCell>
                    <TableCell>
                      {round.round_date
                        ? new Date(round.round_date).toLocaleDateString('en-US', {
                            month: 'short',
                            year: 'numeric',
                          })
                        : 'N/A'}
                    </TableCell>
                    <TableCell>
                      {round.investors && round.investors.length > 0 ? (
                        <div className="flex flex-wrap gap-1">
                          {round.investors.slice(0, 2).map((investor: string, idx: number) => (
                            <Badge key={idx} variant="secondary" className="text-xs">
                              {investor}
                            </Badge>
                          ))}
                          {round.investors.length > 2 && (
                            <Badge variant="secondary" className="text-xs">
                              +{round.investors.length - 2}
                            </Badge>
                          )}
                        </div>
                      ) : (
                        <span className="text-muted-foreground text-sm">N/A</span>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(round)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => deleteMutation.mutate(round.id)}
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

      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <TrendingUp className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                Why Track Funding?
              </h3>
              <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Demonstrates traction and investor confidence</li>
                <li>• Shows company growth and valuation trajectory</li>
                <li>• Highlights backing from reputable investors</li>
                <li>• Provides transparency to potential new investors</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
