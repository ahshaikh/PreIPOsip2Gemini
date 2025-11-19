// V-FINAL-1730-544 (Created)
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Trash2, AlertTriangle } from "lucide-react";

export default function IpWhitelistPage() {
  const queryClient = useQueryClient();
  const [ipAddress, setIpAddress] = useState('');
  const [description, setDescription] = useState('');

  const { data: ips, isLoading } = useQuery({
    queryKey: ['adminIpWhitelist'],
    queryFn: async () => (await api.get('/admin/ip-whitelist')).data,
  });

  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/ip-whitelist', data),
    onSuccess: () => {
      toast.success("IP Address Added");
      queryClient.invalidateQueries({ queryKey: ['adminIpWhitelist'] });
      setIpAddress('');
      setDescription('');
    },
    onError: (e: any) =>
      toast.error("Error", { description: e.response?.data?.message })
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/ip-whitelist/${id}`),
    onSuccess: () => {
      toast.success("IP Address Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminIpWhitelist'] });
    }
  });

  const toggleMutation = useMutation({
    mutationFn: (ip: any) =>
      api.put(`/admin/ip-whitelist/${ip.id}`, { ...ip, is_active: !ip.is_active }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminIpWhitelist'] });
    }
  });

  const handleSubmit = () => {
    createMutation.mutate({
      ip_address: ipAddress,
      description: description,
      is_active: true
    });
  };

  return (
    <div className="space-y-6">
      
      <h1 className="text-3xl font-bold">Admin IP Whitelist</h1>

      {/* Warning Box */}
      <Card className="bg-destructive/10 border-destructive/30">
        <CardContent className="flex items-start gap-4 p-6">
          <AlertTriangle className="h-6 w-6 text-destructive" />
          <div>
            <h3 className="font-semibold text-destructive">
              Warning: Do Not Lock Yourself Out!
            </h3>
            <p className="text-sm text-destructive/80">
              This feature is active. Adding an IP here will restrict all admin access
              to *only* the IPs on this list. Ensure your current IP address is included
              and correct before adding the first entry.
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Add New Rule */}
      <Card>
        <CardHeader>
          <CardTitle>Add New IP Rule</CardTitle>
        </CardHeader>

        <CardContent className="flex flex-col md:flex-row gap-4 items-end">
          <div className="flex-1 space-y-2">
            <Label>IP Address or CIDR Range</Label>
            <Input
              value={ipAddress}
              onChange={e => setIpAddress(e.target.value)}
              placeholder="e.g., 192.168.1.1 or 10.0.0.0/16"
            />
          </div>

          <div className="flex-1 space-y-2">
            <Label>Description</Label>
            <Input
              value={description}
              onChange={e => setDescription(e.target.value)}
              placeholder="e.g., Main Office IP"
            />
          </div>

          <Button
            onClick={handleSubmit}
            disabled={createMutation.isPending}
          >
            <Plus className="mr-2 h-4 w-4" /> Add Rule
          </Button>
        </CardContent>
      </Card>

      {/* Active Rules */}
      <Card>
        <CardHeader>
          <CardTitle>Active IP Rules</CardTitle>
        </CardHeader>

        <CardContent>
          {isLoading ? (
            <p>Loading...</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>IP Address / Range</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {ips?.map((ip: any) => (
                  <TableRow key={ip.id}>
                    <TableCell className="font-mono">{ip.ip_address}</TableCell>
                    <TableCell>{ip.description}</TableCell>

                    <TableCell>
                      <Switch
                        checked={ip.is_active}
                        onCheckedChange={() => toggleMutation.mutate(ip)}
                      />
                    </TableCell>

                    <TableCell>
                      <Button
                        variant="destructive"
                        size="icon"
                        onClick={() => deleteMutation.mutate(ip.id)}
                      >
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
