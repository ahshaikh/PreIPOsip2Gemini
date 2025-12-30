// V-FINAL-1730-670 | V-COMPLIANCE-MODAL-FIX
'use client';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { useState, useEffect } from "react";
import { toast } from "sonner";
import { ArrowDown } from "lucide-react";

// Helper to decode HTML
const decodeHtml = (html: string) => {
  if (typeof window === 'undefined') return html;
  const txt = document.createElement("textarea");
  txt.innerHTML = html;
  return txt.value;
};

interface LegalAcceptanceModalProps {
  documentData?: any; // If passed directly
  slug?: string;      // If needing fetch
  version?: string;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  mode?: 'authenticated' | 'guest'; 
}

export function LegalAcceptanceModal({ 
  documentData, 
  slug, 
  version, 
  isOpen,
  onClose, 
  onSuccess,
  mode = 'authenticated'
}: LegalAcceptanceModalProps) {
  
  // 1. Fetch logic if slug is provided
  const { data: fetchedDoc, isLoading, isError } = useQuery({
    queryKey: ['legalDocModal', slug],
    queryFn: async () => {
      if (!slug) return null;
      try {
        // FIX: Point to the correct public endpoint defined in api.php
        const res = await api.get(`/legal/documents/${slug}`);
        // Handle potentially wrapped response
        return res.data?.data || res.data; 
      } catch (e) {
        console.error("Failed to fetch doc:", e);
        throw e;
      }
    },
    enabled: !!slug && !documentData && isOpen,
    retry: 1
  });

  const activeDoc = documentData || fetchedDoc;

  // Render logic for content
  const renderContent = () => {
    if (isLoading) return <div className="p-10 text-center text-muted-foreground">Loading document...</div>;
    if (isError) return <div className="p-10 text-center text-red-500">Could not load document content.</div>;
    
    if (activeDoc?.content) {
      let content = activeDoc.content;
      // Decode if double escaped
      if (content.includes('&lt;')) content = decodeHtml(content);
      
      return (
        <div 
          className="prose prose-sm max-w-none dark:prose-invert p-6"
          dangerouslySetInnerHTML={{ __html: content }} 
        />
      );
    }
    return <div className="p-10 text-center text-muted-foreground">No content available.</div>;
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => { if(!open) onClose(); }}>
      <DialogContent className="max-w-2xl h-[80vh] flex flex-col p-0 gap-0">
        <DialogHeader className="p-6 pb-4 border-b">
          <DialogTitle>{activeDoc?.title || "Legal Document"}</DialogTitle>
          <DialogDescription>
             {activeDoc?.version ? `Version ${activeDoc.version}` : 'Please review the document below.'}
          </DialogDescription>
        </DialogHeader>
        
        <div className="flex-1 overflow-hidden bg-muted/10 relative">
           <ScrollArea className="h-full w-full">
              {renderContent()}
           </ScrollArea>
           {/* Hint to scroll */}
           <div className="absolute bottom-4 right-6 pointer-events-none opacity-50 bg-background/80 p-2 rounded-full border shadow-sm">
             <ArrowDown className="h-4 w-4 animate-bounce" />
           </div>
        </div>

        <DialogFooter className="p-4 border-t bg-background">
          <Button onClick={onClose} className="w-full sm:w-auto">
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}