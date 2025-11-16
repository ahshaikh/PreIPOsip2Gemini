// V-REMEDIATE-1730-169 (Created) | V-FINAL-1730-370 (Block Editor UI) | V-FINAL-1730-530 (SEO Analyzer UI) | V-FINAL-1730-560 (Versioning UI)

'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { PlusCircle, Edit, FileText, Trash2, Search, CheckCircle, XCircle, Loader2, Save } from "lucide-react";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { ScrollArea } from "@/components/ui/scroll-area"; // Import ScrollArea

// --- SEO Analyzer Component (NEW) ---
function SeoAnalyzer({ pageId }: { pageId: number }) {
  const { data, isLoading, refetch, isFetching } = useQuery({
    queryKey: ['seoAnalysis', pageId],
    queryFn: async () => (await api.get(`/admin/pages/${pageId}/analyze`)).data,
    enabled: false, // Only run on-demand
    staleTime: Infinity,
  });

  const getScoreColor = (score: number) => {
    if (score > 80) return "text-green-600";
    if (score > 50) return "text-yellow-600";
    return "text-red-600";
  };

  return (
    <div className="space-y-4">
      <Button onClick={() => refetch()} disabled={isFetching}>
        {isFetching ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Search className="mr-2 h-4 w-4" />}
        Analyze Page SEO
      </Button>
      
      {isLoading ? (
        <p>Analyzing...</p>
      ) : data && (
        <div className="grid grid-cols-3 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-lg">Score</CardTitle>
            </CardHeader>
            <CardContent>
              <div className={`text-6xl font-bold ${getScoreColor(data.score)}`}>
                {data.score}
                <span className="text-2xl">/100</span>
              </div>
            </CardContent>
          </Card>
          <Card className="col-span-2">
            <CardHeader className="pb-2"><CardTitle className="text-lg">Recommendations</CardTitle></CardHeader>
            <CardContent className="space-y-2">
              {data.recommendations.map((rec: string, i: number) => (
                <div key={i} className="flex items-start gap-2 text-sm">
                  {rec.includes("Great job") ? 
                    <CheckCircle className="h-4 w-4 text-green-500 mt-0.5" /> : 
                    <XCircle className="h-4 w-4 text-red-500 mt-0.5" />}
                  <span>{rec}</span>
                </div>
              ))}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}

// --- Block Editor Component ---
function BlockEditor({ block, onUpdate, onDelete }: { block: any, onUpdate: (data: any) => void, onDelete: () => void }) {
    if (block.type === 'heading') {
        return (
            <div className="border p-4 rounded-lg bg-muted/20 space-y-2">
                <Label>Block: Heading</Label>
                <Input value={block.text} onChange={(e) => onUpdate({ ...block, text: e.target.value })} />
                <Button variant="destructive" size="sm" onClick={onDelete}>Delete Block</Button>
            </div>
        );
    }
    if (block.type === 'text') {
        return (
            <div className="border p-4 rounded-lg bg-muted/20 space-y-2">
                <Label>Block: Text</Label>
                <Textarea value={block.content} onChange={(e) => onUpdate({ ...block, content: e.target.value })} rows={5} />
                <Button variant="destructive" size="sm" onClick={onDelete}>Delete Block</Button>
            </div>
        );
    }
    if (block.type === 'image') {
        return (
            <div className="border p-4 rounded-lg bg-muted/20 space-y-2">
                <Label>Block: Image</Label>
                <Input value={block.src || ''} onChange={(e) => onUpdate({ ...block, src: e.target.value })} placeholder="Image URL" />
                <Input value={block.alt || ''} onChange={(e) => onUpdate({ ...block, alt: e.target.value })} placeholder="Alt Text (Important for SEO)" />
                <Button variant="destructive" size="sm" onClick={onDelete}>Delete Block</Button>
            </div>
        );
    }
    return null;
}

// --- Main Page Component ---
export default function CmsSettingsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPage, setEditingPage] = useState<any>(null);

  // Form State
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [status, setStatus] = useState('draft');
  const [contentBlocks, setContentBlocks] = useState<any[]>([]);
  const [seoMeta, setSeoMeta] = useState({ title: '', description: '' });

  const { data, isLoading } = useQuery({
    queryKey: ['adminPages'],
    queryFn: async () => (await api.get('/admin/pages')).data,
  });

  const createMutation = useMutation({
    mutationFn: (newPage: any) => api.post('/admin/pages', newPage),
    onSuccess: (data) => {
      toast.success("Page Created as Draft");
      queryClient.invalidateQueries({ queryKey: ['adminPages'] });
      handleEdit(data.data); // Open the new page for editing
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });
  
  const updateMutation = useMutation({
    mutationFn: (page: any) => api.put(`/admin/pages/${page.id}`, page),
    onSuccess: () => {
      toast.success("Page Updated!");
      queryClient.invalidateQueries({ queryKey: ['adminPages'] });
      setIsDialogOpen(false); // Close dialog on save
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const resetForm = () => {
    setTitle(''); setSlug(''); setStatus('draft'); setEditingPage(null); setContentBlocks([]);
    setSeoMeta({ title: '', description: '' });
  };

  const handleEdit = (page: any) => {
    setEditingPage(page);
    setTitle(page.title);
    setSlug(page.slug);
    setStatus(page.status);
    setContentBlocks(page.content || []);
    setSeoMeta(page.seo_meta || { title: '', description: '' });
    setIsDialogOpen(true);
  };

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate({ title, slug, status: 'draft', content: [] });
  };
  
  const handleSave = () => {
    const payload = { 
        id: editingPage.id,
        title, 
        slug, 
        content: contentBlocks, 
        status, 
        seo_meta: seoMeta 
    };
    updateMutation.mutate(payload);
  };

  // Block Handlers
  const addBlock = (type: string) => {
    let newBlock = {};
    if (type === 'heading') newBlock = { id: Date.now(), type: 'heading', text: '' };
    if (type === 'text') newBlock = { id: Date.now(), type: 'text', content: '' };
    if (type === 'image') newBlock = { id: Date.now(), type: 'image', src: '', alt: '' };
    setContentBlocks([...contentBlocks, newBlock]);
  };
  
  const updateBlock = (id: number, data: any) => {
    setContentBlocks(contentBlocks.map(b => (b.id === id ? data : b)));
  };

  const deleteBlock = (id: number) => {
    setContentBlocks(contentBlocks.filter(b => b.id !== id));
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Content Management</h1>
          <p className="text-muted-foreground">Manage your website pages and legal documents.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> Create Page</Button>
          </DialogTrigger>
          <DialogContent className="max-w-4xl">
            <DialogHeader>
              <DialogTitle>{editingPage ? `Edit: ${editingPage.title}` : 'Create New Page'}</DialogTitle>
            </DialogHeader>
            
            {/* If creating, show simple form */}
            {!editingPage && (
                <form onSubmit={handleCreate} className="space-y-4">
                    <div className="space-y-2"><Label>Page Title</Label><Input value={title} onChange={(e) => setTitle(e.target.value)} required /></div>
                    <div className="space-y-2"><Label>Slug</Label><Input value={slug} onChange={(e) => setSlug(e.target.value)} required placeholder="/about-us" /></div>
                    <Button type="submit" className="w-full" disabled={createMutation.isPending}>
                      {createMutation.isPending ? "Creating..." : "Create Draft"}
                    </Button>
                </form>
            )}

            {/* If editing, show full editor */}
            {editingPage && (
                <div className="space-y-4">
                <Tabs defaultValue="content">
                    <TabsList>
                        <TabsTrigger value="content">Content</TabsTrigger>
                        <TabsTrigger value="seo">SEO</TabsTrigger>
                    </TabsList>
                    
                    {/* --- Content Tab --- */}
                    <TabsContent value="content" className="space-y-4 pt-4">
                        <div className="grid grid-cols-3 gap-4">
                            <div className="space-y-2"><Label>Page Title</Label><Input value={title} onChange={(e) => setTitle(e.target.value)} /></div>
                            <div classNamea="space-y-2"><Label>Slug (Cannot be changed)</Label><Input value={slug} disabled /></div>
                            <div className="space-y-2"><Label>Status</Label><Select value={status} onValueChange={setStatus}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="draft">Draft</SelectItem><SelectItem value="published">Published</SelectItem></SelectContent></Select></div>
                        </div>
                        <hr />
                        <Label className="text-lg font-semibold">Page Content Blocks</Label>
                        <ScrollArea className="h-[40vh] w-full p-4 border rounded-md">
                            <div className="space-y-4">
                                {contentBlocks.length === 0 && <p className="text-center text-muted-foreground">Click "Add Block" to start.</p>}
                                {contentBlocks.map((block) => (
                                    <BlockEditor key={block.id} block={block} onUpdate={(d) => updateBlock(block.id, d)} onDelete={() => deleteBlock(block.id)} />
                                ))}
                            </div>
                        </ScrollArea>
                        <div className="flex gap-2 justify-center">
                            <Button type="button" variant="outline" size="sm" onClick={() => addBlock('heading')}>+ H2</Button>
                            <Button type="button" variant="outline" size="sm" onClick={() => addBlock('text')}>+ Text</Button>
                            <Button type="button" variant="outline" size="sm" onClick={() => addBlock('image')}>+ Image</Button>
                        </div>
                    </TabsContent>
                    
                    {/* --- SEO Tab (NEW) --- */}
                    <TabsContent value="seo" className="space-y-4 pt-4">
                        <div className="space-y-2">
                            <Label>SEO Title (Meta Title)</Label>
                            <Input value={seoMeta.title} onChange={(e) => setSeoMeta(p => ({...p, title: e.target.value}))} placeholder="If blank, page title is used." />
                        </div>
                        <div className="space-y-2">
                            <Label>SEO Description (Meta Description)</Label>
                            <Textarea value={seoMeta.description} onChange={(e) => setSeoMeta(p => ({...p, description: e.target.value}))} rows={3} placeholder="If blank, first 155 chars of content are used." />
                        </div>
                        <hr />
                        <SeoAnalyzer pageId={editingPage.id} />
                    </TabsContent>
                </Tabs>
                
                <hr />
                <Button onClick={handleSave} className="w-full" disabled={updateMutation.isPending}>
                    <Save className="mr-2 h-4 w-4" />
                    {updateMutation.isPending ? "Saving..." : "Save Changes"}
                </Button>
                </div>
            )}
          </DialogContent>
        </Dialog>

      <Card>
        <CardHeader>
          <CardTitle>Pages</CardTitle>
          <CardDescription>All website pages managed by the CMS.</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Title</TableHead>
                <TableHead>Slug</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Last Updated</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow><TableCell colSpan={5}>Loading...</TableCell></TableRow>
              ) : (
                data?.map((page: any) => (
                  <TableRow key={page.id}>
                    <TableCell className="font-medium flex items-center">
                      <FileText className="mr-2 h-4 w-4 text-muted-foreground" />
                      {page.title}
                    </TableCell>
                    <TableCell className="font-mono text-xs">/{page.slug}</TableCell>
                    <TableCell>
                      <Badge variant={page.status === 'published' ? 'default' : 'outline'}>
                        {page.status}
                      </Badge>
                    </TableCell>
                    <TableCell>{new Date(page.updated_at).toLocaleDateString()}</TableCell>
                    <TableCell>
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(page)}>
                        <Edit className="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}