'use client';

/**
 * TicketChat
 * * [AUDIT FIX]: Implements WebSockets via Laravel Echo for real-time delivery.
 * * [AUDIT FIX]: Implements Pagination logic for "Load More" history.
 */
import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';
import api from '@/lib/api';

export function TicketChat({ ticketId }) {
  const [messages, setMessages] = useState<any[]>([]);
  const [nextPageUrl, setNextPageUrl] = useState<string | null>(null);

  useEffect(() => {
    // 1. Initial Load of Messages
    fetchMessages();

    // 2. [AUDIT FIX]: Subscribe to Private WebSocket Channel
    const echo = new Echo({ broadcaster: 'reverb', key: process.env.NEXT_PUBLIC_REVERB_APP_KEY });

    echo.private(`tickets.${ticketId}`)
      .listen('.MessageSent', (e: any) => {
        setMessages(prev => [e.message, ...prev]);
      });

    return () => echo.leave(`tickets.${ticketId}`);
  }, [ticketId]);

  const fetchMessages = async (url = `/api/v1/tickets/${ticketId}/messages`) => {
    const { data } = await api.get(url);
    setMessages(prev => [...prev, ...data.data]);
    setNextPageUrl(data.next_page_url);
  };

  return (
    <div className="flex flex-col h-[500px]">
      {nextPageUrl && (
        <button onClick={() => fetchMessages(nextPageUrl)} className="text-xs text-blue-500 py-2">
          Load older messages
        </button>
      )}
      
      <div className="flex-1 overflow-y-auto flex flex-col-reverse">
        {messages.map(msg => (
          <div key={msg.id} className="p-2 border-b">
            <span className="font-bold">{msg.user.name}: </span>
            {msg.content}
          </div>
        ))}
      </div>
    </div>
  );
}