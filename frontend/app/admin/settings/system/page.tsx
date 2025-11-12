// V-PHASE6-1730-132 (REVISED)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner"; // <-- IMPORT FROM SONNER
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";

interface Setting {
  key: string;
  value: string;
}

interface SettingsData {
  [group: string]: {
    [key: string]: string;
  };
}

export default function SystemSettingsPage() {
  // const { toast } = useToast(); // <-- REMOVED
  const queryClient = useQueryClient();
  const [settings, setSettings] = useState<Setting[]>([]);

  const { data, isLoading } = useQuery<SettingsData>({
    queryKey: ['adminSettings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  });

  useEffect(() => {
    if (data) {
      const flattenedSettings: Setting[] = [];
      Object.values(data).forEach(group => {
        Object.entries(group).forEach(([key, value]) => {
          flattenedSettings.push({ key, value });
        });
      });
      setSettings(flattenedSettings);
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (updatedSettings: Setting[]) => api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!"); // <-- REVISED
      queryClient.invalidateQueries({ queryKey: ['adminSettings'] });
    },
    onError: () => {
      toast.error("Save Failed"); // <-- REVISED
    }
  });

  const handleToggle = (key: string, checked: boolean) => {
    setSettings(currentSettings =>
      currentSettings.map(s => 
        s.key === key ? { ...s, value: checked ? 'true' : 'false' } : s
      )
    );
  };
  
  const getSettingValue = (key: string) => {
    return settings.find(s => s.key === key)?.value === 'true';
  };

  const handleSave = () => {
    mutation.mutate(settings);
  };
  
  if (isLoading) return <p>Loading settings...</p>;

  const systemToggles = [
    { key: 'registration_enabled', label: 'Enable User Registration' },
    { key: 'login_enabled', label: 'Enable User Login' },
    { key: 'investment_enabled', label: 'Enable New Investments' },
    { key: 'withdrawal_enabled', label: 'Enable Withdrawals' },
    { key: 'referral_enabled', label: 'Enable Referral System' },
    { key: 'lucky_draw_enabled', label: 'Enable Lucky Draws' },
    { key: 'profit_sharing_enabled', label: 'Enable Profit Sharing' },
    { key: 'kyc_required_for_investment', label: 'KYC Required to Invest' },
    { key: 'support_tickets_enabled', label: 'Enable Support Tickets' },
    { key: 'maintenance_mode', label: 'Enable Maintenance Mode' },
  ];

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <CardTitle>System Master Toggles</CardTitle>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          {mutation.isPending ? "Saving..." : "Save All Changes"}
        </Button>
      </CardHeader>
      <CardContent className="space-y-6">
        {systemToggles.map(toggle => (
          <div key={toggle.key} className="flex items-center justify-between p-4 border rounded-lg">
            <Label htmlFor={toggle.key} className="text-base">{toggle.label}</Label>
            <Switch
              id={toggle.key}
              checked={getSettingValue(toggle.key)}
              onCheckedChange={(checked) => handleToggle(toggle.key, checked)}
            />
          </div>
        ))}
      </CardContent>
    </Card>
  );
}