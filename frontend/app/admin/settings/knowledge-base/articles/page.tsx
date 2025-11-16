// V-FINAL-1730-556 (Created)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Edit, Trash2, FileText } from "lucide-react";

export default function KnowledgeBaseArticlePage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingArticle, setEditingArticle] = useState<any>(null);

  // Form State
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [content, setContent] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [status, setStatus] = useState('draft');

  // Fetch Articles
  const { data: articles, isLoading: isLoadingArticles } = useQuery({
    queryKey: ['adminKbArticles'],
    queryFn: async () => (await api.get('/admin/kb-articles')).data.data, // Note: it's paginated
  });
  
  // Fetch Categories for the dropdown
  const { data: categories, isLoading: isLoadingCategories } = useQuery({
    queryKey: ['adminKbCategories'],
    queryFn: async () => (await api.get('/admin/kb-categories')).data,
  });

  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingArticle) {
        return api.put(`/admin/kb-articles/${editingArticle.id}`, data);
      }
      return api.post('/admin/kb-articles', data);
    },
    onSuccess: () => {
      toast.success(editingArticle ? "Article Updated" : "Article Created");
      queryClient.invalidateQueries({ queryKey: ['adminKbArticles'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });
  
  const resetForm = () => {
    setTitle(''); setSlug(''); setContent(''); setCategoryId(''); setStatus('draft');
    setEditingArticle(null);
  };

  const handleEdit = (article: any) => {
    setEditingArticle(article);
    setTitle(article.title);
    setSlug(article.slug);
    setContent(article.content);
    setCategoryId(article.kb_category_id.toString());
    setStatus(article.status);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({ title, content, kb_category_id: categoryId, status, slug });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Knowledge Base Articles</h1>
          <p className="text-muted-foreground">Write and manage help articles.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><Plus className="mr-2 h-4 w-4" /> Create Article</Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl">
            <DialogHeader><DialogTitle>{editingArticle ? 'Edit Article' : 'Create New Article'}</DialogTitle></DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Title</Label>
                <Input value={title} onChange={e => setTitle(e.target.value)} required />
              </div>
              
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label>Category</Label>
                    <Select value={categoryId} onValueChange={setCategoryId} required>
                    <SelectTrigger><SelectValue placeholder="Select a category..." /></SelectTrigger>
                    <SelectContent>
                        {categories?.map((cat: any) => (
                        <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                        ))}
                    </SelectContent>
                    </Select>
                </div>
                <div className="space-y-2">
                    <Label>Status</Label>
                    <Select value={status} onValueChange={setStatus} required>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Content (Markdown supported)</Label>
                <Textarea value={content} onChange={e => setContent(e.target.value)} rows={12} />
              </div>
              
              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : "Save Article"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoadingArticles ? <p>Loading articles...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {articles?.map((article: any) => (
                  <TableRow key={article.id}>
                    <TableCell className="font-medium flex items-center">
                      <FileText className="mr-2 h-4 w-4 text-muted-foreground" />
                      {article.title}
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {article.category?.name || 'N/A'}
                    </TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        article.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {article.status}
                      </span>
                    </TableCell>
                    <TableCell>
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(article)}>
                        <Edit className="h-4 w-4" />
                      </Button>
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