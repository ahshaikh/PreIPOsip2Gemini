// V-FINAL-1730-281 (Created) | V-FINAL-1730-432 
'use client';

import { useState, useEffect, useRef } from 'react';
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { MessageCircle, X, Send, Loader2 } from "lucide-react";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { cn } from "@/lib/utils";
import { toast } from "sonner";

export function LiveChatWidget() {
  const [isOpen, setIsOpen] = useState(false);
  const [message, setMessage] = useState('');
  const [activeTicketId, setActiveTicketId] = useState<number | null>(null);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  
  const queryClient = useQueryClient();
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // 1. Check Login Status
  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    setIsLoggedIn(!!token);
  }, []);

  // 2. Fetch Active Chat Tickets (if logged in)
  const { data: tickets, isLoading } = useQuery({
    queryKey: ['liveChatTickets'],
    queryFn: async () => {
        if (!isLoggedIn) return null;
        // Fetch open tickets, newest first
        const res = await api.get('/user/support-tickets?status=open'); 
        return res.data.data;
    },
    enabled: isOpen && isLoggedIn,
    refetchInterval: 10000 // Poll every 10s for new tickets
  });

  // 3. Auto-select the most recent open ticket
  useEffect(() => {
    if (tickets && tickets.length > 0) {
        const recent = tickets[0];
        if (recent.status !== 'resolved' && recent.status !== 'closed') {
            setActiveTicketId(recent.id);
        }
    }
  }, [tickets]);

  // 4. Fetch Messages for Active Ticket
  const { data: ticketDetail } = useQuery({
    queryKey: ['liveChatMessages', activeTicketId],
    queryFn: async () => {
        if (!activeTicketId) return null;
        return (await api.get(`/user/support-tickets/${activeTicketId}`)).data;
    },
    enabled: !!activeTicketId,
    refetchInterval: 5000 // Poll faster for messages
  });

  // 5. Scroll to bottom on new message
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [ticketDetail?.messages]);

  // 6. Send Message Mutation
  const sendMutation = useMutation({
    mutationFn: async (msg: string) => {
        if (activeTicketId) {
            // Reply to existing ticket
            return api.post(`/user/support-tickets/${activeTicketId}/reply`, { message: msg });
        } else {
            // Create new "Live Chat" ticket
            return api.post('/user/support-tickets', {
                subject: 'Live Chat Request',
                category: 'technical', // Default
                priority: 'medium',
                message: msg
            });
        }
    },
    onSuccess: (data) => {
        setMessage('');
        if (!activeTicketId) {
            // We just created a new ticket, set it as active
            setActiveTicketId(data.data.id);
            queryClient.invalidateQueries({ queryKey: ['liveChatTickets'] });
        }
        queryClient.invalidateQueries({ queryKey: ['liveChatMessages', activeTicketId] });
    },
    onError: () => toast.error("Failed to send message")
  });

  const handleSend = (e: React.FormEvent) => {
    e.preventDefault();
    if (!message.trim()) return;
    sendMutation.mutate(message);
  };

  // --- RENDER GUEST WIDGET ---
  if (!isLoggedIn) {
      return (
        <div className="fixed bottom-6 right-6 z-50">
            {isOpen && (
                <Card className="mb-4 w-80 p-6 shadow-2xl animate-in slide-in-from-bottom-10 fade-in">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="font-bold text-lg">Support Chat</h3>
                        <Button variant="ghost" size="icon" onClick={() => setIsOpen(false)} className="h-6 w-6"><X className="h-4 w-4" /></Button>
                    </div>
                    <p className="text-sm text-muted-foreground mb-4">Please log in to chat with our support team.</p>
                    <Button className="w-full" asChild>
                        <a href="/login">Login Now</a>
                    </Button>
                </Card>
            )}
            <Button 
                onClick={() => setIsOpen(!isOpen)} 
                className="h-14 w-14 rounded-full shadow-xl gradient-primary"
            >
                <MessageCircle className="h-7 w-7 text-white" />
            </Button>
        </div>
      );
  }

  // --- RENDER LOGGED-IN WIDGET ---
  return (
    <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end">
      {isOpen && (
        <Card className="mb-4 w-[350px] h-[500px] flex flex-col shadow-2xl border-primary/20 animate-in slide-in-from-bottom-10 fade-in overflow-hidden">
            {/* Header */}
            <div className="p-4 bg-primary text-primary-foreground flex justify-between items-center shadow-sm">
                <div>
                    <h3 className="font-bold flex items-center gap-2">
                        <span className="relative flex h-2 w-2">
                          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Live Support
                    </h3>
                    <p className="text-xs opacity-90">We typically reply in minutes</p>
                </div>
                <Button variant="ghost" size="icon" onClick={() => setIsOpen(false)} className="h-8 w-8 text-primary-foreground hover:bg-white/20">
                    <X className="h-5 w-5" />
                </Button>
            </div>

            {/* Messages Area */}
            <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                {!activeTicketId && !isLoading && (
                    <div className="text-center text-sm text-muted-foreground mt-10">
                        <p>👋 Hi {ticketDetail?.user?.username || 'there'}! How can we help you today?</p>
                    </div>
                )}
                
                {ticketDetail?.messages?.map((msg: any) => (
                    <div key={msg.id} className={cn("flex w-full", msg.is_admin_reply ? "justify-start" : "justify-end")}>
                        <div className={cn(
                            "max-w-[80%] p-3 text-sm rounded-2xl shadow-sm",
                            msg.is_admin_reply ? "bg-white text-gray-800 rounded-tl-none border" : "bg-primary text-white rounded-tr-none"
                        )}>
                            {msg.message}
                        </div>
                    </div>
                ))}
                <div ref={messagesEndRef} />
            </div>

            {/* Input Area */}
            <div className="p-3 bg-white border-t">
                <form onSubmit={handleSend} className="flex gap-2">
                    <Input 
                        value={message} 
                        onChange={(e) => setMessage(e.target.value)} 
                        placeholder="Type a message..." 
                        className="flex-1 focus-visible:ring-1"
                    />
                    <Button type="submit" size="icon" disabled={sendMutation.isPending || !message.trim()}>
                        {sendMutation.isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                    </Button>
                </form>
            </div>
        </Card>
      )}

      {/* Toggle Button */}
      <Button 
        onClick={() => setIsOpen(!isOpen)} 
        className="h-14 w-14 rounded-full shadow-xl gradient-primary hover:scale-105 transition-transform duration-200"
      >
        {isOpen ? <X className="h-7 w-7" /> : <MessageCircle className="h-7 w-7" />}
      </Button>
    </div>
  );
}