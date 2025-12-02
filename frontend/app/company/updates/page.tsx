'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Plus, Edit, Trash2, Newspaper, Eye, Calendar, Star } from 'lucide-react';

export default function CompanyUpdatesPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingUpdate, setEditingUpdate] = useState<any>(null);

  const [formData, setFormData] = useState({
    title: '',
    content: '',
    update_type: 'news',
    is_featured: false,
    status: 'draft',
  });

  const { data: updates, isLoading } = useQuery({
    queryKey: ['company-updates'],
    queryFn: async () => {
      const response = await companyApi.get('/updates');
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingUpdate) {
        return companyApi.put(`/updates/${editingUpdate.id}`, data);
      } else {
        return companyApi.post('/updates', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-updates'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingUpdate ? 'Update modified' : 'Update published');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Operation failed');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => companyApi.delete(`/updates/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-updates'] });
      queryClient.invalidateQueries({ queryKey: ['company-dashboard'] });
      toast.success('Update deleted');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (update: any) => {
    setEditingUpdate(update);
    setFormData({
      title: update.title,
      content: update.content,
      update_type: update.update_type,
      is_featured: update.is_featured,
      status: update.status,
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingUpdate(null);
    setFormData({
      title: '',
      content: '',
      update_type: 'news',
      is_featured: false,
      status: 'draft',
    });
  };

  const getUpdateTypeColor = (type: string) => {
    const colors: Record<string, string> = {
      news: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
      milestone: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
      funding: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
      product_launch: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
      partnership: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-100',
      other: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100',
    };
    return colors[type] || colors.other;
  };

  const publishedCount = updates?.data?.filter((u: any) => u.status === 'published').length || 0;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Company Updates & News</h1>
          <p className="text-muted-foreground mt-2">
            Keep investors informed about your company's progress and achievements
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Create Update
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingUpdate ? 'Edit Update' : 'Create Update'}</DialogTitle>
              <DialogDescription>
                Share news, milestones, and achievements with your audience
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Title *</Label>
                <Input
                  id="title"
                  placeholder="e.g., We've raised $10M in Series A funding"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="content">Content *</Label>
                <Textarea
                  id="content"
                  rows={8}
                  placeholder="Write your update here. Share details about what happened, why it matters, and what's next..."
                  value={formData.content}
                  onChange={(e) => setFormData({ ...formData, content: e.target.value })}
                  required
                />
                <p className="text-xs text-muted-foreground">
                  Tip: Be specific and transparent. Include relevant dates, numbers, and context.
                </p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="update_type">Update Type *</Label>
                  <Select
                    value={formData.update_type}
                    onValueChange={(value) => setFormData({ ...formData, update_type: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="news">News</SelectItem>
                      <SelectItem value="milestone">Milestone</SelectItem>
                      <SelectItem value="funding">Funding Announcement</SelectItem>
                      <SelectItem value="product_launch">Product Launch</SelectItem>
                      <SelectItem value="partnership">Partnership</SelectItem>
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

              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="is_featured"
                  checked={formData.is_featured}
                  onChange={(e) => setFormData({ ...formData, is_featured: e.target.checked })}
                  className="rounded"
                />
                <Label htmlFor="is_featured" className="cursor-pointer flex items-center gap-2">
                  <Star className="h-4 w-4 text-yellow-500" />
                  Mark as featured (will be highlighted on your profile)
                </Label>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                  Cancel
                </Button>
                <Button type="submit" disabled={saveMutation.isPending}>
                  {saveMutation.isPending ? 'Saving...' : editingUpdate ? 'Update' : 'Publish'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Total Updates</p>
                <p className="text-2xl font-bold mt-1">{updates?.data?.length || 0}</p>
              </div>
              <Newspaper className="h-8 w-8 text-blue-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Published</p>
                <p className="text-2xl font-bold mt-1">{publishedCount}</p>
              </div>
              <Eye className="h-8 w-8 text-green-600" />
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Featured</p>
                <p className="text-2xl font-bold mt-1">
                  {updates?.data?.filter((u: any) => u.is_featured).length || 0}
                </p>
              </div>
              <Star className="h-8 w-8 text-yellow-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Updates List */}
      <Card>
        <CardHeader>
          <CardTitle>All Updates</CardTitle>
          <CardDescription>
            Manage your company news and announcements
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading updates...</div>
          ) : updates?.data?.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <Newspaper className="h-16 w-16 mx-auto mb-4 text-muted-foreground/50" />
              <p className="text-lg font-medium mb-2">No updates posted yet</p>
              <p className="text-sm mb-4">
                Share company news to keep investors engaged and informed
              </p>
            </div>
          ) : (
            <div className="space-y-4">
              {updates?.data?.map((update: any) => (
                <Card key={update.id} className="overflow-hidden">
                  <CardContent className="p-6">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <Badge className={getUpdateTypeColor(update.update_type)}>
                            {update.update_type.replace('_', ' ')}
                          </Badge>
                          {update.is_featured && (
                            <Badge className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100">
                              <Star className="h-3 w-3 mr-1" />
                              Featured
                            </Badge>
                          )}
                          <Badge variant={update.status === 'published' ? 'default' : 'secondary'}>
                            {update.status}
                          </Badge>
                        </div>
                        <h3 className="text-lg font-semibold mb-2">{update.title}</h3>
                        <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                          {update.content}
                        </p>
                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                          <span className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />
                            {update.published_at
                              ? new Date(update.published_at).toLocaleDateString('en-US', {
                                  month: 'short',
                                  day: 'numeric',
                                  year: 'numeric',
                                })
                              : 'Draft'}
                          </span>
                        </div>
                      </div>
                      <div className="flex gap-2 ml-4">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleEdit(update)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => deleteMutation.mutate(update.id)}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <Newspaper className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                Best Practices for Company Updates
              </h3>
              <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Post regular updates to maintain investor engagement</li>
                <li>• Share both successes and challenges with transparency</li>
                <li>• Include specific metrics and dates when relevant</li>
                <li>• Use featured updates for major announcements</li>
                <li>• Keep drafts for upcoming announcements and schedule them</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
