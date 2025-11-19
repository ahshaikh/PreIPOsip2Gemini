// V-REMEDIATE-1730-154
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { useRouter } from "next/navigation";

export default function AdminSupportQueuePage() {
  const router = useRouter();
  const [statusFilter, setStatusFilter] = useState('open');

  // Fetch all tickets based on the status filter
  const { data, isLoading } = useQuery({
    queryKey: ['adminTickets', statusFilter],
    queryFn: async () => (await api.get(`/admin/support-tickets?status=${statusFilter}`)).data,
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Support Ticket Queue</h1>
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">Filter by Status:</span>
          <Select value={statusFilter} onValueChange={setStatusFilter}>
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
          )}
        </CardContent>
      </Card>
    </div>
  );
}