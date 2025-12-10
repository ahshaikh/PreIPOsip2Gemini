// V-CMS-ENHANCEMENT-014 | Page Builder with Block Library
// Created: 2025-12-10 | Supports 5 essential block types: hero, cta, features, richtext, accordion

'use client';

import { useState } from 'react';
import { useParams } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { DragDropContext, Droppable, Draggable, DropResult } from '@hello-pangea/dnd';
import {
  Plus, Save, Eye, Layout, Megaphone, Grid, FileText, ChevronDown,
  GripVertical, Trash2, Copy, Settings, ArrowLeft
} from 'lucide-react';
import Link from 'next/link';
import api from '@/lib/api';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';

// Block type definitions
const BLOCK_TYPES = [
  { type: 'hero', label: 'Hero Section', icon: Layout, description: 'Full-width banner with CTA' },
  { type: 'cta', label: 'Call-to-Action', icon: Megaphone, description: 'Prominent CTA box' },
  { type: 'features', label: 'Features Grid', icon: Grid, description: '3-column feature grid' },
  { type: 'richtext', label: 'Rich Text', icon: FileText, description: 'Text content block' },
  { type: 'accordion', label: 'Accordion/FAQ', icon: ChevronDown, description: 'Expandable sections' },
];

interface Block {
  id?: number;
  type: string;
  name?: string;
  config: any;
  display_order: number;
  is_active: boolean;
}

export default function PageBuilderPage() {
  const params = useParams();
  const pageId = params.pageId as string;
  const queryClient = useQueryClient();

  const [showBlockPicker, setShowBlockPicker] = useState(false);
  const [editingBlock, setEditingBlock] = useState<Block | null>(null);
  const [blockConfig, setBlockConfig] = useState<any>({});

  // Fetch page and blocks
  const { data: pageData, isLoading } = useQuery({
    queryKey: ['page', pageId, 'blocks'],
    queryFn: async () => (await api.get(`/admin/pages/${pageId}/blocks`)).data,
  });

  // Create block mutation
  const createBlockMutation = useMutation({
    mutationFn: (data: any) => api.post(`/admin/pages/${pageId}/blocks`, data),
    onSuccess: () => {
      toast.success('Block added successfully');
      queryClient.invalidateQueries({ queryKey: ['page', pageId, 'blocks'] });
      setShowBlockPicker(false);
      setEditingBlock(null);
      setBlockConfig({});
    },
  });

  // Update block mutation
  const updateBlockMutation = useMutation({
    mutationFn: ({ id, ...data }: any) => api.put(`/admin/page-blocks/${id}`, data),
    onSuccess: () => {
      toast.success('Block updated successfully');
      queryClient.invalidateQueries({ queryKey: ['page', pageId, 'blocks'] });
      setEditingBlock(null);
      setBlockConfig({});
    },
  });

  // Delete block mutation
  const deleteBlockMutation = useMutation({
    mutationFn: (blockId: number) => api.delete(`/admin/page-blocks/${blockId}`),
    onSuccess: () => {
      toast.success('Block deleted successfully');
      queryClient.invalidateQueries({ queryKey: ['page', pageId, 'blocks'] });
    },
  });

  // Duplicate block mutation
  const duplicateBlockMutation = useMutation({
    mutationFn: (blockId: number) => api.post(`/admin/page-blocks/${blockId}/duplicate`),
    onSuccess: () => {
      toast.success('Block duplicated successfully');
      queryClient.invalidateQueries({ queryKey: ['page', pageId, 'blocks'] });
    },
  });

  // Reorder blocks mutation
  const reorderMutation = useMutation({
    mutationFn: (blocks: any[]) => api.post(`/admin/pages/${pageId}/blocks/reorder`, { blocks }),
    onSuccess: () => {
      toast.success('Blocks reordered');
      queryClient.invalidateQueries({ queryKey: ['page', pageId, 'blocks'] });
    },
  });

  const handleDragEnd = (result: DropResult) => {
    if (!result.destination || !pageData?.blocks) return;

    const blocks = Array.from(pageData.blocks);
    const [removed] = blocks.splice(result.source.index, 1);
    blocks.splice(result.destination.index, 0, removed);

    const reordered = blocks.map((block: any, index) => ({
      id: block.id,
      display_order: index,
    }));

    reorderMutation.mutate(reordered);
  };

  const handleAddBlock = (type: string) => {
    setEditingBlock({ type, config: {}, display_order: pageData?.blocks?.length || 0, is_active: true });
    setBlockConfig(getDefaultConfig(type));
    setShowBlockPicker(false);
  };

  const handleSaveBlock = () => {
    if (!editingBlock) return;

    const blockData = {
      ...editingBlock,
      config: blockConfig,
    };

    if (editingBlock.id) {
      updateBlockMutation.mutate(blockData);
    } else {
      createBlockMutation.mutate(blockData);
    }
  };

  const getDefaultConfig = (type: string) => {
    switch (type) {
      case 'hero':
        return { heading: 'Welcome to Our Platform', subheading: 'Start your journey today', cta_text: 'Get Started', cta_url: '/signup' };
      case 'cta':
        return { heading: 'Ready to get started?', text: 'Join thousands of satisfied users', button_text: 'Sign Up Now', button_url: '/signup' };
      case 'features':
        return { heading: 'Our Features', items: [
          { title: 'Fast', description: 'Lightning fast performance', icon: 'Zap' },
          { title: 'Secure', description: 'Enterprise-grade security', icon: 'Shield' },
          { title: 'Scalable', description: 'Grows with your business', icon: 'TrendingUp' },
        ]};
      case 'richtext':
        return { content: '<p>Add your content here...</p>' };
      case 'accordion':
        return { heading: 'Frequently Asked Questions', items: [
          { question: 'How does it work?', answer: 'It works by...' },
          { question: 'Is it secure?', answer: 'Yes, we use...' },
        ]};
      default:
        return {};
    }
  };

  if (isLoading) {
    return <div className="p-8">Loading page builder...</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link href="/admin/settings/cms">
              <Button variant="ghost" size="sm">
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back to Pages
              </Button>
            </Link>
            <div>
              <h1 className="text-2xl font-bold">{pageData?.page?.title || 'Page Builder'}</h1>
              <p className="text-sm text-muted-foreground">
                {pageData?.blocks?.length || 0} blocks
              </p>
            </div>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => window.open(`/page-preview/${pageId}`, '_blank')}>
              <Eye className="h-4 w-4 mr-2" />
              Preview
            </Button>
            <Button onClick={() => setShowBlockPicker(true)}>
              <Plus className="h-4 w-4 mr-2" />
              Add Block
            </Button>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto p-8">
        {(!pageData?.blocks || pageData.blocks.length === 0) ? (
          <Card className="text-center py-12">
            <CardContent>
              <Layout className="h-16 w-16 mx-auto text-muted-foreground mb-4" />
              <h2 className="text-xl font-semibold mb-2">No blocks yet</h2>
              <p className="text-muted-foreground mb-4">
                Start building your page by adding content blocks
              </p>
              <Button onClick={() => setShowBlockPicker(true)}>
                <Plus className="h-4 w-4 mr-2" />
                Add Your First Block
              </Button>
            </CardContent>
          </Card>
        ) : (
          <DragDropContext onDragEnd={handleDragEnd}>
            <Droppable droppableId="blocks">
              {(provided) => (
                <div {...provided.droppableProps} ref={provided.innerRef} className="space-y-4">
                  {pageData.blocks.map((block: any, index: number) => (
                    <Draggable key={block.id} draggableId={String(block.id)} index={index}>
                      {(provided, snapshot) => (
                        <Card
                          ref={provided.innerRef}
                          {...provided.draggableProps}
                          className={snapshot.isDragging ? 'shadow-lg' : ''}
                        >
                          <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center gap-3">
                                <div {...provided.dragHandleProps} className="cursor-grab active:cursor-grabbing">
                                  <GripVertical className="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div>
                                  <div className="flex items-center gap-2">
                                    <CardTitle className="text-base">
                                      {BLOCK_TYPES.find(t => t.type === block.type)?.label || block.type}
                                    </CardTitle>
                                    <Badge variant={block.is_active ? 'default' : 'secondary'}>
                                      {block.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                  </div>
                                  <p className="text-sm text-muted-foreground">
                                    {block.name || 'No name'}
                                  </p>
                                </div>
                              </div>
                              <div className="flex gap-2">
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() => {
                                    setEditingBlock(block);
                                    setBlockConfig(block.config || {});
                                  }}
                                >
                                  <Settings className="h-4 w-4" />
                                </Button>
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() => duplicateBlockMutation.mutate(block.id)}
                                >
                                  <Copy className="h-4 w-4" />
                                </Button>
                                <Button
                                  variant="destructive"
                                  size="sm"
                                  onClick={() => {
                                    if (confirm('Delete this block?')) {
                                      deleteBlockMutation.mutate(block.id);
                                    }
                                  }}
                                >
                                  <Trash2 className="h-4 w-4" />
                                </Button>
                              </div>
                            </div>
                          </CardHeader>
                        </Card>
                      )}
                    </Draggable>
                  ))}
                  {provided.placeholder}
                </div>
              )}
            </Droppable>
          </DragDropContext>
        )}
      </div>

      {/* Block Picker Dialog */}
      <Dialog open={showBlockPicker} onOpenChange={setShowBlockPicker}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Add a Block</DialogTitle>
          </DialogHeader>
          <div className="grid grid-cols-2 gap-4">
            {BLOCK_TYPES.map((blockType) => {
              const Icon = blockType.icon;
              return (
                <Card
                  key={blockType.type}
                  className="cursor-pointer hover:border-primary transition-colors"
                  onClick={() => handleAddBlock(blockType.type)}
                >
                  <CardHeader>
                    <Icon className="h-8 w-8 mb-2 text-primary" />
                    <CardTitle className="text-base">{blockType.label}</CardTitle>
                    <p className="text-sm text-muted-foreground">{blockType.description}</p>
                  </CardHeader>
                </Card>
              );
            })}
          </div>
        </DialogContent>
      </Dialog>

      {/* Block Editor Dialog */}
      <Dialog open={!!editingBlock} onOpenChange={(open) => !open && setEditingBlock(null)}>
        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {editingBlock?.id ? 'Edit Block' : 'Add Block'}: {BLOCK_TYPES.find(t => t.type === editingBlock?.type)?.label}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label>Block Name (optional)</Label>
              <Input
                value={editingBlock?.name || ''}
                onChange={(e) => setEditingBlock(prev => prev ? {...prev, name: e.target.value} : null)}
                placeholder="e.g., Homepage Hero"
              />
            </div>

            <div className="flex items-center justify-between">
              <Label>Active</Label>
              <Switch
                checked={editingBlock?.is_active}
                onCheckedChange={(checked) => setEditingBlock(prev => prev ? {...prev, is_active: checked} : null)}
              />
            </div>

            {/* Block-specific configuration */}
            {editingBlock?.type === 'hero' && (
              <>
                <div>
                  <Label>Heading</Label>
                  <Input
                    value={blockConfig.heading || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, heading: e.target.value})}
                  />
                </div>
                <div>
                  <Label>Subheading</Label>
                  <Input
                    value={blockConfig.subheading || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, subheading: e.target.value})}
                  />
                </div>
                <div>
                  <Label>CTA Button Text</Label>
                  <Input
                    value={blockConfig.cta_text || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, cta_text: e.target.value})}
                  />
                </div>
                <div>
                  <Label>CTA Button URL</Label>
                  <Input
                    value={blockConfig.cta_url || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, cta_url: e.target.value})}
                    placeholder="/signup"
                  />
                </div>
              </>
            )}

            {editingBlock?.type === 'cta' && (
              <>
                <div>
                  <Label>Heading</Label>
                  <Input
                    value={blockConfig.heading || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, heading: e.target.value})}
                  />
                </div>
                <div>
                  <Label>Text</Label>
                  <Textarea
                    value={blockConfig.text || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, text: e.target.value})}
                  />
                </div>
                <div>
                  <Label>Button Text</Label>
                  <Input
                    value={blockConfig.button_text || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, button_text: e.target.value})}
                  />
                </div>
                <div>
                  <Label>Button URL</Label>
                  <Input
                    value={blockConfig.button_url || ''}
                    onChange={(e) => setBlockConfig({...blockConfig, button_url: e.target.value})}
                  />
                </div>
              </>
            )}

            {editingBlock?.type === 'richtext' && (
              <div>
                <Label>Content (HTML)</Label>
                <Textarea
                  value={blockConfig.content || ''}
                  onChange={(e) => setBlockConfig({...blockConfig, content: e.target.value})}
                  rows={10}
                  placeholder="<p>Your content here...</p>"
                />
              </div>
            )}

            <div className="flex justify-end gap-2 pt-4">
              <Button variant="outline" onClick={() => setEditingBlock(null)}>
                Cancel
              </Button>
              <Button onClick={handleSaveBlock}>
                <Save className="h-4 w-4 mr-2" />
                Save Block
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
