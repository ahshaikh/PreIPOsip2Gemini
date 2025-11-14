// V-REMEDIATE-1730-169 (Created) | V-FINAL-1730-370 (Block Editor UI)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { PlusCircle, Edit, FileText, Trash2, GripVertical } from "lucide-react";
import { useState } from "react";

// The component for editing a single block
function BlockEditor({ block, onUpdate, onDelete }: { block: any, onUpdate: (data: any) => void, onDelete: () => void }) {
    if (block.type === 'heading') {
        return (
            <div className="border p-4 rounded-lg bg-muted/20 space-y-2">
                <Label>Block: Heading</Label>
                <Input 
                    value={block.text} 
                    onChange={(e) => onUpdate({ ...block, text: e.target.value })}
                    placeholder="Enter heading text..."
                />
                <Button variant="destructive" size="sm" onClick={onDelete}>Delete Block</Button>
            </div>
        );
    }
    
    if (block.type === 'text') {
        return (
            <div className="border p-4 rounded-lg bg-muted/20 space-y-2">
                <Label>Block: Text</Label>
                <Textarea 
                    value={block.content} 
                    onChange={(e) => onUpdate({ ...block, content: e.target.value })}
                    placeholder="Enter paragraph text..."
                    rows={5}
                />
                <Button variant="destructive" size="sm" onClick={onDelete}>Delete Block</Button>
            </div>
        );
    }

    if (block.type === 'image') {
        return (
            <div className="border p-4 rounded-lg bg-muted/20 space-y-2">
                <Label>Block: Image</Label>
                <Input 
                    value={block.src} 
                    onChange={(e) => onUpdate({ ...block, src: e.target.value })}
                    placeholder="Enter image URL..."
                />
                <Input 
                    value={block.alt} 
                    onChange={(e) => onUpdate({ ...block, alt: e.target.value })}
                    placeholder="Enter alt text..."
                />
                <Button variant="destructive" size="sm" onClick={onDelete}>Delete Block</Button>
            </div>
        );
    }
    return null;
}

export default function CmsSettingsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPage, setEditingPage] = useState<any>(null);

  // Form State
  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [status, setStatus] = useState('draft');
  const [contentBlocks, setContentBlocks] = useState<any[]>([]);

  const { data, isLoading } = useQuery({
    queryKey: ['adminPages'],
    queryFn: async () => (await api.get('/admin/pages')).data,
  });

  const createMutation = useMutation({
    mutationFn: (newPage: any) => api.post('/admin/pages', newPage),
    onSuccess: () => {
      toast.success("Page Created");
      queryClient.invalidateQueries({ queryKey: ['adminPages'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const updateMutation = useMutation({
    mutationFn: (page: any) => api.put(`/admin/pages/${page.id}`, page),
    onSuccess: () => {
      toast.success("Page Updated");
      queryClient.invalidateQueries({ queryKey: ['adminPages'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const resetForm = () => {
    setTitle(''); setSlug(''); setStatus('draft'); setEditingPage(null); setContentBlocks([]);
  };

  const handleEdit = (page: any) => {
    setEditingPage(page);
    setTitle(page.title);
    setSlug(page.slug);
    setStatus(page.status);
    setContentBlocks(page.content || []); // Load blocks
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = { title, slug, content: contentBlocks, status };

    if (editingPage) {
      updateMutation.mutate({ ...payload, id: editingPage.id });
    } else {
      createMutation.mutate(payload);
    }
  };

  // --- Block Handlers ---
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
          <DialogContent className="max-w-3xl">
            <DialogHeader>
              <DialogTitle>{editingPage ? 'Edit Page' : 'Create New Page'}</DialogTitle>
              <DialogDescription>Use the block editor to build your page.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2 col-span-1">
                  <Label>Page Title</Label>
                  <Input value={title} onChange={(e) => setTitle(e.target.value)} required />
                </div>
                <div className="space-y-2 col-span-1">
                  <Label>Slug</Label>
                  <Input value={slug} onChange={(e) => setSlug(e.target.value)} required placeholder="/about-us" />
                </div>
                <div className="space-y-2 col-span-1">
                  <Label>Status</Label>
                  <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="draft">Draft</SelectItem>
                      <SelectItem value="published">Published</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              
              <hr />
              <Label className="text-lg font-semibold">Page Content</Label>
              <div className="space-y-4 max-h-[50vh] overflow-y-auto p-4 border rounded-md">
                {contentBlocks.length === 0 && <p className="text-center text-muted-foreground">Click "Add Block" to start.</p>}
                {contentBlocks.map((block, index) => (
                  <BlockEditor 
                    key={block.id} 
                    block={block}
                    onUpdate={(data) => updateBlock(block.id, data)}
                    onDelete={() => deleteBlock(block.id)}
                  />
                ))}
              </div>
              
              <div className="flex gap-2 justify-center">
                <Button type="button" variant="outline" onClick={() => addBlock('heading')}>+ Heading</Button>
                <Button type="button" variant="outline" onClick={() => addBlock('text')}>+ Text Block</Button>
                <Button type="button" variant="outline" onClick={() => addBlock('image')}>+ Image</Button>
              </div>

              <hr />
              <Button type="submit" className="w-full" disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? "Saving..." : "Save Page"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Pages</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading...</p> : (
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
                {data?.map((page: any) => (
                  <TableRow key={page.id}>
                    <TableCell className="font-medium flex items-center">
                      <FileText className="mr-2 h-4 w-4 text-muted-foreground" />
                      {page.title}
                    </TableCell>
                    <TableCell className="font-mono text-xs">/{page.slug}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        page.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                      }`}>
                        {page.status}
                      </span>
                    </TableCell>
                    <TableCell>{new Date(page.updated_at).toLocaleDateString()}</TableCell>
                    <TableCell>
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(page)}>
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