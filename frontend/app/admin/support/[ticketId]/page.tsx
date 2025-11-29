'use client';

// V-REMEDIATE-1730-155

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { TicketChat } from "@/components/shared/TicketChat";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function AdminTicketDetailPage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const params = useParams();
  const ticketId = params.ticketId as string;

  const [replyMessage, setReplyMessage] = useState('');
  
  // Fetch the single ticket
  const { data: ticket, isLoading } = useQuery({
    queryKey: ['adminTicket', ticketId],
    queryFn: async () => (await api.get(`/admin/support-tickets/${ticketId}`)).data,
  });

  // Fetch the admin's user ID to align the chat
  const { data: adminUser } = useQuery({
    queryKey: ['userProfile'], // We can reuse the user profile query
    queryFn: async () => (await api.get('/user/profile')).data,
  });

  // Mutation to post a reply
  const replyMutation = useMutation({
    mutationFn: (message: string) => api.post(`/admin/support-tickets/${ticketId}/reply`, { message }),
    onSuccess: () => {
      toast.success("Reply Sent!");
      queryClient.invalidateQueries({ queryKey: ['adminTicket', ticketId] });
      setReplyMessage('');
    },
  });

  // Mutation to change status
  const statusMutation = useMutation({
    mutationFn: (status: string) => api.put(`/admin/support-tickets/${ticketId}/status`, { status }),
    onSuccess: () => {
      toast.success("Status Updated!");
      queryClient.invalidateQueries({ queryKey: ['adminTicket', ticketId] });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    replyMutation.mutate(replyMessage);
  };

  if (isLoading || !adminUser) return <div>Loading ticket...</div>;

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => router.push('/admin/support')}>&larr; Back to queue</Button>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {/* Main Chat Window */}
        <Card className="md:col-span-2">
          <CardHeader>
            <CardTitle>Ticket: {ticket.ticket_code}</CardTitle>
            <CardDescription>{ticket.subject}</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-6">
              <TicketChat messages={ticket.messages} currentUserId={adminUser.id} />
              
              <hr />

              <form onSubmit={handleSubmit} className="space-y-4">
                <Label htmlFor="message" className="text-lg font-semibold">Your Reply (as Admin)</Label>
                <Textarea
                  id="message"
                  value={replyMessage}
                  onChange={(e) => setReplyMessage(e.target.value)}
                  required
                  minLength={5}
                  placeholder="Type your reply here..."
                />
                <Button type="submit" disabled={replyMutation.isPending}>
                  {replyMutation.isPending ? "Sending..." : "Send Reply & Set to 'Waiting for User'"}
                </Button>
              </form>
            </div>
          </CardContent>
        </Card>

        {/* Admin Info Panel */}
        <Card className="md:col-span-1 h-fit">
          <CardHeader>
            <CardTitle>Ticket Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label>User</Label>
              <p className="font-semibold">{ticket.user.profile.first_name || ticket.user.username}</p>
              <p className="text-sm text-muted-foreground">{ticket.user.email}</p>
            </div>
            <div>
              <Label>Priority</Label>
              <p className="font-semibold capitalize">{ticket.priority}</p>
            </div>
            <div>
              <Label>Category</Label>
              <p className="font-semibold capitalize">{ticket.category}</p>
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select
                value={ticket.status}
                onValueChange={(status) => statusMutation.mutate(status)}
                disabled={statusMutation.isPending}
              >
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="open">Open (Waiting for Support)</SelectItem>
                  <SelectItem value="waiting_for_user">Waiting for User</SelectItem>
                  <SelectItem value="resolved">Resolved</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}