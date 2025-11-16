// V-FINAL-1730-246 (Created) | V-FINAL-1730-520 | V-FINAL-1730-526 (Advanced Popups)

'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import { Plus, Megaphone, Trash2, Edit } from "lucide-react";

// --- Form Component ---
function BannerForm({
  initialData,
  onSave,
  onCancel,
}: {
  initialData?: any;
  onSave: (data: any) => void;
  onCancel: () => void;
}) {
  const [formData, setFormData] = useState(initialData || {
    title: '',
    content: '',
    type: 'top_bar',
    is_active: true,
    trigger_type: 'load',
    trigger_value: 0,
    frequency: 'always',
    link_url: '',
    targeting_rules: {},
    style_config: {},
  });

  const handleChange = (field: string, value: any) => {
    setFormData((prev: any) => ({ ...prev, [field]: value }));
  };
  
  const handleJsonChange = (field: 'targeting_rules' | 'style_config', value: string) => {
      try {
          handleChange(field, value ? JSON.parse(value) : {});
      } catch (e) {
          // just update the string, let validation handle it
      }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave(formData);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4 max-h-[70vh] overflow-y-auto p-4">
      <div className="space-y-2">
        <Label>Title (Internal Name)</Label>
        <Input value={formData.title} onChange={e => handleChange('title', e.target.value)} />
      </div>
      <div className="space-y-2">
        <Label>Type</Label>
        <Select value={formData.type} onValueChange={v => handleChange('type', v)}>
            <SelectTrigger><SelectValue /></SelectTrigger>
            <SelectContent>
                <SelectItem value="top_bar">Top Announcement Bar</SelectItem>
                <SelectItem value="popup">Modal Popup</SelectItem>
            </SelectContent>
        </Select>
      </div>
      <div className="space-y-2">
        <Label>Content (Text/HTML)</Label>
        <Textarea value={formData.content} onChange={e => handleChange('content', e.target.value)} rows={3} />
      </div>
      <div className="space-y-2">
        <Label>Link URL</Label>
        <Input value={formData.link_url} onChange={e => handleChange('link_url', e.target.value)} placeholder="https://..." />
      </div>

      <hr />
      <h4 className="font-semibold">Rules</h4>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
            <Label>Trigger</Label>
            <Select value={formData.trigger_type} onValueChange={v => handleChange('trigger_type', v)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="load">On Page Load</SelectItem>
                    <SelectItem value="time_delay">Time Delay (Seconds)</SelectItem>
                    <SelectItem value="scroll">Scroll Percentage (%)</SelectItem>
                    <SelectItem value="exit_intent">Exit Intent</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div className="space-y-2">
            <Label>Trigger Value</Label>
            <Input type="number" value={formData.trigger_value} onChange={e => handleChange('trigger_value', parseInt(e.target.value))} />
        </div>
      </div>
      
      <div className="space-y-2">
          <Label>Display Frequency</Label>
          <Select value={formData.frequency} onValueChange={v => handleChange('frequency', v)}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                  <SelectItem value="always">Always (On every page load)</SelectItem>
                  <SelectItem value="once_per_session">Once Per Session</SelectItem>
                  <SelectItem value="once_daily">Once Daily</SelectItem>
                  <SelectItem value="once">Once Ever (Per user)</SelectItem>
              </SelectContent>
          </Select>
      </div>

      <div className="flex items-center space-x-2">
        <Switch checked={formData.is_active} onCheckedChange={v => handleChange('is_active', v)} />
        <Label>Active immediately?</Label>
      </div>

      <Button type="submit" className="w-full">Save Changes</Button>
    </form>
  );
}


// --- MAIN PAGE ---
export default function BannerManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingBanner, setEditingBanner] = useState<any>(null);

  const { data: banners, isLoading } = useQuery({
    queryKey: ['adminBanners'],
    queryFn: async () => (await api.get('/admin/banners')).data,
  });

  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingBanner) {
        return api.put(`/admin/banners/${editingBanner.id}`, data);
      }
      return api.post('/admin/banners', data);
    },
    onSuccess: () => {
      toast.success(editingBanner ? "Banner Updated" : "Banner Created");
      queryClient.invalidateQueries({ queryKey: ['adminBanners'] });
      queryClient.invalidateQueries({ queryKey: ['globalSettings'] });
      setIsDialogOpen(false);
      setEditingBanner(null);
    }
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/banners/${id}`),
    onSuccess: () => {
      toast.success("Banner Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminBanners'] });
      queryClient.invalidateQueries({ queryKey: ['globalSettings'] });
    }
  });

  const handleSave = (data: any) => {
    mutation.mutate(data);
  };

  const handleOpen = (banner: any = null) => {
    setEditingBanner(banner); // null = new, object = editing
    setIsDialogOpen(true);
  };

  if (isLoading) return <div>Loading...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Banner & Popup Manager</h1>
        <Button onClick={() => handleOpen()}><Plus className="mr-2 h-4 w-4" /> Add Announcement</Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          <Table>
            <TableHeader><TableRow>
              <TableHead>Title</TableHead>
              <TableHead>Type</TableHead>
              <TableHead>Trigger</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow></TableHeader>
            <TableBody>
              {banners?.map((banner: any) => (
                <TableRow key={banner.id}>
                  <TableCell className="font-medium">{banner.title}</TableCell>
                  <TableCell><span className="bg-muted px-2 py-1 rounded text-xs">{banner.type}</span></TableCell>
                  <TableCell className="text-xs">{banner.trigger_type} ({banner.trigger_value})</TableCell>
                  <TableCell>
                    <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        banner.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                      {banner.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </TableCell>
                  <TableCell className="space-x-2">
                    <Button variant="outline" size="sm" onClick={() => handleOpen(banner)}>
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button variant="destructive" size="sm" onClick={() => deleteMutation.mutate(banner.id)}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      
      {/* Edit/Create Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{editingBanner ? 'Edit Banner' : 'Create New Banner'}</DialogTitle>
          </DialogHeader>
          <BannerForm
            initialData={editingBanner}
            onSave={handleSave}
            onCancel={() => setIsDialogOpen(false)}
          />
        </DialogContent>
      </Dialog>
    </div>
  );
}