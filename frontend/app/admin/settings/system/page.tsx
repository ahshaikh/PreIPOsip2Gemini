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
      value: String(s.value) // Ensure all values are strings
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
              <div className="space-y-4">
                <div>
                  <h4 className="font-medium mb-2 text-sm text-muted-foreground">Core Modules</h4>
                  {renderSettings([
                    'registration_enabled', 'login_enabled', 'investment_enabled',
                    'withdrawal_enabled', 'support_tickets_enabled'
                  ])}
                </div>
                <Separator />
                <div>
                  <h4 className="font-medium mb-2 text-sm text-muted-foreground">KYC & Verification</h4>
                  {renderSettings(['kyc_enabled', 'kyc_required_for_investment'])}
                </div>
                <Separator />
                <div>
                  <h4 className="font-medium mb-2 text-sm text-muted-foreground">Referral & Rewards</h4>
                  {renderSettings(['referral_enabled', 'lucky_draw_enabled', 'profit_sharing_enabled'])}
                </div>
                <Separator />
                <div>
                  <h4 className="font-medium mb-2 text-sm text-muted-foreground">Bonus Modules</h4>
                  {renderSettings([
                    'progressive_bonus_enabled', 'milestone_bonus_enabled',
                    'consistency_bonus_enabled', 'celebration_bonus_enabled'
                  ])}
                </div>
              </div>
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

          {/* KYC Document Requirements */}
          <Card className="border-blue-200">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-blue-700">
                <Shield className="h-5 w-5" />
                Complete KYC Requirements
              </CardTitle>
              <CardDescription>
                Configure which documents users must upload to complete KYC verification
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4">
                <div className="p-4 border rounded-lg bg-muted/30">
                  <h4 className="font-medium mb-3">Required Identity Documents</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">Aadhaar Card (Front)</Label>
                        <p className="text-sm text-muted-foreground">Front side of Aadhaar card with photo and details</p>
                      </div>
                      {getSetting('kyc_require_aadhaar_front') ? (
                        <SettingInput setting={getSetting('kyc_require_aadhaar_front')} onChange={(v) => handleSettingChange('kyc_require_aadhaar_front', v)} />
                      ) : (
                        <Switch defaultChecked />
                      )}
                    </div>
                    <Separator />
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">Aadhaar Card (Back)</Label>
                        <p className="text-sm text-muted-foreground">Back side of Aadhaar card with address</p>
                      </div>
                      {getSetting('kyc_require_aadhaar_back') ? (
                        <SettingInput setting={getSetting('kyc_require_aadhaar_back')} onChange={(v) => handleSettingChange('kyc_require_aadhaar_back', v)} />
                      ) : (
                        <Switch defaultChecked />
                      )}
                    </div>
                    <Separator />
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">PAN Card</Label>
                        <p className="text-sm text-muted-foreground">Permanent Account Number card for tax purposes</p>
                      </div>
                      {getSetting('kyc_require_pan_card') ? (
                        <SettingInput setting={getSetting('kyc_require_pan_card')} onChange={(v) => handleSettingChange('kyc_require_pan_card', v)} />
                      ) : (
                        <Switch defaultChecked />
                      )}
                    </div>
                  </div>
                </div>

                <div className="p-4 border rounded-lg bg-muted/30">
                  <h4 className="font-medium mb-3">Required Financial Documents</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">Demat Account Sheet</Label>
                        <p className="text-sm text-muted-foreground">Client Master List (CML) or Demat holding statement</p>
                      </div>
                      {getSetting('kyc_require_demat_sheet') ? (
                        <SettingInput setting={getSetting('kyc_require_demat_sheet')} onChange={(v) => handleSettingChange('kyc_require_demat_sheet', v)} />
                      ) : (
                        <Switch defaultChecked />
                      )}
                    </div>
                    <Separator />
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">Bank Account Details</Label>
                        <p className="text-sm text-muted-foreground">Cancelled cheque or bank statement with account details</p>
                      </div>
                      {getSetting('kyc_require_bank_details') ? (
                        <SettingInput setting={getSetting('kyc_require_bank_details')} onChange={(v) => handleSettingChange('kyc_require_bank_details', v)} />
                      ) : (
                        <Switch defaultChecked />
                      )}
                    </div>
                  </div>
                </div>

                <div className="p-4 border rounded-lg bg-muted/30">
                  <h4 className="font-medium mb-3">KYC Approval Settings</h4>
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">Enable Auto-Approval</Label>
                        <p className="text-sm text-muted-foreground">Automatically approve KYC if all documents pass AI verification</p>
                      </div>
                      {getSetting('kyc_auto_approval_enabled') ? (
                        <SettingInput setting={getSetting('kyc_auto_approval_enabled')} onChange={(v) => handleSettingChange('kyc_auto_approval_enabled', v)} />
                      ) : (
                        <Switch />
                      )}
                    </div>
                    <Separator />
                    <div className="flex items-center justify-between">
                      <div>
                        <Label className="text-base">Require Admin Approval</Label>
                        <p className="text-sm text-muted-foreground">Always require manual admin review regardless of auto-verification</p>
                      </div>
                      {getSetting('kyc_require_admin_approval') ? (
                        <SettingInput setting={getSetting('kyc_require_admin_approval')} onChange={(v) => handleSettingChange('kyc_require_admin_approval', v)} />
                      ) : (
                        <Switch defaultChecked />
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* PMLA Compliance */}
          <Card className="border-red-200 bg-red-50/50 dark:bg-red-950/20">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                <AlertTriangle className="h-5 w-5" />
                PMLA (Prevention of Money Laundering) Compliance
              </CardTitle>
              <CardDescription>
                Configure anti-money laundering checks and restrictions as per PMLA guidelines
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="p-4 border border-red-200 rounded-lg bg-white dark:bg-background">
                <h4 className="font-medium mb-3 text-red-700 dark:text-red-400">Deposit Verification</h4>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Verify Deposit Source Account</Label>
                      <p className="text-sm text-muted-foreground">
                        Require deposits to come only from the bank account registered in user's KYC
                      </p>
                    </div>
                    {getSetting('pmla_verify_deposit_source') ? (
                      <SettingInput setting={getSetting('pmla_verify_deposit_source')} onChange={(v) => handleSettingChange('pmla_verify_deposit_source', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Same Name Verification</Label>
                      <p className="text-sm text-muted-foreground">
                        Verify that the depositing account holder name matches user's registered name
                      </p>
                    </div>
                    {getSetting('pmla_same_name_check') ? (
                      <SettingInput setting={getSetting('pmla_same_name_check')} onChange={(v) => handleSettingChange('pmla_same_name_check', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Flag Third-Party Deposits</Label>
                      <p className="text-sm text-muted-foreground">
                        Automatically flag deposits from accounts not registered with the user
                      </p>
                    </div>
                    {getSetting('pmla_flag_third_party') ? (
                      <SettingInput setting={getSetting('pmla_flag_third_party')} onChange={(v) => handleSettingChange('pmla_flag_third_party', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                </div>
              </div>

              <div className="p-4 border border-red-200 rounded-lg bg-white dark:bg-background">
                <h4 className="font-medium mb-3 text-red-700 dark:text-red-400">KYC-Based Restrictions</h4>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Block Deposits Without Complete KYC</Label>
                      <p className="text-sm text-muted-foreground">
                        Users cannot deposit funds until all KYC documents are uploaded and approved
                      </p>
                    </div>
                    {getSetting('pmla_block_deposit_no_kyc') ? (
                      <SettingInput setting={getSetting('pmla_block_deposit_no_kyc')} onChange={(v) => handleSettingChange('pmla_block_deposit_no_kyc', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Block Withdrawals Without KYC</Label>
                      <p className="text-sm text-muted-foreground">
                        Users cannot withdraw funds until KYC is complete and approved
                      </p>
                    </div>
                    {getSetting('pmla_block_withdrawal_no_kyc') ? (
                      <SettingInput setting={getSetting('pmla_block_withdrawal_no_kyc')} onChange={(v) => handleSettingChange('pmla_block_withdrawal_no_kyc', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Block Withdrawals for Manual Deposits</Label>
                      <p className="text-sm text-muted-foreground">
                        If user made a manual deposit (outside system), block withdrawals until verified
                      </p>
                    </div>
                    {getSetting('pmla_block_withdrawal_manual_deposit') ? (
                      <SettingInput setting={getSetting('pmla_block_withdrawal_manual_deposit')} onChange={(v) => handleSettingChange('pmla_block_withdrawal_manual_deposit', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                </div>
              </div>

              <div className="p-4 border border-red-200 rounded-lg bg-white dark:bg-background">
                <h4 className="font-medium mb-3 text-red-700 dark:text-red-400">Transaction Monitoring</h4>
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label className="text-base">High Value Transaction Threshold (₹)</Label>
                    <p className="text-sm text-muted-foreground">
                      Flag transactions above this amount for manual review
                    </p>
                    {getSetting('pmla_high_value_threshold') ? (
                      <SettingInput setting={getSetting('pmla_high_value_threshold')} onChange={(v) => handleSettingChange('pmla_high_value_threshold', v)} />
                    ) : (
                      <Input type="number" placeholder="1000000" defaultValue="1000000" className="max-w-[200px]" />
                    )}
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Daily Transaction Limit (₹)</Label>
                    <p className="text-sm text-muted-foreground">
                      Maximum total transaction amount allowed per day per user
                    </p>
                    {getSetting('pmla_daily_transaction_limit') ? (
                      <SettingInput setting={getSetting('pmla_daily_transaction_limit')} onChange={(v) => handleSettingChange('pmla_daily_transaction_limit', v)} />
                    ) : (
                      <Input type="number" placeholder="5000000" defaultValue="5000000" className="max-w-[200px]" />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Require Source of Funds Declaration</Label>
                      <p className="text-sm text-muted-foreground">
                        Ask users to declare source of funds for large deposits
                      </p>
                    </div>
                    {getSetting('pmla_require_source_declaration') ? (
                      <SettingInput setting={getSetting('pmla_require_source_declaration')} onChange={(v) => handleSettingChange('pmla_require_source_declaration', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                </div>
              </div>

              <div className="p-4 bg-red-100 dark:bg-red-900/30 rounded-lg">
                <p className="text-sm text-red-800 dark:text-red-300 flex items-start gap-2">
                  <Info className="h-4 w-4 mt-0.5 flex-shrink-0" />
                  <span>
                    <strong>Important:</strong> These settings help comply with the Prevention of Money Laundering Act (PMLA), 2002
                    and RBI guidelines. Ensure your platform maintains proper audit trails and reports suspicious transactions
                    to the Financial Intelligence Unit (FIU-IND) as required by law.
                  </span>
                </p>
              </div>
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

          {/* Push Notification Credentials */}
          <Card className="border-orange-200">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-orange-700">
                <Bell className="h-5 w-5" />
                Push Notification Credentials
              </CardTitle>
              <CardDescription>Configure Firebase Cloud Messaging (FCM) for push notifications</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="p-4 border rounded-lg bg-muted/30">
                <h4 className="font-medium mb-3">Firebase Configuration</h4>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Push Notifications</Label>
                      <p className="text-sm text-muted-foreground">Send push notifications to users' devices</p>
                    </div>
                    {getSetting('push_notifications_enabled') ? (
                      <SettingInput setting={getSetting('push_notifications_enabled')} onChange={(v) => handleSettingChange('push_notifications_enabled', v)} />
                    ) : (
                      <Switch />
                    )}
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Firebase Project ID</Label>
                    <p className="text-sm text-muted-foreground">Your Firebase project identifier</p>
                    {getSetting('firebase_project_id') ? (
                      <SettingInput setting={getSetting('firebase_project_id')} onChange={(v) => handleSettingChange('firebase_project_id', v)} />
                    ) : (
                      <Input placeholder="my-project-12345" />
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label className="text-base">Firebase Server Key</Label>
                    <p className="text-sm text-muted-foreground">Legacy server key for FCM (Cloud Messaging)</p>
                    {getSetting('firebase_server_key') ? (
                      <SettingInput setting={getSetting('firebase_server_key')} onChange={(v) => handleSettingChange('firebase_server_key', v)} />
                    ) : (
                      <Input type="password" placeholder="AAAA..." />
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label className="text-base">Firebase Sender ID</Label>
                    <p className="text-sm text-muted-foreground">Unique sender ID for your project</p>
                    {getSetting('firebase_sender_id') ? (
                      <SettingInput setting={getSetting('firebase_sender_id')} onChange={(v) => handleSettingChange('firebase_sender_id', v)} />
                    ) : (
                      <Input placeholder="123456789012" />
                    )}
                  </div>
                </div>
              </div>

              <div className="p-4 border rounded-lg bg-muted/30">
                <h4 className="font-medium mb-3">Web Push (VAPID Keys)</h4>
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label className="text-base">VAPID Public Key</Label>
                    <p className="text-sm text-muted-foreground">Public key for web push subscriptions</p>
                    {getSetting('vapid_public_key') ? (
                      <SettingInput setting={getSetting('vapid_public_key')} onChange={(v) => handleSettingChange('vapid_public_key', v)} />
                    ) : (
                      <Input placeholder="BEl62i..." />
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label className="text-base">VAPID Private Key</Label>
                    <p className="text-sm text-muted-foreground">Private key for signing push messages</p>
                    {getSetting('vapid_private_key') ? (
                      <SettingInput setting={getSetting('vapid_private_key')} onChange={(v) => handleSettingChange('vapid_private_key', v)} />
                    ) : (
                      <Input type="password" placeholder="..." />
                    )}
                  </div>
                </div>
              </div>

              <div className="p-4 border rounded-lg bg-muted/30">
                <h4 className="font-medium mb-3">OneSignal (Alternative)</h4>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Use OneSignal</Label>
                      <p className="text-sm text-muted-foreground">Use OneSignal instead of Firebase for push notifications</p>
                    </div>
                    {getSetting('use_onesignal') ? (
                      <SettingInput setting={getSetting('use_onesignal')} onChange={(v) => handleSettingChange('use_onesignal', v)} />
                    ) : (
                      <Switch />
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label className="text-base">OneSignal App ID</Label>
                    {getSetting('onesignal_app_id') ? (
                      <SettingInput setting={getSetting('onesignal_app_id')} onChange={(v) => handleSettingChange('onesignal_app_id', v)} />
                    ) : (
                      <Input placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" />
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label className="text-base">OneSignal REST API Key</Label>
                    {getSetting('onesignal_api_key') ? (
                      <SettingInput setting={getSetting('onesignal_api_key')} onChange={(v) => handleSettingChange('onesignal_api_key', v)} />
                    ) : (
                      <Input type="password" placeholder="..." />
                    )}
                  </div>
                </div>
              </div>

              <div className="p-4 bg-blue-50 dark:bg-blue-950/30 rounded-lg">
                <p className="text-sm text-blue-800 dark:text-blue-300 flex items-start gap-2">
                  <Info className="h-4 w-4 mt-0.5 flex-shrink-0" />
                  <span>
                    To send push notifications, users must first grant permission in their browser/app.
                    Configure these credentials from your Firebase Console or OneSignal Dashboard.
                  </span>
                </p>
              </div>
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
            </CardContent>
          </Card>

          {/* API & Integration Settings */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Globe className="h-5 w-5" />
                API & Integration Settings
              </CardTitle>
              <CardDescription>Configure external API integrations and rate limits</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 border rounded-lg bg-muted/30">
                <h4 className="font-medium mb-3">Rate Limiting</h4>
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label className="text-base">API Rate Limit (requests/minute)</Label>
                    <p className="text-sm text-muted-foreground">Maximum API requests per minute per user</p>
                    {getSetting('api_rate_limit') ? (
                      <SettingInput setting={getSetting('api_rate_limit')} onChange={(v) => handleSettingChange('api_rate_limit', v)} />
                    ) : (
                      <Input type="number" placeholder="100" defaultValue="100" className="max-w-[200px]" />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable API Throttling</Label>
                      <p className="text-sm text-muted-foreground">Automatically throttle requests exceeding rate limit</p>
                    </div>
                    {getSetting('api_throttling_enabled') ? (
                      <SettingInput setting={getSetting('api_throttling_enabled')} onChange={(v) => handleSettingChange('api_throttling_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                </div>
              </div>
              <div className="p-4 border rounded-lg bg-muted/30">
                <h4 className="font-medium mb-3">External APIs</h4>
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label className="text-base">KYC Verification API URL</Label>
                    <p className="text-sm text-muted-foreground">Third-party KYC verification service endpoint</p>
                    {getSetting('kyc_api_url') ? (
                      <SettingInput setting={getSetting('kyc_api_url')} onChange={(v) => handleSettingChange('kyc_api_url', v)} />
                    ) : (
                      <Input placeholder="https://api.kycprovider.com/verify" />
                    )}
                  </div>
                  <div className="space-y-2">
                    <Label className="text-base">KYC API Key</Label>
                    {getSetting('kyc_api_key') ? (
                      <SettingInput setting={getSetting('kyc_api_key')} onChange={(v) => handleSettingChange('kyc_api_key', v)} />
                    ) : (
                      <Input type="password" placeholder="..." />
                    )}
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Payment Gateway Webhook URL</Label>
                    <p className="text-sm text-muted-foreground">URL for receiving payment callbacks</p>
                    {getSetting('payment_webhook_url') ? (
                      <SettingInput setting={getSetting('payment_webhook_url')} onChange={(v) => handleSettingChange('payment_webhook_url', v)} />
                    ) : (
                      <Input placeholder="https://yoursite.com/api/webhooks/payment" />
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Caching & Performance */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Database className="h-5 w-5" />
                Caching & Performance
              </CardTitle>
              <CardDescription>Configure caching and performance optimization settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 border rounded-lg bg-muted/30">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Redis Caching</Label>
                      <p className="text-sm text-muted-foreground">Use Redis for caching frequently accessed data</p>
                    </div>
                    {getSetting('redis_caching_enabled') ? (
                      <SettingInput setting={getSetting('redis_caching_enabled')} onChange={(v) => handleSettingChange('redis_caching_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Cache TTL (seconds)</Label>
                    <p className="text-sm text-muted-foreground">Default time-to-live for cached data</p>
                    {getSetting('cache_ttl') ? (
                      <SettingInput setting={getSetting('cache_ttl')} onChange={(v) => handleSettingChange('cache_ttl', v)} />
                    ) : (
                      <Input type="number" placeholder="3600" defaultValue="3600" className="max-w-[200px]" />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Query Caching</Label>
                      <p className="text-sm text-muted-foreground">Cache database query results</p>
                    </div>
                    {getSetting('query_caching_enabled') ? (
                      <SettingInput setting={getSetting('query_caching_enabled')} onChange={(v) => handleSettingChange('query_caching_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable CDN for Assets</Label>
                      <p className="text-sm text-muted-foreground">Serve static assets through CDN</p>
                    </div>
                    {getSetting('cdn_enabled') ? (
                      <SettingInput setting={getSetting('cdn_enabled')} onChange={(v) => handleSettingChange('cdn_enabled', v)} />
                    ) : (
                      <Switch />
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Queue & Background Jobs */}
          <Card>
            <CardHeader>
              <CardTitle>Queue & Background Jobs</CardTitle>
              <CardDescription>Configure background job processing settings</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 border rounded-lg bg-muted/30">
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label className="text-base">Queue Driver</Label>
                    <p className="text-sm text-muted-foreground">Backend for processing background jobs</p>
                    <Select defaultValue="redis">
                      <SelectTrigger className="max-w-[200px]">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="sync">Sync (No Queue)</SelectItem>
                        <SelectItem value="database">Database</SelectItem>
                        <SelectItem value="redis">Redis</SelectItem>
                        <SelectItem value="sqs">Amazon SQS</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Max Job Retries</Label>
                    <p className="text-sm text-muted-foreground">Maximum retry attempts for failed jobs</p>
                    {getSetting('max_job_retries') ? (
                      <SettingInput setting={getSetting('max_job_retries')} onChange={(v) => handleSettingChange('max_job_retries', v)} />
                    ) : (
                      <Input type="number" placeholder="3" defaultValue="3" className="max-w-[200px]" />
                    )}
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Job Timeout (seconds)</Label>
                    <p className="text-sm text-muted-foreground">Maximum execution time for background jobs</p>
                    {getSetting('job_timeout') ? (
                      <SettingInput setting={getSetting('job_timeout')} onChange={(v) => handleSettingChange('job_timeout', v)} />
                    ) : (
                      <Input type="number" placeholder="60" defaultValue="60" className="max-w-[200px]" />
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Logging & Debugging */}
          <Card>
            <CardHeader>
              <CardTitle>Logging & Debugging</CardTitle>
              <CardDescription>Configure application logging and debugging options</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 border rounded-lg bg-muted/30">
                <div className="space-y-4">
                  <div className="space-y-2">
                    <Label className="text-base">Log Level</Label>
                    <p className="text-sm text-muted-foreground">Minimum severity level for logging</p>
                    <Select defaultValue="error">
                      <SelectTrigger className="max-w-[200px]">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="debug">Debug (All)</SelectItem>
                        <SelectItem value="info">Info</SelectItem>
                        <SelectItem value="warning">Warning</SelectItem>
                        <SelectItem value="error">Error</SelectItem>
                        <SelectItem value="critical">Critical Only</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Debug Mode</Label>
                      <p className="text-sm text-muted-foreground text-red-600">WARNING: Never enable in production!</p>
                    </div>
                    {getSetting('debug_mode') ? (
                      <SettingInput setting={getSetting('debug_mode')} onChange={(v) => handleSettingChange('debug_mode', v)} />
                    ) : (
                      <Switch />
                    )}
                  </div>
                  <Separator />
                  <div className="space-y-2">
                    <Label className="text-base">Log Retention (days)</Label>
                    <p className="text-sm text-muted-foreground">How long to keep log files</p>
                    {getSetting('log_retention_days') ? (
                      <SettingInput setting={getSetting('log_retention_days')} onChange={(v) => handleSettingChange('log_retention_days', v)} />
                    ) : (
                      <Input type="number" placeholder="30" defaultValue="30" className="max-w-[200px]" />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable SQL Query Logging</Label>
                      <p className="text-sm text-muted-foreground">Log all database queries (performance impact)</p>
                    </div>
                    {getSetting('sql_logging_enabled') ? (
                      <SettingInput setting={getSetting('sql_logging_enabled')} onChange={(v) => handleSettingChange('sql_logging_enabled', v)} />
                    ) : (
                      <Switch />
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Feature Flags */}
          <Card>
            <CardHeader>
              <CardTitle>Feature Flags</CardTitle>
              <CardDescription>Enable or disable experimental features</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 border rounded-lg bg-muted/30">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Beta Features</Label>
                      <p className="text-sm text-muted-foreground">Show beta features to all users</p>
                    </div>
                    {getSetting('beta_features_enabled') ? (
                      <SettingInput setting={getSetting('beta_features_enabled')} onChange={(v) => handleSettingChange('beta_features_enabled', v)} />
                    ) : (
                      <Switch />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Dark Mode</Label>
                      <p className="text-sm text-muted-foreground">Allow users to toggle dark mode</p>
                    </div>
                    {getSetting('dark_mode_enabled') ? (
                      <SettingInput setting={getSetting('dark_mode_enabled')} onChange={(v) => handleSettingChange('dark_mode_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Live Chat</Label>
                      <p className="text-sm text-muted-foreground">Show live chat widget on user dashboard</p>
                    </div>
                    {getSetting('live_chat_enabled') ? (
                      <SettingInput setting={getSetting('live_chat_enabled')} onChange={(v) => handleSettingChange('live_chat_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable Multi-Language</Label>
                      <p className="text-sm text-muted-foreground">Allow users to switch languages</p>
                    </div>
                    {getSetting('multi_language_enabled') ? (
                      <SettingInput setting={getSetting('multi_language_enabled')} onChange={(v) => handleSettingChange('multi_language_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Enable SIP Calculator</Label>
                      <p className="text-sm text-muted-foreground">Show SIP calculator on landing page</p>
                    </div>
                    {getSetting('sip_calculator_enabled') ? (
                      <SettingInput setting={getSetting('sip_calculator_enabled')} onChange={(v) => handleSettingChange('sip_calculator_enabled', v)} />
                    ) : (
                      <Switch defaultChecked />
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Data Management */}
          <Card className="border-red-300">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-red-700">
                <AlertTriangle className="h-5 w-5" />
                Data Management (Danger Zone)
              </CardTitle>
              <CardDescription className="text-red-600">
                These actions are irreversible. Proceed with extreme caution.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 border border-red-200 rounded-lg bg-red-50 dark:bg-red-950/30">
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Clear All Caches</Label>
                      <p className="text-sm text-muted-foreground">Clear Redis, query, and view caches</p>
                    </div>
                    <Button variant="outline" className="border-red-300 text-red-600 hover:bg-red-50">
                      Clear Caches
                    </Button>
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Regenerate API Keys</Label>
                      <p className="text-sm text-muted-foreground">Generate new API keys (invalidates existing)</p>
                    </div>
                    <Button variant="outline" className="border-red-300 text-red-600 hover:bg-red-50">
                      Regenerate
                    </Button>
                  </div>
                  <Separator />
                  <div className="flex items-center justify-between">
                    <div>
                      <Label className="text-base">Purge Old Logs</Label>
                      <p className="text-sm text-muted-foreground">Delete logs older than retention period</p>
                    </div>
                    <Button variant="outline" className="border-red-300 text-red-600 hover:bg-red-50">
                      Purge Logs
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}