'use client';

import { useState } from "react";
import companyApi from "@/lib/companyApi";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import {
  MessageSquare,
  Calendar,
  Star,
  Eye,
  EyeOff,
  CheckCircle2,
  Clock,
  Archive,
  ThumbsUp,
  ChevronLeft,
  ChevronRight
} from "lucide-react";
import { toast } from "sonner";

interface QnA {
  id: number;
  question: string;
  answer: string | null;
  answered_at: string | null;
  is_public: boolean;
  is_featured: boolean;
  helpful_count: number;
  status: 'pending' | 'answered' | 'archived';
  created_at: string;
}

interface Statistics {
  total: number;
  pending: number;
  answered: number;
  public_count: number;
  featured_count: number;
}

export default function CompanyQnaPage() {
  const [currentPage, setCurrentPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedQna, setSelectedQna] = useState<QnA | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [answerText, setAnswerText] = useState('');
  const [isPublic, setIsPublic] = useState(false);

  const queryClient = useQueryClient();

  // Fetch statistics
  const { data: statsData } = useQuery({
    queryKey: ['qnaStats'],
    queryFn: async () => {
      const { data } = await companyApi.get('/qna/statistics');
      return data;
    },
  });

  const stats: Statistics = statsData?.stats || {
    total: 0,
    pending: 0,
    answered: 0,
    public_count: 0,
    featured_count: 0,
  };

  // Fetch Q&As
  const { data: response, isLoading } = useQuery({
    queryKey: ['companyQna', currentPage, statusFilter],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        ...(statusFilter && { status: statusFilter }),
      });
      const { data } = await companyApi.get(`/qna?${params}`);
      return data;
    },
    keepPreviousData: true,
  });

  const qnas: QnA[] = response?.data || [];
  const pagination = response?.pagination || {
    total: 0,
    per_page: 20,
    current_page: 1,
    last_page: 1,
  };

  // Answer Q&A mutation
  const answerMutation = useMutation({
    mutationFn: async ({ id, answer, isPublic }: { id: number; answer: string; isPublic: boolean }) => {
      const { data } = await companyApi.post(`/qna/${id}/answer`, {
        answer,
        is_public: isPublic,
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyQna']);
      queryClient.invalidateQueries(['qnaStats']);
      toast.success('Answer posted successfully');
      setDialogOpen(false);
      setSelectedQna(null);
      setAnswerText('');
      setIsPublic(false);
    },
    onError: () => {
      toast.error('Failed to post answer');
    },
  });

  // Update Q&A mutation
  const updateMutation = useMutation({
    mutationFn: async ({ id, updates }: { id: number; updates: any }) => {
      const { data } = await companyApi.put(`/qna/${id}`, updates);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyQna']);
      queryClient.invalidateQueries(['qnaStats']);
      toast.success('Q&A updated successfully');
    },
    onError: () => {
      toast.error('Failed to update Q&A');
    },
  });

  // Delete Q&A mutation
  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      const { data } = await companyApi.delete(`/qna/${id}`);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyQna']);
      queryClient.invalidateQueries(['qnaStats']);
      toast.success('Q&A deleted successfully');
    },
    onError: () => {
      toast.error('Failed to delete Q&A');
    },
  });

  const handleAnswer = (qna: QnA) => {
    setSelectedQna(qna);
    setAnswerText(qna.answer || '');
    setIsPublic(qna.is_public);
    setDialogOpen(true);
  };

  const handleSubmitAnswer = () => {
    if (selectedQna && answerText.trim()) {
      answerMutation.mutate({
        id: selectedQna.id,
        answer: answerText,
        isPublic: isPublic,
      });
    }
  };

  const handleTogglePublic = (qna: QnA) => {
    updateMutation.mutate({
      id: qna.id,
      updates: { is_public: !qna.is_public },
    });
  };

  const handleToggleFeatured = (qna: QnA) => {
    updateMutation.mutate({
      id: qna.id,
      updates: { is_featured: !qna.is_featured },
    });
  };

  const handleArchive = (qna: QnA) => {
    updateMutation.mutate({
      id: qna.id,
      updates: { status: 'archived' },
    });
  };

  const handleDelete = (qna: QnA) => {
    if (confirm('Are you sure you want to delete this Q&A? This action cannot be undone.')) {
      deleteMutation.mutate(qna.id);
    }
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; icon: any; text: string; color: string }> = {
      pending: { variant: 'secondary', icon: Clock, text: 'Pending', color: 'text-orange-600' },
      answered: { variant: 'default', icon: CheckCircle2, text: 'Answered', color: 'text-green-600' },
      archived: { variant: 'outline', icon: Archive, text: 'Archived', color: 'text-gray-600' },
    };
    const config = variants[status] || variants.pending;
    const Icon = config.icon;
    return (
      <Badge variant={config.variant} className="flex items-center gap-1 w-fit">
        <Icon className={`w-3 h-3 ${config.color}`} />
        {config.text}
      </Badge>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">Q&A Management</h1>
        <p className="text-muted-foreground">
          Answer investor questions and manage your company's Q&A section
        </p>
      </div>

      {/* Statistics */}
      <div className="grid gap-4 md:grid-cols-5">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Questions</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{stats.total}</div>
          </CardContent>
        </Card>
        <Card className="border-orange-200 bg-orange-50">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Pending</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-orange-600">{stats.pending}</div>
          </CardContent>
        </Card>
        <Card className="border-green-200 bg-green-50">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Answered</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{stats.answered}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Public</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{stats.public_count}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Featured</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-purple-600">{stats.featured_count}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <div className="flex gap-2">
        <Button
          variant={statusFilter === '' ? 'default' : 'outline'}
          onClick={() => { setStatusFilter(''); setCurrentPage(1); }}
        >
          All
        </Button>
        <Button
          variant={statusFilter === 'pending' ? 'default' : 'outline'}
          onClick={() => { setStatusFilter('pending'); setCurrentPage(1); }}
        >
          Pending
        </Button>
        <Button
          variant={statusFilter === 'answered' ? 'default' : 'outline'}
          onClick={() => { setStatusFilter('answered'); setCurrentPage(1); }}
        >
          Answered
        </Button>
        <Button
          variant={statusFilter === 'archived' ? 'default' : 'outline'}
          onClick={() => { setStatusFilter('archived'); setCurrentPage(1); }}
        >
          Archived
        </Button>
      </div>

      {/* Q&As List */}
      <Card>
        <CardHeader>
          <CardTitle>Questions ({pagination.total})</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-3">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="animate-pulse h-32 bg-gray-100 rounded"></div>
              ))}
            </div>
          ) : qnas.length > 0 ? (
            <>
              <div className="space-y-4">
                {qnas.map((qna) => (
                  <div
                    key={qna.id}
                    className="border rounded-lg p-5 hover:bg-gray-50 transition-colors"
                  >
                    <div className="flex items-start justify-between mb-3">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          {getStatusBadge(qna.status)}
                          {qna.is_public && (
                            <Badge variant="outline" className="flex items-center gap-1">
                              <Eye className="w-3 h-3" />
                              Public
                            </Badge>
                          )}
                          {qna.is_featured && (
                            <Badge className="bg-purple-100 text-purple-800 flex items-center gap-1">
                              <Star className="w-3 h-3" />
                              Featured
                            </Badge>
                          )}
                        </div>
                        <h4 className="font-semibold text-lg mb-2">{qna.question}</h4>
                        {qna.answer && (
                          <div className="bg-blue-50 border-l-4 border-blue-500 p-3 rounded mb-3">
                            <p className="text-sm text-gray-700 whitespace-pre-wrap">{qna.answer}</p>
                            {qna.answered_at && (
                              <p className="text-xs text-muted-foreground mt-2">
                                Answered on {formatDate(qna.answered_at)}
                              </p>
                            )}
                          </div>
                        )}
                        <div className="flex items-center gap-4 text-sm text-muted-foreground">
                          <span className="flex items-center gap-1">
                            <Calendar className="w-4 h-4" />
                            {formatDate(qna.created_at)}
                          </span>
                          <span className="flex items-center gap-1">
                            <ThumbsUp className="w-4 h-4" />
                            {qna.helpful_count} helpful
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="flex gap-2 mt-4 pt-4 border-t">
                      {qna.status === 'pending' || !qna.answer ? (
                        <Button size="sm" onClick={() => handleAnswer(qna)}>
                          <MessageSquare className="w-4 h-4 mr-2" />
                          Answer Question
                        </Button>
                      ) : (
                        <Button size="sm" variant="outline" onClick={() => handleAnswer(qna)}>
                          Edit Answer
                        </Button>
                      )}

                      {qna.status === 'answered' && (
                        <>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleTogglePublic(qna)}
                          >
                            {qna.is_public ? (
                              <><EyeOff className="w-4 h-4 mr-2" />Make Private</>
                            ) : (
                              <><Eye className="w-4 h-4 mr-2" />Make Public</>
                            )}
                          </Button>

                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleToggleFeatured(qna)}
                          >
                            <Star className={`w-4 h-4 mr-2 ${qna.is_featured ? 'fill-current' : ''}`} />
                            {qna.is_featured ? 'Unfeature' : 'Feature'}
                          </Button>
                        </>
                      )}

                      {qna.status !== 'archived' && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => handleArchive(qna)}
                        >
                          <Archive className="w-4 h-4 mr-2" />
                          Archive
                        </Button>
                      )}

                      <Button
                        size="sm"
                        variant="destructive"
                        onClick={() => handleDelete(qna)}
                      >
                        Delete
                      </Button>
                    </div>
                  </div>
                ))}
              </div>

              {/* Pagination */}
              {pagination.last_page > 1 && (
                <div className="mt-6 flex justify-center items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                    disabled={pagination.current_page === 1}
                  >
                    <ChevronLeft className="w-4 h-4 mr-1" />
                    Previous
                  </Button>

                  <span className="text-sm text-muted-foreground px-4">
                    Page {pagination.current_page} of {pagination.last_page}
                  </span>

                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setCurrentPage(p => Math.min(pagination.last_page, p + 1))}
                    disabled={pagination.current_page === pagination.last_page}
                  >
                    Next
                    <ChevronRight className="w-4 h-4 ml-1" />
                  </Button>
                </div>
              )}
            </>
          ) : (
            <div className="py-12 text-center text-muted-foreground">
              <MessageSquare className="w-16 h-16 mx-auto mb-4 text-gray-300" />
              <p>No questions found</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Answer Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>
              {selectedQna?.answer ? 'Edit Answer' : 'Answer Question'}
            </DialogTitle>
            <DialogDescription>
              Provide a detailed answer to this investor question
            </DialogDescription>
          </DialogHeader>

          {selectedQna && (
            <div className="space-y-4">
              <div className="bg-gray-50 p-4 rounded-lg">
                <label className="text-sm font-medium text-muted-foreground block mb-2">Question</label>
                <p className="text-lg font-semibold">{selectedQna.question}</p>
                <p className="text-xs text-muted-foreground mt-2">
                  Asked on {formatDate(selectedQna.created_at)}
                </p>
              </div>

              <div>
                <label className="text-sm font-medium mb-2 block">Your Answer</label>
                <Textarea
                  value={answerText}
                  onChange={(e) => setAnswerText(e.target.value)}
                  placeholder="Write a detailed answer..."
                  rows={8}
                  className="resize-none"
                />
              </div>

              <div className="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                <div>
                  <p className="font-medium">Make this answer public</p>
                  <p className="text-sm text-muted-foreground">
                    Public answers will be visible on your company profile
                  </p>
                </div>
                <Switch
                  checked={isPublic}
                  onCheckedChange={setIsPublic}
                />
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={handleSubmitAnswer}
              disabled={!answerText.trim() || answerMutation.isLoading}
            >
              {answerMutation.isLoading ? 'Posting...' : 'Post Answer'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
