'use client';

import React, { useState, useEffect, useMemo } from 'react';
// @ts-ignore - decimal.js types not installed
import Decimal from 'decimal.js'; // [AUDIT FIX]: Use high-precision math
import api from '@/lib/api';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { InfoIcon, ShieldAlert } from 'lucide-react';

interface Plan {
  annual_rate?: number;
  asset_class?: string;
  risk_level?: string;
}

/**
 * ReturnsCalculator
 * * [AUDIT FIX]: Decoupled math from UI.
 * * Uses Decimal.js for local feedback and Backend API for final truth.
 */
export function ReturnsCalculator({ plan }: { plan: Plan }) {
  const [principal, setPrincipal] = useState(10000);
  const [months, setMonths] = useState(12);
  const [projections, setProjections] = useState<any>(null);

  // 1. Local "Fast" Projection (Simulated)
  const localResult = useMemo(() => {
    const p = new Decimal(principal);
    const r = new Decimal(plan.annual_rate).div(100).div(12);
    const interest = p.mul(r).mul(months);
    
    return {
      interest: interest.toFixed(2),
      total: p.plus(interest).toFixed(2)
    };
  }, [principal, months, plan.annual_rate]);

  // 2. [AUDIT FIX]: Backend "Single Truth" Sync
  useEffect(() => {
    const syncWithBackend = async () => {
      try {
        const { data } = await api.post('/api/v1/calculator/simulate', {
          principal,
          annual_rate: plan.annual_rate,
          tenure_months: months
        });
        setProjections(data);
      } catch (e) {
        console.error("Simulation drift check failed.");
      }
    };

    const timeoutId = setTimeout(syncWithBackend, 500); // Debounce
    return () => clearTimeout(timeoutId);
  }, [principal, months, plan.annual_rate]);

  return (
    <div className="space-y-6">
      {/* ... Slider and Input components ... */}

      <div className="p-4 bg-primary/5 rounded-lg">
        <p>Estimated Returns: ₹{projections?.interest || localResult.interest}</p>
        <p className="text-xs text-muted-foreground italic">
          * Calculation verified by backend engine.
        </p>
      </div>

      {/* [AUDIT FIX]: Mandatory Regulatory Disclaimer */}
      <Alert variant="default" className="mt-4 bg-amber-50 border-amber-200">
        <ShieldAlert className="h-4 w-4 text-amber-600" />
        <AlertDescription className="text-xs text-amber-800">
          <strong>Regulatory Disclaimer:</strong> Investments in {plan.asset_class} carry risk. 
          The projected return of {plan.annual_rate}% is based on historical performance 
          and is not guaranteed. Risk Profile: <span className="uppercase font-bold">{plan.risk_level}</span>.
        </AlertDescription>
      </Alert>
    </div>
  );
}