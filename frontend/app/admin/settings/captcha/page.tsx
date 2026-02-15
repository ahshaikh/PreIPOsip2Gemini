// V-FINAL-1730-547 (Created)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Save, Shield } from "lucide-react";

interface Setting {
  key: string;
  value: string;
}

export default function CaptchaSettingsPage() {
  const queryClient = useQueryClient();
  const [settings, setSettings] = useState<Record<string, string>>({});
  
  const { data, isLoading } = useQuery({
    queryKey: ['adminSettings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  });

  // Migrate onSuccess logic to useEffect
  useEffect(() => {
    if (data) {
      // Flatten all settings into a simple map
      const flatMap: Record<string, string> = {};
      (Object.values(data) as Record<string, any>[]).forEach((group: Record<string, any>) => {
        Object.values(group).forEach((setting: { key: string; value: string }) => {
          flatMap[setting.key] = setting.value;
        });
      });
      setSettings(flatMap);
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (updatedSettings: Setting[]) => api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("CAPTCHA Settings Saved!");
      queryClient.invalidateQueries({ queryKey: ['adminSettings'] });
    },
    onError: () => toast.error("Save Failed")
  });

  const handleSettingChange = (key: string, value: string | boolean) => {
    setSettings(prev => ({ ...prev, [key]: String(value) }));
  };
  
  const handleSave = () => {
    const payload = Object.entries(settings).map(([key, value]) => ({ key, value }));
    mutation.mutate(payload);
  };
  
  if (isLoading) return <p>Loading settings...</p>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">CAPTCHA Settings</h1>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          <Save className="mr-2 h-4 w-4" />
          {mutation.isPending ? "Saving..." : "Save Changes"}
        </Button>
      </div>

      <Card>
        <CardContent className="pt-6 space-y-6">
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <Label htmlFor="captcha_enabled" className="text-base font-bold">Enable CAPTCHA</Label>
            <Switch
              id="captcha_enabled"
              checked={settings['captcha_enabled'] === 'true'}
              onCheckedChange={(c) => handleSettingChange('captcha_enabled', c)}
            />
          </div>

          <div className="space-y-2">
            <Label>CAPTCHA Provider</Label>
            <Select
              value={settings['captcha_provider'] || 'recaptcha_v2'}
              onValueChange={(v) => handleSettingChange('captcha_provider', v)}
            >
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="recaptcha_v2">Google reCAPTCHA v2 (Checkbox)</SelectItem>
                <SelectItem value="recaptcha_v3">Google reCAPTCHA v3 (Score)</SelectItem>
                <SelectItem value="hcaptcha">hCaptcha</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>Site Key</Label>
              <Input 
                value={settings['captcha_site_key'] || ''}
                onChange={(e) => handleSettingChange('captcha_site_key', e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Secret Key</Label>
              <Input 
                type="password"
                value={settings['captcha_secret_key'] || ''}
                onChange={(e) => handleSettingChange('captcha_secret_key', e.target.value)}
              />
            </div>
          </div>
          
          {settings['captcha_provider'] === 'recaptcha_v3' && (
            <div className="space-y-2">
              <Label>v3 Score Threshold (0.1 - 1.0)</Label>
              <Input 
                type="number"
                step="0.1"
                min="0.1"
                max="1.0"
                value={settings['captcha_threshold'] || '0.5'}
                onChange={(e) => handleSettingChange('captcha_threshold', e.target.value)}
              />
            </div>
          )}

          <div className="space-y-2">
            <Label>Show CAPTCHA on:</Label>
            <div className="flex flex-col gap-2 p-4 border rounded-md">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="show_on_login"
                  checked={settings['captcha_show_on_login'] === 'true'}
                  onCheckedChange={(c) => handleSettingChange('captcha_show_on_login', c)}
                />
                <Label htmlFor="show_on_login">Login Page</Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="show_on_register"
                  checked={settings['captcha_show_on_registration'] === 'true'}
                  onCheckedChange={(c) => handleSettingChange('captcha_show_on_registration', c)}
                />
                <Label htmlFor="show_on_register">Registration Page</Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="show_on_contact"
                  checked={settings['captcha_show_on_contact'] === 'true'}
                  onCheckedChange={(c) => handleSettingChange('captcha_show_on_contact', c)}
                />
                <Label htmlFor="show_on_contact">Contact Form</Label>
              </div>
            </div>
          </div>

        </CardContent>
      </Card>
    </div>
  );
}