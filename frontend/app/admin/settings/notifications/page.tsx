// V-FINAL-1730-538 (Created)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Save, Send, Bell, Mail, MessageSquare } from "lucide-react";

interface Setting {
  id: number;
  key: string;
  value: string;
  type: 'boolean' | 'string' | 'number' | 'text';
  group: string;
}

export default function NotificationSettingsPage() {
  const queryClient = useQueryClient();
  const [settings, setSettings] = useState<Record<string, string>>({});
  const [testMobile, setTestMobile] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['adminSettings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
    onSuccess: (data) => {
      // Flatten all settings into a simple map
      const flatMap: Record<string, string> = {};
      Object.values(data.notification || {}).forEach((setting: any) => {
        flatMap[setting.key] = setting.value;
      });
      setSettings(flatMap);
    }
  });

  const updateMutation = useMutation({
    mutationFn: (updatedSettings: { key: string, value: string }[]) => 
      api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!");
      queryClient.invalidateQueries({ queryKey: ['adminSettings'] });
    },
    onError: () => toast.error("Save Failed")
  });

  const testSmsMutation = useMutation({
    mutationFn: (mobile: string) => api.post('/admin/notifications/test-sms', { mobile }),
    onSuccess: (data) => {
      toast.success("Test Sent", { description: data.data.message });
    },
    onError: (e: any) => toast.error("Test Failed", { description: e.response?.data?.message })
  });

  const handleSettingChange = (key: string, value: string) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };

  const handleSave = () => {
    // Convert map back to array for the API
    const payload = Object.entries(settings).map(([key, value]) => ({ key, value }));
    updateMutation.mutate(payload);
  };

  if (isLoading) return <p>Loading settings...</p>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Notification Settings</h1>
        <Button onClick={handleSave} disabled={updateMutation.isPending}>
          <Save className="mr-2 h-4 w-4" />
          {updateMutation.isPending ? "Saving..." : "Save All Changes"}
        </Button>
      </div>

      <Tabs defaultValue="sms" className="w-full">
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="sms"><MessageSquare className="mr-2 h-4 w-4"/> SMS</TabsTrigger>
          <TabsTrigger value="email"><Mail className="mr-2 h-4 w-4"/> Email</TabsTrigger>
          <TabsTrigger value="push"><Bell className="mr-2 h-4 w-4"/> Push</TabsTrigger>
        </TabsList>

        {/* --- SMS Settings --- */}
        <TabsContent value="sms" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>SMS Gateway</CardTitle>
              <CardDescription>Configure your provider for sending all SMS.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Provider</Label>
                <Select
                  value={settings['sms_provider'] || 'log'}
                  onValueChange={(v) => handleSettingChange('sms_provider', v)}
                >
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="log">Log (Disable SMS, write to log)</SelectItem>
                    <SelectItem value="msg91">MSG91</SelectItem>
                    <SelectItem value="twilio">Twilio</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* --- MSG91 Settings --- */}
              {settings['sms_provider'] === 'msg91' && (
                <Card className="p-4 bg-muted/30">
                  <CardTitle className="text-lg mb-4">MSG91 Settings</CardTitle>
                  <div className="space-y-4">
                    <div className="space-y-2">
                      <Label>Auth Key</Label>
                      <Input 
                        type="password"
                        value={settings['msg91_auth_key'] || ''}
                        onChange={(e) => handleSettingChange('msg91_auth_key', e.target.value)}
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Sender ID</Label>
                        <Input 
                          value={settings['msg91_sender_id'] || ''}
                          onChange={(e) => handleSettingChange('msg91_sender_id', e.target.value)}
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>DLT Template ID (for OTPs)</Label>
                        <Input 
                          value={settings['msg91_dlt_te_id'] || ''}
                          onChange={(e) => handleSettingChange('msg91_dlt_te_id', e.target.value)}
                        />
                      </div>
                    </div>
                  </div>
                </Card>
              )}
              
              {/* --- Twilio Settings --- */}
              {settings['sms_provider'] === 'twilio' && (
                <Card className="p-4 bg-muted/30">
                  <CardTitle className="text-lg mb-4">Twilio Settings</CardTitle>
                  <div className="space-y-4">
                    <div className="space-y-2">
                      <Label>Account SID</Label>
                      <Input 
                        value={settings['twilio_sid'] || ''}
                        onChange={(e) => handleSettingChange('twilio_sid', e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Auth Token</Label>
                      <Input 
                        type="password"
                        value={settings['twilio_token'] || ''}
                        onChange={(e) => handleSettingChange('twilio_token', e.target.value)}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>From Phone Number</Label>
                      <Input 
                        value={settings['twilio_from'] || ''}
                        onChange={(e) => handleSettingChange('twilio_from', e.target.value)}
                      />
                    </div>
                  </div>
                </Card>
              )}
            </CardContent>
          </Card>
          
          {/* --- Test SMS --- */}
          <Card>
            <CardHeader><CardTitle>Test SMS Delivery</CardTitle></CardHeader>
            <CardContent className="flex gap-4 items-end">
              <div className="flex-1 space-y-2">
                <Label>10-Digit Mobile Number (e.g., 9876543210)</Label>
                <Input value={testMobile} onChange={e => setTestMobile(e.target.value)} />
              </div>
              <Button 
                onClick={() => testSmsMutation.mutate(testMobile)} 
                disabled={testSmsMutation.isPending || !testMobile}
              >
                <Send className="mr-2 h-4 w-4" />
                {testSmsMutation.isPending ? "Sending..." : "Send Test"}
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="email">
          <Card>
            <CardHeader><CardTitle>Email Settings (Mailgun/SMTP)</CardTitle></CardHeader>
            <CardContent>
              <p className="text-muted-foreground">Email settings are configured in the <strong>.env</strong> file (e.g., `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`).</p>
            </CardContent>
          </Card>
        </TabsContent>
        
        <TabsContent value="push">
          <Card>
            <CardHeader><CardTitle>Push Notifications (Firebase)</CardTitle></CardHeader>
            <CardContent>
              <p className="text-muted-foreground">Coming in V2.0.</p>
            </CardContent>
          </Card>
        </TabsContent>

      </Tabs>
    </div>
  );
}