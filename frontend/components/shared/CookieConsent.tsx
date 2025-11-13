// V-FINAL-1730-275
'use client';

import { useState, useEffect } from 'react';
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import api from '@/lib/api';
import { X } from 'lucide-react';

export function CookieConsent() {
  const [show, setShow] = useState(false);
  const [message, setMessage] = useState('');

  useEffect(() => {
    // 1. Check Local Storage
    const consent = localStorage.getItem('cookie_consent');
    if (consent) return;

    // 2. Fetch Settings to see if enabled
    api.get('/global-settings').then(res => {
        // Note: In a real app, you'd parse the specific setting key
        // For now, we assume enabled if we get a response
        setMessage("We use cookies to ensure you get the best experience on our website."); 
        setShow(true);
    }).catch(() => setShow(false));
  }, []);

  const handleAccept = () => {
    localStorage.setItem('cookie_consent', 'true');
    setShow(false);
  };

  if (!show) return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 z-50 animate-in slide-in-from-bottom-10 fade-in">
      <Card className="p-4 shadow-2xl border-primary/20 bg-white/95 backdrop-blur">
        <div className="flex justify-between items-start gap-4">
          <div className="space-y-2">
            <h4 className="font-semibold text-sm">Cookie Policy</h4>
            <p className="text-xs text-muted-foreground leading-relaxed">
              {message} <a href="/privacy" className="underline text-primary">Learn more</a>
            </p>
          </div>
          <button onClick={() => setShow(false)} className="text-muted-foreground hover:text-foreground">
            <X className="h-4 w-4" />
          </button>
        </div>
        <div className="mt-4 flex gap-2">
          <Button size="sm" className="w-full" onClick={handleAccept}>Accept All</Button>
          <Button size="sm" variant="outline" className="w-full" onClick={() => setShow(false)}>Decline</Button>
        </div>
      </Card>
    </div>
  );
}