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
  ChevronRight
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
 *
 * [AUDIT FIX] Addresses gap: "No mark as read, filters, pagination"
 */

interface Notification {
  id: number;
  type: string;
  data: {
    title: string;
    message: string;
    type?: 'info' | 'success' | 'warning' | 'error';
    category?: 'payment' | 'bonus' | 'system' | 'investment' | 'kyc';
  };
  read_at: string | null;
  created_at: string;
}

export default function NotificationsPage() {
  const [currentPage, setCurrentPage] = useState(1);
  const [filter, setFilter] = useState<'all' | 'unread' | 'read'>('all');
  const queryClient = useQueryClient();

  // Fetch notifications with pagination
  const { data, isLoading } = useQuery({
    queryKey: ['notificationsFull', currentPage, filter],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        ...(filter !== 'all' && { filter }),
      });
      const response = await api.get(`/user/notifications?${params}`);
      return response.data;
    },
  });

  // Mark as read mutation
  const markAsReadMutation = useMutation({
    mutationFn: async (notificationId: number) => {
      return await api.post(`/user/notifications/${notificationId}/mark-read`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificationsFull'] });
      toast.success('Notification marked as read');
    },
    onError: () => {
      toast.error('Failed to mark notification as read');
    },
  });

  // Mark all as read mutation
  const markAllAsReadMutation = useMutation({
    mutationFn: async () => {
      return await api.post('/user/notifications/mark-all-read');
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['notificationsFull'] });
      toast.success('All notifications marked as read');
    },
    onError: () => {
      toast.error('Failed to mark all as read');
    },
  });

  // Delete notification mutation
  const deleteNotificationMutation = useMutation({
    mutationFn: async (notificationId: number) => {
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

  // Get icon based on notification category
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

  // Get badge for notification type
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

  const notifications = data?.notifications || [];
  const unreadCount = data?.unread_count || 0;
  const pagination = data?.pagination || { current_page: 1, last_page: 1, total: 0 };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-lg">Loading notifications...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Notifications</h1>
          <p className="text-muted-foreground mt-1">
            You have {unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}
          </p>
        </div>

        {unreadCount > 0 && (
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
      <Tabs value={filter} onValueChange={(v) => setFilter(v as typeof filter)}>
        <TabsList>
          <TabsTrigger value="all">
            All ({pagination.total})
          </TabsTrigger>
          <TabsTrigger value="unread">
            Unread ({unreadCount})
          </TabsTrigger>
          <TabsTrigger value="read">
            Read ({pagination.total - unreadCount})
          </TabsTrigger>
        </TabsList>

        <TabsContent value={filter} className="mt-6">
          {notifications.length === 0 ? (
            <Card>
              <CardContent className="p-12 text-center">
                <Bell className="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                <h3 className="text-lg font-semibold mb-2">No notifications</h3>
                <p className="text-muted-foreground">
                  {filter === 'unread'
                    ? "You're all caught up! No unread notifications."
                    : "You don't have any notifications yet."}
                </p>
              </CardContent>
            </Card>
          ) : (
            <Card>
              <CardContent className="p-0">
                {notifications.map((n: Notification) => (
                  <div
                    key={n.id}
                    className={cn(
                      "p-6 border-b last:border-b-0 hover:bg-gray-50/50 transition-colors",
                      !n.read_at && "bg-blue-50/30"
                    )}
                  >
                    <div className="flex gap-4">
                      {/* Icon */}
                      <div className="flex-shrink-0 mt-1">
                        {getNotificationIcon(n)}
                      </div>

                      {/* Content */}
                      <div className="flex-1 min-w-0">
                        <div className="flex justify-between items-start mb-2">
                          <div className="flex items-center gap-2">
                            <h3 className="font-semibold">{n.data.title}</h3>
                            {!n.read_at && (
                              <Badge variant="secondary" className="text-xs">New</Badge>
                            )}
                            {n.data.type && getTypeBadge(n.data.type)}
                          </div>
                          <span className="text-sm text-muted-foreground whitespace-nowrap ml-2">
                            {new Date(n.created_at).toLocaleString()}
                          </span>
                        </div>
                        <p className="text-gray-600 text-sm">{n.data.message}</p>
                      </div>

                      {/* Actions */}
                      <div className="flex flex-col gap-2 flex-shrink-0">
                        {!n.read_at && (
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => markAsReadMutation.mutate(n.id)}
                            disabled={markAsReadMutation.isPending}
                            title="Mark as read"
                          >
                            <Check className="w-4 h-4" />
                          </Button>
                        )}
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => {
                            if (confirm('Are you sure you want to delete this notification?')) {
                              deleteNotificationMutation.mutate(n.id);
                            }
                          }}
                          disabled={deleteNotificationMutation.isPending}
                          title="Delete"
                          className="text-red-600 hover:text-red-700 hover:bg-red-50"
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>

      {/* Pagination */}
      {pagination.last_page > 1 && (
        <div className="flex justify-center items-center gap-2 mt-6">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
            disabled={currentPage === 1}
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