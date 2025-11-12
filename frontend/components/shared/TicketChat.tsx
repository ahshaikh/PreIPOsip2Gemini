<?php
// V-REMEDIATE-1730-152
'use client';

import { cn } from "@/lib/utils";
import { Card, CardContent } from "@/components/ui/card";

export function TicketChat({ messages, currentUserId }: { messages: any[], currentUserId: number }) {
  if (!messages || messages.length === 0) {
    return <p>No messages yet.</p>;
  }

  return (
    <div className="space-y-4">
      {messages.map((msg) => {
        // Determine if the message is from the current user or the "other" person
        // This component is used by both users and admins, so we check the author ID
        const isMe = msg.author.id === currentUserId;

        return (
          <div
            key={msg.id}
            className={cn(
              "flex items-end space-x-2",
              isMe ? "justify-end" : "justify-start"
            )}
          >
            <Card
              className={cn(
                "max-w-xs md:max-w-md p-3",
                isMe
                  ? "bg-primary text-primary-foreground"
                  : "bg-muted"
              )}
            >
              <p className="font-semibold mb-1 text-sm">
                {isMe ? "You" : msg.author.username}
              </p>
              <p className="text-sm">{msg.message}</p>
              <p className="text-xs text-right opacity-70 mt-2">
                {new Date(msg.created_at).toLocaleString()}
              </p>
            </Card>
          </div>
        );
      })}
    </div>
  );
}