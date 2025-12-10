// V-CMS-ENHANCEMENT-009 | Blog Categories Management
// Created: 2025-12-10 | Purpose: Admin interface for dynamic blog category management

'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Edit, Trash2, GripVertical, FolderOpen, Eye, EyeOff, BarChart3 } from "lucide-react";

// Lucide icon options for category selection
const ICON_OPTIONS = [
  'Newspaper', 'TrendingUp', 'BarChart3', 'BookOpen', 'Sparkles',
  'Trophy', 'Megaphone', 'GraduationCap', 'Target', 'Rocket',
  'Lightbulb', 'Heart', 'Star', 'Zap', 'Package'
];

interface BlogCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  color: string;
  icon: string | null;
  display_order: number;
  is_active: boolean;
  blog_posts_count?: number;
  created_at: string;
  updated_at: string;
}

export default function BlogCategoriesPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<BlogCategory | null>(null);
  const [deleteConfirmCategory, setDeleteConfirmCategory] = useState<BlogCategory | null>(null);

  // Form State
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [description, setDescription] = useState('');
  const [color, setColor] = useState('#667eea');
  const [icon, setIcon] = useState('Newspaper');
  const [displayOrder, setDisplayOrder] = useState('0');
  const [isActive, setIsActive] = useState(true);

  // Fetch categories
  const { data: categories, isLoading } = useQuery({
    queryKey: ['adminBlogCategories'],
    queryFn: async () => (await api.get('/admin/blog-categories')).data,
  });

  // Fetch stats
  const { data: stats } = useQuery({
    queryKey: ['blogCategoriesStats'],
    queryFn: async () => (await api.get('/admin/blog-categories/stats/overview')).data,
  });

  // Create/Update mutation
  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingCategory) {
        return api.put(`/admin/blog-categories/${editingCategory.id}`, data);
      }
      return api.post('/admin/blog-categories', data);
    },
    onSuccess: () => {
      toast.success(editingCategory ? "Category Updated" : "Category Created");
      queryClient.invalidateQueries({ queryKey: ['adminBlogCategories'] });
      queryClient.invalidateQueries({ queryKey: ['blogCategoriesStats'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/blog-categories/${id}`),
    onSuccess: () => {
      toast.success("Category Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminBlogCategories'] });
      queryClient.invalidateQueries({ queryKey: ['blogCategoriesStats'] });
      setDeleteConfirmCategory(null);
    },
    onError: (e: any) => {
      const message = e.response?.data?.message || "Failed to delete category";
      toast.error("Error", { description: message });
      setDeleteConfirmCategory(null);
    }
  });

  // Toggle active mutation
  const toggleActiveMutation = useMutation({
    mutationFn: (category: BlogCategory) =>
      api.put(`/admin/blog-categories/${category.id}`, { is_active: !category.is_active }),
    onSuccess: () => {
      toast.success("Category visibility updated");
      queryClient.invalidateQueries({ queryKey: ['adminBlogCategories'] });
    }
  });

  const resetForm = () => {
    setName(''); setSlug(''); setDescription(''); setColor('#667eea');
    setIcon('Newspaper'); setDisplayOrder('0'); setIsActive(true);
    setEditingCategory(null);
  };

  const handleEdit = (category: BlogCategory) => {
    setEditingCategory(category);
    setName(category.name);
    setSlug(category.slug);
    setDescription(category.description || '');
    setColor(category.color);
    setIcon(category.icon || 'Newspaper');
    setDisplayOrder(category.display_order.toString());
    setIsActive(category.is_active);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({
      name,
      slug: slug || undefined, // Let backend auto-generate if empty
      description: description || null,
      color,
      icon,
      display_order: parseInt(displayOrder),
      is_active: isActive,
    });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Blog Categories</h1>
          <p className="text-muted-foreground">Organize your blog posts with dynamic categories</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <Button onClick={() => setIsDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" /> Add Category
          </Button>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>{editingCategory ? 'Edit Category' : 'Create New Category'}</DialogTitle>
              <DialogDescription>
                {editingCategory ? 'Update the category details.' : 'Create a new blog category for organizing posts.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Category Name *</Label>
                  <Input
                    value={name}
                    onChange={e => setName(e.target.value)}
                    placeholder="e.g., Investment Tips"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label>Slug (URL-friendly)</Label>
                  <Input
                    value={slug}
                    onChange={e => setSlug(e.target.value)}
                    placeholder="Leave empty to auto-generate"
                  />
                  <p className="text-xs text-muted-foreground">Will be auto-generated from name if empty</p>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea
                  value={description}
                  onChange={e => setDescription(e.target.value)}
                  placeholder="Brief description of this category..."
                  rows={2}
                />
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>Color (Badge color)</Label>
                  <div className="flex gap-2">
                    <Input
                      type="color"
                      value={color}
                      onChange={e => setColor(e.target.value)}
                      className="w-14 h-10 p-1"
                    />
                    <Input
                      value={color}
                      onChange={e => setColor(e.target.value)}
                      placeholder="#667eea"
                      className="flex-1"
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>Icon</Label>
                  <select
                    value={icon}
                    onChange={e => setIcon(e.target.value)}
                    className="w-full h-10 px-3 rounded-md border border-input bg-background"
                  >
                    {ICON_OPTIONS.map(iconName => (
                      <option key={iconName} value={iconName}>{iconName}</option>
                    ))}
                  </select>
                </div>

                <div className="space-y-2">
                  <Label>Display Order</Label>
                  <Input
                    type="number"
                    value={displayOrder}
                    onChange={e => setDisplayOrder(e.target.value)}
                    min="0"
                  />
                </div>
              </div>

              <div className="flex items-center space-x-2 p-4 bg-muted/50 rounded-lg">
                <Switch checked={isActive} onCheckedChange={setIsActive} />
                <Label>Active (visible to users)</Label>
              </div>

              {/* Preview */}
              <div className="p-4 bg-muted/30 rounded-lg">
                <p className="text-sm text-muted-foreground mb-2">Preview:</p>
                <Badge style={{ backgroundColor: color, color: '#fff' }} className="text-sm px-3 py-1">
                  {name || 'Category Name'}
                </Badge>
              </div>

              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingCategory ? "Save Changes" : "Create Category")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Categories</CardTitle>
            <FolderOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_categories || 0}</div>
            <p className="text-xs text-muted-foreground">{stats?.active_categories || 0} active</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">With Posts</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.categories_with_posts || 0}</div>
            <p className="text-xs text-muted-foreground">Categories being used</p>
          </CardContent>
        </Card>
        <Card className="col-span-2">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">Most Used Category</CardTitle>
          </CardHeader>
          <CardContent>
            {stats?.most_used_category ? (
              <div>
                <div className="text-2xl font-bold">{stats.most_used_category.name}</div>
                <p className="text-xs text-muted-foreground">
                  {stats.most_used_category.blog_posts_count} post(s)
                </p>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">No categories with posts yet</p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Categories Table */}
      <Card>
        <CardHeader>
          <CardTitle>All Categories</CardTitle>
          <CardDescription>Manage and organize your blog categories</CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-center py-8 text-muted-foreground">Loading categories...</p>
          ) : categories?.length === 0 ? (
            <p className="text-center py-8 text-muted-foreground">
              No categories yet. Click "Add Category" to create one.
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12"></TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Slug</TableHead>
                  <TableHead>Posts</TableHead>
                  <TableHead>Order</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {categories?.map((category: BlogCategory) => (
                  <TableRow key={category.id}>
                    <TableCell>
                      <GripVertical className="h-4 w-4 text-muted-foreground cursor-move" />
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Badge
                          style={{ backgroundColor: category.color, color: '#fff' }}
                          className="text-xs px-2 py-1"
                        >
                          {category.name}
                        </Badge>
                      </div>
                      {category.description && (
                        <p className="text-xs text-muted-foreground mt-1">{category.description}</p>
                      )}
                    </TableCell>
                    <TableCell className="font-mono text-xs">{category.slug}</TableCell>
                    <TableCell>
                      <span className="text-sm">{category.blog_posts_count || 0} post(s)</span>
                    </TableCell>
                    <TableCell>
                      <span className="text-sm text-muted-foreground">{category.display_order}</span>
                    </TableCell>
                    <TableCell>
                      <Badge variant={category.is_active ? 'default' : 'outline'}>
                        {category.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-1">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => toggleActiveMutation.mutate(category)}
                        >
                          {category.is_active ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => handleEdit(category)}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => setDeleteConfirmCategory(category)}
                          className="text-destructive hover:text-destructive"
                        >
                          <Trash2 className="h-4 w-4" />
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

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmCategory} onOpenChange={() => setDeleteConfirmCategory(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Category</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirmCategory?.name}"?
              {deleteConfirmCategory && deleteConfirmCategory.blog_posts_count! > 0 && (
                <span className="block mt-2 font-medium text-destructive">
                  Warning: This category has {deleteConfirmCategory.blog_posts_count} blog post(s).
                  You must reassign or delete those posts first.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteConfirmCategory && deleteMutation.mutate(deleteConfirmCategory.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Category"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
