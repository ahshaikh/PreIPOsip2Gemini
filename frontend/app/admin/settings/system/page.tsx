// V-FINAL-1730-268 (Upgraded with Text Inputs)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Shield, AlertTriangle } from "lucide-react";

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
  const queryClient = useQueryClient();
  const [settings, setSettings] = useState<Setting[]>([]);
  
  // State for text inputs
  const [ipWhitelist, setIpWhitelist] = useState('');

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
      
      // Load specific text values
      const ipSetting = flattenedSettings.find(s => s.key === 'admin_ip_whitelist');
      if (ipSetting) setIpWhitelist(ipSetting.value || '');
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (updatedSettings: Setting[]) => api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!", { description: "Changes effective immediately." });
      queryClient.invalidateQueries({ queryKey: ['adminSettings'] });
    },
    onError: () => {
      toast.error("Save Failed");
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
    // Merge text inputs into settings array before saving
    const finalSettings = settings.map(s => {
        if (s.key === 'admin_ip_whitelist') return { ...s, value: ipWhitelist };
        return s;
    });
    
    // If IP whitelist key doesn't exist in DB yet, add it
    if (!finalSettings.find(s => s.key === 'admin_ip_whitelist')) {
        finalSettings.push({ key: 'admin_ip_whitelist', value: ipWhitelist });
    }

    mutation.mutate(finalSettings);
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
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">System Configuration</h1>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          {mutation.isPending ? "Saving..." : "Save All Changes"}
        </Button>
      </div>

      {/* 1. Maintenance Mode (High Impact) */}
      <Card className="border-red-200 bg-red-50">
        <CardHeader>
            <CardTitle className="text-red-700 flex items-center gap-2">
                <AlertTriangle className="h-5 w-5" /> Emergency Controls
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div className="flex items-center justify-between p-4 bg-white border rounded-lg">
                <div>
                    <Label htmlFor="maintenance_mode" className="text-base font-bold">Maintenance Mode</Label>
                    <p className="text-sm text-muted-foreground">When enabled, the public site is inaccessible. Admins can still log in.</p>
                </div>
                <Switch
                    id="maintenance_mode"
                    checked={getSettingValue('maintenance_mode')}
                    onCheckedChange={(checked) => handleToggle('maintenance_mode', checked)}
                />
            </div>
        </CardContent>
      </Card>

      {/* 2. Feature Toggles */}
      <Card>
        <CardHeader>
          <CardTitle>Feature Toggles</CardTitle>
          <CardDescription>Enable or disable specific platform modules.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
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

      {/* 3. Advanced Security (New) */}
      <Card>
        <CardHeader>
            <CardTitle className="flex items-center gap-2">
                <Shield className="h-5 w-5 text-blue-600" /> Advanced Security
            </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
            <div className="space-y-2">
                <Label>Admin IP Whitelist</Label>
                <Textarea 
                    value={ipWhitelist} 
                    onChange={e => setIpWhitelist(e.target.value)} 
                    placeholder="192.168.1.1, 203.0.113.5 (Comma separated)"
                />
                <p className="text-xs text-muted-foreground">
                    Leave empty to allow all IPs. If set, ONLY these IPs can access the Admin Panel. 
                    <strong>Warning: Ensure your current IP is included or you will lock yourself out.</strong>
                </p>
            </div>
        </CardContent>
      </Card>
    </div>
  );
}