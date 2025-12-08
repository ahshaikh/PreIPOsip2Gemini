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
import { Plus, Edit, Trash2, FileText, ChevronLeft, ChevronRight, Loader2, Search, Filter, X } from "lucide-react";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";

export default function KnowledgeBaseArticlePage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingArticle, setEditingArticle] = useState<any>(null);
  const [deleteConfirmArticle, setDeleteConfirmArticle] = useState<any>(null);

  // Debug toggle
  const [showDebug, setShowDebug] = useState(false);

  // Pagination State
  const [page, setPage] = useState(1);

  // Search & Filter State
  const [searchQuery, setSearchQuery] = useState('');
  const [filterCategory, setFilterCategory] = useState('');
  const [filterStatus, setFilterStatus] = useState('');

  // Form State
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [summary, setSummary] = useState('');
  const [content, setContent] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [status, setStatus] = useState('draft');
  const [lastUpdated, setLastUpdated] = useState('');
  const [seoTitle, setSeoTitle] = useState('');
  const [seoDescription, setSeoDescription] = useState('');

  // Build query params
  const buildQueryParams = () => {
    const params = new URLSearchParams();
    params.append('page', page.toString());
    if (searchQuery) params.append('search', searchQuery);
    if (filterCategory) params.append('category_id', filterCategory);
    if (filterStatus) params.append('status', filterStatus);
    return params.toString();
  };

  // Fetch Data with pagination and filters
  const { data: kbResponse, isLoading: isLoadingArticles, isRefetching } = useQuery({
    queryKey: ['adminKbArticles', page, searchQuery, filterCategory, filterStatus],
    queryFn: async () => {
        const response = await api.get(`/admin/kb-articles?${buildQueryParams()}`);
        return response.data;
    },
  });

  const articles = kbResponse?.data || [];
  const lastPage = kbResponse?.last_page || 1;
  const total = kbResponse?.total || 0;

  const { data: categories } = useQuery({
    queryKey: ['adminKbCategories'],
    queryFn: async () => (await api.get('/admin/kb-categories')).data,
  });

  const parseMeta = (meta: any) => {
    if (!meta) return { title: '', description: '' };
    if (typeof meta === 'string') {
        try { return JSON.parse(meta); }
        catch (e) { return { title: '', description: '' }; }
    }
    return meta;
  };

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
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message || "Check console for details" })
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
        await api.delete(`/admin/kb-articles/${id}`);
    },
    onSuccess: async () => {
      toast.success("Article Deleted");
      await queryClient.invalidateQueries({ queryKey: ['adminKbArticles'] });
      setDeleteConfirmArticle(null);
      // Force page 1 if we deleted the last item
      if(articles.length === 1 && page > 1) setPage(p => p - 1);
    },
    onError: (e: any) => toast.error("Delete Failed", { description: e.response?.data?.message || "Server error during delete" })
  });

  const resetForm = () => {
    setTitle(''); setSlug(''); setSummary(''); setContent(''); setCategoryId('');
    setStatus('draft'); setLastUpdated('');
    setSeoTitle(''); setSeoDescription('');
    setEditingArticle(null);
  };

  const handleEdit = (article: any) => {
    console.log("Editing Article Data:", article); // Check Console F12
    setEditingArticle(article);

    setTitle(article.title || '');
    setSlug(article.slug || '');
    setSummary(article.summary || '');
    setContent(article.content || '');
    setCategoryId(article.kb_category_id ? String(article.kb_category_id) : '');
    setStatus(article.status || 'draft');

    const dateVal = article.last_updated ? new Date(article.last_updated).toISOString().split('T')[0] : '';
    setLastUpdated(dateVal);

    const meta = parseMeta(article.seo_meta);
    setSeoTitle(meta?.title || '');
    setSeoDescription(meta?.description || '');

    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
        title, summary, content, kb_category_id: categoryId, status, last_updated: lastUpdated,
        seo_meta: { title: seoTitle, description: seoDescription }
    };
    mutation.mutate(payload);
  };

  return (
    <div className="space-y-6 pb-20">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Knowledge Base Articles</h1>
          <p className="text-muted-foreground">Manage help articles.</p>
        </div>
        <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => setShowDebug(!showDebug)}>
                {showDebug ? "Hide Debug" : "Show Debug"}
            </Button>
            <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
            <DialogTrigger asChild>
                <Button><Plus className="mr-2 h-4 w-4" /> Create Article</Button>
            </DialogTrigger>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader><DialogTitle>{editingArticle ? 'Edit Article' : 'Create New Article'}</DialogTitle></DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div className="space-y-2"><Label>Title</Label><Input value={title} onChange={e => setTitle(e.target.value)} required /></div>
                        <div className="space-y-2"><Label>Summary</Label><Textarea value={summary} onChange={e => setSummary(e.target.value)} rows={2} placeholder="Short summary..." /></div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2"><Label>Category</Label><Select value={categoryId} onValueChange={setCategoryId} required><SelectTrigger><SelectValue placeholder="Select..." /></SelectTrigger><SelectContent>{categories?.map((cat: any) => (<SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>))}</SelectContent></Select></div>
                            <div className="space-y-2"><Label>Status</Label><Select value={status} onValueChange={setStatus} required><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="draft">Draft</SelectItem><SelectItem value="published">Published</SelectItem></SelectContent></Select></div>
                        </div>
                        <div className="space-y-2"><Label>Last Updated</Label><Input type="date" value={lastUpdated} onChange={e => setLastUpdated(e.target.value)} /></div>
                    </div>
                    <div className="space-y-4 p-4 bg-slate-50 border rounded-lg">
                        <h3 className="font-semibold text-sm">SEO Configuration</h3>
                        <div className="space-y-2"><Label className="text-xs">Meta Title</Label><Input value={seoTitle} onChange={e => setSeoTitle(e.target.value)} /></div>
                        <div className="space-y-2"><Label className="text-xs">Meta Description</Label><Textarea value={seoDescription} onChange={e => setSeoDescription(e.target.value)} className="h-24" /></div>
                    </div>
                </div>
                <div className="space-y-2"><Label>Content (Markdown)</Label><Textarea value={content} onChange={e => setContent(e.target.value)} rows={15} className="font-mono text-sm" /></div>
                <div className="flex justify-end gap-2"><Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button><Button type="submit" disabled={mutation.isPending}>{mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}{editingArticle ? "Update" : "Create"}</Button></div>
                </form>
            </DialogContent>
            </Dialog>
        </div>
      </div>

      {/* --- DEBUG BOX (Visible only when toggled) --- */}
      {showDebug && (
        <div className="bg-slate-950 text-green-400 p-4 rounded-md font-mono text-xs overflow-x-auto border border-green-800 mb-4">
            <h3 className="text-white font-bold mb-2">DEBUG: RAW API RESPONSE (First Item)</h3>
            {articles.length > 0 ? (
                <pre>{JSON.stringify(articles[0], null, 2)}</pre>
            ) : (
                <p>No articles loaded to inspect.</p>
            )}
            <div className="mt-2 pt-2 border-t border-green-900 text-white">
                <p>Total Items: {total}</p>
                <p>Current Page: {page}</p>
                <p>Last Page: {lastPage}</p>
            </div>
        </div>
      )}

      {/* Search and Filter Bar */}
      <Card>
        <CardHeader>
          <CardTitle>Search & Filter</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search articles by title or content..."
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setPage(1); // Reset to first page on search
                }}
                className="pl-9"
              />
            </div>
            <Select value={filterCategory} onValueChange={(value) => {
              setFilterCategory(value);
              setPage(1);
            }}>
              <SelectTrigger className="w-full md:w-[200px]">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue placeholder="All Categories" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Categories</SelectItem>
                {categories?.map((cat: any) => (
                  <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={filterStatus} onValueChange={(value) => {
              setFilterStatus(value);
              setPage(1);
            }}>
              <SelectTrigger className="w-full md:w-[180px]">
                <SelectValue placeholder="All Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Status</SelectItem>
                <SelectItem value="draft">Draft</SelectItem>
                <SelectItem value="published">Published</SelectItem>
              </SelectContent>
            </Select>
            {(searchQuery || filterCategory || filterStatus) && (
              <Button
                variant="outline"
                size="icon"
                onClick={() => {
                  setSearchQuery('');
                  setFilterCategory('');
                  setFilterStatus('');
                  setPage(1);
                }}
              >
                <X className="h-4 w-4" />
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="pt-6">
          {isLoadingArticles ? (
            <div className="flex justify-center py-8">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : articles.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <FileText className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No articles found matching your criteria</p>
            </div>
          ) : (
            <>
                <Table>
                <TableHeader><TableRow><TableHead>Title</TableHead><TableHead>Category</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead></TableRow></TableHeader>
                <TableBody>
                    {articles.map((article: any) => (
                        <TableRow key={article.id}>
                            <TableCell className="font-medium">
                                <div className="flex items-center"><FileText className="mr-2 h-4 w-4 text-muted-foreground" />
                                    <div className="flex flex-col ml-2"><span>{article.title}</span><span className="text-xs text-muted-foreground truncate max-w-[200px]">{article.summary || "No Summary"}</span></div>
                                </div>
                            </TableCell>
                            <TableCell>{article.category?.name || 'Uncategorized'}</TableCell>
                            <TableCell><span className={`px-2 py-1 rounded-full text-xs font-semibold ${article.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>{article.status}</span></TableCell>
                            <TableCell className="text-right">
                            <div className="flex justify-end items-center gap-2">
                                <Button variant="ghost" size="sm" onClick={() => handleEdit(article)}><Edit className="h-4 w-4" /></Button>
                                <Button variant="ghost" size="sm" className="text-red-500 hover:bg-red-50" onClick={() => setDeleteConfirmArticle(article)}><Trash2 className="h-4 w-4" /></Button>
                            </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
                </Table>

                {/* --- PAGINATION CONTROLS --- */}
                {lastPage > 1 && (
                  <div className="flex items-center justify-between space-x-2 py-4 border-t mt-4 bg-slate-50 p-2 rounded-b-lg">
                      <div className="text-sm text-muted-foreground">Showing {articles.length} of {total} results (Page {page} of {lastPage})</div>
                      <div className="flex items-center space-x-2">
                          <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1 || isRefetching}><ChevronLeft className="h-4 w-4 mr-2" /> Previous</Button>
                          <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(lastPage, p + 1))} disabled={page === lastPage || isRefetching}>Next <ChevronRight className="h-4 w-4 ml-2" /></Button>
                      </div>
                  </div>
                )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmArticle} onOpenChange={() => setDeleteConfirmArticle(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Article</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete "{deleteConfirmArticle?.title}"? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmArticle.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Article"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
