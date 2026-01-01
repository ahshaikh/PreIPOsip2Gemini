"use client";

import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Checkbox } from "@/components/ui/checkbox";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import api from "@/lib/api";
import {
  Bell,
  Send,
  Users,
  Target,
  Calendar,
  Clock,
  Plus,
  Edit,
  Trash2,
  Eye,
  RefreshCw,
  CheckCircle,
  XCircle,
  AlertCircle,
  Filter,
  Search,
  Download,
  Upload,
  Smartphone,
  Globe,
  BarChart3,
  TrendingUp,
  MessageSquare,
  Megaphone,
  Settings,
  History,
  Zap,
  Save,
  Mail
} from "lucide-react";

interface PushNotification {
  id: number;
  title: string;
  body: string;
  image_url?: string;
  action_url?: string;
  target_audience: string;
  scheduled_at?: string;
  sent_at?: string;
  status: 'draft' | 'scheduled' | 'sending' | 'sent' | 'failed';
  total_recipients: number;
  delivered: number;
  opened: number;
  clicked: number;
  created_at: string;
}

interface NotificationTemplate {
  id: number;
  name: string;
  title: string;
  body: string;
  image_url?: string;
  category: string;
}

export default function PushNotificationsPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState("send");
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [showComposeDialog, setShowComposeDialog] = useState(false);
  const [showTemplateDialog, setShowTemplateDialog] = useState(false);
  const [selectedNotification, setSelectedNotification] = useState<PushNotification | null>(null);
  const [settings, setSettings] = useState<Record<string, string>>({});
  const [testMobile, setTestMobile] = useState('');

  // Form state for composing notification
  const [composeForm, setComposeForm] = useState({
    title: "",
    body: "",
    image_url: "",
    action_url: "",
    target_audience: "all",
    target_users: [] as number[],
    schedule_type: "now",
    scheduled_date: "",
    scheduled_time: "",
    use_template: false,
    template_id: 0
  });

  // Fetch notifications history
  const { data: notifications, isLoading: notificationsLoading, refetch: refetchNotifications } = useQuery({
    queryKey: ["push-notifications", statusFilter, searchTerm],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (statusFilter !== "all") params.append("status", statusFilter);
      if (searchTerm) params.append("search", searchTerm);
      const response = await api.get(`/admin/notifications/push?${params}`);
      return response.data;
    }
  });

  // Fetch notification stats
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ["push-notification-stats"],
    queryFn: async () => {
      const response = await api.get("/admin/notifications/push/stats");
      return response.data;
    }
  });

  // Fetch templates
  const { data: templates, isLoading: templatesLoading } = useQuery({
    queryKey: ["notification-templates"],
    queryFn: async () => {
      const response = await api.get("/admin/notifications/templates");
      return response.data;
    }
  });

  // Fetch user segments
  const { data: segments } = useQuery({
    queryKey: ["user-segments"],
    queryFn: async () => {
      const response = await api.get("/admin/users/segments");
      return response.data;
    }
  });

  // Fetch notification settings
  const { data: settingsData } = useQuery({
    queryKey: ['notificationSettings'],
    queryFn: async () => {
      const res = await api.get('/admin/settings');
      const flatMap: Record<string, string> = {};
      Object.values(res.data.notification || {}).forEach((setting: any) => {
        flatMap[setting.key] = setting.value;
      });
      setSettings(flatMap);
      return res.data;
    }
  });

  // Send notification mutation
  const sendNotification = useMutation({
    mutationFn: async (data: any) => {
      const response = await api.post("/admin/notifications/push/send", data);
      return response.data;
    },
    onSuccess: () => {
      toast.success("Notification sent successfully!");
      setShowComposeDialog(false);
      resetComposeForm();
      refetchNotifications();
      queryClient.invalidateQueries({ queryKey: ["push-notification-stats"] });
    },
    onError: (error: any) => {
      toast.error("Failed to send notification", { description: error.response?.data?.message });
    }
  });

  // Schedule notification mutation
  const scheduleNotification = useMutation({
    mutationFn: async (data: any) => {
      const response = await api.post("/admin/notifications/push/schedule", data);
      return response.data;
    },
    onSuccess: () => {
      toast.success("Notification scheduled successfully!");
      setShowComposeDialog(false);
      resetComposeForm();
      refetchNotifications();
    },
    onError: (error: any) => {
      toast.error("Failed to schedule notification", { description: error.response?.data?.message });
    }
  });

  // Cancel scheduled notification
  const cancelNotification = useMutation({
    mutationFn: async (id: number) => {
      const response = await api.post(`/admin/notifications/push/${id}/cancel`);
      return response.data;
    },
    onSuccess: () => {
      toast.success("Notification cancelled!");
      refetchNotifications();
    },
    onError: () => {
      toast.error("Failed to cancel notification");
    }
  });

  // Delete notification
  const deleteNotification = useMutation({
    mutationFn: async (id: number) => {
      const response = await api.delete(`/admin/notifications/push/${id}`);
      return response.data;
    },
    onSuccess: () => {
      toast.success("Notification deleted!");
      refetchNotifications();
    },
    onError: () => {
      toast.error("Failed to delete notification");
    }
  });

  // Save template mutation
  const saveTemplate = useMutation({
    mutationFn: async (data: any) => {
      const response = await api.post("/admin/notifications/templates", data);
      return response.data;
    },
    onSuccess: () => {
      toast.success("Template saved!");
      setShowTemplateDialog(false);
      queryClient.invalidateQueries({ queryKey: ["notification-templates"] });
    },
    onError: () => {
      toast.error("Failed to save template");
    }
  });

  // Update notification settings mutation
  const updateSettingsMutation = useMutation({
    mutationFn: (updatedSettings: { key: string, value: string }[]) =>
      api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!");
      queryClient.invalidateQueries({ queryKey: ['notificationSettings'] });
    },
    onError: () => toast.error("Save Failed")
  });

  // Test SMS mutation
  const testSmsMutation = useMutation({
    mutationFn: (mobile: string) => api.post('/admin/notifications/test-sms', { mobile }),
    onSuccess: (data) => {
      toast.success("Test Sent", { description: data.data.message });
    },
    onError: (e: any) => toast.error("Test Failed", { description: e.response?.data?.message })
  });

  const resetComposeForm = () => {
    setComposeForm({
      title: "",
      body: "",
      image_url: "",
      action_url: "",
      target_audience: "all",
      target_users: [],
      schedule_type: "now",
      scheduled_date: "",
      scheduled_time: "",
      use_template: false,
      template_id: 0
    });
  };

  const handleSendNotification = () => {
    if (!composeForm.title || !composeForm.body) {
      toast.error("Please fill in title and body");
      return;
    }

    // FIX: Backend expects 'target_type' not 'target_audience'
    // FIX: Backend expects 'user_ids' not 'target_users'
    // FIX: Don't send null fields to avoid validation errors
    const payload: any = {
      title: composeForm.title,
      body: composeForm.body,
      target_type: composeForm.target_audience,
    };

    // Only add optional fields if they have values (don't send null)
    if (composeForm.image_url) payload.image_url = composeForm.image_url;
    if (composeForm.action_url) payload.action_url = composeForm.action_url;
    if (composeForm.target_audience === "specific" && composeForm.target_users.length > 0) {
      payload.user_ids = composeForm.target_users;
    }

    if (composeForm.schedule_type === "scheduled") {
      if (!composeForm.scheduled_date || !composeForm.scheduled_time) {
        toast.error("Please select schedule date and time");
        return;
      }
      scheduleNotification.mutate({
        ...payload,
        scheduled_at: `${composeForm.scheduled_date}T${composeForm.scheduled_time}`
      });
    } else {
      sendNotification.mutate(payload);
    }
  };

  const handleTemplateSelect = (templateId: number) => {
    // FIX: Removed mockTemplates reference - use only real templates from backend
    const template = displayTemplates.find((t: NotificationTemplate) => t.id === templateId);
    if (template) {
      setComposeForm(prev => ({
        ...prev,
        title: template.title,
        body: template.body,
        image_url: template.image_url || "",
        template_id: templateId
      }));
    }
  };

  const handleSettingChange = (key: string, value: string) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleSaveSettings = () => {
    const payload = Object.entries(settings).map(([key, value]) => ({ key, value }));
    updateSettingsMutation.mutate(payload);
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: "default" | "secondary" | "destructive" | "outline", icon: any }> = {
      draft: { variant: "secondary", icon: Edit },
      scheduled: { variant: "outline", icon: Clock },
      sending: { variant: "default", icon: RefreshCw },
      sent: { variant: "default", icon: CheckCircle },
      failed: { variant: "destructive", icon: XCircle }
    };
    const config = variants[status] || variants.draft;
    const Icon = config.icon;
    return (
      <Badge variant={config.variant} className="capitalize">
        <Icon className="h-3 w-3 mr-1" />
        {status}
      </Badge>
    );
  };

  // FIX: Removed all mock data - now using real backend data only
  const displayStats = stats || {
    total_sent: 0,
    total_delivered: 0,
    total_opened: 0,
    total_clicked: 0,
    delivery_rate: 0,
    open_rate: 0,
    click_rate: 0,
    subscribers: 0,
    active_devices: 0
  };
  const displayNotifications = notifications || [];
  const displayTemplates = templates || [];
  const displaySegments = segments || [];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Push Notifications</h1>
          <p className="text-muted-foreground">Send and manage site-wide push notifications</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => refetchNotifications()}>
            <RefreshCw className="h-4 w-4 mr-2" />
            Refresh
          </Button>
          <Button onClick={() => setShowComposeDialog(true)}>
            <Send className="h-4 w-4 mr-2" />
            Send Notification
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-blue-50 rounded-full">
                <Send className="h-6 w-6 text-blue-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Total Sent</p>
                <p className="text-2xl font-bold">{displayStats.total_sent?.toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-green-50 rounded-full">
                <CheckCircle className="h-6 w-6 text-green-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Delivery Rate</p>
                <p className="text-2xl font-bold text-green-600">{displayStats.delivery_rate}%</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-purple-50 rounded-full">
                <Eye className="h-6 w-6 text-purple-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Open Rate</p>
                <p className="text-2xl font-bold text-purple-600">{displayStats.open_rate}%</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-orange-50 rounded-full">
                <TrendingUp className="h-6 w-6 text-orange-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Click Rate</p>
                <p className="text-2xl font-bold text-orange-600">{displayStats.click_rate}%</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-4">
              <div className="p-3 bg-indigo-50 rounded-full">
                <Smartphone className="h-6 w-6 text-indigo-600" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">Active Devices</p>
                <p className="text-2xl font-bold">{displayStats.active_devices?.toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Main Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="send"><Send className="h-4 w-4 mr-2" />Send</TabsTrigger>
          <TabsTrigger value="history"><History className="h-4 w-4 mr-2" />History</TabsTrigger>
          <TabsTrigger value="scheduled"><Clock className="h-4 w-4 mr-2" />Scheduled</TabsTrigger>
          <TabsTrigger value="templates"><MessageSquare className="h-4 w-4 mr-2" />Templates</TabsTrigger>
          <TabsTrigger value="segments"><Users className="h-4 w-4 mr-2" />Segments</TabsTrigger>
          <TabsTrigger value="analytics"><BarChart3 className="h-4 w-4 mr-2" />Analytics</TabsTrigger>
          <TabsTrigger value="settings"><Settings className="h-4 w-4 mr-2" />Settings</TabsTrigger>
        </TabsList>

        {/* Send Tab */}
        <TabsContent value="send" className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2">
            {/* Quick Send Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Zap className="h-5 w-5 text-yellow-500" />
                  Quick Send
                </CardTitle>
                <CardDescription>Send a notification to all users instantly</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>Title</Label>
                  <Input
                    placeholder="Notification title..."
                    value={composeForm.title}
                    onChange={(e) => setComposeForm(prev => ({ ...prev, title: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Message</Label>
                  <Textarea
                    placeholder="Notification message..."
                    value={composeForm.body}
                    onChange={(e) => setComposeForm(prev => ({ ...prev, body: e.target.value }))}
                    rows={3}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Target Audience</Label>
                  <Select
                    value={composeForm.target_audience}
                    onValueChange={(v) => setComposeForm(prev => ({ ...prev, target_audience: v }))}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {/* FIX: Defensive check for displaySegments */}
                      {(Array.isArray(displaySegments) ? displaySegments : []).map((segment: any) => (
                        <SelectItem key={segment.id} value={segment.id}>
                          {segment.name} ({segment.count?.toLocaleString()})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <Button
                  className="w-full"
                  onClick={handleSendNotification}
                  disabled={sendNotification.isPending || !composeForm.title || !composeForm.body}
                >
                  <Send className="h-4 w-4 mr-2" />
                  {sendNotification.isPending ? "Sending..." : "Send Now"}
                </Button>
              </CardContent>
            </Card>

            {/* Use Template Card */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <MessageSquare className="h-5 w-5 text-blue-500" />
                  Use Template
                </CardTitle>
                <CardDescription>Choose from pre-made templates</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-2">
                  {/* FIX: Defensive check for displayTemplates.slice */}
                  {(Array.isArray(displayTemplates) ? displayTemplates : []).slice(0, 4).map((template: NotificationTemplate) => (
                    <div
                      key={template.id}
                      className="p-3 border rounded-lg hover:bg-muted/50 cursor-pointer transition-colors"
                      onClick={() => handleTemplateSelect(template.id)}
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="font-medium">{template.name}</p>
                          <p className="text-sm text-muted-foreground truncate">{template.title}</p>
                        </div>
                        <Badge variant="outline">{template.category}</Badge>
                      </div>
                    </div>
                  ))}
                </div>
                <Button variant="outline" className="w-full" onClick={() => setActiveTab("templates")}>
                  View All Templates
                </Button>
              </CardContent>
            </Card>
          </div>

          {/* Recent Notifications */}
          <Card>
            <CardHeader>
              <CardTitle>Recent Notifications</CardTitle>
              <CardDescription>Last 5 notifications sent</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Audience</TableHead>
                    <TableHead>Sent</TableHead>
                    <TableHead>Delivered</TableHead>
                    <TableHead>Opened</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {/* FIX: Defensive check for displayNotifications.slice */}
                  {(Array.isArray(displayNotifications) ? displayNotifications : []).slice(0, 5).map((notification: PushNotification) => (
                    <TableRow key={notification.id}>
                      <TableCell className="font-medium">{notification.title}</TableCell>
                      <TableCell className="capitalize">{notification.target_audience.replace(/_/g, " ")}</TableCell>
                      <TableCell>{notification.total_recipients?.toLocaleString()}</TableCell>
                      <TableCell className="text-green-600">{notification.delivered?.toLocaleString()}</TableCell>
                      <TableCell className="text-purple-600">{notification.opened?.toLocaleString()}</TableCell>
                      <TableCell>{getStatusBadge(notification.status)}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* History Tab */}
        <TabsContent value="history" className="space-y-6">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Notification History</CardTitle>
                <div className="flex gap-2">
                  <div className="relative">
                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Search notifications..."
                      className="pl-8 w-[250px]"
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                    />
                  </div>
                  <Select value={statusFilter} onValueChange={setStatusFilter}>
                    <SelectTrigger className="w-[150px]">
                      <SelectValue placeholder="Filter status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Status</SelectItem>
                      <SelectItem value="sent">Sent</SelectItem>
                      <SelectItem value="scheduled">Scheduled</SelectItem>
                      <SelectItem value="failed">Failed</SelectItem>
                    </SelectContent>
                  </Select>
                  <Button variant="outline">
                    <Download className="h-4 w-4 mr-2" />
                    Export
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Message</TableHead>
                    <TableHead>Audience</TableHead>
                    <TableHead>Sent At</TableHead>
                    <TableHead>Recipients</TableHead>
                    <TableHead>Open Rate</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {/* FIX: Defensive check for displayNotifications.map */}
                  {(Array.isArray(displayNotifications) ? displayNotifications : []).map((notification: PushNotification) => (
                    <TableRow key={notification.id}>
                      <TableCell className="font-medium">{notification.title}</TableCell>
                      <TableCell className="max-w-[200px] truncate">{notification.body}</TableCell>
                      <TableCell className="capitalize">{notification.target_audience.replace(/_/g, " ")}</TableCell>
                      <TableCell>{notification.sent_at || notification.scheduled_at || "-"}</TableCell>
                      <TableCell>{notification.total_recipients?.toLocaleString()}</TableCell>
                      <TableCell>
                        {notification.total_recipients > 0
                          ? `${((notification.opened / notification.total_recipients) * 100).toFixed(1)}%`
                          : "-"}
                      </TableCell>
                      <TableCell>{getStatusBadge(notification.status)}</TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          <Button variant="ghost" size="sm">
                            <Eye className="h-4 w-4" />
                          </Button>
                          {notification.status === "scheduled" && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => cancelNotification.mutate(notification.id)}
                            >
                              <XCircle className="h-4 w-4 text-red-500" />
                            </Button>
                          )}
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => deleteNotification.mutate(notification.id)}
                          >
                            <Trash2 className="h-4 w-4 text-red-500" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Scheduled Tab */}
        <TabsContent value="scheduled" className="space-y-6">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Scheduled Notifications</CardTitle>
                  <CardDescription>Notifications waiting to be sent</CardDescription>
                </div>
                <Button onClick={() => setShowComposeDialog(true)}>
                  <Plus className="h-4 w-4 mr-2" />
                  Schedule New
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {/* FIX: Defensive check for displayNotifications.filter */}
              {(Array.isArray(displayNotifications) ? displayNotifications : []).filter((n: PushNotification) => n.status === "scheduled").length === 0 ? (
                <div className="text-center py-12 text-muted-foreground">
                  <Clock className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p>No scheduled notifications</p>
                  <p className="text-sm">Schedule a notification to send later</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Title</TableHead>
                      <TableHead>Message</TableHead>
                      <TableHead>Audience</TableHead>
                      <TableHead>Scheduled For</TableHead>
                      <TableHead>Recipients</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {/* FIX: Defensive check for displayNotifications.filter.map */}
                    {(Array.isArray(displayNotifications) ? displayNotifications : [])
                      .filter((n: PushNotification) => n.status === "scheduled")
                      .map((notification: PushNotification) => (
                        <TableRow key={notification.id}>
                          <TableCell className="font-medium">{notification.title}</TableCell>
                          <TableCell className="max-w-[200px] truncate">{notification.body}</TableCell>
                          <TableCell className="capitalize">{notification.target_audience.replace(/_/g, " ")}</TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Calendar className="h-4 w-4 text-muted-foreground" />
                              {notification.scheduled_at}
                            </div>
                          </TableCell>
                          <TableCell>{notification.total_recipients?.toLocaleString()}</TableCell>
                          <TableCell className="text-right">
                            <div className="flex justify-end gap-1">
                              <Button variant="ghost" size="sm">
                                <Edit className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => cancelNotification.mutate(notification.id)}
                              >
                                <XCircle className="h-4 w-4 text-red-500" />
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Templates Tab */}
        <TabsContent value="templates" className="space-y-6">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Notification Templates</CardTitle>
                  <CardDescription>Reusable notification templates</CardDescription>
                </div>
                <Button onClick={() => setShowTemplateDialog(true)}>
                  <Plus className="h-4 w-4 mr-2" />
                  Create Template
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {/* FIX: Defensive check for displayTemplates.map */}
                {(Array.isArray(displayTemplates) ? displayTemplates : []).map((template: NotificationTemplate) => (
                  <Card key={template.id} className="hover:shadow-md transition-shadow">
                    <CardHeader className="pb-2">
                      <div className="flex items-center justify-between">
                        <CardTitle className="text-base">{template.name}</CardTitle>
                        <Badge variant="outline">{template.category}</Badge>
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-2">
                      <div>
                        <p className="text-sm font-medium">{template.title}</p>
                        <p className="text-sm text-muted-foreground line-clamp-2">{template.body}</p>
                      </div>
                      <div className="flex gap-2 pt-2">
                        <Button
                          size="sm"
                          variant="outline"
                          className="flex-1"
                          onClick={() => {
                            handleTemplateSelect(template.id);
                            setShowComposeDialog(true);
                          }}
                        >
                          <Send className="h-3 w-3 mr-1" />
                          Use
                        </Button>
                        <Button size="sm" variant="ghost">
                          <Edit className="h-3 w-3" />
                        </Button>
                        <Button size="sm" variant="ghost">
                          <Trash2 className="h-3 w-3 text-red-500" />
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Segments Tab */}
        <TabsContent value="segments" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>User Segments</CardTitle>
              <CardDescription>Target specific groups of users with your notifications</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {/* FIX: Defensive check for displaySegments.map */}
                {(Array.isArray(displaySegments) ? displaySegments : []).map((segment: any) => (
                  <Card key={segment.id} className="hover:shadow-md transition-shadow">
                    <CardContent className="pt-6">
                      <div className="flex items-center gap-4">
                        <div className="p-3 bg-primary/10 rounded-full">
                          <Target className="h-6 w-6 text-primary" />
                        </div>
                        <div className="flex-1">
                          <p className="font-medium">{segment.name}</p>
                          <p className="text-2xl font-bold">{segment.count?.toLocaleString()}</p>
                          <p className="text-xs text-muted-foreground">users</p>
                        </div>
                        <Button
                          size="sm"
                          onClick={() => {
                            setComposeForm(prev => ({ ...prev, target_audience: segment.id }));
                            setShowComposeDialog(true);
                          }}
                        >
                          <Send className="h-3 w-3 mr-1" />
                          Send
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Analytics Tab */}
        <TabsContent value="analytics" className="space-y-6">
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Delivery Performance</CardTitle>
                <CardDescription>Last 30 days</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-6">
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Delivery Rate</span>
                      <span className="font-medium">{displayStats.delivery_rate}%</span>
                    </div>
                    <Progress value={displayStats.delivery_rate} className="h-2" />
                  </div>
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Open Rate</span>
                      <span className="font-medium">{displayStats.open_rate}%</span>
                    </div>
                    <Progress value={displayStats.open_rate} className="h-2" />
                  </div>
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Click Rate</span>
                      <span className="font-medium">{displayStats.click_rate}%</span>
                    </div>
                    <Progress value={displayStats.click_rate} className="h-2" />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Device Distribution</CardTitle>
                <CardDescription>Active subscribers by platform</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex items-center gap-3">
                      <Globe className="h-5 w-5 text-blue-500" />
                      <span>Web Browser</span>
                    </div>
                    <div className="text-right">
                      <p className="font-bold">1,450</p>
                      <p className="text-xs text-muted-foreground">62%</p>
                    </div>
                  </div>
                  <div className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex items-center gap-3">
                      <Smartphone className="h-5 w-5 text-green-500" />
                      <span>Android</span>
                    </div>
                    <div className="text-right">
                      <p className="font-bold">650</p>
                      <p className="text-xs text-muted-foreground">28%</p>
                    </div>
                  </div>
                  <div className="flex items-center justify-between p-3 border rounded-lg">
                    <div className="flex items-center gap-3">
                      <Smartphone className="h-5 w-5 text-gray-500" />
                      <span>iOS</span>
                    </div>
                    <div className="text-right">
                      <p className="font-bold">240</p>
                      <p className="text-xs text-muted-foreground">10%</p>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Top Performing Notifications</CardTitle>
              <CardDescription>Based on click-through rate</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Title</TableHead>
                    <TableHead>Sent</TableHead>
                    <TableHead>Delivered</TableHead>
                    <TableHead>Opened</TableHead>
                    <TableHead>Clicked</TableHead>
                    <TableHead>CTR</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {/* FIX: Defensive check for displayNotifications.filter.sort.slice.map */}
                  {(Array.isArray(displayNotifications) ? displayNotifications : [])
                    .filter((n: PushNotification) => n.status === "sent")
                    .sort((a: PushNotification, b: PushNotification) =>
                      (b.clicked / b.total_recipients) - (a.clicked / a.total_recipients)
                    )
                    .slice(0, 5)
                    .map((notification: PushNotification) => (
                      <TableRow key={notification.id}>
                        <TableCell className="font-medium">{notification.title}</TableCell>
                        <TableCell>{notification.total_recipients?.toLocaleString()}</TableCell>
                        <TableCell className="text-green-600">{notification.delivered?.toLocaleString()}</TableCell>
                        <TableCell className="text-purple-600">{notification.opened?.toLocaleString()}</TableCell>
                        <TableCell className="text-orange-600">{notification.clicked?.toLocaleString()}</TableCell>
                        <TableCell className="font-bold">
                          {((notification.clicked / notification.total_recipients) * 100).toFixed(1)}%
                        </TableCell>
                      </TableRow>
                    ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Settings Tab */}
        <TabsContent value="settings" className="space-y-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-2xl font-bold">Notification Settings</h2>
              <p className="text-muted-foreground">Configure SMS, Email, and Push notification providers</p>
            </div>
            <Button onClick={handleSaveSettings} disabled={updateSettingsMutation.isPending}>
              <Save className="mr-2 h-4 w-4" />
              {updateSettingsMutation.isPending ? "Saving..." : "Save All Changes"}
            </Button>
          </div>

          <Tabs defaultValue="sms" className="w-full">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="sms"><MessageSquare className="mr-2 h-4 w-4"/> SMS</TabsTrigger>
              <TabsTrigger value="email"><Mail className="mr-2 h-4 w-4"/> Email</TabsTrigger>
              <TabsTrigger value="push"><Bell className="mr-2 h-4 w-4"/> Push</TabsTrigger>
            </TabsList>

            {/* SMS Settings */}
            <TabsContent value="sms" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>SMS Gateway</CardTitle>
                  <CardDescription>Configure your provider for sending all SMS.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label>Provider</Label>
                    <Select
                      value={settings['sms_provider'] || 'log'}
                      onValueChange={(v) => handleSettingChange('sms_provider', v)}
                    >
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="log">Log (Disable SMS, write to log)</SelectItem>
                        <SelectItem value="msg91">MSG91</SelectItem>
                        <SelectItem value="twilio">Twilio</SelectItem>
                        <SelectItem value="gupshup">Gupshup</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  {/* MSG91 Settings */}
                  {settings['sms_provider'] === 'msg91' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">MSG91 Settings</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>Auth Key</Label>
                          <Input
                            type="password"
                            value={settings['msg91_auth_key'] || ''}
                            onChange={(e) => handleSettingChange('msg91_auth_key', e.target.value)}
                          />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div className="space-y-2">
                            <Label>Sender ID</Label>
                            <Input
                              value={settings['msg91_sender_id'] || ''}
                              onChange={(e) => handleSettingChange('msg91_sender_id', e.target.value)}
                            />
                          </div>
                          <div className="space-y-2">
                            <Label>DLT Template ID (for OTPs)</Label>
                            <Input
                              value={settings['msg91_dlt_te_id'] || ''}
                              onChange={(e) => handleSettingChange('msg91_dlt_te_id', e.target.value)}
                            />
                          </div>
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* Twilio Settings */}
                  {settings['sms_provider'] === 'twilio' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">Twilio Settings</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>Account SID</Label>
                          <Input
                            value={settings['twilio_sid'] || ''}
                            onChange={(e) => handleSettingChange('twilio_sid', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Auth Token</Label>
                          <Input
                            type="password"
                            value={settings['twilio_token'] || ''}
                            onChange={(e) => handleSettingChange('twilio_token', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>From Phone Number</Label>
                          <Input
                            value={settings['twilio_from'] || ''}
                            onChange={(e) => handleSettingChange('twilio_from', e.target.value)}
                          />
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* Gupshup Settings */}
                  {settings['sms_provider'] === 'gupshup' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">Gupshup Settings</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>API Key</Label>
                          <Input
                            type="password"
                            value={settings['gupshup_api_key'] || ''}
                            onChange={(e) => handleSettingChange('gupshup_api_key', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>User ID</Label>
                          <Input
                            value={settings['gupshup_user_id'] || ''}
                            onChange={(e) => handleSettingChange('gupshup_user_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Password</Label>
                          <Input
                            type="password"
                            value={settings['gupshup_password'] || ''}
                            onChange={(e) => handleSettingChange('gupshup_password', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Sender Mask (From Number/ID)</Label>
                          <Input
                            value={settings['gupshup_sender'] || ''}
                            onChange={(e) => handleSettingChange('gupshup_sender', e.target.value)}
                          />
                        </div>
                      </div>
                    </Card>
                  )}
                </CardContent>
              </Card>

              {/* Test SMS */}
              <Card>
                <CardHeader><CardTitle>Test SMS Delivery</CardTitle></CardHeader>
                <CardContent className="flex gap-4 items-end">
                  <div className="flex-1 space-y-2">
                    <Label>10-Digit Mobile Number (e.g., 9876543210)</Label>
                    <Input value={testMobile} onChange={e => setTestMobile(e.target.value)} />
                  </div>
                  <Button
                    onClick={() => testSmsMutation.mutate(testMobile)}
                    disabled={testSmsMutation.isPending || !testMobile}
                  >
                    <Send className="mr-2 h-4 w-4" />
                    {testSmsMutation.isPending ? "Sending..." : "Send Test"}
                  </Button>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="email">
              <Card>
                <CardHeader><CardTitle>Email Settings (Mailgun/SMTP)</CardTitle></CardHeader>
                <CardContent>
                  <p className="text-muted-foreground">Email settings are configured in the <strong>.env</strong> file (e.g., `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`).</p>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="push" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Push Notification Provider</CardTitle>
                  <CardDescription>Configure your preferred push notification service</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label>Provider</Label>
                    <Select
                      value={settings['push_provider'] || 'fcm'}
                      onValueChange={(v) => handleSettingChange('push_provider', v)}
                    >
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="fcm">Firebase Cloud Messaging (FCM)</SelectItem>
                        <SelectItem value="onesignal">OneSignal</SelectItem>
                        <SelectItem value="apn">Apple Push Notification (APN)</SelectItem>
                        <SelectItem value="clevertap">CleverTap</SelectItem>
                        <SelectItem value="moengage">MoEngage</SelectItem>
                        <SelectItem value="aws-sns">AWS SNS</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  {/* FCM Settings */}
                  {settings['push_provider'] === 'fcm' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">Firebase Cloud Messaging (FCM)</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>Server Key</Label>
                          <Input
                            type="password"
                            placeholder="AAAA..."
                            value={settings['fcm_server_key'] || ''}
                            onChange={(e) => handleSettingChange('fcm_server_key', e.target.value)}
                          />
                          <p className="text-xs text-muted-foreground">
                            Get from Firebase Console → Project Settings → Cloud Messaging → Server Key
                          </p>
                        </div>
                        <div className="space-y-2">
                          <Label>Sender ID</Label>
                          <Input
                            placeholder="123456789012"
                            value={settings['fcm_sender_id'] || ''}
                            onChange={(e) => handleSettingChange('fcm_sender_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Default Icon URL (optional)</Label>
                          <Input
                            placeholder="https://..."
                            value={settings['push_default_icon'] || ''}
                            onChange={(e) => handleSettingChange('push_default_icon', e.target.value)}
                          />
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* OneSignal Settings */}
                  {settings['push_provider'] === 'onesignal' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">OneSignal</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>App ID</Label>
                          <Input
                            placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                            value={settings['onesignal_app_id'] || ''}
                            onChange={(e) => handleSettingChange('onesignal_app_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>REST API Key</Label>
                          <Input
                            type="password"
                            placeholder="YOUR_REST_API_KEY"
                            value={settings['onesignal_api_key'] || ''}
                            onChange={(e) => handleSettingChange('onesignal_api_key', e.target.value)}
                          />
                          <p className="text-xs text-muted-foreground">
                            Get from OneSignal Dashboard → Settings → Keys & IDs
                          </p>
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* Apple Push Notification (APN) Settings */}
                  {settings['push_provider'] === 'apn' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">Apple Push Notification (APN)</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>Team ID</Label>
                          <Input
                            placeholder="XXXXXXXXXX"
                            value={settings['apn_team_id'] || ''}
                            onChange={(e) => handleSettingChange('apn_team_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Key ID</Label>
                          <Input
                            placeholder="YYYYYYYYYY"
                            value={settings['apn_key_id'] || ''}
                            onChange={(e) => handleSettingChange('apn_key_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Bundle ID</Label>
                          <Input
                            placeholder="com.yourcompany.app"
                            value={settings['apn_bundle_id'] || ''}
                            onChange={(e) => handleSettingChange('apn_bundle_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Private Key (.p8 file content)</Label>
                          <textarea
                            className="w-full h-32 px-3 py-2 text-sm border rounded-md"
                            placeholder="-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEG...
-----END PRIVATE KEY-----"
                            value={settings['apn_private_key'] || ''}
                            onChange={(e) => handleSettingChange('apn_private_key', e.target.value)}
                          />
                          <p className="text-xs text-muted-foreground">
                            Get from Apple Developer Portal → Certificates, Identifiers & Profiles → Keys
                          </p>
                        </div>
                        <div className="space-y-2">
                          <Label>Environment</Label>
                          <Select
                            value={settings['apn_environment'] || 'production'}
                            onValueChange={(v) => handleSettingChange('apn_environment', v)}
                          >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                              <SelectItem value="production">Production</SelectItem>
                              <SelectItem value="sandbox">Sandbox (Development)</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* CleverTap Settings */}
                  {settings['push_provider'] === 'clevertap' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">CleverTap</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>Account ID</Label>
                          <Input
                            placeholder="YOUR_ACCOUNT_ID"
                            value={settings['clevertap_account_id'] || ''}
                            onChange={(e) => handleSettingChange('clevertap_account_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Passcode</Label>
                          <Input
                            type="password"
                            placeholder="YOUR_PASSCODE"
                            value={settings['clevertap_passcode'] || ''}
                            onChange={(e) => handleSettingChange('clevertap_passcode', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Region</Label>
                          <Select
                            value={settings['clevertap_region'] || 'in1'}
                            onValueChange={(v) => handleSettingChange('clevertap_region', v)}
                          >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                              <SelectItem value="in1">India (in1)</SelectItem>
                              <SelectItem value="us1">US (us1)</SelectItem>
                              <SelectItem value="sg1">Singapore (sg1)</SelectItem>
                              <SelectItem value="eu1">Europe (eu1)</SelectItem>
                            </SelectContent>
                          </Select>
                          <p className="text-xs text-muted-foreground">
                            Get from CleverTap Dashboard → Settings → Account
                          </p>
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* MoEngage Settings */}
                  {settings['push_provider'] === 'moengage' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">MoEngage</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>App ID</Label>
                          <Input
                            placeholder="YOUR_APP_ID"
                            value={settings['moengage_app_id'] || ''}
                            onChange={(e) => handleSettingChange('moengage_app_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Data API ID</Label>
                          <Input
                            placeholder="YOUR_DATA_API_ID"
                            value={settings['moengage_data_api_id'] || ''}
                            onChange={(e) => handleSettingChange('moengage_data_api_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Data API Key</Label>
                          <Input
                            type="password"
                            placeholder="YOUR_DATA_API_KEY"
                            value={settings['moengage_data_api_key'] || ''}
                            onChange={(e) => handleSettingChange('moengage_data_api_key', e.target.value)}
                          />
                          <p className="text-xs text-muted-foreground">
                            Get from MoEngage Dashboard → Settings → APIs
                          </p>
                        </div>
                      </div>
                    </Card>
                  )}

                  {/* AWS SNS Settings */}
                  {settings['push_provider'] === 'aws-sns' && (
                    <Card className="p-4 bg-muted/30">
                      <CardTitle className="text-lg mb-4">AWS Simple Notification Service (SNS)</CardTitle>
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label>AWS Access Key ID</Label>
                          <Input
                            placeholder="AKIAIOSFODNN7EXAMPLE"
                            value={settings['aws_access_key_id'] || ''}
                            onChange={(e) => handleSettingChange('aws_access_key_id', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>AWS Secret Access Key</Label>
                          <Input
                            type="password"
                            placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
                            value={settings['aws_secret_access_key'] || ''}
                            onChange={(e) => handleSettingChange('aws_secret_access_key', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>AWS Region</Label>
                          <Select
                            value={settings['aws_region'] || 'ap-south-1'}
                            onValueChange={(v) => handleSettingChange('aws_region', v)}
                          >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                              <SelectItem value="us-east-1">US East (N. Virginia)</SelectItem>
                              <SelectItem value="us-west-2">US West (Oregon)</SelectItem>
                              <SelectItem value="ap-south-1">Asia Pacific (Mumbai)</SelectItem>
                              <SelectItem value="ap-southeast-1">Asia Pacific (Singapore)</SelectItem>
                              <SelectItem value="eu-west-1">Europe (Ireland)</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                        <div className="space-y-2">
                          <Label>Platform Application ARN (iOS)</Label>
                          <Input
                            placeholder="arn:aws:sns:region:account-id:app/APNS/your-app-name"
                            value={settings['aws_sns_ios_arn'] || ''}
                            onChange={(e) => handleSettingChange('aws_sns_ios_arn', e.target.value)}
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Platform Application ARN (Android)</Label>
                          <Input
                            placeholder="arn:aws:sns:region:account-id:app/GCM/your-app-name"
                            value={settings['aws_sns_android_arn'] || ''}
                            onChange={(e) => handleSettingChange('aws_sns_android_arn', e.target.value)}
                          />
                          <p className="text-xs text-muted-foreground">
                            Get from AWS SNS Console → Mobile → Push notifications → Platform applications
                          </p>
                        </div>
                      </div>
                    </Card>
                  )}
                </CardContent>
              </Card>

              {/* Advanced Settings */}
              <Card>
                <CardHeader>
                  <CardTitle>Advanced Push Settings</CardTitle>
                  <CardDescription>Configure delivery behavior and retry logic</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Default Sound</Label>
                      <Input
                        placeholder="default"
                        value={settings['push_sound'] || 'default'}
                        onChange={(e) => handleSettingChange('push_sound', e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Time to Live (TTL) in seconds</Label>
                      <Input
                        type="number"
                        placeholder="86400"
                        value={settings['push_ttl'] || '86400'}
                        onChange={(e) => handleSettingChange('push_ttl', e.target.value)}
                      />
                      <p className="text-xs text-muted-foreground">How long to retry delivery (default: 24 hours)</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </TabsContent>
      </Tabs>

      {/* Compose Dialog */}
      <Dialog open={showComposeDialog} onOpenChange={setShowComposeDialog}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Compose Notification</DialogTitle>
            <DialogDescription>Create and send a push notification to your users</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Title *</Label>
              <Input
                placeholder="Notification title..."
                value={composeForm.title}
                onChange={(e) => setComposeForm(prev => ({ ...prev, title: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>Message *</Label>
              <Textarea
                placeholder="Notification message..."
                value={composeForm.body}
                onChange={(e) => setComposeForm(prev => ({ ...prev, body: e.target.value }))}
                rows={3}
              />
              <p className="text-xs text-muted-foreground">{composeForm.body.length}/500 characters</p>
            </div>
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label>Image URL (optional)</Label>
                <Input
                  placeholder="https://..."
                  value={composeForm.image_url}
                  onChange={(e) => setComposeForm(prev => ({ ...prev, image_url: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>Action URL (optional)</Label>
                <Input
                  placeholder="https://..."
                  value={composeForm.action_url}
                  onChange={(e) => setComposeForm(prev => ({ ...prev, action_url: e.target.value }))}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Target Audience</Label>
              <Select
                value={composeForm.target_audience}
                onValueChange={(v) => setComposeForm(prev => ({ ...prev, target_audience: v }))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {/* FIX: Defensive check for displaySegments */}
                  {(Array.isArray(displaySegments) ? displaySegments : []).map((segment: any) => (
                    <SelectItem key={segment.id} value={segment.id}>
                      {segment.name} ({segment.count?.toLocaleString()} users)
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>When to Send</Label>
              <div className="flex gap-4">
                <label className="flex items-center gap-2">
                  <input
                    type="radio"
                    name="schedule_type"
                    value="now"
                    checked={composeForm.schedule_type === "now"}
                    onChange={() => setComposeForm(prev => ({ ...prev, schedule_type: "now" }))}
                    className="h-4 w-4"
                  />
                  Send Immediately
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="radio"
                    name="schedule_type"
                    value="scheduled"
                    checked={composeForm.schedule_type === "scheduled"}
                    onChange={() => setComposeForm(prev => ({ ...prev, schedule_type: "scheduled" }))}
                    className="h-4 w-4"
                  />
                  Schedule for Later
                </label>
              </div>
            </div>
            {composeForm.schedule_type === "scheduled" && (
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label>Date</Label>
                  <Input
                    type="date"
                    value={composeForm.scheduled_date}
                    onChange={(e) => setComposeForm(prev => ({ ...prev, scheduled_date: e.target.value }))}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Time</Label>
                  <Input
                    type="time"
                    value={composeForm.scheduled_time}
                    onChange={(e) => setComposeForm(prev => ({ ...prev, scheduled_time: e.target.value }))}
                  />
                </div>
              </div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowComposeDialog(false)}>
              Cancel
            </Button>
            <Button
              onClick={handleSendNotification}
              disabled={sendNotification.isPending || scheduleNotification.isPending}
            >
              {sendNotification.isPending || scheduleNotification.isPending
                ? "Processing..."
                : composeForm.schedule_type === "scheduled"
                  ? "Schedule"
                  : "Send Now"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}