
// V-REMEDIATE-1730-153
'use client';

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

export default function UserTicketDetailPage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const params = useParams();
  const ticketId = params.ticketId as string;

  const [replyMessage, setReplyMessage] = useState('');
  
  // Fetch the single ticket
  const { data: ticket, isLoading } = useQuery({
    queryKey: ['userTicket', ticketId],
    queryFn: async () => (await api.get(`/user/support-tickets/${ticketId}`)).data,
  });

  // Fetch the user's ID to correctly align the chat
  const { data: user } = useQuery({
    queryKey: ['userProfile'],
    queryFn: async () => (await api.get('/user/profile')).data,
  });

  // Mutation to post a reply
  const mutation = useMutation({
    mutationFn: (message: string) => api.post(`/user/support-tickets/${ticketId}/reply`, { message }),
    onSuccess: () => {
      toast.success("Reply Sent!");
      queryClient.invalidateQueries({ queryKey: ['userTicket', ticketId] });
      setReplyMessage('');
    },
    onError: (error: any) => {
      toast.error("Reply Failed", { description: error.response?.data?.message });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate(replyMessage);
  };

  if (isLoading || !user) return <div>Loading ticket...</div>;

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => router.push('/support')}>&larr; Back to all tickets</Button>
      <Card>
        <CardHeader>
          <CardTitle>Ticket: {ticket.ticket_code}</CardTitle>
          <CardDescription>{ticket.subject}</CardDescription>
          <div className="flex gap-4 pt-2">
            <span className="capitalize text-sm">Category: <span className="font-semibold">{ticket.category}</span></span>
            <span className="capitalize text-sm">Priority: <span className="font-semibold">{ticket.priority}</span></span>
            <span className="capitalize text-sm">Status: <span className="font-semibold">{ticket.status.replace('_', ' ')}</span></span>
          </div>
        </CardHeader>
        <CardContent>
          <div className="space-y-6">
            <TicketChat messages={ticket.messages} currentUserId={user.id} />
            
            <hr />

            <form onSubmit={handleSubmit} className="space-y-4">
              <Label htmlFor="message" className="text-lg font-semibold">Your Reply</Label>
              <Textarea
                id="message"
                value={replyMessage}
                onChange={(e) => setReplyMessage(e.target.value)}
                required
                minLength={5}
                placeholder="Type your reply here..."
              />
              <Button type="submit" disabled={mutation.isPending}>
                {mutation.isPending ? "Sending..." : "Send Reply"}
              </Button>
            </form>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}