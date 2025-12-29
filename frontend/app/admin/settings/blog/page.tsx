// V-FINAL-1730-193 | V-ENHANCED-BLOG | V-CMS-ENHANCEMENT-010 (Dynamic Categories)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { PlusCircle, Edit, BookOpen, Trash2, Eye, Search, Calendar, FileText, Image, Tag, TrendingUp, Copy, ExternalLink, Settings, FolderOpen } from "lucide-react";
import { useState } from "react";
import Link from "next/link";

export default function BlogSettingsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPost, setEditingPost] = useState<any>(null);
  const [deleteConfirmPost, setDeleteConfirmPost] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  // Form State - V-CMS-ENHANCEMENT-010: Changed category (string) to categoryId (number)
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [excerpt, setExcerpt] = useState('');
  const [status, setStatus] = useState('draft');
  const [categoryId, setCategoryId] = useState<number | null>(null); // Changed from category (string) to categoryId (number)
  const [featuredImage, setFeaturedImage] = useState('');
  const [isFeatured, setIsFeatured] = useState(false);
  const [seoTitle, setSeoTitle] = useState('');
  const [seoDescription, setSeoDescription] = useState('');
  const [tags, setTags] = useState<string[]>([]);
  const [newTag, setNewTag] = useState('');

  // Fetch blog posts
  const { data, isLoading } = useQuery({
    queryKey: ['adminBlogPosts'],
    queryFn: async () => (await api.get('/admin/blog-posts')).data,
  });

  // V-CMS-ENHANCEMENT-010: Fetch dynamic blog categories from API
  const { data: categories, isLoading: categoriesLoading } = useQuery({
    queryKey: ['adminBlogCategories'],
    queryFn: async () => (await api.get('/admin/blog-categories')).data,
  });

  const mutation = useMutation({
    mutationFn: (post: any) => {
      if (editingPost) {
        return api.put(`/admin/blog-posts/${editingPost.id}`, post);
      }
      return api.post('/admin/blog-posts', post);
    },
    onSuccess: () => {
      toast.success(editingPost ? "Post Updated" : "Post Created");
      queryClient.invalidateQueries({ queryKey: ['adminBlogPosts'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/blog-posts/${id}`),
    onSuccess: () => {
      toast.success("Post Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminBlogPosts'] });
      setDeleteConfirmPost(null);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const duplicateMutation = useMutation({
    mutationFn: (post: any) => api.post('/admin/blog-posts', {
      title: `${post.title} (Copy)`,
      content: post.content,
      excerpt: post.excerpt,
      category_id: post.category_id, // V-CMS-ENHANCEMENT-010: Use category_id instead of category
      status: 'draft',
      featured_image: post.featured_image,
      seo_title: post.seo_title,
      seo_description: post.seo_description,
      tags: post.tags,
      is_featured: false, // Duplicated posts are not featured by default
    }),
    onSuccess: () => {
      toast.success("Post duplicated as draft");
      queryClient.invalidateQueries({ queryKey: ['adminBlogPosts'] });
    },
  });

  const resetForm = () => {
    setTitle(''); setContent(''); setExcerpt(''); setStatus('draft');
    setCategoryId(null); // V-CMS-ENHANCEMENT-010: Reset to null instead of 'news'
    setFeaturedImage(''); setIsFeatured(false);
    setSeoTitle(''); setSeoDescription(''); setTags([]); setNewTag('');
    setEditingPost(null);
  };

  const handleEdit = (post: any) => {
    setEditingPost(post);
    setTitle(post.title);
    setContent(post.content);
    setExcerpt(post.excerpt || '');
    setStatus(post.status);
    setCategoryId(post.category_id || null); // V-CMS-ENHANCEMENT-010: Use category_id
    setFeaturedImage(post.featured_image || '');
    setIsFeatured(post.is_featured || false);
    setSeoTitle(post.seo_title || '');
    setSeoDescription(post.seo_description || '');
    setTags(Array.isArray(post.tags) ? post.tags : []);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
      title,
      content,
      excerpt,
      status,
      category_id: categoryId, // V-CMS-ENHANCEMENT-010: Send category_id instead of category
      featured_image: featuredImage,
      is_featured: isFeatured,
      seo_title: seoTitle,
      seo_description: seoDescription,
      tags,
    };
    mutation.mutate(payload);
  };

  const addTag = () => {
    if (newTag.trim() && !tags.includes(newTag.trim())) {
      setTags([...tags, newTag.trim()]);
      setNewTag('');
    }
  };

  const removeTag = (tagToRemove: string) => {
    setTags(tags.filter(t => t !== tagToRemove));
  };

  // Filter posts
  const filteredPosts = data?.filter((post: any) => {
    const matchesSearch = searchQuery === '' ||
      post.title.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesStatus = statusFilter === 'all' || post.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Blog Management</h1>
          <p className="text-muted-foreground">Manage news, updates, and articles.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> Create Post</Button>
          </DialogTrigger>
          <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingPost ? 'Edit Post' : 'Create New Post'}</DialogTitle>
              <DialogDescription>
                {editingPost ? 'Update your blog post content and settings.' : 'Write a new article for your blog.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-6">
              <Tabs defaultValue="content">
                <TabsList className="mb-4">
                  <TabsTrigger value="content">Content</TabsTrigger>
                  <TabsTrigger value="media">Media & Tags</TabsTrigger>
                  <TabsTrigger value="seo">SEO</TabsTrigger>
                </TabsList>

                {/* Content Tab */}
                <TabsContent value="content" className="space-y-4">
                  <div className="grid grid-cols-3 gap-4">
                    <div className="col-span-2 space-y-2">
                      <Label>Post Title</Label>
                      <Input value={title} onChange={(e) => setTitle(e.target.value)} required placeholder="Enter post title" />
                    </div>
                    <div className="space-y-2">
                      <div className="flex items-center justify-between">
                        <Label>Category</Label>
                        <Link href="/admin/settings/blog-categories" target="_blank">
                          <Button type="button" variant="ghost" size="sm" className="h-6 px-2 text-xs">
                            <Settings className="h-3 w-3 mr-1" />
                            Manage
                          </Button>
                        </Link>
                      </div>
                      <Select
                        value={categoryId?.toString() || undefined}
                        onValueChange={(val) => setCategoryId(val ? parseInt(val) : null)}
                        disabled={categoriesLoading}
                      >
                        <SelectTrigger>
                          <SelectValue placeholder={categoriesLoading ? "Loading categories..." : "Select category (optional)..."} />
                        </SelectTrigger>
                        <SelectContent>
                          {categories?.filter((c: any) => c.is_active).map((cat: any) => (
                            <SelectItem key={cat.id} value={cat.id.toString()}>
                              <div className="flex items-center gap-2">
                                <span
                                  className="w-3 h-3 rounded-full flex-shrink-0"
                                  style={{ backgroundColor: cat.color }}
                                />
                                {cat.name}
                              </div>
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {!categoriesLoading && categories?.length === 0 && (
                        <p className="text-xs text-muted-foreground">
                          No categories yet. <Link href="/admin/settings/blog-categories" className="text-primary underline">Create one</Link>
                        </p>
                      )}
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Excerpt (Short Description)</Label>
                    <Textarea
                      value={excerpt}
                      onChange={(e) => setExcerpt(e.target.value)}
                      rows={2}
                      placeholder="Brief summary shown in post listings..."
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Content</Label>
                    <Textarea
                      value={content}
                      onChange={(e) => setContent(e.target.value)}
                      rows={12}
                      placeholder="Write your article content here... Supports markdown."
                    />
                    <p className="text-xs text-muted-foreground">Markdown formatting supported.</p>
                  </div>
                </TabsContent>

                {/* Media & Tags Tab */}
                <TabsContent value="media" className="space-y-4">
                  <div className="space-y-2">
                    <Label>Featured Image URL</Label>
                    <Input
                      value={featuredImage}
                      onChange={(e) => setFeaturedImage(e.target.value)}
                      placeholder="https://example.com/image.jpg"
                    />
                    {featuredImage && (
                      <div className="mt-2 p-2 border rounded-lg">
                        <img src={featuredImage} alt="Preview" className="max-h-40 object-cover rounded" />
                      </div>
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label>Tags</Label>
                    <div className="flex gap-2">
                      <Input
                        value={newTag}
                        onChange={(e) => setNewTag(e.target.value)}
                        placeholder="Add a tag..."
                        onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addTag())}
                      />
                      <Button type="button" variant="outline" onClick={addTag}>
                        <Tag className="h-4 w-4" />
                      </Button>
                    </div>
                    <div className="flex flex-wrap gap-2 mt-2">
                      {tags.map((tag, index) => (
                        <Badge key={index} variant="secondary" className="px-3 py-1">
                          {tag}
                          <button type="button" onClick={() => removeTag(tag)} className="ml-2 hover:text-destructive">×</button>
                        </Badge>
                      ))}
                      {tags.length === 0 && <p className="text-xs text-muted-foreground">No tags added.</p>}
                    </div>
                  </div>
                </TabsContent>

                {/* SEO Tab */}
                <TabsContent value="seo" className="space-y-4">
                  <div className="space-y-2">
                    <Label>SEO Title</Label>
                    <Input
                      value={seoTitle}
                      onChange={(e) => setSeoTitle(e.target.value)}
                      placeholder="Leave empty to use post title"
                    />
                    <p className="text-xs text-muted-foreground">{seoTitle.length}/60 characters recommended</p>
                  </div>
                  <div className="space-y-2">
                    <Label>SEO Description</Label>
                    <Textarea
                      value={seoDescription}
                      onChange={(e) => setSeoDescription(e.target.value)}
                      rows={3}
                      placeholder="Brief description for search engines..."
                    />
                    <p className="text-xs text-muted-foreground">{seoDescription.length}/160 characters recommended</p>
                  </div>
                  {/* SEO Preview */}
                  <div className="p-4 bg-muted/50 rounded-lg">
                    <p className="text-sm text-muted-foreground mb-2">Search Preview</p>
                    <div className="space-y-1">
                      <p className="text-blue-600 text-lg">{seoTitle || title || 'Post Title'}</p>
                      <p className="text-green-700 text-sm">example.com/blog/{editingPost?.slug || 'post-slug'}</p>
                      <p className="text-sm text-muted-foreground line-clamp-2">{seoDescription || excerpt || 'Post description will appear here...'}</p>
                    </div>
                  </div>
                </TabsContent>
              </Tabs>

              {/* Status & Actions */}
              <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg">
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2">
                    <Label>Status:</Label>
                    <Select value={status} onValueChange={setStatus}>
                      <SelectTrigger className="w-32"><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="published">Published</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Switch checked={isFeatured} onCheckedChange={setIsFeatured} />
                    <Label>Featured Post</Label>
                  </div>
                </div>
              </div>

              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingPost ? "Save Changes" : "Create Post")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Posts</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data?.length || 0}</div>
            <p className="text-xs text-muted-foreground">All time</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Published</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data?.filter((p: any) => p.status === 'published').length || 0}</div>
            <p className="text-xs text-muted-foreground">Live on site</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Drafts</CardTitle>
            <Edit className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data?.filter((p: any) => p.status === 'draft').length || 0}</div>
            <p className="text-xs text-muted-foreground">Not published</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Featured</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{data?.filter((p: any) => p.is_featured).length || 0}</div>
            <p className="text-xs text-muted-foreground">Highlighted posts</p>
          </CardContent>
        </Card>
      </div>

      {/* Posts Table */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>All Posts</CardTitle>
            <div className="flex items-center gap-2">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search posts..."
                  value={searchQuery}
                  onChange={e => setSearchQuery(e.target.value)}
                  className="pl-10 w-64"
                />
              </div>
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-32"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="published">Published</SelectItem>
                  <SelectItem value="draft">Draft</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Post</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredPosts?.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                      No posts found.
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredPosts?.map((post: any) => (
                    <TableRow key={post.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          {post.featured_image ? (
                            <img src={post.featured_image} alt="" className="w-12 h-12 object-cover rounded" />
                          ) : (
                            <div className="w-12 h-12 bg-muted rounded flex items-center justify-center">
                              <Image className="h-5 w-5 text-muted-foreground" />
                            </div>
                          )}
                          <div>
                            <div className="font-medium flex items-center gap-2">
                              {post.is_featured && <TrendingUp className="h-4 w-4 text-yellow-500" />}
                              {post.title}
                            </div>
                            <div className="text-xs text-muted-foreground font-mono">/{post.slug}</div>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        {post.blog_category ? (
                          <Badge
                            style={{ backgroundColor: post.blog_category.color, color: '#fff' }}
                            className="text-xs px-2 py-1"
                          >
                            {post.blog_category.name}
                          </Badge>
                        ) : (
                          <Badge variant="outline" className="text-xs">
                            Uncategorized
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        <Badge variant={post.status === 'published' ? 'default' : 'secondary'}>
                          {post.status}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-1 text-sm">
                          <Calendar className="h-3 w-3" />
                          {new Date(post.created_at).toLocaleDateString()}
                        </div>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm" onClick={() => window.open(`/blog/${post.slug}`, '_blank')}>
                            <ExternalLink className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="sm" onClick={() => handleEdit(post)}>
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="sm" onClick={() => duplicateMutation.mutate(post)}>
                            <Copy className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setDeleteConfirmPost(post)}
                            className="text-destructive hover:text-destructive"
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmPost} onOpenChange={() => setDeleteConfirmPost(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Post</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirmPost?.title}"? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmPost.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Post"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}