// V-FINAL-1730-217
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Shield, Trash2 } from "lucide-react";

export default function RolesPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [roleName, setRoleName] = useState('');
  const [selectedPerms, setSelectedPerms] = useState<string[]>([]);

  const { data, isLoading } = useQuery({
    queryKey: ['adminRoles'],
    queryFn: async () => (await api.get('/admin/roles')).data,
  });

  const createMutation = useMutation({
    mutationFn: (newRole: any) => api.post('/admin/roles', newRole),
    onSuccess: () => {
      toast.success("Role Created");
      queryClient.invalidateQueries({ queryKey: ['adminRoles'] });
      setIsDialogOpen(false);
      setRoleName(''); setSelectedPerms([]);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/roles/${id}`),
    onSuccess: () => {
      toast.success("Role Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminRoles'] });
    }
  });

  const togglePerm = (name: string) => {
    if (selectedPerms.includes(name)) {
      setSelectedPerms(selectedPerms.filter(p => p !== name));
    } else {
      setSelectedPerms([...selectedPerms, name]);
    }
  };

  const handleSubmit = () => {
    createMutation.mutate({ name: roleName, permissions: selectedPerms });
  };

  if (isLoading) return <div>Loading roles...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Role Management</h1>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild><Button><Plus className="mr-2 h-4 w-4" /> Create Role</Button></DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader><DialogTitle>Create New Role</DialogTitle></DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Role Name</Label>
                <Input value={roleName} onChange={e => setRoleName(e.target.value)} placeholder="e.g. Manager" />
              </div>
              <div className="space-y-2">
                <Label>Permissions</Label>
                <div className="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto border p-2 rounded">
                  {data?.permissions.map((perm: any) => (
                    <div key={perm.id} className="flex items-center space-x-2">
                      <Checkbox 
                        id={perm.name} 
                        checked={selectedPerms.includes(perm.name)}
                        onCheckedChange={() => togglePerm(perm.name)}
                      />
                      <label htmlFor={perm.name} className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                        {perm.name}
                      </label>
                    </div>
                  ))}
                </div>
              </div>
              <Button onClick={handleSubmit} disabled={createMutation.isPending} className="w-full">
                {createMutation.isPending ? "Creating..." : "Create Role"}
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="pt-6">
          <Table>
            <TableHeader><TableRow><TableHead>Role</TableHead><TableHead>Permissions</TableHead><TableHead>Actions</TableHead></TableRow></TableHeader>
            <TableBody>
              {data?.roles.map((role: any) => (
                <TableRow key={role.id}>
                  <TableCell className="font-medium flex items-center">
                    <Shield className="mr-2 h-4 w-4 text-muted-foreground" />
                    {role.name}
                  </TableCell>
                  <TableCell>
                    <div className="flex flex-wrap gap-1">
                      {role.permissions.slice(0, 5).map((p: any) => (
                        <span key={p.id} className="bg-muted px-2 py-1 rounded-full text-xs">{p.name}</span>
                      ))}
                      {role.permissions.length > 5 && <span className="text-xs text-muted-foreground">+{role.permissions.length - 5} more</span>}
                    </div>
                  </TableCell>
                  <TableCell>
                    {role.name !== 'super-admin' && role.name !== 'admin' && (
                      <Button variant="ghost" size="sm" onClick={() => deleteMutation.mutate(role.id)}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}