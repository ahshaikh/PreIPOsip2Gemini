// V-FINAL-1730-227
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { Activity, Database, HardDrive, Server, RefreshCcw, CheckCircle, XCircle } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function SystemHealthPage() {
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['systemHealth'],
    queryFn: async () => (await api.get('/admin/system/health')).data,
    refetchInterval: 30000, // Auto-refresh every 30s
  });

  if (isLoading) return <div>Checking system vitals...</div>;

  const StatusIcon = ({ status }: { status: string }) => {
    return status === 'healthy' ? 
      <CheckCircle className="h-5 w-5 text-green-500" /> : 
      <XCircle className="h-5 w-5 text-red-500" />;
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">System Health</h1>
        <Button variant="outline" onClick={() => refetch()}>
          <RefreshCcw className="mr-2 h-4 w-4" /> Refresh
        </Button>
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Database</CardTitle>
            <Database className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2 mb-2">
              <StatusIcon status={data.database.status} />
              <span className="text-2xl font-bold capitalize">{data.database.status}</span>
            </div>
            <p className="text-xs text-muted-foreground">Latency: {data.database.latency_ms}ms</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Cache (Redis)</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2 mb-2">
              <StatusIcon status={data.cache.status} />
              <span className="text-2xl font-bold capitalize">{data.cache.status}</span>
            </div>
            <p className="text-xs text-muted-foreground">Driver: {data.cache.driver}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Queue Workers</CardTitle>
            <Server className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2 mb-2">
              <StatusIcon status={data.queue.status} />
              <span className="text-2xl font-bold capitalize">{data.queue.status}</span>
            </div>
            <p className="text-xs text-muted-foreground">Backlog: {data.queue.pending_jobs} jobs</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Storage</CardTitle>
            <HardDrive className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2 mb-2">
              <StatusIcon status={data.storage.status} />
              <span className="text-2xl font-bold">{data.storage.usage_percent}% Used</span>
            </div>
            <p className="text-xs text-muted-foreground">{data.storage.free_gb} GB Free</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div><strong>Server Time:</strong> {data.server_time}</div>
            <div><strong>PHP Version:</strong> {data.php_version}</div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}