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
import { Plus, Search, Edit, Trash2 } from 'lucide-react';

export default function TutorialsManagementPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingTutorial, setEditingTutorial] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');

  const [formData, setFormData] = useState({
    title: '',
    description: '',
    content: '',
    video_url: '',
    difficulty: 'beginner',
    duration_minutes: '',
    category: '',
    status: 'draft',
  });

  const { data: tutorials, isLoading } = useQuery({
    queryKey: ['admin-tutorials', searchQuery],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (searchQuery) params.append('search', searchQuery);
      const response = await api.get(`/admin/tutorials?${params.toString()}`);
      return response.data;
    },
  });

  const saveMutation = useMutation({
    mutationFn: async (data: any) => {
      if (editingTutorial) {
        return api.put(`/admin/tutorials/${editingTutorial.id}`, data);
      } else {
        return api.post('/admin/tutorials', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-tutorials'] });
      setIsDialogOpen(false);
      resetForm();
      toast.success(editingTutorial ? 'Tutorial updated' : 'Tutorial created');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to save tutorial');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => api.delete(`/admin/tutorials/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-tutorials'] });
      toast.success('Tutorial deleted');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate(formData);
  };

  const handleEdit = (tutorial: any) => {
    setEditingTutorial(tutorial);
    setFormData({
      title: tutorial.title || '',
      description: tutorial.description || '',
      content: tutorial.content || '',
      video_url: tutorial.video_url || '',
      difficulty: tutorial.difficulty || 'beginner',
      duration_minutes: tutorial.duration_minutes || '',
      category: tutorial.category || '',
      status: tutorial.status || 'draft',
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingTutorial(null);
    setFormData({
      title: '',
      description: '',
      content: '',
      video_url: '',
      difficulty: 'beginner',
      duration_minutes: '',
      category: '',
      status: 'draft',
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Tutorials Management</h1>
          <p className="text-muted-foreground">Manage educational tutorials and guides</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => {
          setIsDialogOpen(open);
          if (!open) resetForm();
        }}>
          <DialogTrigger asChild>
            <Button onClick={resetForm}>
              <Plus className="mr-2 h-4 w-4" /> Add Tutorial
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingTutorial ? 'Edit Tutorial' : 'Add Tutorial'}</DialogTitle>
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
                  <Label htmlFor="difficulty">Difficulty *</Label>
                  <Select
                    value={formData.difficulty}
                    onValueChange={(value) => setFormData({ ...formData, difficulty: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="beginner">Beginner</SelectItem>
                      <SelectItem value="intermediate">Intermediate</SelectItem>
                      <SelectItem value="advanced">Advanced</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="duration_minutes">Duration (minutes)</Label>
                  <Input
                    id="duration_minutes"
                    type="number"
                    value={formData.duration_minutes}
                    onChange={(e) => setFormData({ ...formData, duration_minutes: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="category">Category</Label>
                  <Input
                    id="category"
                    value={formData.category}
                    onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                    placeholder="e.g., Getting Started"
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
                      <SelectItem value="published">Published</SelectItem>
                      <SelectItem value="archived">Archived</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={3}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="content">Tutorial Content</Label>
                <Textarea
                  id="content"
                  value={formData.content}
                  onChange={(e) => setFormData({ ...formData, content: e.target.value })}
                  rows={8}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="video_url">Video URL (Optional)</Label>
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
            <CardTitle>All Tutorials</CardTitle>
            <div className="relative w-64">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search tutorials..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">Loading tutorials...</div>
          ) : tutorials?.data?.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              No tutorials found. Add your first tutorial!
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Difficulty</TableHead>
                  <TableHead>Duration</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {tutorials?.data?.map((tutorial: any) => (
                  <TableRow key={tutorial.id}>
                    <TableCell className="font-medium">{tutorial.title}</TableCell>
                    <TableCell>{tutorial.category || '-'}</TableCell>
                    <TableCell>
                      <Badge variant={
                        tutorial.difficulty === 'beginner' ? 'default' :
                        tutorial.difficulty === 'intermediate' ? 'secondary' : 'outline'
                      }>
                        {tutorial.difficulty}
                      </Badge>
                    </TableCell>
                    <TableCell>{tutorial.duration_minutes ? `${tutorial.duration_minutes} min` : '-'}</TableCell>
                    <TableCell>
                      <Badge variant={tutorial.status === 'published' ? 'default' : 'secondary'}>
                        {tutorial.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(tutorial)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => deleteMutation.mutate(tutorial.id)}
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
