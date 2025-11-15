// V-FINAL-1730-487 (Created)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Edit, Trash2 } from "lucide-react";

export default function CannedResponsesPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingResponse, setEditingResponse] = useState<any>(null);

  // Form State
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');

  const { data: responses, isLoading } = useQuery({
    queryKey: ['adminCannedResponses'],
    queryFn: async () => (await api.get('/admin/canned-responses')).data,
  });

  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingResponse) {
        return api.put(`/admin/canned-responses/${editingResponse.id}`, data);
      }
      return api.post('/admin/canned-responses', data);
    },
    onSuccess: () => {
      toast.success(editingResponse ? "Response Updated" : "Response Created");
      queryClient.invalidateQueries({ queryKey: ['adminCannedResponses'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });
  
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/canned-responses/${id}`),
    onSuccess: () => {
      toast.success("Response Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminCannedResponses'] });
    }
  });

  const resetForm = () => {
    setTitle(''); setBody(''); setEditingResponse(null);
  };

  const handleEdit = (response: any) => {
    setEditingResponse(response);
    setTitle(response.title);
    setBody(response.body);
    setIsDialogOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({ title, body });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Canned Responses</h1>
          <p className="text-muted-foreground">Manage templates for support ticket replies.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><Plus className="mr-2 h-4 w-4" /> Create Response</Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>{editingResponse ? 'Edit Response' : 'Create New Response'}</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Title</Label>
                <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Blurry PAN Photo" required />
              </div>
              <div className="space-y-2">
                <Label>Body</Label>
                <Textarea 
                  value={body} 
                  onChange={(e) => setBody(e.target.value)} 
                  rows={8} 
                  placeholder="Hello {{user_name}}, we could not verify..."
                  required
                />
                <p className="text-xs text-muted-foreground">
                  Use variables: {`{{user_name}}`}
                </p>
              </div>
              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : "Save Template"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Body (Excerpt)</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {responses?.map((res: any) => (
                  <TableRow key={res.id}>
                    <TableCell className="font-medium">{res.title}</TableCell>
                    <TableCell className="text-muted-foreground italic">
                      {res.body.substring(0, 75)}...
                    </TableCell>
                    <TableCell className="space-x-2">
                      <Button variant="ghost" size="sm" onClick={() => handleEdit(res)}>
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="sm" onClick={() => deleteMutation.mutate(res.id)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
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