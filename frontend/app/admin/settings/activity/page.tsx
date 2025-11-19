// V-FINAL-1730-228 (Created) | V-FINAL-1730-450 
'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { SearchInput } from "@/components/shared/SearchInput";
import { PaginationControls } from "@/components/shared/PaginationControls";
import { useSearchParams } from "next/navigation";

export default function ActivityLogPage() {
  const searchParams = useSearchParams();
  const page = searchParams.get('page') || '1';
  const search = searchParams.get('search') || '';

  const { data: logData, isLoading } = useQuery({
    queryKey: ['adminActivityLogs', page, search],
    queryFn: async () => (await api.get(`/admin/system/activity-logs?page=${page}&search=${search}`)).data,
  });

  if (isLoading) return <div>Loading logs...</div>;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Global Activity Audit</h1>
        <SearchInput placeholder="Search logs (e.g., 'kyc_approved' or user email)..." />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>System & Admin Events</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Time</TableHead>
                <TableHead>User / Actor</TableHead>
                <TableHead>Action</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>IP Address</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {logData?.data.map((log: any) => (
                <TableRow key={log.id}>
                  <TableCell>{new Date(log.created_at).toLocaleString()}</TableCell>
                  <TableCell>
                    {log.user ? (
                      <div>
                        <div className="font-medium">{log.user.username}</div>
                        <div className="text-xs text-muted-foreground">{log.user.email}</div>
                      </div>
                    ) : <span className="text-muted-foreground">System</span>}
                  </TableCell>
                  <TableCell><span className="bg-muted px-2 py-1 rounded text-xs font-mono">{log.action}</span></TableCell>
                  <TableCell>{log.description}</TableCell>
                  <TableCell className="font-mono text-xs">{log.ip_address}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {logData && <PaginationControls meta={logData.meta} />}
        </CardContent>
      </Card>
    </div>
  );
}