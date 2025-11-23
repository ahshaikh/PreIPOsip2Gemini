// V-PHASE6-1730-132 (Created - Revised) | V-FINAL-1730-268 (Upgraded with Text Inputs) | V-FINAL-1730-449 (Full Admin Security) | V-FINAL-1730-491 (Dynamic UI) | V-ENHANCED-SETTINGS
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useEffect, useState } from "react";
import { Shield, AlertTriangle, Settings, DollarSign, Bell, Globe, Database, Lock, Mail, Phone, Info, RefreshCw, HelpCircle } from "lucide-react";

interface Setting {
  id: number;
  key: string;
  value: string;
  type: 'boolean' | 'string' | 'number' | 'text';
  group: string;
}

// Setting descriptions for help tooltips
const settingDescriptions: Record<string, string> = {
  maintenance_mode: "When enabled, only admins can access the site. Users see a maintenance message.",
  registration_enabled: "Allow new users to register on the platform.",
  login_enabled: "Allow users to log in. Disable during critical maintenance.",
  investment_enabled: "Allow users to make new investments and payments.",
  withdrawal_enabled: "Allow users to request withdrawals from their wallet.",
  support_tickets_enabled: "Allow users to create and view support tickets.",
  maintenance_message: "Message shown to users when maintenance mode is enabled.",
  min_withdrawal_amount: "Minimum amount (in ₹) users can withdraw.",
  auto_approval_max_amount: "Withdrawals up to this amount are auto-approved for trusted users.",
  payment_grace_period_days: "Number of days before a payment is marked as overdue.",
  referral_bonus_amount: "Fixed bonus amount (in ₹) given for successful referrals.",
  tds_rate: "Tax Deducted at Source rate (decimal, e.g., 0.10 for 10%).",
  tds_threshold: "TDS is applied only when withdrawal exceeds this amount.",
  password_history_limit: "Number of previous passwords to remember (prevents reuse).",
  fraud_amount_threshold: "Flag transactions above this amount for manual review.",
  fraud_new_user_days: "Consider users as 'new' for fraud checks within these days.",
  admin_ip_whitelist: "Enable IP whitelist restriction for admin access.",
  allowed_ips: "Comma-separated list of allowed admin IP addresses.",
  sms_provider: "SMS gateway provider (msg91, twilio, etc.).",
  email_provider: "Email service provider (smtp, mailgun, ses, etc.).",
  records_per_page: "Default number of records to show in admin tables.",
  session_timeout_minutes: "Auto-logout users after this many minutes of inactivity.",
  max_login_attempts: "Lock account after this many failed login attempts.",
  lockout_duration_minutes: "Account lockout duration after max failed attempts.",
  two_factor_required: "Require 2FA for all admin accounts.",
  email_verification_required: "Require email verification for new users.",
  mobile_verification_required: "Require mobile verification for new users.",
};

// Helper component to render the correct input based on type
function SettingInput({ setting, onChange, description }: { setting: Setting, onChange: (value: string) => void, description?: string }) {
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
      return <Input type="number" value={currentValue} onChange={handleChange} className="max-w-[200px]" />;
    case 'text':
      return <Textarea value={currentValue} onChange={handleChange} rows={4} />;
    case 'string':
    default:
      return <Input value={currentValue} onChange={handleChange} />;
  }
}

// Enhanced setting row with description
function SettingRow({ setting, onChange, customLabel }: { setting: Setting, onChange: (key: string, value: string) => void, customLabel?: string }) {
  const description = settingDescriptions[setting.key];
  const label = customLabel || setting.key.replace(/_/g, ' ');

  return (
    <div className="flex items-start justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors">
      <div className="flex-1 mr-4">
        <div className="flex items-center gap-2">
          <Label htmlFor={setting.key} className="text-base font-medium capitalize">
            {label}
          </Label>
          {description && (
            <span className="text-muted-foreground cursor-help" title={description}>
              <HelpCircle className="h-4 w-4" />
            </span>
          )}
        </div>
        {description && (
          <p className="text-sm text-muted-foreground mt-1">{description}</p>
        )}
        <code className="text-xs text-muted-foreground bg-muted px-1 rounded mt-1 inline-block">{setting.key}</code>
      </div>
      <div className="w-1/3 flex justify-end">
        <SettingInput
          setting={setting}
          onChange={(value) => onChange(setting.key, value)}
        />
      </div>
    </div>
  );
}

export default function SystemSettingsPage() {
  const queryClient = useQueryClient();
  const [settingsMap, setSettingsMap] = useState<Record<string, Setting>>({});
  const [hasChanges, setHasChanges] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['adminSettingsFull'],
    queryFn: async () => (await api.get('/admin/settings')).data,
  });

  useEffect(() => {
    if (data) {
      const flatMap: Record<string, Setting> = {};
      Object.values(data).forEach((group: any) => {
        group.forEach((setting: Setting) => {
          flatMap[setting.key] = setting;
        });
      });
      setSettingsMap(flatMap);
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (updatedSettings: { key: string, value: string }[]) =>
      api.put('/admin/settings', { settings: updatedSettings }),
    onSuccess: () => {
      toast.success("Settings Saved!", { description: "Changes may take a minute to apply." });
      queryClient.invalidateQueries({ queryKey: ['adminSettingsFull'] });
      setHasChanges(false);
    },
    onError: () => toast.error("Save Failed")
  });

  const handleSettingChange = (key: string, value: string) => {
    setSettingsMap(prev => ({
      ...prev,
      [key]: { ...prev[key], value: value }
    }));
    setHasChanges(true);
  };

  const handleSave = () => {
    const settingsPayload = Object.values(settingsMap).map(s => ({
      key: s.key,
      value: s.value
    }));
    mutation.mutate(settingsPayload);
  };

  const handleReset = () => {
    queryClient.invalidateQueries({ queryKey: ['adminSettingsFull'] });
    setHasChanges(false);
    toast.info("Settings Reset", { description: "All changes have been discarded." });
  };

  if (isLoading) return <div className="flex items-center justify-center p-8"><RefreshCw className="h-6 w-6 animate-spin mr-2" /> Loading settings...</div>;

  const getSetting = (key: string) => settingsMap[key];

  const renderSettings = (keys: string[]) => {
    return keys.map(key => {
      const setting = getSetting(key);
      if (!setting) return null;
      return <SettingRow key={key} setting={setting} onChange={handleSettingChange} />;
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">General Settings</h1>
          <p className="text-muted-foreground">Configure system-wide settings for your platform</p>
        </div>
        <div className="flex gap-2">
          {hasChanges && (
            <Button variant="outline" onClick={handleReset}>
              Discard Changes
            </Button>
          )}
          <Button onClick={handleSave} disabled={mutation.isPending || !hasChanges}>
            {mutation.isPending ? "Saving..." : hasChanges ? "Save All Changes" : "No Changes"}
          </Button>
        </div>
      </div>

      {hasChanges && (
        <Card className="border-yellow-500 bg-yellow-50 dark:bg-yellow-950">
          <CardContent className="pt-4">
            <div className="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
              <AlertTriangle className="h-4 w-4" />
              <span className="font-medium">You have unsaved changes</span>
            </div>
          </CardContent>
        </Card>
      )}

      <Tabs defaultValue="system" className="w-full">
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="system"><Settings className="mr-2 h-4 w-4"/> System</TabsTrigger>
          <TabsTrigger value="financial"><DollarSign className="mr-2 h-4 w-4"/> Financial</TabsTrigger>
          <TabsTrigger value="security"><Shield className="mr-2 h-4 w-4"/> Security</TabsTrigger>
          <TabsTrigger value="notifications"><Bell className="mr-2 h-4 w-4"/> Notifications</TabsTrigger>
          <TabsTrigger value="advanced"><Database className="mr-2 h-4 w-4"/> Advanced</TabsTrigger>
        </TabsList>

        {/* --- 1. System --- */}
        <TabsContent value="system" className="space-y-4 mt-4">
          <Card className="border-red-300 bg-red-50 dark:bg-red-950">
            <CardHeader>
              <CardTitle className="text-red-700 dark:text-red-300 flex items-center gap-2">
                <AlertTriangle className="h-5 w-5" />
                Maintenance Mode
              </CardTitle>
              <CardDescription>
                When enabled, all non-admin users will see a maintenance message instead of the site.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {getSetting('maintenance_mode') && (
                <div className="flex items-center justify-between">
                  <Label className="font-medium">Enable Maintenance Mode</Label>
                  <SettingInput setting={getSetting('maintenance_mode')} onChange={(v) => handleSettingChange('maintenance_mode', v)} />
                </div>
              )}
              {getSetting('maintenance_message') && (
                <div className="space-y-2">
                  <Label className="font-medium">Maintenance Message</Label>
                  <SettingInput setting={getSetting('maintenance_message')} onChange={(v) => handleSettingChange('maintenance_message', v)} />
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Globe className="h-5 w-5" />
                Module Controls
              </CardTitle>
              <CardDescription>Enable or disable major platform features</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings([
                'registration_enabled', 'login_enabled', 'investment_enabled',
                'withdrawal_enabled', 'support_tickets_enabled'
              ])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Display Settings</CardTitle>
              <CardDescription>Configure how data is displayed in the admin panel</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['records_per_page'])}
            </CardContent>
          </Card>
        </TabsContent>

        {/* --- 2. Financial --- */}
        <TabsContent value="financial" className="space-y-4 mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <DollarSign className="h-5 w-5" />
                Withdrawal Settings
              </CardTitle>
              <CardDescription>Configure withdrawal limits and auto-approval rules</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['min_withdrawal_amount', 'auto_approval_max_amount'])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Payment Settings</CardTitle>
              <CardDescription>Configure payment grace periods and rules</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['payment_grace_period_days'])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Tax & Compliance</CardTitle>
              <CardDescription>Configure TDS and other tax-related settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['tds_rate', 'tds_threshold'])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Referral & Bonuses</CardTitle>
              <CardDescription>Configure referral program bonuses</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['referral_bonus_amount'])}
            </CardContent>
          </Card>
        </TabsContent>

        {/* --- 3. Security --- */}
        <TabsContent value="security" className="space-y-4 mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Lock className="h-5 w-5" />
                Authentication Security
              </CardTitle>
              <CardDescription>Configure login security and session settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['password_history_limit', 'session_timeout_minutes', 'max_login_attempts', 'lockout_duration_minutes'])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Verification Requirements</CardTitle>
              <CardDescription>Configure required verification for new users</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['email_verification_required', 'mobile_verification_required', 'two_factor_required'])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Fraud Prevention</CardTitle>
              <CardDescription>Configure fraud detection thresholds</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['fraud_amount_threshold', 'fraud_new_user_days'])}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>IP Access Control</CardTitle>
              <CardDescription>Restrict admin access by IP address</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['admin_ip_whitelist', 'allowed_ips'])}
            </CardContent>
          </Card>
        </TabsContent>

        {/* --- 4. Notifications --- */}
        <TabsContent value="notifications" className="space-y-4 mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Mail className="h-5 w-5" />
                Email Configuration
              </CardTitle>
              <CardDescription>Configure email service provider settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['email_provider'])}
              <div className="p-4 bg-muted rounded-lg">
                <p className="text-sm text-muted-foreground">
                  <Info className="inline h-4 w-4 mr-1" />
                  Email provider credentials are configured in the .env file for security.
                </p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Phone className="h-5 w-5" />
                SMS Configuration
              </CardTitle>
              <CardDescription>Configure SMS gateway provider</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {renderSettings(['sms_provider', 'msg91_auth_key', 'msg91_sender_id', 'twilio_sid', 'twilio_token', 'twilio_from'])}
            </CardContent>
          </Card>
        </TabsContent>

        {/* --- 5. Advanced --- */}
        <TabsContent value="advanced" className="space-y-4 mt-4">
          <Card className="border-orange-300">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-orange-700">
                <AlertTriangle className="h-5 w-5" />
                Advanced Settings
              </CardTitle>
              <CardDescription className="text-orange-600">
                These settings can significantly impact system behavior. Change with caution.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 bg-orange-50 dark:bg-orange-950 rounded-lg text-sm">
                <p className="font-medium text-orange-800 dark:text-orange-200">Warning</p>
                <p className="text-orange-700 dark:text-orange-300">
                  Modifying advanced settings may affect system stability. Ensure you understand each setting before making changes.
                </p>
              </div>
              {/* Add any additional advanced settings here */}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}