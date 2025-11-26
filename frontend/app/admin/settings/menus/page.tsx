// V-FINAL-1730-519 (Created)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { toast } from "sonner";
import { Plus, Trash2, Save, GripVertical } from "lucide-react";

export default function MenuManagerPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<string>("");
  const [menuItems, setMenuItems] = useState<any[]>([]);

  const { data: menus, isLoading } = useQuery({
    queryKey: ['adminMenus'],
    queryFn: async () => (await api.get('/admin/menus')).data,
  });

  // Set first tab as active when data loads
  useEffect(() => {
    if (menus && menus.length > 0 && !activeTab) {
      setActiveTab(menus[0].slug);
    }
  }, [menus, activeTab]);

  // Update local item state when tab changes
  useEffect(() => {
    if (menus && activeTab) {
      const activeMenu = menus.find((m: any) => m.slug === activeTab);
      if (activeMenu) setMenuItems(Array.isArray(activeMenu.items) ? activeMenu.items : []);
    }
  }, [activeTab, menus]);

  const mutation = useMutation({
    mutationFn: (data: any) => {
        const menu = menus.find((m: any) => m.slug === activeTab);
        return api.put(`/admin/menus/${menu.id}`, data);
    },
    onSuccess: () => {
      toast.success("Menu Updated");
      queryClient.invalidateQueries({ queryKey: ['adminMenus'] });
      queryClient.invalidateQueries({ queryKey: ['globalSettings'] }); // Invalidate public cache
    }
  });

  const addMenuItem = () => {
    setMenuItems([...menuItems, { label: 'New Link', url: '/', display_order: menuItems.length }]);
  };

  const updateItem = (index: number, field: string, value: string) => {
    const updated = [...menuItems];
    updated[index] = { ...updated[index], [field]: value };
    setMenuItems(updated);
  };

  const removeItem = (index: number) => {
    setMenuItems(menuItems.filter((_, i) => i !== index));
  };

  const handleSave = () => {
    mutation.mutate({ items: menuItems });
  };

  if (isLoading) return <div>Loading menus...</div>;

  return (
    <div className="space-y-6">
        <div className="flex justify-between items-center">
            <h1 className="text-3xl font-bold">Menu Manager</h1>
            <Button onClick={handleSave} disabled={mutation.isPending}>
                <Save className="mr-2 h-4 w-4" /> 
                {mutation.isPending ? "Saving..." : "Save Changes"}
            </Button>
        </div>

        <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList>
                {menus?.map((m: any) => (
                    <TabsTrigger key={m.id} value={m.slug} className="capitalize">{m.name}</TabsTrigger>
                ))}
            </TabsList>
            
            {menus?.map((m: any) => (
                <TabsContent key={m.id} value={m.slug} className="mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Edit: {m.name}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {menuItems.map((item, index) => (
                                <div key={index} className="flex gap-4 items-end border p-4 rounded-lg bg-muted/20">
                                    <GripVertical className="h-5 w-5 text-muted-foreground" />
                                    <div className="flex-1 space-y-2">
                                        <Label>Label</Label>
                                        <Input value={item.label} onChange={(e) => updateItem(index, 'label', e.target.value)} />
                                    </div>
                                    <div className="flex-1 space-y-2">
                                        <Label>URL (e.g., /about or https://...)</Label>
                                        <Input value={item.url} onChange={(e) => updateItem(index, 'url', e.target.value)} />
                                    </div>
                                    <Button variant="destructive" size="icon" onClick={() => removeItem(index)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            ))}
                            <Button variant="outline" onClick={addMenuItem} className="w-full">
                                <Plus className="mr-2 h-4 w-4" /> Add Link
                            </Button>
                        </CardContent>
                    </Card>
                </TabsContent>
            ))}
        </Tabs>
    </div>
  );
}