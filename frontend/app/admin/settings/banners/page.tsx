// V-FINAL-1730-246
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Plus, Megaphone } from "lucide-react";

export default function BannerManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  // Form State
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [type, setType] = useState('top_bar');
  const [isActive, setIsActive] = useState(true);

  const { data: banners, isLoading } = useQuery({
    queryKey: ['adminBanners'],
    queryFn: async () => (await api.get('/admin/banners')).data,
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/banners', data),
    onSuccess: () => {
      toast.success("Banner Created");
      queryClient.invalidateQueries({ queryKey: ['adminBanners'] });
      setIsDialogOpen(false);
      setTitle(''); setContent('');
    }
  });

  const toggleMutation = useMutation({
    mutationFn: (banner: any) => api.put(`/admin/banners/${banner.id}`, { is_active: !banner.is_active }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['adminBanners'] })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/banners/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['adminBanners'] })
  });

  const handleSubmit = () => {
    createMutation.mutate({ title, content, type, is_active: isActive });
  };

  if (isLoading) return <div>Loading...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Banner & Popup Manager</h1>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild><Button><Plus className="mr-2 h-4 w-4" /> Add Announcement</Button></DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create New Banner</DialogTitle></DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Title (Internal Name)</Label>
                <Input value={title} onChange={e => setTitle(e.target.value)} />
              </div>
              <div className="space-y-2">
                <Label>Type</Label>
                <Select value={type} onValueChange={setType}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="top_bar">Top Announcement Bar</SelectItem>
                        <SelectItem value="popup">Modal Popup</SelectItem>
                    </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Content (Text/HTML)</Label>
                <Input value={content} onChange={e => setContent(e.target.value)} />
              </div>
              <div className="flex items-center space-x-2">
                <Switch checked={isActive} onCheckedChange={setIsActive} />
                <Label>Active immediately?</Label>
              </div>
              <Button onClick={handleSubmit} className="w-full" disabled={createMutation.isPending}>Create</Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid gap-4">
        {banners?.map((banner: any) => (
            <Card key={banner.id}>
                <CardContent className="flex items-center justify-between p-6">
                    <div className="flex items-center gap-4">
                        <div className="p-2 bg-primary/10 rounded">
                            <Megaphone className="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h3 className="font-bold">{banner.title}</h3>
                            <p className="text-sm text-muted-foreground">{banner.content}</p>
                            <div className="flex gap-2 mt-1">
                                <span className="text-xs bg-muted px-2 py-0.5 rounded uppercase">{banner.type.replace('_', ' ')}</span>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center space-x-2">
                            <Switch checked={banner.is_active} onCheckedChange={() => toggleMutation.mutate(banner)} />
                            <span className="text-sm text-muted-foreground">{banner.is_active ? 'Active' : 'Inactive'}</span>
                        </div>
                        <Button variant="destructive" size="sm" onClick={() => deleteMutation.mutate(banner.id)}>Delete</Button>
                    </div>
                </CardContent>
            </Card>
        ))}
      </div>
    </div>
  );
}