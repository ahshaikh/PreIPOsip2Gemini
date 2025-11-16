// V-FINAL-1730-564 (Created)
'use client';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/api";
import { useState } from "react";
import { toast } from "sonner";

interface LegalAcceptanceModalProps {
  slug: string;
  version: number;
  onClose: () => void;
  onSuccess: () => void; // Function to re-run the original failed action (e.g., retry payment)
}

export function LegalAcceptanceModal({ slug, version, onClose, onSuccess }: LegalAcceptanceModalProps) {
  const [isChecked, setIsChecked] = useState(false);
  
  // 1. Fetch the document content
  const { data: page, isLoading } = useQuery({
    queryKey: ['legalPage', slug],
    queryFn: async () => (await api.get(`/page/${slug}`)).data,
  });

  // 2. Mutation to accept
  const mutation = useMutation({
    mutationFn: ()_ => api.post('/user/legal/accept', { slug, version }),
    onSuccess: () => {
      toast.success("Terms Accepted!");
      onSuccess(); // Re-run the original action (e.g., initiate payment)
      onClose();
    },
    onError: () => toast.error("Failed to save acceptance. Please try again.")
  });

  // Helper to render blocks
  const renderContent = ()_ => {
    if (isLoading) return <p>Loading document...</p>;
    if (!page?.content) return <p>Could not load document.</p>;
    
    return page.content.map((block: any) => {
      if (block.type === 'heading') return <h3 key={block.id} className="text-lg font-semibold mt-4">{block.text}</h3>;
      if (block.type === 'text') return <p key={block.id} className="text-sm my-2">{block.content}</p>;
      return null;
    });
  };

  return (
    <Dialog open={true} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Update to our {page?.title || "Terms"}</DialogTitle>
          <DialogDescription>
            Before you can proceed, you must read and accept the updated terms.
          </DialogDescription>
        </DialogHeader>
        
        <ScrollArea className="h-[50vh] w-full border rounded-md p-4 bg-muted/20">
          {renderContent()}
        </ScrollArea>
        
        <div className="flex items-center space-x-2">
          <Checkbox id="accept" checked={isChecked} onCheckedChange={(c) => setIsChecked(c as boolean)} />
          <Label htmlFor="accept" className="font-medium">
            I have read, understood, and agree to the {page?.title || "Terms"}.
          </Label>
        </div>
        
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button 
            onClick={()_ => mutation.mutate()} 
            disabled={!isChecked || mutation.isPending}
          >
            {mutation.isPending ? "Accepting..." : "Accept & Continue"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}