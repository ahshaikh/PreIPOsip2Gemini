'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import api from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { ArrowLeft, Save, Loader2 } from 'lucide-react';

interface ArticleFormProps {
  initialData?: any;
  isEditing?: boolean;
}

export default function ArticleForm({ initialData, isEditing = false }: ArticleFormProps) {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState<any[]>([]);
  
  const [formData, setFormData] = useState({
    title: initialData?.title || '',
    slug: initialData?.slug || '',
    summary: initialData?.summary || '',
    kb_category_id: initialData?.kb_category_id || '',
    content: initialData?.content || '',
    status: initialData?.status || 'draft',
    // Default to today's date if creating new, or use existing date (YYYY-MM-DD)
    last_updated: initialData?.last_updated
      ? new Date(initialData.last_updated).toISOString().split('T')[0]
      : new Date().toISOString().split('T')[0],
  });

  // Update form when initialData changes (for edit mode)
  useEffect(() => {
    if (initialData) {
      setFormData({
        title: initialData.title || '',
        slug: initialData.slug || '',
        summary: initialData.summary || '',
        kb_category_id: initialData.kb_category_id || '',
        content: initialData.content || '',
        status: initialData.status || 'draft',
        last_updated: initialData.last_updated
          ? new Date(initialData.last_updated).toISOString().split('T')[0]
          : new Date().toISOString().split('T')[0],
      });
    }
  }, [initialData]);

  // Fetch Categories for Dropdown
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        // Correct Admin Route
        const { data } = await api.get('/admin/kb-categories'); 
        setCategories(data);
      } catch (error) {
        console.error('Failed to load categories', error);
        toast.error('Could not load categories');
      }
    };
    fetchCategories();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      const payload = { ...formData };
      
      if (isEditing && initialData?.id) {
        await api.put(`/admin/kb-articles/${initialData.id}`, payload);
        toast.success('Article updated successfully');
      } else {
        await api.post('/admin/kb-articles', payload);
        toast.success('Article created successfully');
      }
      
      router.push('/admin/help-center');
      router.refresh();
    } catch (error: any) {
      console.error(error);
      toast.error(error.response?.data?.message || 'Failed to save article');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Top Bar */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" onClick={() => router.back()}>
          <ArrowLeft className="w-4 h-4 mr-2" /> Back
        </Button>
        <h1 className="text-2xl font-bold tracking-tight">
          {isEditing ? 'Edit Article' : 'New Article'}
        </h1>
      </div>

      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>Article Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            
            {/* Row 1: Title & Slug */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-2">
                <Label htmlFor="title">Title</Label>
                <Input
                  id="title"
                  placeholder="e.g., How to complete KYC"
                  required
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="slug">Slug (Optional)</Label>
                <Input
                  id="slug"
                  placeholder="Auto-generated if empty"
                  value={formData.slug}
                  onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                />
              </div>
            </div>

            {/* Row 2: Summary */}
            <div className="space-y-2">
                <Label htmlFor="summary">Summary (SEO Description)</Label>
                <Textarea
                  id="summary"
                  placeholder="Brief overview of the article..."
                  className="h-20"
                  value={formData.summary}
                  onChange={(e) => setFormData({ ...formData, summary: e.target.value })}
                />
            </div>

            {/* Row 3: Category, Status, Last Updated */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="space-y-2">
                <Label>Category</Label>
                <Select 
                  value={String(formData.kb_category_id)} 
                  onValueChange={(val) => setFormData({ ...formData, kb_category_id: val })}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select a Category" />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((cat) => (
                      <SelectItem key={cat.id} value={String(cat.id)}>
                        {cat.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Status</Label>
                <Select 
                  value={formData.status} 
                  onValueChange={(val) => setFormData({ ...formData, status: val })}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="draft">Draft</SelectItem>
                    <SelectItem value="published">Published</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="last_updated">Last Updated</Label>
                <Input
                  id="last_updated"
                  type="date"
                  value={formData.last_updated}
                  onChange={(e) => setFormData({ ...formData, last_updated: e.target.value })}
                />
              </div>
            </div>

            {/* Row 4: Content Editor */}
            <div className="space-y-2">
              <Label htmlFor="content">Content (HTML Supported)</Label>
              <Textarea
                id="content"
                className="min-h-[300px] font-mono text-sm"
                placeholder="<p>Write your article content here...</p>"
                required
                value={formData.content}
                onChange={(e) => setFormData({ ...formData, content: e.target.value })}
              />
              <p className="text-xs text-muted-foreground">
                You can write raw HTML or plain text.
              </p>
            </div>

            {/* Footer Buttons */}
            <div className="flex justify-end pt-4">
              <Button type="submit" disabled={loading} className="min-w-[150px]">
                {loading ? (
                  <>
                    <Loader2 className="w-4 h-4 mr-2 animate-spin" /> Saving...
                  </>
                ) : (
                  <>
                    <Save className="w-4 h-4 mr-2" /> {isEditing ? 'Update Article' : 'Create Article'}
                  </>
                )}
              </Button>
            </div>

          </CardContent>
        </Card>
      </form>
    </div>
  );
}