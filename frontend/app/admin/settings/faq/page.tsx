// V-FINAL-1730-187 | V-ENHANCED-FAQ
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Trash2, Edit, Plus, GripVertical, HelpCircle, Search, Eye, EyeOff, ChevronDown, ChevronUp, MessageCircleQuestion } from "lucide-react";

// FAQ categories
const FAQ_CATEGORIES = [
  { value: 'general', label: 'General' },
  { value: 'account', label: 'Account & Profile' },
  { value: 'investment', label: 'Investments & Plans' },
  { value: 'payment', label: 'Payments & Wallet' },
  { value: 'kyc', label: 'KYC & Verification' },
  { value: 'referral', label: 'Referrals & Bonuses' },
  { value: 'security', label: 'Security' },
  { value: 'other', label: 'Other' },
];

export default function FaqSettingsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingFaq, setEditingFaq] = useState<any>(null);
  const [deleteConfirmFaq, setDeleteConfirmFaq] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeCategory, setActiveCategory] = useState('all');
  const [expandedFaq, setExpandedFaq] = useState<number | null>(null);

  // Form State
  const [question, setQuestion] = useState('');
  const [answer, setAnswer] = useState('');
  const [category, setCategory] = useState('general');
  const [isPublished, setIsPublished] = useState(true);
  const [displayOrder, setDisplayOrder] = useState('0');

  const { data: faqs, isLoading } = useQuery({
    queryKey: ['adminFaqs'],
    queryFn: async () => (await api.get('/admin/faqs')).data,
  });

  const resetForm = () => {
    setQuestion(''); setAnswer(''); setCategory('general');
    setIsPublished(true); setDisplayOrder('0'); setEditingFaq(null);
  };

  const handleEdit = (faq: any) => {
    setEditingFaq(faq);
    setQuestion(faq.question);
    setAnswer(faq.answer);
    setCategory(faq.category || 'general');
    setIsPublished(faq.is_published !== false);
    setDisplayOrder(faq.display_order?.toString() || '0');
    setIsDialogOpen(true);
  };

  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingFaq) {
        return api.put(`/admin/faqs/${editingFaq.id}`, data);
      }
      return api.post('/admin/faqs', data);
    },
    onSuccess: () => {
      toast.success(editingFaq ? "FAQ Updated" : "FAQ Added");
      queryClient.invalidateQueries({ queryKey: ['adminFaqs'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/faqs/${id}`),
    onSuccess: () => {
      toast.success("FAQ Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminFaqs'] });
      setDeleteConfirmFaq(null);
    }
  });

  const togglePublishMutation = useMutation({
    mutationFn: (faq: any) => api.put(`/admin/faqs/${faq.id}`, { is_published: !faq.is_published }),
    onSuccess: () => {
      toast.success("FAQ visibility updated");
      queryClient.invalidateQueries({ queryKey: ['adminFaqs'] });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({
      question,
      answer,
      category,
      is_published: isPublished,
      display_order: parseInt(displayOrder)
    });
  };

  // Filter FAQs
  const filteredFaqs = faqs?.filter((faq: any) => {
    const matchesSearch = searchQuery === '' ||
      faq.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
      faq.answer.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = activeCategory === 'all' || faq.category === activeCategory;
    return matchesSearch && matchesCategory;
  });

  // Group FAQs by category
  const groupedFaqs = filteredFaqs?.reduce((acc: any, faq: any) => {
    const cat = faq.category || 'general';
    if (!acc[cat]) acc[cat] = [];
    acc[cat].push(faq);
    return acc;
  }, {});

  // Count by category
  const categoryCounts = faqs?.reduce((acc: any, faq: any) => {
    const cat = faq.category || 'general';
    acc[cat] = (acc[cat] || 0) + 1;
    return acc;
  }, {});

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">FAQ Manager</h1>
          <p className="text-muted-foreground">Manage frequently asked questions for your platform.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <Button onClick={() => setIsDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" /> Add FAQ
          </Button>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>{editingFaq ? 'Edit FAQ' : 'Add New FAQ'}</DialogTitle>
              <DialogDescription>
                {editingFaq ? 'Update the question and answer.' : 'Create a new FAQ entry for your users.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Category</Label>
                  <Select value={category} onValueChange={setCategory}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {FAQ_CATEGORIES.map(cat => (
                        <SelectItem key={cat.value} value={cat.value}>{cat.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Display Order</Label>
                  <Input type="number" value={displayOrder} onChange={e => setDisplayOrder(e.target.value)} min="0" />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Question</Label>
                <Input
                  value={question}
                  onChange={e => setQuestion(e.target.value)}
                  placeholder="e.g., How do I reset my password?"
                  required
                />
              </div>
              <div className="space-y-2">
                <Label>Answer</Label>
                <Textarea
                  value={answer}
                  onChange={e => setAnswer(e.target.value)}
                  placeholder="Provide a clear and helpful answer..."
                  rows={5}
                  required
                />
                <p className="text-xs text-muted-foreground">Supports basic markdown formatting.</p>
              </div>
              <div className="flex items-center space-x-2 p-4 bg-muted/50 rounded-lg">
                <Switch checked={isPublished} onCheckedChange={setIsPublished} />
                <Label>Publish immediately (visible to users)</Label>
              </div>
              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingFaq ? "Save Changes" : "Add FAQ")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total FAQs</CardTitle>
            <MessageCircleQuestion className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{faqs?.length || 0}</div>
            <p className="text-xs text-muted-foreground">{faqs?.filter((f: any) => f.is_published).length || 0} published</p>
          </CardContent>
        </Card>
        {FAQ_CATEGORIES.slice(0, 3).map(cat => (
          <Card key={cat.value}>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium">{cat.label}</CardTitle>
              <HelpCircle className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{categoryCounts?.[cat.value] || 0}</div>
              <p className="text-xs text-muted-foreground">questions</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Search and Filter */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>All FAQs</CardTitle>
            <div className="flex items-center gap-4">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search FAQs..."
                  value={searchQuery}
                  onChange={e => setSearchQuery(e.target.value)}
                  className="pl-10 w-64"
                />
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <Tabs value={activeCategory} onValueChange={setActiveCategory}>
            <TabsList className="mb-4">
              <TabsTrigger value="all">All ({faqs?.length || 0})</TabsTrigger>
              {FAQ_CATEGORIES.map(cat => (
                categoryCounts?.[cat.value] > 0 && (
                  <TabsTrigger key={cat.value} value={cat.value}>
                    {cat.label} ({categoryCounts[cat.value]})
                  </TabsTrigger>
                )
              ))}
            </TabsList>

            <div className="space-y-3">
              {isLoading ? (
                <p className="text-center py-8 text-muted-foreground">Loading FAQs...</p>
              ) : filteredFaqs?.length === 0 ? (
                <p className="text-center py-8 text-muted-foreground">
                  {searchQuery ? 'No FAQs match your search.' : 'No FAQs in this category.'}
                </p>
              ) : (
                filteredFaqs?.map((faq: any) => (
                  <div
                    key={faq.id}
                    className={`border rounded-lg transition-all ${
                      expandedFaq === faq.id ? 'ring-2 ring-primary/20' : ''
                    } ${!faq.is_published ? 'opacity-60' : ''}`}
                  >
                    <div
                      className="flex items-center justify-between p-4 cursor-pointer hover:bg-muted/50"
                      onClick={() => setExpandedFaq(expandedFaq === faq.id ? null : faq.id)}
                    >
                      <div className="flex items-center gap-3 flex-1">
                        <GripVertical className="h-4 w-4 text-muted-foreground" />
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{faq.question}</span>
                            {!faq.is_published && (
                              <Badge variant="outline" className="text-xs">Draft</Badge>
                            )}
                          </div>
                          <div className="flex items-center gap-2 mt-1">
                            <Badge variant="secondary" className="text-xs">
                              {FAQ_CATEGORIES.find(c => c.value === (faq.category || 'general'))?.label}
                            </Badge>
                            {faq.display_order > 0 && (
                              <span className="text-xs text-muted-foreground">Order: {faq.display_order}</span>
                            )}
                          </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={(e) => { e.stopPropagation(); togglePublishMutation.mutate(faq); }}
                        >
                          {faq.is_published ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                        </Button>
                        <Button variant="ghost" size="sm" onClick={(e) => { e.stopPropagation(); handleEdit(faq); }}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={(e) => { e.stopPropagation(); setDeleteConfirmFaq(faq); }}
                          className="text-destructive hover:text-destructive"
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                        {expandedFaq === faq.id ? (
                          <ChevronUp className="h-4 w-4 text-muted-foreground" />
                        ) : (
                          <ChevronDown className="h-4 w-4 text-muted-foreground" />
                        )}
                      </div>
                    </div>
                    {expandedFaq === faq.id && (
                      <div className="px-4 pb-4 pt-0 border-t bg-muted/30">
                        <p className="text-sm text-muted-foreground whitespace-pre-wrap pt-4">{faq.answer}</p>
                      </div>
                    )}
                  </div>
                ))
              )}
            </div>
          </Tabs>
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmFaq} onOpenChange={() => setDeleteConfirmFaq(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete FAQ</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this FAQ?
              <span className="block mt-2 font-medium">"{deleteConfirmFaq?.question}"</span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmFaq.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete FAQ"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}