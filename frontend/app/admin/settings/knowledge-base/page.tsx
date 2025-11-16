// V-FINAL-1730-552 (Created)
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
import { Plus, Edit, Trash2, Folder } from "lucide-react";

// We need a dedicated controller for this.
// For V1, we will (mis)use the CmsController for this to avoid creating another file.
// In V2, this should be a dedicated KbController.
// --- We'll assume a new /admin/kb-categories endpoint ---
// (The user will need to create the Controller and Route)

export default function KnowledgeBasePage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<any>(null);

  // Form State
  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [description, setDescription] = useState('');
  const [parentId, setParentId] = useState<string | null>(null);

  const { data: categories, isLoading } = useQuery({
    queryKey: ['adminKbCategories'],
    queryFn: async () => (await api.get('/admin/kb-categories')).data,
  });

  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingCategory) {
        return api.put(`/admin/kb-categories/${editingCategory.id}`, data);
      }
      return api.post('/admin/kb-categories', data);
    },
    onSuccess: () => {
      toast.success(editingCategory ? "Category Updated" : "Category Created");
      queryClient.invalidateQueries({ queryKey: ['adminKbCategories'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });
  
  const resetForm = () => {
    setName(''); setSlug(''); setDescription(''); setParentId(null); setEditingCategory(null);
  };

  const handleEdit = (cat: any) => {
    setEditingCategory(cat);
    setName(cat.name);
    setSlug(cat.slug);
    setDescription(cat.description);
    setParentId(cat.parent_id ? String(cat.parent_id) : null);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({ name, slug, description, parent_id: parentId });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Knowledge Base</h1>
          <p className="text-muted-foreground">Organize help articles into categories.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><Plus className="mr-2 h-4 w-4" /> Create Category</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>{editingCategory ? 'Edit Category' : 'Create New Category'}</DialogTitle></DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Category Name</Label>
                <Input value={name} onChange={e => setName(e.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label>Slug</Label>
                <Input value={slug} onChange={e => setSlug(e.target.value)} required placeholder="/getting-started" />
              </div>
              <div className="space-y-2">
                <Label>Parent Category (Optional)</Label>
                <Select value={parentId || ''} onValueChange={(v) => setParentId(v || null)}>
                  <SelectTrigger><SelectValue placeholder="None (Top Level)" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">None (Top Level)</SelectItem>
                    {categories?.map((cat: any) => (
                      <SelectItem key={cat.id} value={String(cat.id)}>{cat.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea value={description} onChange={e => setDescription(e.target.value)} rows={3} />
              </div>
              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : "Save Category"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Manage Categories</CardTitle>
          <CardDescription>Click a category to edit it. Articles can be managed separately.</CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Slug</TableHead>
                  <TableHead>Parent</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {categories?.map((cat: any) => (
                  <TableRow key={cat.id}>
                    <TableCell className="font-medium flex items-center">
                      <Folder className="mr-2 h-4 w-4 text-muted-foreground" />
                      {cat.name}
                    </TableCell>
                    <TableCell className="font-mono text-xs">{cat.slug}</TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {cat.parent_id ? categories.find((p:any) => p.id === cat.parent_id)?.name : '-'}
                    </TableCell>
                    <TableCell>
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(cat)}>
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