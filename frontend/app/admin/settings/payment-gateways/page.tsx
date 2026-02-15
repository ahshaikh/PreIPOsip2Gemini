// V-FINAL-1730-572 (Created)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Save, CreditCard } from "lucide-react";

interface Setting {
  key: string;
  value: string;
}

export default function PaymentGatewaySettingsPage() {
  const queryClient = useQueryClient();
  const [settings, setSettings] = useState<Record<string, string>>({});

  const { data, isLoading } = useQuery({
    queryKey: ['adminSettings'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  });

  // Migrate onSuccess logic to useEffect
  useEffect(() => {
    if (data) {
      // Flatten all financial settings into a simple map
      const flatMap: Record<string, string> = {};
      Object.values(data.financial || {}).forEach((setting: any) => {
        flatMap[setting.key] = setting.value;
      });
      setSettings(flatMap);
    }
  });

  const mutation = useMutation({
    mutationFn: (updatedSettings: Setting[]) => api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!");
      queryClient.invalidateQueries({ queryKey: ['adminSettings'] });
    },
    onError: () => toast.error("Save Failed")
  });

  const handleSettingChange = (key: string, value: string) => {
    setSettings(prev => ({ ...prev, [key]: value }));
  };
  
  const handleSave = () => {
    const payload = Object.entries(settings).map(([key, value]) => ({ key, value }));
    mutation.mutate(payload);
  };
  
  if (isLoading) return <p>Loading settings...</p>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Payment Gateway Settings</h1>
        <Button onClick={handleSave} disabled={mutation.isPending}>
          <Save className="mr-2 h-4 w-4" />
          {mutation.isPending ? "Saving..." : "Save Changes"}
        </Button>
      </div>

      <Tabs defaultValue="razorpay" className="w-full">
        <TabsList>
          <TabsTrigger value="razorpay"><CreditCard className="mr-2 h-4 w-4"/> Razorpay</TabsTrigger>
        </TabsList>

        {/* --- Razorpay Settings --- */}
        <TabsContent value="razorpay" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Razorpay Credentials</CardTitle>
              <CardDescription>
                Configure the API keys for processing payments and mandates.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Key ID</Label>
                <Input 
                  value={settings['razorpay_key_id'] || ''}
                  onChange={(e) => handleSettingChange('razorpay_key_id', e.target.value)}
                  placeholder="rzp_live_..."
                />
              </div>
              <div className="space-y-2">
                <Label>Key Secret</Label>
                <Input 
                  type="password"
                  value={settings['razorpay_key_secret'] || ''}
                  onChange={(e) => handleSettingChange('razorpay_key_secret', e.target.value)}
                  placeholder="**************"
                />
              </div>
              <div className="space-y-2">
                <Label>Webhook Secret</Label>
                <Input 
                  type="password"
                  value={settings['razorpay_webhook_secret'] || ''}
                  onChange={(e) => handleSettingChange('razorpay_webhook_secret', e.target.value)}
                  placeholder="Used to verify webhook authenticity"
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}