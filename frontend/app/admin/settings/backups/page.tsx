// V-SYSTEM-CONFIG-001 (Comprehensive Backup Management)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import api from "@/lib/api";
import { Download, Database, AlertTriangle, Trash2, RefreshCw, Save, Clock, HardDrive, Mail } from "lucide-react";
import { useState, useEffect } from "react";
import { toast } from "sonner";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { formatDistanceToNow } from "date-fns";

interface BackupConfig {
  backup_enabled: boolean;
  backup_schedule: string;
  backup_time: string;
  backup_retention_days: number;
  backup_storage: string;
  backup_notification_email: string;
  backup_include_uploads: boolean;
  backup_include_files: boolean;
  backup_email_report: boolean;
}

interface BackupFile {
  filename: string;
  size: number;
  created_at: string;
  path: string;
}

export default function BackupPage() {
  const queryClient = useQueryClient();
  const [config, setConfig] = useState<BackupConfig>({
    backup_enabled: true,
    backup_schedule: 'daily',
    backup_time: '02:00',
    backup_retention_days: 30,
    backup_storage: 'local',
    backup_notification_email: '',
    backup_include_uploads: true,
    backup_include_files: true,
    backup_email_report: false,
  });

  const { data: configData, isLoading: configLoading } = useQuery({
    queryKey: ['backupConfig'],
    queryFn: async () => {
      const res = await api.get('/admin/system/backup/config');
      return res.data;
    },
  });

  const { data: historyData, refetch: refetchHistory } = useQuery({
    queryKey: ['backupHistory'],
    queryFn: async () => {
      const res = await api.get('/admin/system/backup/history');
      return res.data;
    },
  });

  useEffect(() => {
    if (configData) {
      setConfig(configData);
    }
  }, [configData]);

  const updateConfigMutation = useMutation({
    mutationFn: async (newConfig: BackupConfig) => {
      await api.put('/admin/system/backup/config', newConfig);
    },
    onSuccess: () => {
      toast.success("Backup configuration saved");
      queryClient.invalidateQueries({ queryKey: ['backupConfig'] });
    },
    onError: () => {
      toast.error("Failed to save configuration");
    },
  });

  const createBackupMutation = useMutation({
    mutationFn: async () => {
      await api.post('/admin/system/backup/create', {
        include_files: config.backup_include_files,
      });
    },
    onSuccess: () => {
      toast.success("Backup created successfully");
      refetchHistory();
    },
    onError: () => {
      toast.error("Failed to create backup");
    },
  });

  const deleteBackupMutation = useMutation({
    mutationFn: async (filename: string) => {
      await api.delete(`/admin/system/backup/${filename}`);
    },
    onSuccess: () => {
      toast.success("Backup deleted");
      refetchHistory();
    },
    onError: () => {
      toast.error("Failed to delete backup");
    },
  });

  const downloadBackup = async (filename: string) => {
    try {
      const response = await api.get(`/admin/system/backup/download/${filename}`, {
        responseType: 'blob',
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Backup downloaded");
    } catch (e) {
      toast.error("Download failed");
    }
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
  };

  const handleSave = () => {
    updateConfigMutation.mutate(config);
  };

  if (configLoading) {
    return <div className="flex items-center justify-center p-8"><RefreshCw className="h-6 w-6 animate-spin mr-2" /> Loading...</div>;
  }

  const backups: BackupFile[] = historyData?.backups || [];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Backup Management</h1>
          <p className="text-muted-foreground">Configure automated backups and manage backup files</p>
        </div>
      </div>

      {/* Configuration */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Database className="h-5 w-5" />
            Backup Configuration
          </CardTitle>
          <CardDescription>Configure automated backup settings and scheduling</CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="flex items-center justify-between">
            <div>
              <Label className="text-base font-medium">Enable Automated Backups</Label>
              <p className="text-sm text-muted-foreground">Automatically create backups on schedule</p>
            </div>
            <Switch
              checked={config.backup_enabled}
              onCheckedChange={(checked) => setConfig({ ...config, backup_enabled: checked })}
            />
          </div>

          {config.backup_enabled && (
            <>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Schedule Frequency</Label>
                  <Select
                    value={config.backup_schedule}
                    onValueChange={(value) => setConfig({ ...config, backup_schedule: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="daily">Daily</SelectItem>
                      <SelectItem value="weekly">Weekly</SelectItem>
                      <SelectItem value="monthly">Monthly</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label>Backup Time</Label>
                  <Input
                    type="time"
                    value={config.backup_time}
                    onChange={(e) => setConfig({ ...config, backup_time: e.target.value })}
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Retention Period (days)</Label>
                <Input
                  type="number"
                  min="1"
                  max="365"
                  value={config.backup_retention_days}
                  onChange={(e) => setConfig({ ...config, backup_retention_days: parseInt(e.target.value) })}
                  className="max-w-[200px]"
                />
                <p className="text-sm text-muted-foreground">Backups older than this will be automatically deleted</p>
              </div>

              <div className="space-y-2">
                <Label>Storage Location</Label>
                <Select
                  value={config.backup_storage}
                  onValueChange={(value) => setConfig({ ...config, backup_storage: value })}
                >
                  <SelectTrigger className="max-w-[200px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="local">Local Storage</SelectItem>
                    <SelectItem value="s3">AWS S3</SelectItem>
                    <SelectItem value="ftp">FTP Server</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-base font-medium">Include Uploaded Files</Label>
                    <p className="text-sm text-muted-foreground">Backup user-uploaded files and documents</p>
                  </div>
                  <Switch
                    checked={config.backup_include_uploads}
                    onCheckedChange={(checked) => setConfig({ ...config, backup_include_uploads: checked })}
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-base font-medium">Include System Files</Label>
                    <p className="text-sm text-muted-foreground">Backup configuration and system files</p>
                  </div>
                  <Switch
                    checked={config.backup_include_files}
                    onCheckedChange={(checked) => setConfig({ ...config, backup_include_files: checked })}
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-base font-medium">Email Backup Reports</Label>
                    <p className="text-sm text-muted-foreground">Send email notification after each backup</p>
                  </div>
                  <Switch
                    checked={config.backup_email_report}
                    onCheckedChange={(checked) => setConfig({ ...config, backup_email_report: checked })}
                  />
                </div>
              </div>

              {config.backup_email_report && (
                <div className="space-y-2">
                  <Label>Notification Email</Label>
                  <Input
                    type="email"
                    value={config.backup_notification_email}
                    onChange={(e) => setConfig({ ...config, backup_notification_email: e.target.value })}
                    placeholder="admin@example.com"
                  />
                </div>
              )}
            </>
          )}

          <div className="flex justify-end pt-4 border-t">
            <Button onClick={handleSave} disabled={updateConfigMutation.isPending}>
              <Save className="mr-2 h-4 w-4" />
              {updateConfigMutation.isPending ? "Saving..." : "Save Configuration"}
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Manual Backup */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <RefreshCw className="h-5 w-5" />
            Manual Backup
          </CardTitle>
          <CardDescription>Create a backup immediately</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="bg-blue-50 border border-blue-200 p-4 rounded-md flex gap-3 mb-4">
            <AlertTriangle className="h-5 w-5 text-blue-600" />
            <div>
              <h4 className="font-semibold text-blue-800">Important</h4>
              <p className="text-sm text-blue-700">This will create a full database backup. Large databases may take several minutes.</p>
            </div>
          </div>
          
          <Button 
            onClick={() => createBackupMutation.mutate()} 
            disabled={createBackupMutation.isPending}
          >
            <Database className="mr-2 h-4 w-4" />
            {createBackupMutation.isPending ? "Creating Backup..." : "Create Backup Now"}
          </Button>
        </CardContent>
      </Card>

      {/* Backup History */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="h-5 w-5" />
            Backup History
          </CardTitle>
          <CardDescription>View and manage your backup files</CardDescription>
        </CardHeader>
        <CardContent>
          {backups.length === 0 ? (
            <div className="text-center py-8 text-muted-foreground">
              <Database className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <p>No backups found. Create your first backup to get started.</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Filename</TableHead>
                  <TableHead>Size</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {backups.map((backup) => (
                  <TableRow key={backup.filename}>
                    <TableCell className="font-mono text-sm">{backup.filename}</TableCell>
                    <TableCell>{formatFileSize(backup.size)}</TableCell>
                    <TableCell>
                      {formatDistanceToNow(new Date(backup.created_at), { addSuffix: true })}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => downloadBackup(backup.filename)}
                        >
                          <Download className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => {
                            if (confirm(`Delete backup ${backup.filename}?`)) {
                              deleteBackupMutation.mutate(backup.filename);
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
    </div>
  );
}
