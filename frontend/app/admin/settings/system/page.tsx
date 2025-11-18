// V-FINAL-1730-268 (Upgraded with Text Inputs) | V-FINAL-1730-449 (Full Admin Security) | V-FINAL-1730-491 (Dynamic UI)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Shield, AlertTriangle, Settings, DollarSign, Bell } from "lucide-react";

interface Setting {
  id: number;
  key: string;
  value: string;
  type: 'boolean' | 'string' | 'number' | 'text';
  group: string;
}

// Helper component to render the correct input based on type
function SettingInput({ setting, onChange }: { setting: Setting, onChange: (value: string) => void }) {
  const [currentValue, setCurrentValue] = useState(setting.value);

  useEffect(() => {
    setCurrentValue(setting.value);
  }, [setting]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setCurrentValue(e.target.value);
    onChange(e.target.value);
  };

  const handleToggle = (checked: boolean) => {
    const newValue = checked ? 'true' : 'false';
    setCurrentValue(newValue);
    onChange(newValue);
  };

  switch (setting.type) {
    case 'boolean':
      return <Switch checked={currentValue === 'true'} onCheckedChange={handleToggle} />;
    case 'number':
      return <Input type="number" value={currentValue} onChange={handleChange} />;
    case 'text':
      return <Textarea value={currentValue} onChange={handleChange} rows={5} />;
    case 'string':
    default:
      return <Input value={currentValue} onChange={handleChange} />;
  }
}

export default function SystemSettingsPage() {
  const queryClient = useQueryClient();
  // Store settings as an object map for easy access
  const [settingsMap, setSettingsMap] = useState<Record<string, Setting>>({});

  const { data, isLoading } = useQuery({
    queryKey: ['adminSettingsFull'],
    queryFn: async () => (await api.get('/admin/settings')).data,
    onSuccess: (data) => {
      // Flatten the grouped data into a single map
      const flatMap: Record<string, Setting> = {};
      Object.values(data).forEach((group: any) => {
        group.forEach((setting: Setting) => {
          flatMap[setting.key] = setting;
        });
      });
      setSettingsMap(flatMap);
    }
  });

  const mutation = useMutation({
    mutationFn: (updatedSettings: { key: string, value: string }[]) => 
      api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!", { description: "Changes may take a minute to apply." });
      queryClient.invalidateQueries({ queryKey: ['adminSettingsFull'] });
    },
    onError: () => toast.error("Save Failed")
  });

  const handleSettingChange = (key: string, value: string) => {
    setSettingsMap(prev => ({
      ...prev,
      [key]: { ...prev[key], value: value }
    }));
  };
  
  const handleSave = () => {
    // Convert the map back into the array format the API expects
    const settingsPayload = Object.values(settingsMap).map(s => ({
      key: s.key,
      value: s.value
    }));
    mutation.mutate(settingsPayload);
  };
  
  if (isLoading) return <p>Loading settings...</p>;

  const getSetting = (key: string) => {
    return settingsMap[key];
  };
  
  // Helper to render a group of settings
  const renderSettingGroup = (keys: string[]) => {
    return keys.map(key => {
      const setting = getSetting(key);
      if (!setting) return null;
      
      return (
        <div key={key} className="flex items-center justify-between p-4 border rounded-lg">
          <div>
            <Label htmlFor={key} className="text-base font-medium capitalize">
              {key.replace(/_/g, ' ')}
            </Label>
            <p className="text-sm text-muted-foreground">{setting.key}</p>
          </div>
          <div className="w-1/2">
            <SettingInput
              setting={setting}
              onChange={(value) => handleSettingChange(key, value)}
            />
          </div>
        </div>
      );
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">General Settings</h1>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          {mutation.isPending ? "Saving..." : "Save All Changes"}
        </Button>
      </div>

      <Tabs defaultValue="system" className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="system"><Settings className="mr-2 h-4 w-4"/> System</TabsTrigger>
          <TabsTrigger value="financial"><DollarSign className="mr-2 h-4 w-4"/> Financial</TabsTrigger>
          <TabsTrigger value="security"><Shield className="mr-2 h-4 w-4"/> Security</TabsTrigger>
          <TabsTrigger value="notifications"><Bell className="mr-2 h-4 w-4"/> Notifications</TabsTrigger>
        </TabsList>

        {/* --- 1. System --- */}
        <TabsContent value="system" className="space-y-4">
          <Card className="border-red-200 bg-red-50">
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <Label htmlFor="maintenance_mode" className="text-base font-bold text-red-700">Maintenance Mode</Label>
                  <p className="text-sm text-muted-foreground">Blocks all non-admin access to the site.</p>
                </div>
                {getSetting('maintenance_mode') && (
                  <SettingInput setting={getSetting('maintenance_mode')} onChange={(v) => handleSettingChange('maintenance_mode', v)} />
                )}
              </div>
            </CardContent>
          </Card>
          {renderSettingGroup([
            'registration_enabled', 'login_enabled', 'investment_enabled', 'withdrawal_enabled',
            'support_tickets_enabled', 'maintenance_message'
          ])}
        </TabsContent>

        {/* --- 2. Financial --- */}
        <TabsContent value="financial" className="space-y-4">
          {renderSettingGroup([
            'min_withdrawal_amount', 'auto_approval_max_amount', 'payment_grace_period_days',
            'referral_bonus_amount', 'tds_rate', 'tds_threshold'
          ])}
        </TabsContent>

        {/* --- 3. Security --- */}
        <TabsContent value="security" className="space-y-4">
          {renderSettingGroup([
            'password_history_limit', 'fraud_amount_threshold', 'fraud_new_user_days',
            'admin_ip_whitelist', 'allowed_ips'
          ])}
        </TabsContent>
        
        {/* --- 4. Notifications --- */}
        <TabsContent value="notifications" className="space-y-4">
          {renderSettingGroup([
            'sms_provider', 'msg91_auth_key', 'msg91_sender_id', 'twilio_sid', 'twilio_token', 'twilio_from'
          ])}
        </TabsContent>
      </Tabs>
    </div>
  );
}