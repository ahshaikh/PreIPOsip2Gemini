'use client';

import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import { 
  BarChart3, ThumbsUp, MessageSquare, Eye, 
  Plus, Edit, Trash2, FolderOpen, FileText 
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { toast } from 'sonner';
import Link from 'next/link';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger 
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export default function HelpCenterAdminDashboard() {
  const queryClient = useQueryClient();
  
  // --- STATE ---
  const [isCategoryModalOpen, setIsCategoryModalOpen] = useState(false);
  const [newCategoryName, setNewCategoryName] = useState('');
  // ADDED: State for the new icon
  const [newCategoryIcon, setNewCategoryIcon] = useState('');

  // --- QUERIES ---
  const { data: stats } = useQuery({
    queryKey: ['help-center-stats'],
    queryFn: async () => (await api.get('/admin/help-center-analytics/stats')).data
  });

  const { data: articles } = useQuery({
    queryKey: ['kb-articles'],
    queryFn: async () => (await api.get('/admin/kb-articles')).data
  });

  const { data: categories } = useQuery({
    queryKey: ['kb-categories'],
    queryFn: async () => (await api.get('/admin/kb-categories')).data
  });

  // --- MUTATIONS ---
  const deleteArticleMutation = useMutation({
    mutationFn: async (id: number) => await api.delete(`/admin/kb-articles/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kb-articles'] });
      toast.success('Article deleted');
    },
    onError: () => toast.error('Failed to delete article')
  });

  // UPDATED: Mutation now accepts an object { name, icon }
  const createCategoryMutation = useMutation({
    mutationFn: async (data: { name: string, icon: string }) => 
      await api.post('/admin/kb-categories', { 
        name: data.name, 
        icon: data.icon, 
        display_order: 0 
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kb-categories'] });
      toast.success('Category created');
      
      // Reset Form and Close
      setIsCategoryModalOpen(false);
      setNewCategoryName('');
      setNewCategoryIcon(''); // Reset icon
    },
    onError: () => toast.error('Failed to create category')
  });

  const deleteCategoryMutation = useMutation({
    mutationFn: async (id: number) => await api.delete(`/admin/kb-categories/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['kb-categories'] });
      toast.success('Category deleted');
    },
    onError: (err: any) => toast.error(err.response?.data?.message || 'Failed to delete category')
  });

  // Helper to handle the submit action
  const handleCreateCategory = () => {
    createCategoryMutation.mutate({ 
      name: newCategoryName, 
      icon: newCategoryIcon 
    });
  };

  return (
    <div className="space-y-8 p-8">
      
      {/* HEADER & ACTIONS */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold text-slate-900 dark:text-white">Help Center Manager</h1>
          <p className="text-slate-500">Manage articles, headings, and view analytics.</p>
        </div>
        <div className="flex gap-2">
          
          {/* Add Category Dialog */}
          <Dialog open={isCategoryModalOpen} onOpenChange={setIsCategoryModalOpen}>
            <DialogTrigger asChild>
              <Button variant="outline"><FolderOpen className="w-4 h-4 mr-2" /> New Heading</Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Add New Heading (Category)</DialogTitle>
              </DialogHeader>
              
              <div className="space-y-4 py-4">
                {/* Category Name Input */}
                <div className="space-y-2">
                  <Label>Name</Label>
                  <Input 
                    value={newCategoryName} 
                    onChange={(e) => setNewCategoryName(e.target.value)} 
                    placeholder="e.g., Account Management"
                  />
                </div>

                {/* ADDED: Icon Selection Block */}
                <div className="space-y-2">
                    <Label className="block text-gray-700 mb-2">Category Icon</Label>
                    <select 
                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        value={newCategoryIcon} 
                        onChange={(e) => setNewCategoryIcon(e.target.value)}
                    >
                        <option value="">Select an Icon</option>
                        <option value="fas fa-home">Home 🏠</option>
                        <option value="fas fa-book">Book 📖</option>
                        <option value="fas fa-cog">Settings ⚙️</option>
                        <option value="fas fa-user">User 👤</option>
                        <option value="fas fa-question-circle">Question ❓</option>
                        <option value="fas fa-chart-line">Analytics 📈</option>
                        <option value="fas fa-lock">Security 🔒</option>
                    </select>
                </div>

                {/* Submit Button */}
                <Button 
                  onClick={handleCreateCategory} 
                  disabled={!newCategoryName || !newCategoryIcon} 
                >
                  Create Heading
                </Button>
              </div>
            </DialogContent>
          </Dialog>

          {/* Add Article Button */}
          <Link href="/admin/help-center/articles/create">
            <Button className="bg-blue-600 hover:bg-blue-700">
              <Plus className="w-4 h-4 mr-2" /> New Article
            </Button>
          </Link>
        </div>
      </div>

      {/* STATS CARDS */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <StatsCard title="Total Views" value={stats?.total_views || 0} icon={Eye} color="blue" />
        <StatsCard title="Satisfaction" value={`${stats?.satisfaction_rate || 0}%`} icon={ThumbsUp} color={stats?.satisfaction_rate > 80 ? 'green' : 'yellow'} />
        <StatsCard title="Total Votes" value={stats?.total_feedback || 0} icon={MessageSquare} color="purple" />
        <StatsCard title="Views Today" value={stats?.views_today || 0} icon={BarChart3} color="orange" />
      </div>

      {/* CONTENT TABS */}
      <Tabs defaultValue="articles" className="w-full">
        <TabsList>
          <TabsTrigger value="articles">Articles</TabsTrigger>
          <TabsTrigger value="categories">Headings (Categories)</TabsTrigger>
        </TabsList>

        {/* ARTICLES TAB */}
        <TabsContent value="articles" className="mt-6">
          <Card>
            <CardHeader><CardTitle>All Articles</CardTitle></CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Views</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {articles?.data?.map((article: any) => (
                    <TableRow key={article.id}>
                      <TableCell className="font-medium">{article.title}</TableCell>
                      <TableCell>{article.category?.name || 'Uncategorized'}</TableCell>
                      <TableCell>
                        <Badge variant={article.status === 'published' ? 'default' : 'secondary'}>
                          {article.status}
                        </Badge>
                      </TableCell>
                      <TableCell>{article.views}</TableCell>
                      <TableCell className="text-right space-x-2">
                        <Link href={`/admin/help-center/articles/${article.id}`}>
                          <Button variant="ghost" size="sm"><Edit className="w-4 h-4 text-blue-500" /></Button>
                        </Link>
                        <Button 
                          variant="ghost" size="sm" 
                          onClick={() => {
                            if(confirm('Are you sure?')) deleteArticleMutation.mutate(article.id)
                          }}
                        >
                          <Trash2 className="w-4 h-4 text-red-500" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                  {articles?.data?.length === 0 && (
                    <TableRow><TableCell colSpan={5} className="text-center py-8 text-muted-foreground">No articles found</TableCell></TableRow>
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* CATEGORIES TAB */}
        <TabsContent value="categories" className="mt-6">
          <Card>
            <CardHeader><CardTitle>Manage Headings</CardTitle></CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Icon</TableHead>
                    <TableHead>Slug</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {categories?.map((cat: any) => (
                    <TableRow key={cat.id}>
                      <TableCell className="font-medium flex items-center gap-2">
                        {/* Display the icon if it exists, otherwise generic folder */}
                        {cat.icon ? <i className={`${cat.icon} text-slate-400`} /> : <FolderOpen className="w-4 h-4 text-slate-400" />}
                        {cat.name}
                      </TableCell>
                      <TableCell className="text-xs text-gray-500">{cat.icon || 'No Icon'}</TableCell>
                      <TableCell>{cat.slug}</TableCell>
                      <TableCell className="text-right">
                        <Button 
                          variant="ghost" size="sm" 
                          onClick={() => {
                            if(confirm('Delete heading? This will fail if it has articles.')) deleteCategoryMutation.mutate(cat.id)
                          }}
                        >
                          <Trash2 className="w-4 h-4 text-red-500" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

// Stats Card Helper
function StatsCard({ title, value, icon: Icon, color }: any) {
  const colors: any = {
    blue: "text-blue-600 bg-blue-50 dark:bg-blue-900/20",
    green: "text-green-600 bg-green-50 dark:bg-green-900/20",
    red: "text-red-600 bg-red-50 dark:bg-red-900/20",
    orange: "text-orange-600 bg-orange-50 dark:bg-orange-900/20",
    purple: "text-purple-600 bg-purple-50 dark:bg-purple-900/20",
    yellow: "text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20",
  };

  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between mb-4">
          <span className="text-sm font-medium text-slate-500">{title}</span>
          <div className={`p-2 rounded-lg ${colors[color] || colors.blue}`}>
            <Icon className="w-5 h-5" />
          </div>
        </div>
        <div className="flex items-baseline gap-2">
          <h3 className="text-2xl font-bold text-slate-900 dark:text-white">{value}</h3>
        </div>
      </CardContent>
    </Card>
  );
}