// V-FINAL-1730-519 (Created) | V-CMS-ENHANCEMENT-009 (Multi-level support) | V-CMS-ENHANCEMENT-013 (Drag-drop with @hello-pangea/dnd)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { toast } from "sonner";
import { Plus, Trash2, Save, GripVertical, ChevronRight, Layers, Move } from "lucide-react";
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';

interface MenuItem {
  id?: number;
  label: string;
  url: string;
  parent_id: number | null;
  display_order: number;
  children?: MenuItem[];
}

interface Menu {
  id: number;
  name: string;
  slug: string;
  items: MenuItem[];
}

export default function MenuManagerPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<string>("");
  const [menuItems, setMenuItems] = useState<MenuItem[]>([]);

  const { data: menus, isLoading } = useQuery<Menu[]>({
    queryKey: ['adminMenus'],
    queryFn: async () => (await api.get('/admin/menus')).data,
  });

  // Set first tab as active when data loads
  useEffect(() => {
    if (menus && menus.length > 0 && !activeTab) {
      setActiveTab(menus[0].slug);
    }
  }, [menus, activeTab]);

  // Flatten nested items for editing
  const flattenItems = (items: MenuItem[], parentId: number | null = null): MenuItem[] => {
    let flat: MenuItem[] = [];
    items.forEach((item) => {
      flat.push({ ...item, parent_id: parentId });
      if (item.children && item.children.length > 0) {
        flat = flat.concat(flattenItems(item.children, item.id || null));
      }
    });
    return flat;
  };

  // Update local item state when tab changes
  useEffect(() => {
    if (menus && activeTab) {
      const activeMenu = menus.find((m: Menu) => m.slug === activeTab);
      if (activeMenu) {
        // Flatten nested structure for editing
        const flattened = flattenItems(activeMenu.items);
        setMenuItems(flattened);
      }
    }
  }, [activeTab, menus]);

  const mutation = useMutation({
    mutationFn: (data: any) => {
        const menu = menus?.find((m: Menu) => m.slug === activeTab);
        return api.put(`/admin/menus/${menu?.id}`, data);
    },
    onSuccess: () => {
      toast.success("Menu Updated Successfully");
      queryClient.invalidateQueries({ queryKey: ['adminMenus'] });
      queryClient.invalidateQueries({ queryKey: ['globalSettings'] }); // Invalidate public cache
    },
    onError: () => {
      toast.error("Failed to update menu");
    }
  });

  const addMenuItem = (parentId: number | null = null) => {
    const newItem: MenuItem = {
      label: 'New Link',
      url: '/',
      parent_id: parentId,
      display_order: menuItems.filter(item => item.parent_id === parentId).length
    };
    setMenuItems([...menuItems, newItem]);
  };

  const updateItem = (index: number, field: keyof MenuItem, value: any) => {
    const updated = [...menuItems];
    updated[index] = { ...updated[index], [field]: value };
    setMenuItems(updated);
  };

  const removeItem = (index: number) => {
    const itemToRemove = menuItems[index];
    // Also remove all children of this item
    const updatedItems = menuItems.filter((item, i) => {
      if (i === index) return false;
      if (item.parent_id === itemToRemove.id) return false;
      return true;
    });
    setMenuItems(updatedItems);
  };

  // Handle drag-drop reordering
  const handleDragEnd = (result: DropResult) => {
    if (!result.destination) return;

    const sourceIndex = result.source.index;
    const destinationIndex = result.destination.index;

    if (sourceIndex === destinationIndex) return;

    // Reorder items
    const items = Array.from(menuItems);
    const [removed] = items.splice(sourceIndex, 1);
    items.splice(destinationIndex, 0, removed);

    // Update display_order for all items
    const updatedItems = items.map((item, idx) => ({
      ...item,
      display_order: idx
    }));

    setMenuItems(updatedItems);
    toast.success("Items reordered. Click 'Save Changes' to persist.");
  };

  const handleSave = () => {
    // Validate: check for circular references and max depth
    const hasCircularRef = menuItems.some((item, idx) => {
      if (!item.parent_id) return false;
      let parentIdx = menuItems.findIndex(m => m.id === item.parent_id);
      let depth = 0;
      while (parentIdx !== -1 && depth < 10) {
        const parent = menuItems[parentIdx];
        if (!parent.parent_id) break;
        if (parent.parent_id === item.id) return true; // Circular!
        parentIdx = menuItems.findIndex(m => m.id === parent.parent_id);
        depth++;
      }
      return depth >= 10;
    });

    if (hasCircularRef) {
      toast.error("Invalid menu structure: circular reference detected");
      return;
    }

    mutation.mutate({ items: menuItems });
  };

  // Get nesting level for visual display
  const getNestingLevel = (item: MenuItem): number => {
    let level = 0;
    let currentParentId = item.parent_id;
    while (currentParentId !== null && level < 10) {
      const parent = menuItems.find(m => m.id === currentParentId);
      if (!parent) break;
      level++;
      currentParentId = parent.parent_id;
    }
    return level;
  };

  // Get available parent options for an item (excluding itself and its descendants)
  const getParentOptions = (currentItem: MenuItem, currentIndex: number): MenuItem[] => {
    return menuItems.filter((item, idx) => {
      // Can't be its own parent
      if (idx === currentIndex) return false;
      // Can't be a child of itself
      if (item.parent_id === currentItem.id) return false;
      // Can't create more than 2 levels (so only top-level items can be parents)
      const level = getNestingLevel(item);
      if (level >= 2) return false;
      return true;
    });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="text-lg font-medium">Loading menus...</div>
          <div className="text-sm text-muted-foreground">Please wait</div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
        <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold">Menu Manager</h1>
              <p className="text-muted-foreground mt-1">
                Create and manage multi-level navigation menus with nested items (up to 3 levels). Drag to reorder.
              </p>
            </div>
            <Button onClick={handleSave} disabled={mutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {mutation.isPending ? "Saving..." : "Save Changes"}
            </Button>
        </div>

        <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList>
                {menus?.map((m: Menu) => (
                    <TabsTrigger key={m.id} value={m.slug} className="capitalize">{m.name}</TabsTrigger>
                ))}
            </TabsList>

            {menus?.map((m: Menu) => (
                <TabsContent key={m.id} value={m.slug} className="mt-4 space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                              <Layers className="h-5 w-5" />
                              {m.name} Menu
                            </CardTitle>
                            <CardDescription>
                              Manage menu items with nested sub-items. <Move className="inline h-3 w-3" /> <strong>Drag items</strong> to reorder them.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {menuItems.length === 0 ? (
                              <div className="text-center py-12 border-2 border-dashed rounded-lg">
                                <Layers className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <p className="text-muted-foreground mb-4">No menu items yet. Add your first link to get started.</p>
                                <Button onClick={() => addMenuItem(null)}>
                                  <Plus className="mr-2 h-4 w-4" /> Add First Link
                                </Button>
                              </div>
                            ) : (
                              <DragDropContext onDragEnd={handleDragEnd}>
                                <Droppable droppableId="menu-items">
                                  {(provided, snapshot) => (
                                    <div
                                      {...provided.droppableProps}
                                      ref={provided.innerRef}
                                      className={`space-y-3 ${snapshot.isDraggingOver ? 'bg-primary/5 rounded-lg p-2' : ''}`}
                                    >
                                      {menuItems.map((item, index) => {
                                        const nestingLevel = getNestingLevel(item);
                                        const parentOptions = getParentOptions(item, index);

                                        return (
                                          <Draggable
                                            key={`item-${index}`}
                                            draggableId={`item-${index}`}
                                            index={index}
                                          >
                                            {(provided, snapshot) => (
                                              <div
                                                ref={provided.innerRef}
                                                {...provided.draggableProps}
                                                className={`flex gap-4 items-end border p-4 rounded-lg transition-all ${
                                                  snapshot.isDragging
                                                    ? 'bg-primary/10 shadow-lg scale-105 border-primary'
                                                    : 'bg-muted/20 hover:bg-muted/30'
                                                }`}
                                                style={{
                                                  ...provided.draggableProps.style,
                                                  marginLeft: snapshot.isDragging ? 0 : `${nestingLevel * 32}px`,
                                                  borderLeft: nestingLevel > 0 && !snapshot.isDragging ? '3px solid hsl(var(--primary))' : undefined
                                                }}
                                              >
                                                  <div
                                                    {...provided.dragHandleProps}
                                                    className="flex items-center gap-2 self-center cursor-grab active:cursor-grabbing"
                                                  >
                                                    <GripVertical className={`h-5 w-5 ${snapshot.isDragging ? 'text-primary' : 'text-muted-foreground'}`} />
                                                    {nestingLevel > 0 && !snapshot.isDragging && (
                                                      <ChevronRight className="h-4 w-4 text-primary" />
                                                    )}
                                                  </div>

                                                  <div className="flex-1 space-y-2">
                                                      <Label className="flex items-center gap-2">
                                                        Label
                                                        {nestingLevel > 0 && (
                                                          <span className="text-xs text-primary font-medium">
                                                            (Level {nestingLevel + 1} - Sub-item)
                                                          </span>
                                                        )}
                                                      </Label>
                                                      <Input
                                                        value={item.label}
                                                        onChange={(e) => updateItem(index, 'label', e.target.value)}
                                                        placeholder="e.g., About Us"
                                                      />
                                                  </div>

                                                  <div className="flex-1 space-y-2">
                                                      <Label>URL</Label>
                                                      <Input
                                                        value={item.url}
                                                        onChange={(e) => updateItem(index, 'url', e.target.value)}
                                                        placeholder="/about or https://example.com"
                                                      />
                                                  </div>

                                                  {nestingLevel < 2 && (
                                                    <div className="w-64 space-y-2">
                                                        <Label>Parent Menu Item (Optional)</Label>
                                                        <Select
                                                          value={item.parent_id?.toString() || 'none'}
                                                          onValueChange={(val) => {
                                                            const newParentId = val === 'none' ? null : parseInt(val);
                                                            updateItem(index, 'parent_id', newParentId);
                                                          }}
                                                        >
                                                          <SelectTrigger>
                                                            <SelectValue placeholder="No parent (top-level)" />
                                                          </SelectTrigger>
                                                          <SelectContent>
                                                            <SelectItem value="none">
                                                              <span className="font-medium">No parent (top-level)</span>
                                                            </SelectItem>
                                                            {parentOptions.map((parentItem, parentIdx) => {
                                                              const actualIndex = menuItems.indexOf(parentItem);
                                                              return (
                                                                <SelectItem key={parentIdx} value={parentItem.id?.toString() || `temp-${actualIndex}`}>
                                                                  {parentItem.label}
                                                                </SelectItem>
                                                              );
                                                            })}
                                                          </SelectContent>
                                                        </Select>
                                                    </div>
                                                  )}

                                                  {nestingLevel === 2 && (
                                                    <div className="w-64 flex items-end">
                                                      <p className="text-xs text-muted-foreground">
                                                        Max nesting level reached
                                                      </p>
                                                    </div>
                                                  )}

                                                  <Button variant="destructive" size="icon" onClick={() => removeItem(index)}>
                                                      <Trash2 className="h-4 w-4" />
                                                  </Button>
                                              </div>
                                            )}
                                          </Draggable>
                                        );
                                      })}
                                      {provided.placeholder}
                                    </div>
                                  )}
                                </Droppable>
                              </DragDropContext>
                            )}

                            <div className="flex gap-2 pt-4">
                              <Button variant="outline" onClick={() => addMenuItem(null)} className="flex-1">
                                  <Plus className="mr-2 h-4 w-4" /> Add Top-Level Link
                              </Button>
                              {menuItems.some(item => item.parent_id === null) && (
                                <Button
                                  variant="outline"
                                  onClick={() => {
                                    const topLevelItems = menuItems.filter(item => item.parent_id === null);
                                    if (topLevelItems.length > 0) {
                                      // Add as child of first top-level item for demo
                                      addMenuItem(topLevelItems[0].id || null);
                                    }
                                  }}
                                  className="flex-1"
                                >
                                  <ChevronRight className="mr-2 h-4 w-4" /> Add Sub-Item (select parent above)
                                </Button>
                              )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Help Card */}
                    <Card>
                      <CardHeader>
                        <CardTitle className="text-lg">How to Create & Manage Nested Menus</CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-2 text-sm text-muted-foreground">
                        <div className="flex items-start gap-2">
                          <div className="mt-1 h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span className="text-xs font-bold text-primary">1</span>
                          </div>
                          <p>Click <strong>"Add Top-Level Link"</strong> to create main menu items (shown at the root level)</p>
                        </div>
                        <div className="flex items-start gap-2">
                          <div className="mt-1 h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span className="text-xs font-bold text-primary">2</span>
                          </div>
                          <p>Use the <strong>"Parent Menu Item"</strong> dropdown to make an item a child of another item</p>
                        </div>
                        <div className="flex items-start gap-2">
                          <div className="mt-1 h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span className="text-xs font-bold text-primary">3</span>
                          </div>
                          <p>You can nest up to <strong>3 levels deep</strong> (parent → child → grandchild)</p>
                        </div>
                        <div className="flex items-start gap-2">
                          <div className="mt-1 h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span className="text-xs font-bold text-primary">4</span>
                          </div>
                          <p><strong>Drag and drop</strong> items using the <GripVertical className="inline h-3 w-3" /> handle to reorder them</p>
                        </div>
                        <div className="flex items-start gap-2">
                          <div className="mt-1 h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span className="text-xs font-bold text-primary">5</span>
                          </div>
                          <p>Nested items are shown with <strong>indentation</strong> and a <strong>blue border</strong> on the left</p>
                        </div>
                        <div className="flex items-start gap-2">
                          <div className="mt-1 h-5 w-5 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span className="text-xs font-bold text-primary">6</span>
                          </div>
                          <p>Click <strong>"Save Changes"</strong> when you're done to update the menu on your site</p>
                        </div>
                      </CardContent>
                    </Card>
                </TabsContent>
            ))}
        </Tabs>
    </div>
  );
}
