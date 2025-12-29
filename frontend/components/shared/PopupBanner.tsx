// V-FINAL-1730-545 (Fix Hydration Error: div inside p)
'use client';

import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import DOMPurify from 'dompurify';
import api from '@/lib/api';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function PopupBanner() {
  const [openPopup, setOpenPopup] = useState<any>(null);
  const [topBar, setTopBar] = useState<any>(null);
  const [isTopBarVisible, setIsTopBarVisible] = useState(false);

  // 1. Fetch Banners
  const { data: banners } = useQuery({
    queryKey: ['publicBanners'],
    queryFn: async () => {
        try {
            const res = await api.get('/public/banners');
            const data = Array.isArray(res.data) ? res.data : (res.data?.data || []);
            // console.log("📢 Banners Fetched:", data); 
            return data;
        } catch (err) {
            console.error("❌ Failed to fetch banners:", err);
            return [];
        }
    },
    staleTime: 60000,
    retry: 1,
  });

  useEffect(() => {
    if (!banners || banners.length === 0) return;

    // 2. Process "Popup" Type
    const activePopup = banners.find((b: any) => b.type === 'popup' && b.is_active);
    if (activePopup) {
      const storageKey = `popup_viewed_${activePopup.id}`;
      // Only show if not seen in this session
      if (!sessionStorage.getItem(storageKey)) {
        if (activePopup.trigger_type === 'time_delay') {
            setTimeout(() => setOpenPopup(activePopup), (activePopup.trigger_value || 0) * 1000);
        } else {
            setOpenPopup(activePopup);
        }
      }
    }

    // 3. Process "Top Bar" Type
    const activeTopBar = banners.find((b: any) => b.type === 'top_bar' && b.is_active);
    if (activeTopBar) {
        const storageKey = `topbar_dismissed_${activeTopBar.id}`;
        if (!sessionStorage.getItem(storageKey)) {
            setTopBar(activeTopBar);
            setIsTopBarVisible(true);
        }
    }

  }, [banners]);

  const handleClosePopup = () => {
    if (openPopup) {
        sessionStorage.setItem(`popup_viewed_${openPopup.id}`, 'true');
        setOpenPopup(null);
    }
  };

  const handleDismissTopBar = () => {
      if (topBar) {
          sessionStorage.setItem(`topbar_dismissed_${topBar.id}`, 'true');
          setIsTopBarVisible(false);
      }
  };

  // Prevent rendering hidden DOM wrappers if no popup is active
  // This is crucial for avoiding other hydration mismatches
  const showDialog = !!openPopup;

  return (
    <>
      {/* --- TOP BAR RENDERER --- */}
      {isTopBarVisible && topBar && (
        <div className="bg-primary text-primary-foreground px-4 py-3 relative z-50 shadow-md">
            <div className="container flex items-center justify-between">
                <div 
                    className="text-sm font-medium pr-8"
                    dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(topBar.content) }} 
                />
                <button 
                    onClick={handleDismissTopBar} 
                    className="absolute right-2 top-1/2 -translate-y-1/2 p-1 hover:bg-primary-foreground/20 rounded-full transition-colors"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>
        </div>
      )}

      {/* --- POPUP RENDERER --- */}
      {showDialog && (
        <Dialog open={showDialog} onOpenChange={(open) => !open && handleClosePopup()}>
          <DialogContent className="sm:max-w-[500px]">
            <DialogHeader>
              <DialogTitle>{openPopup.title}</DialogTitle>
              
              {/* FIX: Use asChild to prevent <p> nesting error */}
              <DialogDescription asChild>
                <div className="text-muted-foreground">
                    {openPopup.image_url && (
                        <div className="mb-4 rounded-lg overflow-hidden">
                            <img 
                                src={openPopup.image_url} 
                                alt={openPopup.title} 
                                className="w-full h-auto object-cover max-h-[300px]" 
                            />
                        </div>
                    )}
                    {openPopup.content && (
                        <div 
                            className="prose prose-sm max-w-none text-foreground"
                            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(openPopup.content) }} 
                        />
                    )}
                </div>
              </DialogDescription>

            </DialogHeader>
            
            {openPopup.link_url && (
                <div className="mt-4 flex justify-end">
                    <Button asChild onClick={handleClosePopup}>
                        <a href={openPopup.link_url} target="_blank" rel="noopener noreferrer">
                            Learn More
                        </a>
                    </Button>
                </div>
            )}
          </DialogContent>
        </Dialog>
      )}
    </>
  );
}