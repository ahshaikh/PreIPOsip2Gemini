'use client';

import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import DOMPurify from 'dompurify';
import api from '@/lib/api';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';

export default function PopupBanner() {
  const [isOpen, setIsOpen] = useState(false);

  const { data: settings } = useQuery({
    queryKey: ['globalSettings'],
    queryFn: async () => (await api.get('/global-settings')).data,
    staleTime: 60000,
  });

  const popup = settings?.banners?.find((b: any) => b.type === 'popup' && b.is_active);

  useEffect(() => {
    if (popup) {
      const seen = sessionStorage.getItem(`popup_${popup.id}`);
      if (!seen) {
        setIsOpen(true);
        sessionStorage.setItem(`popup_${popup.id}`, 'true');
      }
    }
  }, [popup]);

  // ⭐ FIX: prevent Radix from rendering hidden DOM wrappers
  if (!popup || !isOpen) return null;

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{popup.title}</DialogTitle>
          <DialogDescription>
            <div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(popup.content) }} />
          </DialogDescription>
        </DialogHeader>
      </DialogContent>
    </Dialog>
  );
}
