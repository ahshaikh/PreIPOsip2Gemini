// V-FINAL-1730-236
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { cn } from "@/lib/utils";

export default function NotificationsPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['notificationsFull'],
    queryFn: async () => (await api.get('/user/notifications')).data,
  });

  if (isLoading) return <div>Loading...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Notifications</h1>
      <Card>
        <CardContent className="p-0">
          {data?.notifications.map((n: any) => (
            <div key={n.id} className={cn("p-6 border-b last:border-b-0", !n.read_at && "bg-blue-50/30")}>
              <div className="flex justify-between mb-2">
                <h3 className="font-semibold">{n.data.title}</h3>
                <span className="text-sm text-muted-foreground">{new Date(n.created_at).toLocaleString()}</span>
              </div>
              <p className="text-gray-600">{n.data.message}</p>
            </div>
          ))}
        </CardContent>
      </Card>
    </div>
  );
}