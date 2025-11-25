'use client';

import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { toast } from "sonner";
import {
  Bell,
  Lock,
  Eye,
  Shield,
  Globe,
  Smartphone,
  Mail,
  MessageSquare,
  DollarSign,
  CreditCard,
} from "lucide-react";
import api from "@/lib/api";

interface NotificationSettings {
  email_notifications: boolean;
  sms_notifications: boolean;
  push_notifications: boolean;
  payment_alerts: boolean;
  investment_updates: boolean;
  promotional_emails: boolean;
  weekly_summary: boolean;
  kyc_updates: boolean;
  withdrawal_alerts: boolean;
  bonus_alerts: boolean;
}

interface SecuritySettings {
  two_factor_enabled: boolean;
  email_verification: boolean;
  login_alerts: boolean;
  session_timeout: number;
}

interface PreferenceSettings {
  language: string;
  currency: string;
  timezone: string;
  theme: string;
  date_format: string;
  number_format: string;
}

export default function SettingsPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState("notifications");

  // Fetch current settings
  const { data: settings, isLoading } = useQuery({
    queryKey: ['userSettings'],
    queryFn: async () => {
      const response = await api.get('/user/settings');
      const data = response.data;
      return data?.data || data || {};
    },
  });

  const [notificationSettings, setNotificationSettings] = useState<NotificationSettings>({
    email_notifications: settings?.notifications?.email_notifications ?? true,
    sms_notifications: settings?.notifications?.sms_notifications ?? true,
    push_notifications: settings?.notifications?.push_notifications ?? true,
    payment_alerts: settings?.notifications?.payment_alerts ?? true,
    investment_updates: settings?.notifications?.investment_updates ?? true,
    promotional_emails: settings?.notifications?.promotional_emails ?? false,
    weekly_summary: settings?.notifications?.weekly_summary ?? true,
    kyc_updates: settings?.notifications?.kyc_updates ?? true,
    withdrawal_alerts: settings?.notifications?.withdrawal_alerts ?? true,
    bonus_alerts: settings?.notifications?.bonus_alerts ?? true,
  });

  const [securitySettings, setSecuritySettings] = useState<SecuritySettings>({
    two_factor_enabled: settings?.security?.two_factor_enabled ?? false,
    email_verification: settings?.security?.email_verification ?? true,
    login_alerts: settings?.security?.login_alerts ?? true,
    session_timeout: settings?.security?.session_timeout ?? 30,
  });

  const [preferences, setPreferences] = useState<PreferenceSettings>({
    language: settings?.preferences?.language || 'en',
    currency: settings?.preferences?.currency || 'INR',
    timezone: settings?.preferences?.timezone || 'Asia/Kolkata',
    theme: settings?.preferences?.theme || 'light',
    date_format: settings?.preferences?.date_format || 'DD/MM/YYYY',
    number_format: settings?.preferences?.number_format || 'en-IN',
  });

  // Update settings mutation
  const updateSettingsMutation = useMutation({
    mutationFn: async (data: any) => {
      return await api.put('/user/settings', data);
    },
    onSuccess: () => {
      toast.success('Settings updated successfully');
      queryClient.invalidateQueries({ queryKey: ['userSettings'] });
    },
    onError: () => {
      toast.error('Failed to update settings');
    },
  });

  const handleNotificationToggle = (key: keyof NotificationSettings) => {
    const newSettings = {
      ...notificationSettings,
      [key]: !notificationSettings[key],
    };
    setNotificationSettings(newSettings);
    updateSettingsMutation.mutate({ notifications: newSettings });
  };

  const handleSecurityToggle = (key: keyof SecuritySettings) => {
    const newSettings = {
      ...securitySettings,
      [key]: typeof securitySettings[key] === 'boolean' ? !securitySettings[key] : securitySettings[key],
    };
    setSecuritySettings(newSettings);
    updateSettingsMutation.mutate({ security: newSettings });
  };

  const handlePreferenceChange = (key: keyof PreferenceSettings, value: string) => {
    const newPreferences = {
      ...preferences,
      [key]: value,
    };
    setPreferences(newPreferences);
    updateSettingsMutation.mutate({ preferences: newPreferences });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="text-muted-foreground">Loading settings...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Settings</h1>
        <p className="text-muted-foreground">
          Manage your account settings and preferences
        </p>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="notifications">
            <Bell className="mr-2 h-4 w-4" />
            Notifications
          </TabsTrigger>
          <TabsTrigger value="security">
            <Shield className="mr-2 h-4 w-4" />
            Security
          </TabsTrigger>
          <TabsTrigger value="preferences">
            <Globe className="mr-2 h-4 w-4" />
            Preferences
          </TabsTrigger>
          <TabsTrigger value="privacy">
            <Eye className="mr-2 h-4 w-4" />
            Privacy
          </TabsTrigger>
        </TabsList>

        {/* Notifications Tab */}
        <TabsContent value="notifications" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Email Notifications</CardTitle>
              <CardDescription>
                Manage email notification preferences
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Email Notifications</Label>
                  <p className="text-sm text-muted-foreground">
                    Receive notifications via email
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.email_notifications}
                  onCheckedChange={() => handleNotificationToggle('email_notifications')}
                />
              </div>

              <Separator />

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Payment Alerts</Label>
                  <p className="text-sm text-muted-foreground">
                    Get notified about payment confirmations and failures
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.payment_alerts}
                  onCheckedChange={() => handleNotificationToggle('payment_alerts')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Investment Updates</Label>
                  <p className="text-sm text-muted-foreground">
                    Receive updates about your investments and portfolio
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.investment_updates}
                  onCheckedChange={() => handleNotificationToggle('investment_updates')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Withdrawal Alerts</Label>
                  <p className="text-sm text-muted-foreground">
                    Get notified about withdrawal status changes
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.withdrawal_alerts}
                  onCheckedChange={() => handleNotificationToggle('withdrawal_alerts')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Bonus Alerts</Label>
                  <p className="text-sm text-muted-foreground">
                    Receive notifications when you earn bonuses
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.bonus_alerts}
                  onCheckedChange={() => handleNotificationToggle('bonus_alerts')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">KYC Updates</Label>
                  <p className="text-sm text-muted-foreground">
                    Get notified about KYC verification status
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.kyc_updates}
                  onCheckedChange={() => handleNotificationToggle('kyc_updates')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Weekly Summary</Label>
                  <p className="text-sm text-muted-foreground">
                    Receive weekly portfolio and activity summary
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.weekly_summary}
                  onCheckedChange={() => handleNotificationToggle('weekly_summary')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Promotional Emails</Label>
                  <p className="text-sm text-muted-foreground">
                    Receive offers, news, and promotional content
                  </p>
                </div>
                <Switch
                  checked={notificationSettings.promotional_emails}
                  onCheckedChange={() => handleNotificationToggle('promotional_emails')}
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>SMS & Push Notifications</CardTitle>
              <CardDescription>
                Manage SMS and push notification preferences
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5 flex items-center">
                  <MessageSquare className="mr-2 h-4 w-4 text-muted-foreground" />
                  <div>
                    <Label className="text-base">SMS Notifications</Label>
                    <p className="text-sm text-muted-foreground">
                      Receive critical alerts via SMS
                    </p>
                  </div>
                </div>
                <Switch
                  checked={notificationSettings.sms_notifications}
                  onCheckedChange={() => handleNotificationToggle('sms_notifications')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5 flex items-center">
                  <Smartphone className="mr-2 h-4 w-4 text-muted-foreground" />
                  <div>
                    <Label className="text-base">Push Notifications</Label>
                    <p className="text-sm text-muted-foreground">
                      Receive push notifications on your devices
                    </p>
                  </div>
                </div>
                <Switch
                  checked={notificationSettings.push_notifications}
                  onCheckedChange={() => handleNotificationToggle('push_notifications')}
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Security Tab */}
        <TabsContent value="security" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Account Security</CardTitle>
              <CardDescription>
                Manage your account security settings
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Two-Factor Authentication</Label>
                  <p className="text-sm text-muted-foreground">
                    Add an extra layer of security to your account
                  </p>
                </div>
                <Switch
                  checked={securitySettings.two_factor_enabled}
                  onCheckedChange={() => handleSecurityToggle('two_factor_enabled')}
                />
              </div>

              <Separator />

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Email Verification</Label>
                  <p className="text-sm text-muted-foreground">
                    Require email verification for sensitive actions
                  </p>
                </div>
                <Switch
                  checked={securitySettings.email_verification}
                  onCheckedChange={() => handleSecurityToggle('email_verification')}
                />
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label className="text-base">Login Alerts</Label>
                  <p className="text-sm text-muted-foreground">
                    Get notified when someone logs into your account
                  </p>
                </div>
                <Switch
                  checked={securitySettings.login_alerts}
                  onCheckedChange={() => handleSecurityToggle('login_alerts')}
                />
              </div>

              <Separator />

              <div className="space-y-2">
                <Label className="text-base">Session Timeout</Label>
                <p className="text-sm text-muted-foreground">
                  Automatically log out after period of inactivity
                </p>
                <Select
                  value={securitySettings.session_timeout.toString()}
                  onValueChange={(value) => {
                    setSecuritySettings({ ...securitySettings, session_timeout: parseInt(value) });
                    updateSettingsMutation.mutate({ security: { ...securitySettings, session_timeout: parseInt(value) } });
                  }}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="15">15 minutes</SelectItem>
                    <SelectItem value="30">30 minutes</SelectItem>
                    <SelectItem value="60">1 hour</SelectItem>
                    <SelectItem value="120">2 hours</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Change Password</CardTitle>
              <CardDescription>
                Update your password regularly for better security
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button variant="outline">
                <Lock className="mr-2 h-4 w-4" />
                Change Password
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Preferences Tab */}
        <TabsContent value="preferences" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Language & Region</CardTitle>
              <CardDescription>
                Customize your language and regional preferences
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Language</Label>
                <Select
                  value={preferences.language}
                  onValueChange={(value) => handlePreferenceChange('language', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="en">English</SelectItem>
                    <SelectItem value="hi">हिंदी (Hindi)</SelectItem>
                    <SelectItem value="mr">मराठी (Marathi)</SelectItem>
                    <SelectItem value="gu">ગુજરાતી (Gujarati)</SelectItem>
                    <SelectItem value="ta">தமிழ் (Tamil)</SelectItem>
                    <SelectItem value="te">తెలుగు (Telugu)</SelectItem>
                    <SelectItem value="kn">ಕನ್ನಡ (Kannada)</SelectItem>
                    <SelectItem value="bn">বাংলা (Bengali)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Currency</Label>
                <Select
                  value={preferences.currency}
                  onValueChange={(value) => handlePreferenceChange('currency', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="INR">₹ INR - Indian Rupee</SelectItem>
                    <SelectItem value="USD">$ USD - US Dollar</SelectItem>
                    <SelectItem value="EUR">€ EUR - Euro</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Timezone</Label>
                <Select
                  value={preferences.timezone}
                  onValueChange={(value) => handlePreferenceChange('timezone', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Asia/Kolkata">IST (Asia/Kolkata)</SelectItem>
                    <SelectItem value="Asia/Dubai">GST (Asia/Dubai)</SelectItem>
                    <SelectItem value="America/New_York">EST (America/New_York)</SelectItem>
                    <SelectItem value="Europe/London">GMT (Europe/London)</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Date Format</Label>
                <Select
                  value={preferences.date_format}
                  onValueChange={(value) => handlePreferenceChange('date_format', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="DD/MM/YYYY">DD/MM/YYYY</SelectItem>
                    <SelectItem value="MM/DD/YYYY">MM/DD/YYYY</SelectItem>
                    <SelectItem value="YYYY-MM-DD">YYYY-MM-DD</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Display Preferences</CardTitle>
              <CardDescription>
                Customize how information is displayed
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label>Theme</Label>
                <Select
                  value={preferences.theme}
                  onValueChange={(value) => handlePreferenceChange('theme', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="light">Light</SelectItem>
                    <SelectItem value="dark">Dark</SelectItem>
                    <SelectItem value="auto">System Default</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Number Format</Label>
                <Select
                  value={preferences.number_format}
                  onValueChange={(value) => handlePreferenceChange('number_format', value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="en-IN">Indian (1,00,000)</SelectItem>
                    <SelectItem value="en-US">International (100,000)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Privacy Tab */}
        <TabsContent value="privacy" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Data & Privacy</CardTitle>
              <CardDescription>
                Manage your data and privacy settings
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <h3 className="text-sm font-medium mb-2">Data Export</h3>
                <p className="text-sm text-muted-foreground mb-4">
                  Download a copy of your data including transactions, investments, and profile information
                </p>
                <Button variant="outline">
                  <Mail className="mr-2 h-4 w-4" />
                  Request Data Export
                </Button>
              </div>

              <Separator />

              <div>
                <h3 className="text-sm font-medium mb-2">Account Deletion</h3>
                <p className="text-sm text-muted-foreground mb-4">
                  Permanently delete your account and all associated data
                </p>
                <Button variant="destructive">
                  Delete Account
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Cookie Preferences</CardTitle>
              <CardDescription>
                Manage your cookie and tracking preferences
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button variant="outline">
                Manage Cookie Preferences
              </Button>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
