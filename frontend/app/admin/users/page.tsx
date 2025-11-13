// V-FINAL-1730-218 (Bulk & Import Added)
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useRouter, useSearchParams } from "next/navigation";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { SearchInput } from "@/components/shared/SearchInput";
import { useState } from "react";
import { toast } from "sonner";
import { ChevronDown, Upload, Download, Gift, Ban, CheckCircle } from "lucide-react";

export default function UsersPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const searchParams = useSearchParams();
  const [selectedUsers, setSelectedUsers] = useState<number[]>([]);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [isImportOpen, setIsImportOpen] = useState(false);
  
  const page = searchParams.get('page') || '1';
  const search = searchParams.get('search') || '';

  const { data: queryData, isLoading } = useQuery({
    queryKey: ['adminUsers', page, search],
    queryFn: async () => (await api.get(`/admin/users?page=${page}&search=${search}`)).data,
  });

  // Mutations
  const bulkMutation = useMutation({
    mutationFn: (payload: any) => api.post('/admin/users/bulk-action', payload),
    onSuccess: (data) => {
      toast.success("Bulk Action Complete", { description: data.data.message });
      queryClient.invalidateQueries({ queryKey: ['adminUsers'] });
      setSelectedUsers([]);
    }
  });

  const importMutation = useMutation({
    mutationFn: (formData: FormData) => api.post('/admin/users/import', formData),
    onSuccess: (data) => {
      toast.success("Import Successful", { description: data.data.message });
      queryClient.invalidateQueries({ queryKey: ['adminUsers'] });
      setIsImportOpen(false);
    },
    onError: (e: any) => toast.error("Import Failed", { description: e.response?.data?.message })
  });

  // Handlers
  const toggleSelectAll = () => {
    if (selectedUsers.length === queryData?.data.length) {
      setSelectedUsers([]);
    } else {
      setSelectedUsers(queryData?.data.map((u: any) => u.id) || []);
    }
  };

  const toggleUser = (id: number) => {
    if (selectedUsers.includes(id)) {
      setSelectedUsers(selectedUsers.filter(uId => uId !== id));
    } else {
      setSelectedUsers([...selectedUsers, id]);
    }
  };

  const handleBulkAction = (action: string) => {
    if (!selectedUsers.length) return;
    
    let payload: any = { user_ids: selectedUsers, action };
    
    if (action === 'bonus') {
      const amount = prompt("Enter bonus amount for selected users:");
      if (!amount) return;
      payload.data = { amount: parseFloat(amount) };
    }

    if (confirm(`Are you sure you want to ${action} ${selectedUsers.length} users?`)) {
      bulkMutation.mutate(payload);
    }
  };

  const handleImport = () => {
    if (!importFile) return;
    const fd = new FormData();
    fd.append('file', importFile);
    importMutation.mutate(fd);
  };

  const handleExport = async () => {
    try {
      const response = await api.get('/admin/users/export/csv', { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'users_export.csv');
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Export Downloaded");
    } catch (e) { toast.error("Export Failed"); }
  };

  if (isLoading) return <div>Loading users...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">User Management</h1>
        <div className="flex gap-2">
          {/* Import Dialog */}
          <Dialog open={isImportOpen} onOpenChange={setIsImportOpen}>
            <DialogTrigger asChild><Button variant="outline"><Upload className="mr-2 h-4 w-4"/> Import CSV</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Import Users</DialogTitle></DialogHeader>
              <div className="space-y-4">
                <p className="text-sm text-muted-foreground">Upload CSV with columns: Username, Email, Mobile.</p>
                <Input type="file" accept=".csv" onChange={e => setImportFile(e.target.files?.[0] || null)} />
                <Button onClick={handleImport} disabled={!importFile || importMutation.isPending} className="w-full">
                  {importMutation.isPending ? "Importing..." : "Start Import"}
                </Button>
              </div>
            </DialogContent>
          </Dialog>

          {/* Export Button */}
          <Button variant="outline" onClick={handleExport}>
            <Download className="mr-2 h-4 w-4"/> Export
          </Button>
        </div>
      </div>

      {/* Bulk Actions Bar */}
      {selectedUsers.length > 0 && (
        <div className="bg-primary/10 p-2 rounded-lg flex items-center gap-4 animate-in fade-in">
          <span className="text-sm font-medium ml-2">{selectedUsers.length} users selected</span>
          <div className="flex gap-2">
            <Button size="sm" variant="outline" onClick={() => handleBulkAction('activate')}>
              <CheckCircle className="mr-2 h-4 w-4" /> Activate
            </Button>
            <Button size="sm" variant="outline" onClick={() => handleBulkAction('suspend')}>
              <Ban className="mr-2 h-4 w-4" /> Suspend
            </Button>
            <Button size="sm" variant="outline" onClick={() => handleBulkAction('bonus')}>
              <Gift className="mr-2 h-4 w-4" /> Give Bonus
            </Button>
          </div>
        </div>
      )}

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>All Users</CardTitle>
          <SearchInput placeholder="Search users..." />
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-12">
                  <Checkbox 
                    checked={selectedUsers.length === queryData?.data.length && queryData?.data.length > 0}
                    onCheckedChange={toggleSelectAll}
                  />
                </TableHead>
                <TableHead>User ID</TableHead>
                <TableHead>Username</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {queryData?.data.map((user: any) => (
                <TableRow key={user.id}>
                  <TableCell>
                    <Checkbox 
                      checked={selectedUsers.includes(user.id)}
                      onCheckedChange={() => toggleUser(user.id)}
                    />
                  </TableCell>
                  <TableCell>{user.id}</TableCell>
                  <TableCell>{user.username}</TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>
                    <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                      user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    }`}>
                      {user.status}
                    </span>
                  </TableCell>
                  <TableCell>
                    <Button variant="outline" size="sm" onClick={() => router.push(`/admin/users/${user.id}`)}>
                      Manage
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {queryData && <PaginationControls meta={queryData.meta} />}
          
        </CardContent>
      </Card>
    </div>
  );
}