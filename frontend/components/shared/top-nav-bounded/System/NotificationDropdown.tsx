import { Bell } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Badge } from "@/components/ui/badge";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import Link from "next/link";
import { cn } from "@/lib/utils";

interface NotificationItem {
    id: string;
    title?: string;
    data?: { title?: string; message?: string };
    message?: string;
    read_at: string | null;
    created_at: string;
}

export function NotificationDropdown() {
  const { data: rawData, isLoading } = useQuery<any>({
    queryKey: ["user-notifications"],
    queryFn: async () => {
      try {
        const res = await api.get("/user/notifications");
        return res.data;
      } catch (e) {
        return [];
      }
    },
    refetchInterval: 60000 
  });

  // [PROTOCOL 1 FIX] Normalize Laravel Response
  const notifications: NotificationItem[] = Array.isArray(rawData) 
    ? rawData 
    : Array.isArray(rawData?.data) 
        ? rawData.data 
        : [];

  const unreadCount = notifications.filter(n => !n.read_at).length;

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-600 ring-2 ring-background" />
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel className="flex justify-between">
            Notifications
            {unreadCount > 0 && <Badge variant="secondary" className="text-xs">{unreadCount} New</Badge>}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        <div className="max-h-[300px] overflow-y-auto">
            {isLoading ? (
                <div className="p-4 text-center text-sm text-muted-foreground">Checking alerts...</div>
            ) : notifications.length === 0 ? (
                <div className="p-4 text-center text-sm text-muted-foreground">No new notifications</div>
            ) : (
                notifications.slice(0, 5).map(n => {
                    const title = n.title || n.data?.title || "Notification";
                    const message = n.message || n.data?.message || "";
                    return (
                        <DropdownMenuItem key={n.id} className="flex flex-col items-start p-3 gap-1 cursor-default">
                            <div className={cn("text-sm font-medium", !n.read_at && "text-primary")}>{title}</div>
                            <div className="text-xs text-muted-foreground line-clamp-2">{message}</div>
                        </DropdownMenuItem>
                    );
                })
            )}
        </div>
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
            <Link href="/notifications" className="w-full text-center text-xs font-medium cursor-pointer">View All Activity</Link>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}