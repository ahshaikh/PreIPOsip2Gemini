// V-REMEDIATE-1730-169
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
import { PlusCircle, Edit, FileText } from "lucide-react";
import { useState } from "react";

export default function CmsSettingsPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPage, setEditingPage] = useState<any>(null);

  // Form State
  const [title, setTitle] = useState('');
  const [content, setContent] = useState(''); // Simple text for now, can be JSON later
  const [status, setStatus] = useState('draft');

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
    setTitle(''); setContent(''); setStatus('draft'); setEditingPage(null);
  };

  const handleEdit = (page: any) => {
    setEditingPage(page);
    setTitle(page.title);
    // If content is JSON, stringify it, otherwise use as is
    setContent(typeof page.content === 'object' ? JSON.stringify(page.content, null, 2) : page.content);
    setStatus(page.status);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Try to parse content as JSON if it looks like it
    let finalContent = content;
    try {
        finalContent = JSON.parse(content);
    } catch (e) {
        // keep as string
    }

    const payload = { title, content: finalContent, status };

    if (editingPage) {
      updateMutation.mutate({ ...payload, id: editingPage.id });
    } else {
      createMutation.mutate(payload);
    }
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
              <DialogDescription>Define page content. Use JSON for structured layouts.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Page Title</Label>
                  <Input value={title} onChange={(e) => setTitle(e.target.value)} required />
                </div>
                <div className="space-y-2">
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
              <div className="space-y-2">
                <Label>Content (JSON or HTML)</Label>
                <Textarea 
                  value={content} 
                  onChange={(e) => setContent(e.target.value)} 
                  rows={15} 
                  className="font-mono text-xs"
                  placeholder='{"sections": [{"type": "hero", "text": "Welcome"}]}'
                />
              </div>
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