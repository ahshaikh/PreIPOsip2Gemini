// V-FINAL-1730-227 | V-ENHANCED-HEALTH
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Activity, Database, HardDrive, Server, RefreshCcw, CheckCircle, XCircle, AlertTriangle, Cpu, MemoryStick, Clock, Wifi, Mail, Shield, Trash2, Loader2, Globe, Zap } from "lucide-react";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import { useState } from "react";

export default function SystemHealthPage() {
  const queryClient = useQueryClient();
  const [isClearing, setIsClearing] = useState<string | null>(null);

  const { data, isLoading, refetch, isFetching } = useQuery({
    queryKey: ['systemHealth'],
    queryFn: async () => (await api.get('/admin/system/health')).data,
    refetchInterval: 30000,
  });

  const clearCacheMutation = useMutation({
    mutationFn: (type: string) => api.post(`/admin/system/clear-cache`, { type }),
    onSuccess: (_, type) => {
      toast.success(`${type} cache cleared successfully`);
      queryClient.invalidateQueries({ queryKey: ['systemHealth'] });
      setIsClearing(null);
    },
    onError: (e: any) => {
      toast.error("Failed to clear cache", { description: e.response?.data?.message });
      setIsClearing(null);
    }
  });

  const runMaintenanceMutation = useMutation({
    mutationFn: (task: string) => api.post(`/admin/system/maintenance`, { task }),
    onSuccess: (_, task) => {
      toast.success(`${task} completed successfully`);
      queryClient.invalidateQueries({ queryKey: ['systemHealth'] });
    },
    onError: (e: any) => toast.error("Maintenance task failed", { description: e.response?.data?.message })
  });

  if (isLoading) return (
    <div className="flex items-center justify-center h-64">
      <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      <span className="ml-2">Checking system vitals...</span>
    </div>
  );

  const StatusIcon = ({ status }: { status: string }) => {
    if (status === 'healthy') return <CheckCircle className="h-5 w-5 text-green-500" />;
    if (status === 'warning') return <AlertTriangle className="h-5 w-5 text-yellow-500" />;
    return <XCircle className="h-5 w-5 text-red-500" />;
  };

  const getStatusColor = (status: string) => {
    if (status === 'healthy') return 'bg-green-100 text-green-800';
    if (status === 'warning') return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
  };

  const getProgressColor = (percent: number) => {
    if (percent < 60) return 'bg-green-500';
    if (percent < 80) return 'bg-yellow-500';
    return 'bg-red-500';
  };

  // Calculate overall health score
  const healthScore = data ? Math.round(
    ((data.database?.status === 'healthy' ? 25 : 0) +
    (data.cache?.status === 'healthy' ? 25 : 0) +
    (data.queue?.status === 'healthy' ? 25 : 0) +
    (data.storage?.status === 'healthy' ? 25 : data.storage?.usage_percent < 80 ? 15 : 0))
  ) : 0;

  // Check for any critical issues
  const criticalIssues = [];
  if (data?.database?.status !== 'healthy') criticalIssues.push('Database connection issues detected');
  if (data?.cache?.status !== 'healthy') criticalIssues.push('Cache service is not responding');
  if (data?.queue?.status !== 'healthy') criticalIssues.push('Queue workers may be down');
  if (data?.storage?.usage_percent > 90) criticalIssues.push('Storage is critically low');

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">System Health</h1>
          <p className="text-muted-foreground">Monitor your system's performance and status.</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="outline" className="text-xs">
            Auto-refresh: 30s
          </Badge>
          <Button variant="outline" onClick={() => refetch()} disabled={isFetching}>
            <RefreshCcw className={`mr-2 h-4 w-4 ${isFetching ? 'animate-spin' : ''}`} />
            {isFetching ? 'Refreshing...' : 'Refresh'}
          </Button>
        </div>
      </div>

      {/* Critical Alerts */}
      {criticalIssues.length > 0 && (
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertTitle>System Issues Detected</AlertTitle>
          <AlertDescription>
            <ul className="list-disc list-inside mt-2">
              {criticalIssues.map((issue, i) => (
                <li key={i}>{issue}</li>
              ))}
            </ul>
          </AlertDescription>
        </Alert>
      )}

      {/* Overall Health Score */}
      <Card>
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <CardTitle>Overall System Health</CardTitle>
            <Badge className={getStatusColor(healthScore >= 75 ? 'healthy' : healthScore >= 50 ? 'warning' : 'critical')}>
              {healthScore}% Healthy
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <Progress value={healthScore} className="h-3" />
          <p className="text-xs text-muted-foreground mt-2">
            Last checked: {data?.server_time || 'Unknown'}
          </p>
        </CardContent>
      </Card>

      <Tabs defaultValue="services">
        <TabsList>
          <TabsTrigger value="services">Services</TabsTrigger>
          <TabsTrigger value="resources">Resources</TabsTrigger>
          <TabsTrigger value="maintenance">Maintenance</TabsTrigger>
        </TabsList>

        {/* Services Tab */}
        <TabsContent value="services" className="space-y-4">
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">Database</CardTitle>
                <Database className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2 mb-2">
                  <StatusIcon status={data?.database?.status || 'unknown'} />
                  <span className="text-2xl font-bold capitalize">{data?.database?.status || 'Unknown'}</span>
                </div>
                <div className="space-y-1 text-xs text-muted-foreground">
                  <div className="flex justify-between">
                    <span>Latency:</span>
                    <span className="font-medium">{data?.database?.latency_ms || 0}ms</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Connections:</span>
                    <span className="font-medium">{data?.database?.connections || 0}</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">Cache (Redis)</CardTitle>
                <Activity className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2 mb-2">
                  <StatusIcon status={data?.cache?.status || 'unknown'} />
                  <span className="text-2xl font-bold capitalize">{data?.cache?.status || 'Unknown'}</span>
                </div>
                <div className="space-y-1 text-xs text-muted-foreground">
                  <div className="flex justify-between">
                    <span>Driver:</span>
                    <span className="font-medium">{data?.cache?.driver || 'N/A'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Hit Rate:</span>
                    <span className="font-medium">{data?.cache?.hit_rate || 0}%</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">Queue Workers</CardTitle>
                <Server className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2 mb-2">
                  <StatusIcon status={data?.queue?.status || 'unknown'} />
                  <span className="text-2xl font-bold capitalize">{data?.queue?.status || 'Unknown'}</span>
                </div>
                <div className="space-y-1 text-xs text-muted-foreground">
                  <div className="flex justify-between">
                    <span>Pending Jobs:</span>
                    <span className="font-medium">{data?.queue?.pending_jobs || 0}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Failed Jobs:</span>
                    <span className={`font-medium ${(data?.queue?.failed_jobs || 0) > 0 ? 'text-red-500' : ''}`}>
                      {data?.queue?.failed_jobs || 0}
                    </span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">Mail Service</CardTitle>
                <Mail className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2 mb-2">
                  <StatusIcon status={data?.mail?.status || 'healthy'} />
                  <span className="text-2xl font-bold capitalize">{data?.mail?.status || 'Healthy'}</span>
                </div>
                <div className="space-y-1 text-xs text-muted-foreground">
                  <div className="flex justify-between">
                    <span>Driver:</span>
                    <span className="font-medium">{data?.mail?.driver || 'SMTP'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Queued:</span>
                    <span className="font-medium">{data?.mail?.queued || 0}</span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Resources Tab */}
        <TabsContent value="resources" className="space-y-4">
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">Storage</CardTitle>
                <HardDrive className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-2xl font-bold">{data?.storage?.usage_percent || 0}%</span>
                    <StatusIcon status={data?.storage?.status || 'unknown'} />
                  </div>
                  <Progress value={data?.storage?.usage_percent || 0} className="h-2" />
                  <div className="flex justify-between text-xs text-muted-foreground">
                    <span>Used: {data?.storage?.used_gb || 0} GB</span>
                    <span>Free: {data?.storage?.free_gb || 0} GB</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">Memory</CardTitle>
                <MemoryStick className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-2xl font-bold">{data?.memory?.usage_percent || 0}%</span>
                    <StatusIcon status={data?.memory?.usage_percent < 80 ? 'healthy' : 'warning'} />
                  </div>
                  <Progress value={data?.memory?.usage_percent || 0} className="h-2" />
                  <div className="flex justify-between text-xs text-muted-foreground">
                    <span>Used: {data?.memory?.used_mb || 0} MB</span>
                    <span>Limit: {data?.memory?.limit_mb || 0} MB</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium">CPU Load</CardTitle>
                <Cpu className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-2xl font-bold">{data?.cpu?.load_percent || 0}%</span>
                    <StatusIcon status={data?.cpu?.load_percent < 70 ? 'healthy' : 'warning'} />
                  </div>
                  <Progress value={data?.cpu?.load_percent || 0} className="h-2" />
                  <p className="text-xs text-muted-foreground">
                    Load avg: {data?.cpu?.load_avg || '0.0, 0.0, 0.0'}
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* System Info */}
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-medium">System Information</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div className="flex items-center gap-2">
                  <Clock className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-muted-foreground">Server Time</p>
                    <p className="font-medium">{data?.server_time || 'Unknown'}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Zap className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-muted-foreground">PHP Version</p>
                    <p className="font-medium">{data?.php_version || 'Unknown'}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Globe className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-muted-foreground">Laravel Version</p>
                    <p className="font-medium">{data?.laravel_version || 'Unknown'}</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Shield className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-muted-foreground">Environment</p>
                    <p className="font-medium capitalize">{data?.environment || 'Unknown'}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Maintenance Tab */}
        <TabsContent value="maintenance" className="space-y-4">
          <div className="grid md:grid-cols-2 gap-4">
            <Card>
              <CardHeader>
                <CardTitle className="text-sm font-medium">Cache Management</CardTitle>
                <CardDescription>Clear various system caches</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {['application', 'config', 'route', 'view'].map(type => (
                  <div key={type} className="flex items-center justify-between p-3 border rounded-lg">
                    <div>
                      <p className="font-medium capitalize">{type} Cache</p>
                      <p className="text-xs text-muted-foreground">Clear {type} cached data</p>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => { setIsClearing(type); clearCacheMutation.mutate(type); }}
                      disabled={isClearing === type}
                    >
                      {isClearing === type ? <Loader2 className="h-4 w-4 animate-spin" /> : <Trash2 className="h-4 w-4" />}
                    </Button>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-sm font-medium">Maintenance Tasks</CardTitle>
                <CardDescription>Run system maintenance operations</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p className="font-medium">Optimize Database</p>
                    <p className="text-xs text-muted-foreground">Run database optimization queries</p>
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => runMaintenanceMutation.mutate('optimize-db')}
                    disabled={runMaintenanceMutation.isPending}
                  >
                    Run
                  </Button>
                </div>
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p className="font-medium">Clear Failed Jobs</p>
                    <p className="text-xs text-muted-foreground">Remove all failed queue jobs</p>
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => runMaintenanceMutation.mutate('clear-failed-jobs')}
                    disabled={runMaintenanceMutation.isPending}
                  >
                    Clear
                  </Button>
                </div>
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p className="font-medium">Prune Old Logs</p>
                    <p className="text-xs text-muted-foreground">Delete logs older than 30 days</p>
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => runMaintenanceMutation.mutate('prune-logs')}
                    disabled={runMaintenanceMutation.isPending}
                  >
                    Prune
                  </Button>
                </div>
                <div className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p className="font-medium">Storage Link</p>
                    <p className="text-xs text-muted-foreground">Recreate storage symbolic link</p>
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => runMaintenanceMutation.mutate('storage-link')}
                    disabled={runMaintenanceMutation.isPending}
                  >
                    Link
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}