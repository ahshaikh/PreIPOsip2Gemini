// V-FINAL-1730-533 (Created)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { Search, Eye, Download } from "lucide-react";

// --- Single Transcript Viewer ---
function ViewTranscriptModal({ ticket, onClose }: { ticket: any, onClose: () => void }) {
  const { data: ticketDetail, isLoading } = useQuery({
    queryKey: ['adminTicketDetail', ticket.id],
    queryFn: async () => (await api.get(`/admin/support-tickets/${ticket.id}`)).data,
  });

  const exportTranscript = () => {
    let content = `Ticket: ${ticket.subject}\nUser: ${ticket.user.username}\n\n`;
    ticketDetail?.messages.forEach((msg: any) => {
      content += `[${new Date(msg.created_at).toLocaleString()}] ${msg.is_admin_reply ? 'Support' : 'User'}:\n${msg.message}\n\n`;
    });
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `transcript-${ticket.id}.txt`;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <Dialog open={true} onOpenChange={onClose}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Chat Transcript: {ticket.subject}</DialogTitle>
          <DialogDescription>
            Chat between {ticket.user.username} and {ticket.assigned_to?.username || 'Admin'}
          </DialogDescription>
        </DialogHeader>
        <div className="max-h-[60vh] overflow-y-auto p-4 space-y-4 bg-muted/50 rounded-md">
          {isLoading ? <p>Loading...</p> : (
            ticketDetail?.messages.map((msg: any) => (
              <div key={msg.id} className={`flex ${msg.is_admin_reply ? 'justify-start' : 'justify-end'}`}>
                <Card className={`p-3 max-w-[80%] ${msg.is_admin_reply ? 'bg-white' : 'bg-primary text-primary-foreground'}`}>
                  <p className="text-sm">{msg.message}</p>
                  <p className="text-xs opacity-70 mt-1 text-right">{new Date(msg.created_at).toLocaleTimeString()}</p>
                </Card>
              </div>
            ))
          )}
        </div>
        <Button onClick={exportTranscript} variant="outline">
          <Download className="mr-2 h-4 w-4" /> Export Transcript
        </Button>
      </DialogContent>
    </Dialog>
  );
}

// --- Main Page ---
export default function ChatTranscriptsPage() {
  const [filters, setFilters] = useState({
    search: '',
    status: 'resolved',
    agent_id: 'all',
  });
  const [selectedTicket, setSelectedTicket] = useState<any>(null);

  // We set category="technical" to filter *only* Live Chats
  const queryParams = new URLSearchParams({
    category: 'technical',
    status: filters.status,
    search: filters.search,
  });
  if (filters.agent_id !== 'all') {
    queryParams.append('agent_id', filters.agent_id);
  }
  
  const { data, isLoading } = useQuery({
    queryKey: ['adminChatTranscripts', queryParams.toString()],
    queryFn: async () => (await api.get(`/admin/support-tickets?${queryParams.toString()}`)).data,
  });

  // Fetch admins to populate agent filter
  const { data: admins } = useQuery({
    queryKey: ['adminUsers'],
    queryFn: async () => (await api.get('/admin/users?role=admin')).data, // Assuming a role filter exists
  });

  const handleFilterChange = (key: string, value: string) => {
    setFilters(prev => ({ ...prev, [key]: value }));
  };

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Live Chat Transcripts</h1>
      
      {/* Filters */}
      <div className="flex gap-4">
        <Input 
          placeholder="Search subject..." 
          onChange={(e) => handleFilterChange('search', e.target.value)} 
          className="max-w-xs" 
        />
        <Select value={filters.status} onValueChange={(v) => handleFilterChange('status', v)}>
          <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="open">Open</SelectItem>
            <SelectItem value="resolved">Resolved</SelectItem>
            <SelectItem value="closed">Closed</SelectItem>
          </SelectContent>
        </Select>
        <Select value={filters.agent_id} onValueChange={(v) => handleFilterChange('agent_id', v)}>
          <SelectTrigger className="w-[180px]"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Agents</SelectItem>
            {admins?.data.map((admin: any) => (
              <SelectItem key={admin.id} value={admin.id.toString()}>{admin.username}</SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader><TableRow>
                <TableHead>User</TableHead>
                <TableHead>Subject</TableHead>
                <TableHead>Agent</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {data?.data.map((ticket: any) => (
                  <TableRow key={ticket.id}>
                    <TableCell>{ticket.user.username}</TableCell>
                    <TableCell>{ticket.subject}</TableCell>
                    <TableCell>{ticket.assigned_to?.username || 'Unassigned'}</TableCell>
                    <TableCell><Badge variant="outline">{ticket.status}</Badge></TableCell>
                    <TableCell>
                      <Button variant="outline" size="sm" onClick={() => setSelectedTicket(ticket)}>
                        <Eye className="mr-2 h-4 w-4" /> View
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
      
      {selectedTicket && (
        <ViewTranscriptModal 
          ticket={selectedTicket}
          onClose={() => setSelectedTicket(null)}
        />
      )}
    </div>
  );
}