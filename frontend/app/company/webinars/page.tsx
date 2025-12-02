'use client';

import { useState } from "react";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import {
  Video,
  Calendar,
  Clock,
  Users,
  Link as LinkIcon,
  Plus,
  Edit,
  Trash2,
  Upload,
  Eye,
  PlayCircle,
  CheckCircle2,
  XCircle
} from "lucide-react";
import { toast } from "sonner";
import { format, parseISO } from "date-fns";

interface Webinar {
  id: number;
  title: string;
  description: string | null;
  type: 'webinar' | 'investor_call' | 'ama' | 'product_demo';
  scheduled_at: string;
  duration_minutes: number;
  meeting_link: string | null;
  meeting_id: string | null;
  meeting_password: string | null;
  max_participants: number | null;
  registered_count: number;
  speakers: any[] | null;
  agenda: string | null;
  status: 'scheduled' | 'live' | 'completed' | 'cancelled';
  recording_available: boolean;
  recording_url: string | null;
}

interface Statistics {
  total: number;
  upcoming: number;
  completed: number;
  total_registrations: number;
}

export default function CompanyWebinarsPage() {
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedWebinar, setSelectedWebinar] = useState<Webinar | null>(null);
  const [statusFilter, setStatusFilter] = useState('');
  const [recordingDialogOpen, setRecordingDialogOpen] = useState(false);
  const [recordingUrl, setRecordingUrl] = useState('');

  const [formData, setFormData] = useState({
    title: '',
    description: '',
    type: 'webinar',
    scheduled_at: '',
    duration_minutes: 60,
    meeting_link: '',
    meeting_id: '',
    meeting_password: '',
    max_participants: '',
    agenda: '',
  });

  const queryClient = useQueryClient();

  // Fetch statistics
  const { data: statsData } = useQuery({
    queryKey: ['webinarStats'],
    queryFn: async () => {
      const { data } = await api.get('/company/webinars/statistics');
      return data;
    },
  });

  const stats: Statistics = statsData?.stats || {
    total: 0,
    upcoming: 0,
    completed: 0,
    total_registrations: 0,
  };

  // Fetch webinars
  const { data: response, isLoading } = useQuery({
    queryKey: ['companyWebinars', statusFilter],
    queryFn: async () => {
      const params = new URLSearchParams({
        ...(statusFilter && { status: statusFilter }),
      });
      const { data } = await api.get(`/company/webinars?${params}`);
      return data;
    },
  });

  const webinars: Webinar[] = response?.data || [];

  // Create webinar mutation
  const createMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await api.post('/company/webinars', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyWebinars']);
      queryClient.invalidateQueries(['webinarStats']);
      toast.success('Webinar created successfully');
      setDialogOpen(false);
      resetForm();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create webinar');
    },
  });

  // Update webinar mutation
  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: number; data: any }) => {
      const response = await api.put(`/company/webinars/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyWebinars']);
      queryClient.invalidateQueries(['webinarStats']);
      toast.success('Webinar updated successfully');
      setDialogOpen(false);
      resetForm();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to update webinar');
    },
  });

  // Upload recording mutation
  const uploadRecordingMutation = useMutation({
    mutationFn: async ({ id, url }: { id: number; url: string }) => {
      const response = await api.post(`/company/webinars/${id}/recording`, {
        recording_url: url,
      });
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyWebinars']);
      toast.success('Recording uploaded successfully');
      setRecordingDialogOpen(false);
      setRecordingUrl('');
      setSelectedWebinar(null);
    },
    onError: () => {
      toast.error('Failed to upload recording');
    },
  });

  // Delete webinar mutation
  const deleteMutation = useMutation({
    mutationFn: async (id: number) => {
      const response = await api.delete(`/company/webinars/${id}`);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries(['companyWebinars']);
      queryClient.invalidateQueries(['webinarStats']);
      toast.success('Webinar deleted successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to delete webinar');
    },
  });

  const resetForm = () => {
    setFormData({
      title: '',
      description: '',
      type: 'webinar',
      scheduled_at: '',
      duration_minutes: 60,
      meeting_link: '',
      meeting_id: '',
      meeting_password: '',
      max_participants: '',
      agenda: '',
    });
    setSelectedWebinar(null);
  };

  const handleCreate = () => {
    resetForm();
    setDialogOpen(true);
  };

  const handleEdit = (webinar: Webinar) => {
    setSelectedWebinar(webinar);
    setFormData({
      title: webinar.title,
      description: webinar.description || '',
      type: webinar.type,
      scheduled_at: format(parseISO(webinar.scheduled_at), "yyyy-MM-dd'T'HH:mm"),
      duration_minutes: webinar.duration_minutes,
      meeting_link: webinar.meeting_link || '',
      meeting_id: webinar.meeting_id || '',
      meeting_password: webinar.meeting_password || '',
      max_participants: webinar.max_participants?.toString() || '',
      agenda: webinar.agenda || '',
    });
    setDialogOpen(true);
  };

  const handleSubmit = () => {
    const data = {
      ...formData,
      max_participants: formData.max_participants ? parseInt(formData.max_participants) : null,
    };

    if (selectedWebinar) {
      updateMutation.mutate({ id: selectedWebinar.id, data });
    } else {
      createMutation.mutate(data);
    }
  };

  const handleDelete = (webinar: Webinar) => {
    if (confirm(`Are you sure you want to delete "${webinar.title}"?`)) {
      deleteMutation.mutate(webinar.id);
    }
  };

  const handleUploadRecording = (webinar: Webinar) => {
    setSelectedWebinar(webinar);
    setRecordingUrl('');
    setRecordingDialogOpen(true);
  };

  const handleSubmitRecording = () => {
    if (selectedWebinar && recordingUrl) {
      uploadRecordingMutation.mutate({ id: selectedWebinar.id, url: recordingUrl });
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; className: string; text: string }> = {
      scheduled: { variant: 'secondary', className: 'bg-blue-100 text-blue-800', text: 'Scheduled' },
      live: { variant: 'default', className: 'bg-green-100 text-green-800', text: 'Live' },
      completed: { variant: 'outline', className: 'bg-gray-100 text-gray-800', text: 'Completed' },
      cancelled: { variant: 'destructive', className: 'bg-red-100 text-red-800', text: 'Cancelled' },
    };
    const config = variants[status] || variants.scheduled;
    return (
      <Badge className={config.className}>{config.text}</Badge>
    );
  };

  const getTypeBadge = (type: string) => {
    const labels: Record<string, string> = {
      webinar: 'Webinar',
      investor_call: 'Investor Call',
      ama: 'AMA',
      product_demo: 'Product Demo',
    };
    return <Badge variant="outline">{labels[type] || type}</Badge>;
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Webinar Management</h1>
          <p className="text-muted-foreground">
            Schedule and manage investor webinars and calls
          </p>
        </div>
        <Button onClick={handleCreate}>
          <Plus className="w-4 h-4 mr-2" />
          Schedule Webinar
        </Button>
      </div>

      {/* Statistics */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Webinars</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{stats.total}</div>
          </CardContent>
        </Card>
        <Card className="border-blue-200 bg-blue-50">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Upcoming</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{stats.upcoming}</div>
          </CardContent>
        </Card>
        <Card className="border-green-200 bg-green-50">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Completed</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{stats.completed}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total Registrations</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-purple-600">{stats.total_registrations}</div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <div className="flex gap-2">
        <Button
          variant={statusFilter === '' ? 'default' : 'outline'}
          onClick={() => setStatusFilter('')}
        >
          All
        </Button>
        <Button
          variant={statusFilter === 'scheduled' ? 'default' : 'outline'}
          onClick={() => setStatusFilter('scheduled')}
        >
          Scheduled
        </Button>
        <Button
          variant={statusFilter === 'live' ? 'default' : 'outline'}
          onClick={() => setStatusFilter('live')}
        >
          Live
        </Button>
        <Button
          variant={statusFilter === 'completed' ? 'default' : 'outline'}
          onClick={() => setStatusFilter('completed')}
        >
          Completed
        </Button>
      </div>

      {/* Webinars List */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {isLoading ? (
          [...Array(6)].map((_, i) => (
            <Card key={i} className="animate-pulse">
              <CardHeader>
                <div className="h-6 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              </CardHeader>
              <CardContent>
                <div className="h-20 bg-gray-200 rounded"></div>
              </CardContent>
            </Card>
          ))
        ) : webinars.length > 0 ? (
          webinars.map((webinar) => (
            <Card key={webinar.id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="flex items-start justify-between mb-2">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      {getStatusBadge(webinar.status)}
                      {getTypeBadge(webinar.type)}
                    </div>
                    <CardTitle className="text-xl line-clamp-2">{webinar.title}</CardTitle>
                  </div>
                </div>
                {webinar.description && (
                  <CardDescription className="line-clamp-2">
                    {webinar.description}
                  </CardDescription>
                )}
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Calendar className="w-4 h-4" />
                  {format(parseISO(webinar.scheduled_at), 'MMM dd, yyyy')}
                </div>
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Clock className="w-4 h-4" />
                  {format(parseISO(webinar.scheduled_at), 'hh:mm a')} ({webinar.duration_minutes} mins)
                </div>
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Users className="w-4 h-4" />
                  {webinar.registered_count} registered
                  {webinar.max_participants && ` / ${webinar.max_participants} max`}
                </div>
                {webinar.recording_available && (
                  <div className="flex items-center gap-2 text-sm text-green-600">
                    <CheckCircle2 className="w-4 h-4" />
                    Recording available
                  </div>
                )}

                <div className="flex gap-2 pt-3 border-t">
                  <Button size="sm" variant="outline" onClick={() => handleEdit(webinar)}>
                    <Edit className="w-4 h-4 mr-1" />
                    Edit
                  </Button>
                  {webinar.status === 'completed' && !webinar.recording_available && (
                    <Button size="sm" variant="outline" onClick={() => handleUploadRecording(webinar)}>
                      <Upload className="w-4 h-4 mr-1" />
                      Upload
                    </Button>
                  )}
                  <Button size="sm" variant="destructive" onClick={() => handleDelete(webinar)}>
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))
        ) : (
          <div className="col-span-full">
            <Card>
              <CardContent className="py-16 text-center">
                <Video className="w-16 h-16 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-2xl font-semibold mb-2">No webinars scheduled</h3>
                <p className="text-muted-foreground mb-4">
                  Start engaging with investors by scheduling your first webinar
                </p>
                <Button onClick={handleCreate}>
                  <Plus className="w-4 h-4 mr-2" />
                  Schedule Webinar
                </Button>
              </CardContent>
            </Card>
          </div>
        )}
      </div>

      {/* Create/Edit Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {selectedWebinar ? 'Edit Webinar' : 'Schedule New Webinar'}
            </DialogTitle>
            <DialogDescription>
              Fill in the details for your webinar or investor call
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div>
              <label className="text-sm font-medium mb-2 block">Title *</label>
              <Input
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                placeholder="Q1 2025 Investor Update"
              />
            </div>

            <div>
              <label className="text-sm font-medium mb-2 block">Type *</label>
              <Select value={formData.type} onValueChange={(value) => setFormData({ ...formData, type: value })}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="webinar">Webinar</SelectItem>
                  <SelectItem value="investor_call">Investor Call</SelectItem>
                  <SelectItem value="ama">AMA (Ask Me Anything)</SelectItem>
                  <SelectItem value="product_demo">Product Demo</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <label className="text-sm font-medium mb-2 block">Description</label>
              <Textarea
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Describe what this webinar will cover..."
                rows={3}
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium mb-2 block">Scheduled Date & Time *</label>
                <Input
                  type="datetime-local"
                  value={formData.scheduled_at}
                  onChange={(e) => setFormData({ ...formData, scheduled_at: e.target.value })}
                />
              </div>
              <div>
                <label className="text-sm font-medium mb-2 block">Duration (minutes) *</label>
                <Input
                  type="number"
                  value={formData.duration_minutes}
                  onChange={(e) => setFormData({ ...formData, duration_minutes: parseInt(e.target.value) || 60 })}
                  min="15"
                  max="480"
                />
              </div>
            </div>

            <div>
              <label className="text-sm font-medium mb-2 block">Meeting Link</label>
              <Input
                value={formData.meeting_link}
                onChange={(e) => setFormData({ ...formData, meeting_link: e.target.value })}
                placeholder="https://zoom.us/j/..."
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium mb-2 block">Meeting ID</label>
                <Input
                  value={formData.meeting_id}
                  onChange={(e) => setFormData({ ...formData, meeting_id: e.target.value })}
                  placeholder="123-456-789"
                />
              </div>
              <div>
                <label className="text-sm font-medium mb-2 block">Meeting Password</label>
                <Input
                  value={formData.meeting_password}
                  onChange={(e) => setFormData({ ...formData, meeting_password: e.target.value })}
                  placeholder="••••••"
                />
              </div>
            </div>

            <div>
              <label className="text-sm font-medium mb-2 block">Max Participants</label>
              <Input
                type="number"
                value={formData.max_participants}
                onChange={(e) => setFormData({ ...formData, max_participants: e.target.value })}
                placeholder="Leave empty for unlimited"
                min="1"
              />
            </div>

            <div>
              <label className="text-sm font-medium mb-2 block">Agenda</label>
              <Textarea
                value={formData.agenda}
                onChange={(e) => setFormData({ ...formData, agenda: e.target.value })}
                placeholder="Outline the topics that will be covered..."
                rows={4}
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={handleSubmit}
              disabled={!formData.title || !formData.scheduled_at || createMutation.isLoading || updateMutation.isLoading}
            >
              {selectedWebinar ? 'Update Webinar' : 'Create Webinar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Upload Recording Dialog */}
      <Dialog open={recordingDialogOpen} onOpenChange={setRecordingDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Upload Recording</DialogTitle>
            <DialogDescription>
              Provide the URL to the webinar recording
            </DialogDescription>
          </DialogHeader>

          <div>
            <label className="text-sm font-medium mb-2 block">Recording URL *</label>
            <Input
              value={recordingUrl}
              onChange={(e) => setRecordingUrl(e.target.value)}
              placeholder="https://..."
            />
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setRecordingDialogOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={handleSubmitRecording}
              disabled={!recordingUrl || uploadRecordingMutation.isLoading}
            >
              {uploadRecordingMutation.isLoading ? 'Uploading...' : 'Upload Recording'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
