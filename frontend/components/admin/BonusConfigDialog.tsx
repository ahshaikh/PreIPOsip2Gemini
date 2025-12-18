'use client';

/**
 * V-AUDIT-REFACTOR-2025 | V-CALCULATION-PARITY | V-SIMULATOR-INTEGRATION
 * Refactored to address Module 6 Audit Gaps:
 * 1. Logic Decoupling: Removed local JS bonus math from bonusCalculations.ts.
 * 2. Live Simulation: Fetches real preview numbers from the Backend Simulator API.
 * 3. Single Truth: Ensures Admin and User see identical bonus projections.
 */

import { useState, useEffect } from 'react';
import api from '@/lib/api';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Gift, BarChart3, Save, Loader2 } from 'lucide-react';
import { BonusPreview } from './BonusPreview'; 

export function BonusConfigDialog({ open, onOpenChange, planName, monthlyAmount, durationMonths, configs: initialConfigs, onSave, isSaving = false }) {
  const [activeTab, setActiveTab] = useState('welcome');
  const [configs, setConfigs] = useState(initialConfigs);
  const [previewData, setPreviewData] = useState(null);
  const [isSimulating, setIsSimulating] = useState(false);

  /**
   * [AUDIT FIX]: Backend-Driven Simulation
   * Fetches projected bonus numbers directly from the server strategy engine.
   */
  const fetchLiveSimulation = async () => {
    setIsSimulating(true);
    try {
      const { data } = await api.post('/api/v1/admin/bonuses/simulate', {
        monthly_amount: monthlyAmount,
        duration: durationMonths,
        configs: configs
      });
      setPreviewData(data);
    } catch (error) {
      console.error("Simulation failed", error);
    } finally {
      setIsSimulating(false);
    }
  };

  // Fetch simulation whenever the user moves to the Preview tab
  useEffect(() => {
    if (activeTab === 'preview' && open) {
      fetchLiveSimulation();
    }
  }, [activeTab, open]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-6xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Gift className="h-6 w-6" /> Configure Bonuses: {planName}
          </DialogTitle>
        </DialogHeader>

        <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
          <TabsList className="grid w-full grid-cols-5">
            <TabsTrigger value="welcome">Welcome</TabsTrigger>
            <TabsTrigger value="progressive">Progressive</TabsTrigger>
            <TabsTrigger value="milestone">Milestone</TabsTrigger>
            <TabsTrigger value="consistency">Consistency</TabsTrigger>
            <TabsTrigger value="preview">
              <BarChart3 className="h-4 w-4 mr-2" /> Live Preview
            </TabsTrigger>
          </TabsList>

          {/* Setup Tabs (Welcome, Progressive, etc.) simply update the 'configs' state */}
          <TabsContent value="welcome">
             {/* Form Inputs here update local 'configs' state */}
          </TabsContent>

          {/* [AUDIT FIX]: The Preview tab no longer uses local JS math */}
          <TabsContent value="preview" className="mt-6">
            {isSimulating ? (
              <div className="flex justify-center p-12"><Loader2 className="animate-spin h-8 w-8" /></div>
            ) : (
              <BonusPreview 
                data={previewData} // Data comes directly from the Backend Strategy Engine
                monthlyAmount={monthlyAmount} 
              />
            )}
          </TabsContent>
        </Tabs>

        <div className="flex justify-end gap-2 pt-4 border-t">
          <Button variant="outline" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button onClick={() => onSave(configs)} disabled={isSaving}>
            <Save className="h-4 w-4 mr-2" /> {isSaving ? 'Saving...' : 'Save Configuration'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}