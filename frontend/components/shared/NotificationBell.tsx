// V-FINAL-1730-235
'use client';

import { Bell } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { cn } from "@/lib/utils";

export function NotificationBell() {
  const router = useRouter();
  const queryClient = useQueryClient();

  const { data } = useQuery({
    queryKey: ['notifications'],
    queryFn: async () => (await api.get('/user/notifications')).data,
    refetchInterval: 60000, // Check every minute
  });

  const readMutation = useMutation({
    mutationFn: (id: string) => api.post(`/user/notifications/${id}/read`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] })
  });

  const notifications = data?.notifications || [];
  const unreadCount = data?.unread_count || 0;

  const handleClick = (n: any) => {
    if (!n.read_at) {
      readMutation.mutate(n.id);
    }
    // If the notification has a link (e.g. data.link), go there
    if (n.data.link) {
      router.push(n.data.link);
    }
  };

  const markAllRead = () => {
    readMutation.mutate('all');
  };

  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <span className="absolute top-1 right-1 h-2.5 w-2.5 rounded-full bg-red-600 border-2 border-white" />
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-80 p-0" align="end">
        <div className="flex items-center justify-between p-4 border-b">
          <h4 className="font-semibold">Notifications</h4>
          {unreadCount > 0 && (
            <Button variant="ghost" size="sm" className="text-xs h-auto p-0" onClick={markAllRead}>
              Mark all read
            </Button>
          )}
        </div>
        <div className="max-h-[300px] overflow-y-auto">
          {notifications.length === 0 ? (
            <div className="p-4 text-center text-sm text-muted-foreground">
              No notifications
            </div>
          ) : (
            notifications.map((n: any) => (
              <div 
                key={n.id} 
                className={cn(
                  "p-4 border-b last:border-b-0 hover:bg-muted/50 cursor-pointer transition-colors",
                  !n.read_at && "bg-blue-50/50"
                )}
                onClick={() => handleClick(n)}
              >
                <div className="flex justify-between items-start gap-2">
                  <p className="text-sm font-medium leading-none">{n.data.title || 'Notification'}</p>
                  <span className="text-[10px] text-muted-foreground whitespace-nowrap">
                    {new Date(n.created_at).toLocaleDateString()}
                  </span>
                </div>
                <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                  {n.data.message}
                </p>
              </div>
            ))
          )}
        </div>
        <div className="p-2 border-t text-center">
          <Button variant="ghost" size="sm" className="w-full text-xs" onClick={() => router.push('/notifications')}>
            View All
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}