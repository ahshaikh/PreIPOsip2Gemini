// V-FINAL-1730-236 | V-AUDIT-FIX-ENHANCEMENT (Enhanced with filters, mark as read, pagination)
'use client';

import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { cn } from "@/lib/utils";
import {
  Bell,
  Check,
  CheckCheck,
  Trash2,
  Info,
  AlertCircle,
  CheckCircle,
  XCircle,
  DollarSign,
  Gift,
  TrendingUp,
  FileText,
  ChevronLeft,
  ChevronRight,
  Loader2
} from "lucide-react";
import { toast } from "sonner";

/**
 * Enhanced Notifications Page
 *
 * New Features:
 * - Mark as read/unread functionality
 * - Mark all as read
 * - Filter by status (all, unread, read)
 * - Delete notifications
 * - Pagination
 * - Category-based icons
 * - Better visual hierarchy
 */

interface Notification {
  id: number | string;
  type: string;
  data: {
    title?: string;
    message?: string;
    type?: 'info' | 'success' | 'warning' | 'error';
    category?: 'payment' | 'bonus' | 'system' | 'investment' | 'kyc';
  };
  read_at: string | null;
  created_at: string;
}

interface NotificationResponse {
    data: Notification[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    counts: { // [PROTOCOL 1] Explicit Server Counts
        all: number;
        unread: number;
        read: number;
    };
}

export default function NotificationsPage() {
  const [currentPage, setCurrentPage] = useState(1);
  const [filter, setFilter] = useState<'all' | 'unread' | 'read'>('all');
  const queryClient = useQueryClient();

  // Fetch notifications with pagination
  const { data: response, isLoading, isError } = useQuery<NotificationResponse>({
    queryKey: ['notificationsFull', currentPage, filter],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '10',
        ...(filter !== 'all' && { filter }),
      });
      const res = await api.get(`/user/notifications?${params}`);
      return res.data;
    },
    // Keep previous data while fetching new page for smoother transition
    placeholderData: (previousData) => previousData,
  });

  // Mutation: Mark as Read
  const markAsReadMutation = useMutation({
    mutationFn: async (notificationId: number | string) => {
      return await api.post(`/user/notifications/${notificationId}/read`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificationsFull'] });
      queryClient.invalidateQueries({ queryKey: ['user-notifications'] }); // Update TopNav bell
      toast.success('Notification marked as read');
    },
    onError: () => {
      toast.error('Failed to mark notification as read');
    },
  });

  // Mutation: Mark All as Read
  const markAllAsReadMutation = useMutation({
    mutationFn: async () => {
      return await api.post('/user/notifications/mark-all-read');
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificationsFull'] });
      queryClient.invalidateQueries({ queryKey: ['user-notifications'] });
      toast.success('All notifications marked as read');
    },
    onError: () => {
      toast.error('Failed to mark all as read');
    },
  });

  // Mutation: Delete
  const deleteNotificationMutation = useMutation({
    mutationFn: async (notificationId: number | string) => {
      return await api.delete(`/user/notifications/${notificationId}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificationsFull'] });
      toast.success('Notification deleted');
    },
    onError: () => {
      toast.error('Failed to delete notification');
    },
  });

  // Icon Helper
  const getNotificationIcon = (notification: Notification) => {
    const category = notification.data.category || 'system';
    const type = notification.data.type || 'info';

    const iconMap: Record<string, any> = {
      payment: DollarSign,
      bonus: Gift,
      investment: TrendingUp,
      kyc: FileText,
      system: Bell,
    };

    const Icon = iconMap[category] || Bell;

    const colorMap: Record<string, string> = {
      info: 'text-blue-600',
      success: 'text-green-600',
      warning: 'text-yellow-600',
      error: 'text-red-600',
    };

    return <Icon className={cn('w-5 h-5', colorMap[type])} />;
  };

  // Badge Helper
  const getTypeBadge = (type?: string) => {
    const badgeMap: Record<string, { variant: any; label: string }> = {
      info: { variant: 'outline' as const, label: 'Info' },
      success: { variant: 'default' as const, label: 'Success' },
      warning: { variant: 'destructive' as const, label: 'Warning' },
      error: { variant: 'destructive' as const, label: 'Error' },
    };

    const badge = badgeMap[type || 'info'];
    return <Badge variant={badge.variant} className="text-xs">{badge.label}</Badge>;
  };

  // [PROTOCOL 1 FIX] Defensive Adapter Pattern
  // Ensure 'notifications' is ALWAYS an array to prevent "map is not a function" crashes
  const rawNotifications = response?.data;
  const notifications: Notification[] = Array.isArray(rawNotifications) 
      ? rawNotifications 
      : (Array.isArray((rawNotifications as any)?.data) ? (rawNotifications as any).data : []);

  // [PROTOCOL 1 FIX] Use Server Counts (Authority)
  const counts = response?.counts || { all: 0, unread: 0, read: 0 };
  const pagination = response?.meta || { current_page: 1, last_page: 1, total: 0 };

  if (isLoading && !response) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="flex flex-col items-center gap-2 text-muted-foreground">
             <Loader2 className="h-8 w-8 animate-spin text-primary" />
             <p>Loading notifications...</p>
        </div>
      </div>
    );
  }

  if (isError) {
      return (
          <div className="flex flex-col items-center justify-center h-96 text-muted-foreground">
              <AlertCircle className="h-12 w-12 mb-4 text-destructive/50" />
              <p>Unable to load notifications.</p>
              <Button variant="link" onClick={() => queryClient.invalidateQueries({ queryKey: ['notificationsFull'] })}>
                  Try Again
              </Button>
          </div>
      );
  }

  return (
    <div className="space-y-6 container py-8 max-w-5xl">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Notifications</h1>
          <p className="text-muted-foreground mt-1">
             Manage your alerts and account updates.
          </p>
        </div>

        {counts.unread > 0 && (
          <Button
            onClick={() => markAllAsReadMutation.mutate()}
            disabled={markAllAsReadMutation.isPending}
            variant="outline"
          >
            <CheckCheck className="w-4 h-4 mr-2" />
            Mark All as Read
          </Button>
        )}
      </div>

      {/* Filters */}
      <Tabs value={filter} onValueChange={(v) => { setFilter(v as typeof filter); setCurrentPage(1); }}>
        <TabsList className="grid w-full sm:w-[400px] grid-cols-3">
          <TabsTrigger value="all">
            All <Badge variant="secondary" className="ml-2">{counts.all}</Badge>
          </TabsTrigger>
          <TabsTrigger value="unread">
            Unread 
            {counts.unread > 0 && <Badge variant="destructive" className="ml-2">{counts.unread}</Badge>}
          </TabsTrigger>
          <TabsTrigger value="read">
            Read <Badge variant="outline" className="ml-2">{counts.read}</Badge>
          </TabsTrigger>
        </TabsList>

        <div className="mt-6">
          {notifications.length === 0 ? (
            <Card className="bg-muted/50 border-dashed">
              <CardContent className="p-12 text-center">
                <div className="mx-auto bg-background rounded-full p-4 w-16 h-16 flex items-center justify-center mb-4 shadow-sm">
                    <Bell className="w-8 h-8 text-muted-foreground/50" />
                </div>
                <h3 className="text-lg font-semibold mb-2">No notifications found</h3>
                <p className="text-muted-foreground max-w-sm mx-auto">
                  {filter === 'unread'
                    ? "You're all caught up! Check the 'All' tab for past activity."
                    : "We'll notify you when something important happens with your account."}
                </p>
              </CardContent>
            </Card>
          ) : (
            <div className="space-y-4">
                {notifications.map((n: Notification) => (
                  <Card 
                    key={n.id}
                    className={cn(
                      "transition-all hover:shadow-md border-l-4",
                      !n.read_at ? "bg-card border-l-primary shadow-sm" : "bg-muted/10 border-l-transparent opacity-80 hover:opacity-100"
                    )}
                  >
                    <CardContent className="p-5">
                        <div className="flex gap-4">
                            {/* Icon Column */}
                            <div className="flex-shrink-0 pt-1">
                                <div className={cn(
                                    "p-2.5 rounded-full shadow-sm",
                                    !n.read_at ? "bg-primary/10 text-primary" : "bg-muted text-muted-foreground"
                                )}>
                                    {getNotificationIcon(n)}
                                </div>
                            </div>

                            {/* Content Column */}
                            <div className="flex-1 min-w-0 space-y-1.5">
                                <div className="flex flex-wrap justify-between items-start gap-2">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <h3 className={cn("font-semibold text-base", !n.read_at ? "text-foreground" : "text-muted-foreground")}>
                                            {n.data.title || "Notification"}
                                        </h3>
                                        {!n.read_at && <Badge className="h-5 px-1.5 text-[10px] bg-blue-600 hover:bg-blue-700">NEW</Badge>}
                                        {n.data.type && getTypeBadge(n.data.type)}
                                    </div>
                                    <span className="text-xs text-muted-foreground whitespace-nowrap">
                                        {new Date(n.created_at).toLocaleString(undefined, { 
                                            month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' 
                                        })}
                                    </span>
                                </div>
                                
                                <p className="text-sm text-muted-foreground leading-relaxed line-clamp-2">
                                    {n.data.message}
                                </p>
                            </div>

                            {/* Actions Column */}
                            <div className="flex flex-col gap-1 flex-shrink-0 self-start sm:self-center pl-2 border-l sm:border-l-0 sm:pl-0">
                                {!n.read_at && (
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        className="h-8 w-8 text-primary hover:text-primary hover:bg-primary/10"
                                        onClick={() => markAsReadMutation.mutate(n.id)}
                                        disabled={markAsReadMutation.isPending}
                                        title="Mark as read"
                                    >
                                        <Check className="w-4 h-4" />
                                    </Button>
                                )}
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-8 w-8 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                    onClick={() => {
                                        if (confirm('Delete this notification?')) {
                                            deleteNotificationMutation.mutate(n.id);
                                        }
                                    }}
                                    disabled={deleteNotificationMutation.isPending}
                                    title="Delete"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                  </Card>
                ))}
            </div>
          )}
        </div>
      </Tabs>

      {/* Pagination */}
      {pagination.last_page > 1 && (
        <div className="flex justify-center items-center gap-4 mt-8 pt-4 border-t">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
            disabled={currentPage === 1}
          >
            <ChevronLeft className="w-4 h-4 mr-1" />
            Previous
          </Button>

          <span className="text-sm text-muted-foreground font-medium">
            Page {pagination.current_page} of {pagination.last_page}
          </span>

          <Button
            variant="outline"
            size="sm"
            onClick={() => setCurrentPage(p => Math.min(pagination.last_page, p + 1))}
            disabled={currentPage === pagination.last_page}
          >
            Next
            <ChevronRight className="w-4 h-4 ml-1" />
          </Button>
        </div>
      )}
    </div>
  );
}