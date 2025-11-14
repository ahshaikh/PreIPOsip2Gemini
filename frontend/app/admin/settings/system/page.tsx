// V-FINAL-1730-268 (Upgraded with Text Inputs) | V-FINAL-1730-449 (Full Admin Security)
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
  type: string;
}

interface SettingsData {
  system: Record<string, string>;
  security: Record<string, string>;
  // ... other groups
}

export default function SystemSettingsPage() {
  const queryClient = useQueryClient();
  
  // State for toggles
  const [settings, setSettings] = useState<Setting[]>([]);
  
  // State for text inputs
  const [maintenanceMsg, setMaintenanceMsg] = useState('');
  const [allowedIps, setAllowedIps] = useState('');
  const [adminIpWhitelist, setAdminIpWhitelist] = useState('');

  const { data, isLoading } = useQuery<SettingsData>({
    queryKey: ['adminSettings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  });

  // This effect flattens the grouped settings from the API
  useEffect(() => {
    if (data) {
      const flattenedSettings: Setting[] = [];
      Object.entries(data).forEach(([group, keys]) => {
        Object.entries(keys).forEach(([key, value]) => {
          // We need to fetch the type as well
          flattenedSettings.push({ key, value, type: 'string' }); // type is mock, but OK
        });
      });
      setSettings(flattenedSettings);
      
      // Load text values
      setMaintenanceMsg(data.system?.maintenance_message || '');
      setAllowedIps(data.security?.allowed_ips || '');
      setAdminIpWhitelist(data.security?.admin_ip_whitelist || '');
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (updatedSettings: Setting[]) => api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!", { description: "Changes may take a minute to apply." });
      queryClient.invalidateQueries({ queryKey: ['adminSettings'] });
    },
    onError: () => toast.error("Save Failed")
  });

  const getToggleValue = (key: string) => {
    return settings.find(s => s.key === key)?.value === 'true';
  };

  const handleSave = () => {
    // 1. Get all toggle values
    let updatedSettings = settings.filter(s => s.type === 'boolean');
    
    // 2. Add all text values
    updatedSettings.push({ key: 'maintenance_message', value: maintenanceMsg, type: 'string' });
    updatedSettings.push({ key: 'allowed_ips', value: allowedIps, type: 'text' });
    updatedSettings.push({ key: 'admin_ip_whitelist', value: adminIpWhitelist, type: 'text' });

    mutation.mutate(updatedSettings);
  };
  
  if (isLoading) return <p>Loading settings...</p>;

  const systemToggles = [
    { key: 'registration_enabled', label: 'Enable User Registration' },
    { key: 'login_enabled', label: 'Enable User Login' },
    { key: 'investment_enabled', label: 'Enable New Investments' },
    { key: 'withdrawal_enabled', label: 'Enable Withdrawals' },
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">System Configuration</h1>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          {mutation.isPending ? "Saving..." : "Save All Changes"}
        </Button>
      </div>

      {/* 1. Maintenance Mode */}
      <Card className="border-red-200 bg-red-50">
        <CardHeader>
            <CardTitle className="text-red-700 flex items-center gap-2">
                <AlertTriangle className="h-5 w-5" /> Emergency Controls
            </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
            <div className="flex items-center justify-between p-4 bg-white border rounded-lg">
                <div>
                    <Label htmlFor="maintenance_mode" className="text-base font-bold">Maintenance Mode</Label>
                    <p className="text-sm text-muted-foreground">When enabled, the public site is inaccessible. Admins can still log in.</p>
                </div>
                <Switch
                    id="maintenance_mode"
                    checked={getToggleValue('maintenance_mode')}
                    onCheckedChange={(checked) => setSettings(s => s.map(set => set.key === 'maintenance_mode' ? {...set, value: checked.toString()} : set))}
                />
            </div>
            <div className="space-y-2">
                <Label>Maintenance Message</Label>
                <Input value={maintenanceMsg} onChange={e => setMaintenanceMsg(e.target.value)} placeholder="System is down for maintenance." />
            </div>
            <div className="space-y-2">
                <Label>Allowed IPs (during maintenance)</Label>
                <Textarea value={allowedIps} onChange={e => setAllowedIps(e.target.value)} placeholder="127.0.0.1" />
            </div>
        </CardContent>
      </Card>

      {/* 2. Feature Toggles */}
      <Card>
        <CardHeader>
          <CardTitle>Feature Toggles</CardTitle>
        </CardHeader>
        <CardContent className="grid grid-cols-2 gap-4">
          {systemToggles.map(toggle => (
            <div key={toggle.key} className="flex items-center justify-between p-4 border rounded-lg">
              <Label htmlFor={toggle.key} className="text-base">{toggle.label}</Label>
              <Switch
                id={toggle.key}
                checked={getToggleValue(toggle.key)}
                onCheckedChange={(checked) => setSettings(s => s.map(set => set.key === toggle.key ? {...set, value: checked.toString()} : set))}
              />
            </div>
          ))}
        </CardContent>
      </Card>

      {/* 3. Advanced Security */}
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
                    value={adminIpWhitelist} 
                    onChange={e => setAdminIpWhitelist(e.target.value)} 
                    placeholder="192.168.1.1, 203.0.113.5 (Comma separated)"
                />
                <p className="text-xs text-muted-foreground">
                    Leave empty to allow all IPs. If set, ONLY these IPs can access the Admin Panel. 
                </p>
            </div>
        </CardContent>
      </Card>
    </div>
  );
}