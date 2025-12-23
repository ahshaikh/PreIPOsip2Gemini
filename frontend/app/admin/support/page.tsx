// V-REMEDIATE-1730-154 | V-PROTOCOL-7-PAGINATION
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { PaginationControls } from "@/components/shared/PaginationControls"; // [Protocol 7]

export default function AdminSupportQueuePage() {
  const router = useRouter();
  // [Protocol 7] State for pagination
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('open');

  // Fetch all tickets based on the status filter
  const { data, isLoading } = useQuery({
    queryKey: ['adminTickets', page, statusFilter],
    queryFn: async () => {
      // [Protocol 7] Pass page param to backend
      const params = new URLSearchParams({
        page: page.toString(),
        status: statusFilter,
      });
      return (await api.get(`/admin/support-tickets?${params.toString()}`)).data;
    },
    placeholderData: (previousData) => previousData,
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Support Ticket Queue</h1>
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">Filter by Status:</span>
          <Select 
            value={statusFilter} 
            onValueChange={(val) => {
              setStatusFilter(val);
              setPage(1); // Reset to page 1 on filter change
            }}
          >
            <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="open">Open</SelectItem>
              <SelectItem value="waiting_for_user">Waiting for User</SelectItem>
              <SelectItem value="resolved">Resolved</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Tickets ({data?.total || 0})</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading tickets...</p> : (
            <div className="space-y-4">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ticket ID</TableHead>
                    <TableHead>User</TableHead>
                    <TableHead>Subject</TableHead>
                    <TableHead>Priority</TableHead>
                    <TableHead>Last Updated</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data.map((ticket: any) => (
                    <TableRow key={ticket.id}>
                      <TableCell className="font-medium">{ticket.ticket_code}</TableCell>
                      <TableCell>{ticket.user.username}</TableCell>
                      <TableCell>{ticket.subject}</TableCell>
                      <TableCell className="capitalize">{ticket.priority}</TableCell>
                      <TableCell>{new Date(ticket.updated_at).toLocaleString()}</TableCell>
                      <TableCell>
                        <Button variant="outline" size="sm" onClick={() => router.push(`/admin/support/${ticket.id}`)}>
                          View
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {/* [Protocol 7] Dynamic Pagination Controls */}
              {data && (
                <PaginationControls
                  currentPage={data.current_page}
                  totalPages={data.last_page}
                  onPageChange={setPage}
                  totalItems={data.total}
                  from={data.from}
                  to={data.to}
                />
              )}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}