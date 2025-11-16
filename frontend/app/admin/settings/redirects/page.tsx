// V-FINAL-1730-522 (Created) | V-FINAL-1730-534 (Bulk Import Added)
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Trash2, Upload, LineChart } from "lucide-react";

export default function RedirectManagerPage() {
  const queryClient = useQueryClient();
  const [fromUrl, setFromUrl] = useState('/old-page');
  const [toUrl, setToUrl] = useState('/new-page');
  const [status, setStatus] = useState('301');

  // --- NEW: CSV Import State ---
  const [importFile, setImportFile] = useState<File | null>(null);

  const { data: redirects, isLoading } = useQuery({
    queryKey: ['adminRedirects'],
    queryFn: async () => (await api.get('/admin/redirects')).data,
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/redirects', data),
    onSuccess: () => {
      toast.success("Redirect Created");
      queryClient.invalidateQueries({ queryKey: ['adminRedirects'] });
      setFromUrl(''); setToUrl('');
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });
  
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/redirects/${id}`),
    onSuccess: () => {
      toast.success("Redirect Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminRedirects'] });
    }
  });

  // --- NEW: CSV Import Mutation ---
  const importMutation = useMutation({
    mutationFn: (formData: FormData) => api.post('/admin/redirects/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
    onSuccess: (data) => {
      toast.success("Import Complete", { description: `${data.data.imported} redirects added.` });
      queryClient.invalidateQueries({ queryKey: ['adminRedirects'] });
      setImportFile(null);
    },
    onError: (e: any) => toast.error("Import Failed", { description: e.response?.data?.message })
  });
  
  const handleSubmit = () => {
    createMutation.mutate({ from_url: fromUrl, to_url: toUrl, status_code: parseInt(status) });
  };

  const handleImport = () => {
    if (!importFile) return;
    const fd = new FormData();
    fd.append('file', importFile);
    importMutation.mutate(fd);
  };

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Redirect Manager</h1>
      
      <Card>
        <CardHeader><CardTitle>Create New Redirect</CardTitle></CardHeader>
        <CardContent className="flex flex-col md:flex-row gap-4 items-end">
          <div className="flex-1 space-y-2">
            <Label>From URL (Old Path)</Label>
            <Input value={fromUrl} onChange={e => setFromUrl(e.target.value)} />
          </div>
          <div className="flex-1 space-y-2">
            <Label>To URL (New Path)</Label>
            <Input value={toUrl} onChange={e => setToUrl(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Type</Label>
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="301">301 (Permanent)</SelectItem>
                <SelectItem value="302">302 (Temporary)</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <Button onClick={handleSubmit} disabled={createMutation.isPending}><Plus className="mr-2 h-4 w-4" /> Add Redirect</Button>
        </CardContent>
      </Card>
      
      {/* --- NEW: Bulk Import --- */}
      <Card>
        <CardHeader><CardTitle>Bulk Import (CSV)</CardTitle></CardHeader>
        <CardContent className="flex gap-4 items-end">
            <div className="flex-1 space-y-2">
                <Label>Upload CSV (Format: from_url, to_url, status_code)</Label>
                <Input type="file" accept=".csv" onChange={e => setImportFile(e.target.files?.[0] || null)} />
            </div>
            <Button onClick={handleImport} disabled={importMutation.isPending || !importFile}>
                <Upload className="mr-2 h-4 w-4" />
                {importMutation.isPending ? "Importing..." : "Import"}
            </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Active Redirects</CardTitle></CardHeader>
        <CardContent>
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader><TableRow>
                <TableHead>From</TableHead>
                <TableHead>To</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Hits</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {redirects?.map((r: any) => (
                  <TableRow key={r.id}>
                    <TableCell className="font-mono">{r.from_url}</TableCell>
                    <TableCell className="font-mono">{r.to_url}</TableCell>
                    <TableCell><span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{r.status_code}</span></TableCell>
                    <TableCell className="font-medium flex items-center">
                        <LineChart className="h-4 w-4 mr-1 text-muted-foreground" />
                        {r.hit_count}
                    </TableCell>
                    <TableCell>
                      <Button variant="destructive" size="icon" onClick={() => deleteMutation.mutate(r.id)}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
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