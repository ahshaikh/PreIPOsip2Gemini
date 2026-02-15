'use client';

/**
 * V-AUDIT-REFACTOR-2025 | V-LOGIC-CONSOLIDATION
 * [AUDIT FIX]: Removed all "Simulation Logic." 
 * This component now strictly acts as a data entry form.
 * The "Summary" tab now fetches real validation from the backend.
 */

import { useState, useEffect } from 'react';
import api from '@/lib/api';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ShieldCheck, Loader2 } from 'lucide-react';

interface EligibilityConfigDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  planId: number;
  planName: string;
  config: unknown;
  onSave: (config: unknown) => void;
}

export function EligibilityConfigDialog({ open, onOpenChange, planId, planName, config, onSave }: EligibilityConfigDialogProps) {
  const [activeConfig, setActiveConfig] = useState(config);
  const [serverSummary, setServerSummary] = useState<{ eligible: boolean, reasons: string[] } | null>(null);
  const [isValidating, setIsValidating] = useState(false);

  /**
   * [AUDIT FIX]: Instead of simulating logic in JS, we hit a validation endpoint.
   * This ensures the Admin sees exactly what the User will experience.
   */
  const fetchServerValidation = async () => {
    setIsValidating(true);
    try {
      // POST the current local state to see how the backend Rule Engine interprets it
      const response = await api.post(`/api/v1/plans/validate-config`, { config: activeConfig });
      setServerSummary(response.data);
    } finally {
      setIsValidating(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Eligibility Rules - {planName}</DialogTitle>
        </DialogHeader>

        <Tabs defaultValue="setup" onValueChange={(v) => v === 'summary' && fetchServerValidation()}>
          <TabsList>
            <TabsTrigger value="setup">Configuration</TabsTrigger>
            <TabsTrigger value="summary">Live Preview (Backend Verified)</TabsTrigger>
          </TabsList>

          <TabsContent value="setup">
            {/* Form inputs for Age, KYC, etc. go here - purely setting 'activeConfig' state */}
          </TabsContent>

          <TabsContent value="summary">
            {isValidating ? (
              <div className="flex justify-center p-8"><Loader2 className="animate-spin" /></div>
            ) : (
              <div className="space-y-4">
                <h4 className="font-bold">Backend Enforcement Preview:</h4>
                {serverSummary?.reasons.map((msg, i) => (
                  <p key={i} className="text-destructive">• {msg}</p>
                ))}
                {serverSummary?.eligible && <p className="text-success">✓ Configuration is valid and active.</p>}
              </div>
            )}
          </TabsContent>
        </Tabs>

        <Button onClick={() => onSave(activeConfig)}>Save Config</Button>
      </DialogContent>
    </Dialog>
  );
}