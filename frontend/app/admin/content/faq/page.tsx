// V-CMS-ENHANCEMENT-015 | FAQ Category & Question Manager
// Created: 2025-12-10 | Purpose: Manage FAQ categories and questions

'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Plus, Pencil, Trash2, HelpCircle, FolderOpen } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

interface FaqCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  display_order: number;
  is_active: boolean;
}

interface FaqQuestion {
  id: number;
  category_id: number;
  question: string;
  answer: string;
  display_order: number;
  is_active: boolean;
  views_count?: number;
}

export default function FaqManagerPage() {
  const queryClient = useQueryClient();
  const [categoryDialogOpen, setCategoryDialogOpen] = useState(false);
  const [questionDialogOpen, setQuestionDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<FaqCategory | null>(null);
  const [editingQuestion, setEditingQuestion] = useState<FaqQuestion | null>(null);
  const [selectedCategoryId, setSelectedCategoryId] = useState<number | null>(null);

  const [categoryForm, setCategoryForm] = useState({
    name: '',
    slug: '',
    description: '',
  });

  const [questionForm, setQuestionForm] = useState({
    category_id: 0,
    question: '',
    answer: '',
  });

  // Fetch categories
  const { data: categories = [] } = useQuery<FaqCategory[]>({
    queryKey: ['faqCategories'],
    queryFn: async () => (await api.get('/admin/faq-categories')).data.data,
  });

  // Fetch questions
  const { data: questions = [] } = useQuery<FaqQuestion[]>({
    queryKey: ['faqQuestions', selectedCategoryId],
    queryFn: async () => {
      const url = selectedCategoryId
        ? `/admin/faq-questions?category_id=${selectedCategoryId}`
        : '/admin/faq-questions';
      return (await api.get(url)).data.data;
    },
  });

  // Category mutations
  const createCategoryMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/faq-categories', data),
    onSuccess: () => {
      toast.success('Category created successfully');
      queryClient.invalidateQueries({ queryKey: ['faqCategories'] });
      setCategoryDialogOpen(false);
      resetCategoryForm();
    },
  });

  const updateCategoryMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      api.put(`/admin/faq-categories/${id}`, data),
    onSuccess: () => {
      toast.success('Category updated successfully');
      queryClient.invalidateQueries({ queryKey: ['faqCategories'] });
      setCategoryDialogOpen(false);
      resetCategoryForm();
    },
  });

  const deleteCategoryMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/faq-categories/${id}`),
    onSuccess: () => {
      toast.success('Category deleted successfully');
      queryClient.invalidateQueries({ queryKey: ['faqCategories'] });
    },
  });

  // Question mutations
  const createQuestionMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/faq-questions', data),
    onSuccess: () => {
      toast.success('Question created successfully');
      queryClient.invalidateQueries({ queryKey: ['faqQuestions'] });
      setQuestionDialogOpen(false);
      resetQuestionForm();
    },
  });

  const updateQuestionMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      api.put(`/admin/faq-questions/${id}`, data),
    onSuccess: () => {
      toast.success('Question updated successfully');
      queryClient.invalidateQueries({ queryKey: ['faqQuestions'] });
      setQuestionDialogOpen(false);
      resetQuestionForm();
    },
  });

  const deleteQuestionMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/faq-questions/${id}`),
    onSuccess: () => {
      toast.success('Question deleted successfully');
      queryClient.invalidateQueries({ queryKey: ['faqQuestions'] });
    },
  });

  const resetCategoryForm = () => {
    setCategoryForm({ name: '', slug: '', description: '' });
    setEditingCategory(null);
  };

  const resetQuestionForm = () => {
    setQuestionForm({ category_id: 0, question: '', answer: '' });
    setEditingQuestion(null);
  };

  const handleCategorySubmit = () => {
    if (editingCategory) {
      updateCategoryMutation.mutate({ id: editingCategory.id, data: categoryForm });
    } else {
      createCategoryMutation.mutate(categoryForm);
    }
  };

  const handleQuestionSubmit = () => {
    if (editingQuestion) {
      updateQuestionMutation.mutate({ id: editingQuestion.id, data: questionForm });
    } else {
      createQuestionMutation.mutate(questionForm);
    }
  };

  const openEditCategory = (category: FaqCategory) => {
    setEditingCategory(category);
    setCategoryForm({
      name: category.name,
      slug: category.slug,
      description: category.description || '',
    });
    setCategoryDialogOpen(true);
  };

  const openEditQuestion = (question: FaqQuestion) => {
    setEditingQuestion(question);
    setQuestionForm({
      category_id: question.category_id,
      question: question.question,
      answer: question.answer,
    });
    setQuestionDialogOpen(true);
  };

  const openNewCategory = () => {
    resetCategoryForm();
    setCategoryDialogOpen(true);
  };

  const openNewQuestion = () => {
    resetQuestionForm();
    setQuestionDialogOpen(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">FAQ Manager</h1>
          <p className="text-muted-foreground mt-1">Manage FAQ categories and questions</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Categories Section */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="flex items-center">
                  <FolderOpen className="mr-2 h-5 w-5" />
                  Categories
                </CardTitle>
                <CardDescription>Organize your FAQs into categories</CardDescription>
              </div>
              <Dialog open={categoryDialogOpen} onOpenChange={setCategoryDialogOpen}>
                <DialogTrigger asChild>
                  <Button size="sm" onClick={openNewCategory}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Category
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>
                      {editingCategory ? 'Edit Category' : 'New Category'}
                    </DialogTitle>
                    <DialogDescription>
                      Create or edit an FAQ category
                    </DialogDescription>
                  </DialogHeader>
                  <div className="space-y-4">
                    <div className="space-y-2">
                      <Label>Category Name</Label>
                      <Input
                        value={categoryForm.name}
                        onChange={(e) => {
                          const name = e.target.value;
                          setCategoryForm({
                            ...categoryForm,
                            name,
                            slug: name.toLowerCase().replace(/\s+/g, '-'),
                          });
                        }}
                        placeholder="General Questions"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Slug</Label>
                      <Input
                        value={categoryForm.slug}
                        onChange={(e) =>
                          setCategoryForm({ ...categoryForm, slug: e.target.value })
                        }
                        placeholder="general-questions"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Description (Optional)</Label>
                      <Textarea
                        value={categoryForm.description}
                        onChange={(e) =>
                          setCategoryForm({ ...categoryForm, description: e.target.value })
                        }
                        rows={3}
                        placeholder="Brief description of this category"
                      />
                    </div>
                  </div>
                  <DialogFooter>
                    <Button variant="outline" onClick={() => setCategoryDialogOpen(false)}>
                      Cancel
                    </Button>
                    <Button
                      onClick={handleCategorySubmit}
                      disabled={
                        createCategoryMutation.isPending || updateCategoryMutation.isPending
                      }
                    >
                      {editingCategory ? 'Update' : 'Create'}
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {categories.map((category) => (
                  <TableRow key={category.id}>
                    <TableCell>
                      <div>
                        <div className="font-medium">{category.name}</div>
                        <div className="text-xs text-muted-foreground">{category.slug}</div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant={category.is_active ? 'default' : 'secondary'}>
                        {category.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openEditCategory(category)}
                        >
                          <Pencil className="h-3 w-3" />
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => deleteCategoryMutation.mutate(category.id)}
                        >
                          <Trash2 className="h-3 w-3" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        {/* Questions Section */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="flex items-center">
                  <HelpCircle className="mr-2 h-5 w-5" />
                  Questions
                </CardTitle>
                <CardDescription>Manage FAQ questions and answers</CardDescription>
              </div>
              <Dialog open={questionDialogOpen} onOpenChange={setQuestionDialogOpen}>
                <DialogTrigger asChild>
                  <Button size="sm" onClick={openNewQuestion}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Question
                  </Button>
                </DialogTrigger>
                <DialogContent className="max-w-2xl">
                  <DialogHeader>
                    <DialogTitle>
                      {editingQuestion ? 'Edit Question' : 'New Question'}
                    </DialogTitle>
                    <DialogDescription>
                      Create or edit an FAQ question
                    </DialogDescription>
                  </DialogHeader>
                  <div className="space-y-4">
                    <div className="space-y-2">
                      <Label>Category</Label>
                      <Select
                        value={questionForm.category_id.toString()}
                        onValueChange={(value) =>
                          setQuestionForm({ ...questionForm, category_id: parseInt(value) })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select a category" />
                        </SelectTrigger>
                        <SelectContent>
                          {categories.map((cat) => (
                            <SelectItem key={cat.id} value={cat.id.toString()}>
                              {cat.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Question</Label>
                      <Input
                        value={questionForm.question}
                        onChange={(e) =>
                          setQuestionForm({ ...questionForm, question: e.target.value })
                        }
                        placeholder="What is your question?"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Answer</Label>
                      <Textarea
                        value={questionForm.answer}
                        onChange={(e) =>
                          setQuestionForm({ ...questionForm, answer: e.target.value })
                        }
                        rows={6}
                        placeholder="Detailed answer to the question"
                      />
                    </div>
                  </div>
                  <DialogFooter>
                    <Button variant="outline" onClick={() => setQuestionDialogOpen(false)}>
                      Cancel
                    </Button>
                    <Button
                      onClick={handleQuestionSubmit}
                      disabled={
                        createQuestionMutation.isPending || updateQuestionMutation.isPending
                      }
                    >
                      {editingQuestion ? 'Update' : 'Create'}
                    </Button>
                  </DialogFooter>
                </DialogContent>
              </Dialog>
            </div>
          </CardHeader>
          <CardContent>
            <div className="mb-4">
              <Select
                value={selectedCategoryId?.toString() || 'all'}
                onValueChange={(value) =>
                  setSelectedCategoryId(value === 'all' ? null : parseInt(value))
                }
              >
                <SelectTrigger>
                  <SelectValue placeholder="Filter by category" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Categories</SelectItem>
                  {categories.map((cat) => (
                    <SelectItem key={cat.id} value={cat.id.toString()}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-3">
              {questions.map((question) => (
                <Card key={question.id}>
                  <CardContent className="p-4">
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <h4 className="font-medium mb-1">{question.question}</h4>
                        <p className="text-sm text-muted-foreground line-clamp-2">
                          {question.answer}
                        </p>
                        {question.views_count !== undefined && (
                          <p className="text-xs text-muted-foreground mt-2">
                            {question.views_count} views
                          </p>
                        )}
                      </div>
                      <div className="flex gap-2 ml-4">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => openEditQuestion(question)}
                        >
                          <Pencil className="h-3 w-3" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => deleteQuestionMutation.mutate(question.id)}
                        >
                          <Trash2 className="h-3 w-3" />
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
