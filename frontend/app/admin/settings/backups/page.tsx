// V-FINAL-1730-229
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import api from "@/lib/api";
import { Download, Database, AlertTriangle } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";

export default function BackupPage() {
  const [isDownloading, setIsDownloading] = useState(false);

  const handleDownload = async () => {
    setIsDownloading(true);
    try {
      const response = await api.get('/admin/system/backup/db', { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `backup_${new Date().toISOString()}.sql`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Database Backup Downloaded");
    } catch (e) {
      toast.error("Download Failed");
    } finally {
      setIsDownloading(false);
    }
  };

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Data Backups</h1>

      <Card>
        <CardHeader>
          <CardTitle>Database Backup</CardTitle>
          <CardDescription>Download a complete SQL dump of the current database.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="bg-amber-50 border border-amber-200 p-4 rounded-md flex gap-3">
            <AlertTriangle className="h-5 w-5 text-amber-600" />
            <div>
              <h4 className="font-semibold text-amber-800">Important</h4>
              <p className="text-sm text-amber-700">This action generates a full snapshot of your production data. Store it securely.</p>
            </div>
          </div>
          
          <Button onClick={handleDownload} disabled={isDownloading}>
            <Download className="mr-2 h-4 w-4" />
            {isDownloading ? "Generating SQL..." : "Download SQL Dump"}
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}