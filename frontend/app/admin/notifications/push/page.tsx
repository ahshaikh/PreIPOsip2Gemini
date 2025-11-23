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
  Zap
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

    const payload = {
      title: composeForm.title,
      body: composeForm.body,
      image_url: composeForm.image_url || null,
      action_url: composeForm.action_url || null,
      target_audience: composeForm.target_audience,
      target_users: composeForm.target_audience === "specific" ? composeForm.target_users : null
    };

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
    const template = (templates || mockTemplates).find((t: NotificationTemplate) => t.id === templateId);
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

  // Mock data for demonstration
  const mockStats = {
    total_sent: 1250,
    total_delivered: 1180,
    total_opened: 890,
    total_clicked: 456,
    delivery_rate: 94.4,
    open_rate: 75.4,
    click_rate: 38.6,
    subscribers: 2500,
    active_devices: 2340
  };

  const mockNotifications: PushNotification[] = [
    {
      id: 1,
      title: "New Investment Opportunity!",
      body: "SpaceX Series F round now available. Invest from ₹5,000.",
      target_audience: "all",
      sent_at: "2024-03-15 10:30:00",
      status: "sent",
      total_recipients: 2500,
      delivered: 2380,
      opened: 1850,
      clicked: 920,
      created_at: "2024-03-15"
    },
    {
      id: 2,
      title: "KYC Reminder",
      body: "Complete your KYC to start investing. Only takes 2 minutes!",
      target_audience: "incomplete_kyc",
      sent_at: "2024-03-14 09:00:00",
      status: "sent",
      total_recipients: 450,
      delivered: 420,
      opened: 280,
      clicked: 145,
      created_at: "2024-03-14"
    },
    {
      id: 3,
      title: "Weekend Bonus Offer",
      body: "Get 2x referral bonus this weekend only! Share now.",
      target_audience: "all",
      scheduled_at: "2024-03-20 08:00:00",
      status: "scheduled",
      total_recipients: 2500,
      delivered: 0,
      opened: 0,
      clicked: 0,
      created_at: "2024-03-13"
    }
  ];

  const mockTemplates: NotificationTemplate[] = [
    { id: 1, name: "New IPO Alert", title: "New Investment Opportunity!", body: "{{company_name}} is now available for investment. Starting at ₹{{min_amount}}.", category: "investment" },
    { id: 2, name: "KYC Reminder", title: "Complete Your KYC", body: "Hi {{user_name}}, complete your KYC verification to start investing.", category: "kyc" },
    { id: 3, name: "Payment Success", title: "Payment Successful!", body: "Your payment of ₹{{amount}} has been received. Happy investing!", category: "payment" },
    { id: 4, name: "Referral Bonus", title: "You've Earned a Bonus!", body: "Congratulations! You've earned ₹{{amount}} as referral bonus.", category: "bonus" }
  ];

  const mockSegments = [
    { id: "all", name: "All Users", count: 2500 },
    { id: "active", name: "Active Investors", count: 1800 },
    { id: "inactive", name: "Inactive Users (30+ days)", count: 400 },
    { id: "incomplete_kyc", name: "Incomplete KYC", count: 450 },
    { id: "high_value", name: "High Value Investors (₹1L+)", count: 320 },
    { id: "new_users", name: "New Users (Last 7 days)", count: 85 }
  ];

  const displayStats = stats || mockStats;
  const displayNotifications = notifications || mockNotifications;
  const displayTemplates = templates || mockTemplates;
  const displaySegments = segments || mockSegments;

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
                      {displaySegments.map((segment: any) => (
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
                  {displayTemplates.slice(0, 4).map((template: NotificationTemplate) => (
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
                  {displayNotifications.slice(0, 5).map((notification: PushNotification) => (
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
                  {displayNotifications.map((notification: PushNotification) => (
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
              {displayNotifications.filter((n: PushNotification) => n.status === "scheduled").length === 0 ? (
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
                    {displayNotifications
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
                {displayTemplates.map((template: NotificationTemplate) => (
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
                {displaySegments.map((segment: any) => (
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
                  {displayNotifications
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
                  {displaySegments.map((segment: any) => (
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
