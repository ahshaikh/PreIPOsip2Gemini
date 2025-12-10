// V-SYSTEM-CONFIG-002 (Cron Job Management)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Input } from "@/components/ui/input";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import api from "@/lib/api";
import { Clock, Play, Edit, Trash2, Plus, RefreshCw, AlertCircle, CheckCircle2, XCircle, Info } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { formatDistanceToNow } from "date-fns";

interface SystemCronJob {
  id: string;
  name: string;
  command: string;
  expression: string;
  description: string;
  schedule: string;
  is_system: boolean;
}

interface ScheduledTask {
  id: number;
  name: string;
  command: string;
  expression: string;
  description?: string;
  parameters?: any;
  is_active: boolean;
  last_run_at?: string;
  last_run_status?: string;
  last_run_output?: string;
  last_run_duration?: number;
  next_run_at?: string;
  run_count: number;
  failure_count: number;
  created_by?: number;
  creator?: { username: string };
}

export default function CronJobsPage() {
  const queryClient = useQueryClient();
  const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
  const [editingTask, setEditingTask] = useState<ScheduledTask | null>(null);
  const [newTask, setNewTask] = useState({
    name: '',
    command: '',
    expression: '',
    description: '',
    is_active: true,
  });

  const { data: systemJobsData, isLoading: systemLoading } = useQuery({
    queryKey: ['systemCronJobs'],
    queryFn: async () => {
      const res = await api.get('/admin/developer/system-cron-jobs');
      return res.data;
    },
  });

  const { data: customTasksData, isLoading: customLoading, refetch: refetchCustom } = useQuery({
    queryKey: ['scheduledTasks'],
    queryFn: async () => {
      const res = await api.get('/admin/developer/tasks');
      return res.data;
    },
  });

  const createTaskMutation = useMutation({
    mutationFn: async (task: any) => {
      await api.post('/admin/developer/tasks', task);
    },
    onSuccess: () => {
      toast.success("Cron job created");
      setIsCreateDialogOpen(false);
      setNewTask({ name: '', command: '', expression: '', description: '', is_active: true });
      refetchCustom();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.error || "Failed to create cron job");
    },
  });

  const updateTaskMutation = useMutation({
    mutationFn: async ({ id, ...data }: any) => {
      await api.put(`/admin/developer/tasks/${id}`, data);
    },
    onSuccess: () => {
      toast.success("Cron job updated");
      setEditingTask(null);
      refetchCustom();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.error || "Failed to update cron job");
    },
  });

  const runTaskMutation = useMutation({
    mutationFn: async (taskId: number) => {
      await api.post(`/admin/developer/tasks/${taskId}/run`);
    },
    onSuccess: () => {
      toast.success("Task executed successfully");
      refetchCustom();
    },
    onError: () => {
      toast.error("Task execution failed");
    },
  });

  const deleteTaskMutation = useMutation({
    mutationFn: async (taskId: number) => {
      await api.delete(`/admin/developer/tasks/${taskId}`);
    },
    onSuccess: () => {
      toast.success("Cron job deleted");
      refetchCustom();
    },
    onError: () => {
      toast.error("Failed to delete cron job");
    },
  });

  const toggleTaskMutation = useMutation({
    mutationFn: async ({ id, is_active }: { id: number; is_active: boolean }) => {
      await api.put(`/admin/developer/tasks/${id}`, { is_active });
    },
    onSuccess: () => {
      toast.success("Cron job updated");
      refetchCustom();
    },
    onError: () => {
      toast.error("Failed to update cron job");
    },
  });

  const handleCreate = () => {
    createTaskMutation.mutate(newTask);
  };

  const handleUpdate = () => {
    if (editingTask) {
      updateTaskMutation.mutate({ id: editingTask.id, ...newTask });
    }
  };

  const systemJobs: SystemCronJob[] = systemJobsData?.jobs || [];
  const customTasks: ScheduledTask[] = customTasksData?.tasks || [];

  if (systemLoading || customLoading) {
    return <div className="flex items-center justify-center p-8"><RefreshCw className="h-6 w-6 animate-spin mr-2" /> Loading...</div>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Cron Job Management</h1>
          <p className="text-muted-foreground">Manage scheduled tasks and system cron jobs</p>
        </div>
        <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Create Custom Cron Job
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create Custom Cron Job</DialogTitle>
              <DialogDescription>Add a new scheduled task to run automatically</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Name</Label>
                <Input
                  value={newTask.name}
                  onChange={(e) => setNewTask({ ...newTask, name: e.target.value })}
                  placeholder="e.g., Daily Report Generation"
                />
              </div>
              <div className="space-y-2">
                <Label>Command</Label>
                <Input
                  value={newTask.command}
                  onChange={(e) => setNewTask({ ...newTask, command: e.target.value })}
                  placeholder="e.g., report:generate"
                />
                <p className="text-sm text-muted-foreground">Artisan command or class name</p>
              </div>
              <div className="space-y-2">
                <Label>Cron Expression</Label>
                <Input
                  value={newTask.expression}
                  onChange={(e) => setNewTask({ ...newTask, expression: e.target.value })}
                  placeholder="e.g., 0 2 * * * (daily at 2 AM)"
                />
                <p className="text-sm text-muted-foreground">
                  Format: minute hour day month weekday (e.g., "0 2 * * *" = daily at 2 AM)
                </p>
              </div>
              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea
                  value={newTask.description}
                  onChange={(e) => setNewTask({ ...newTask, description: e.target.value })}
                  placeholder="Optional description"
                  rows={3}
                />
              </div>
              <div className="flex items-center space-x-2">
                <Switch
                  checked={newTask.is_active}
                  onCheckedChange={(checked) => setNewTask({ ...newTask, is_active: checked })}
                />
                <Label>Enable immediately</Label>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsCreateDialogOpen(false)}>Cancel</Button>
              <Button onClick={handleCreate} disabled={createTaskMutation.isPending}>
                {createTaskMutation.isPending ? "Creating..." : "Create"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      {/* System Cron Jobs */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            System Cron Jobs
          </CardTitle>
          <CardDescription>Pre-configured system tasks (cannot be modified)</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Command</TableHead>
                <TableHead>Schedule</TableHead>
                <TableHead>Description</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {systemJobs.map((job) => (
                <TableRow key={job.id}>
                  <TableCell className="font-medium">{job.name}</TableCell>
                  <TableCell className="font-mono text-sm">{job.command}</TableCell>
                  <TableCell>
                    <Badge variant="outline">{job.schedule}</Badge>
                  </TableCell>
                  <TableCell className="text-muted-foreground">{job.description}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Custom Scheduled Tasks */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            Custom Scheduled Tasks
          </CardTitle>
          <CardDescription>User-created cron jobs that can be managed</CardDescription>
        </CardHeader>
        <CardContent>
          {customTasks.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <Clock className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No custom cron jobs found. Create one to get started.</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Command</TableHead>
                  <TableHead>Schedule</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Last Run</TableHead>
                  <TableHead>Next Run</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {customTasks.map((task) => (
                  <TableRow key={task.id}>
                    <TableCell className="font-medium">{task.name}</TableCell>
                    <TableCell className="font-mono text-sm">{task.command}</TableCell>
                    <TableCell>
                      <Badge variant="outline" className="font-mono text-xs">{task.expression}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Switch
                          checked={task.is_active}
                          onCheckedChange={(checked) => {
                            toggleTaskMutation.mutate({ id: task.id, is_active: checked });
                          }}
                        />
                        {task.is_active ? (
                          <Badge variant="default" className="bg-green-500">Active</Badge>
                        ) : (
                          <Badge variant="secondary">Disabled</Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      {task.last_run_at ? (
                        <div className="flex items-center gap-2">
                          {task.last_run_status === 'success' ? (
                            <CheckCircle2 className="h-4 w-4 text-green-500" />
                          ) : task.last_run_status === 'failed' ? (
                            <XCircle className="h-4 w-4 text-red-500" />
                          ) : (
                            <AlertCircle className="h-4 w-4 text-yellow-500" />
                          )}
                          <span className="text-sm">
                            {formatDistanceToNow(new Date(task.last_run_at), { addSuffix: true })}
                          </span>
                        </div>
                      ) : (
                        <span className="text-muted-foreground text-sm">Never</span>
                      )}
                    </TableCell>
                    <TableCell>
                      {task.next_run_at ? (
                        <span className="text-sm">
                          {formatDistanceToNow(new Date(task.next_run_at), { addSuffix: true })}
                        </span>
                      ) : (
                        <span className="text-muted-foreground text-sm">-</span>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            setEditingTask(task);
                            setNewTask({
                              name: task.name,
                              command: task.command,
                              expression: task.expression,
                              description: task.description || '',
                              is_active: task.is_active,
                            });
                          }}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => runTaskMutation.mutate(task.id)}
                          disabled={runTaskMutation.isPending}
                        >
                          <Play className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            if (confirm(`Delete cron job "${task.name}"?`)) {
                              deleteTaskMutation.mutate(task.id);
                            }
                          }}
                        >
                          <Trash2 className="h-4 w-4" />
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

      {/* Edit Dialog */}
      {editingTask && (
        <Dialog open={!!editingTask} onOpenChange={(open) => !open && setEditingTask(null)}>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Edit Cron Job</DialogTitle>
              <DialogDescription>Update scheduled task configuration</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Name</Label>
                <Input
                  value={newTask.name}
                  onChange={(e) => setNewTask({ ...newTask, name: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label>Command</Label>
                <Input
                  value={newTask.command}
                  onChange={(e) => setNewTask({ ...newTask, command: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label>Cron Expression</Label>
                <Input
                  value={newTask.expression}
                  onChange={(e) => setNewTask({ ...newTask, expression: e.target.value })}
                />
              </div>
              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea
                  value={newTask.description}
                  onChange={(e) => setNewTask({ ...newTask, description: e.target.value })}
                  rows={3}
                />
              </div>
              <div className="flex items-center space-x-2">
                <Switch
                  checked={newTask.is_active}
                  onCheckedChange={(checked) => setNewTask({ ...newTask, is_active: checked })}
                />
                <Label>Enabled</Label>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditingTask(null)}>Cancel</Button>
              <Button onClick={handleUpdate} disabled={updateTaskMutation.isPending}>
                {updateTaskMutation.isPending ? "Updating..." : "Update"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}

      {/* Info Card */}
      <Card className="border-blue-200 bg-blue-50 dark:bg-blue-950/20">
        <CardContent className="pt-6">
          <div className="flex gap-3">
            <Info className="h-5 w-5 text-blue-600 mt-0.5" />
            <div className="space-y-2">
              <h4 className="font-medium text-blue-900 dark:text-blue-100">Cron Expression Format</h4>
              <p className="text-sm text-blue-800 dark:text-blue-200">
                Cron expressions use 5 fields: <code className="bg-blue-100 dark:bg-blue-900 px-1 rounded">minute hour day month weekday</code>
              </p>
              <ul className="text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                <li><code>0 2 * * *</code> - Daily at 2:00 AM</li>
                <li><code>0 */6 * * *</code> - Every 6 hours</li>
                <li><code>0 0 * * 0</code> - Weekly on Sunday</li>
                <li><code>*/5 * * * *</code> - Every 5 minutes</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

