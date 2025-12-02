'use client';

import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import companyApi from '@/lib/companyApi';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import { Save, Lock, User, Mail, Phone, Shield } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export default function AccountSettingsPage() {
  const queryClient = useQueryClient();

  const { data: profileData } = useQuery({
    queryKey: ['company-profile'],
    queryFn: async () => {
      const response = await companyApi.get('/profile');
      return response.data;
    },
  });

  const user = profileData?.user;
  const company = profileData?.company;

  const [profileForm, setProfileForm] = useState({
    contact_person_name: '',
    contact_person_designation: '',
    phone: '',
  });

  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
  });

  useEffect(() => {
    if (user) {
      setProfileForm({
        contact_person_name: user.contact_person_name || '',
        contact_person_designation: user.contact_person_designation || '',
        phone: user.phone || '',
      });
    }
  }, [user]);

  const updateProfileMutation = useMutation({
    mutationFn: async (data: any) => {
      return companyApi.put('/profile', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['company-profile'] });
      toast.success('Profile updated successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to update profile');
    },
  });

  const changePasswordMutation = useMutation({
    mutationFn: async (data: any) => {
      return companyApi.post('/change-password', data);
    },
    onSuccess: () => {
      setPasswordForm({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
      });
      toast.success('Password changed successfully');
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to change password');
    },
  });

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfileMutation.mutate(profileForm);
  };

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
      toast.error('New passwords do not match');
      return;
    }

    if (passwordForm.new_password.length < 8) {
      toast.error('Password must be at least 8 characters');
      return;
    }

    changePasswordMutation.mutate(passwordForm);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Account Settings</h1>
        <p className="text-muted-foreground mt-2">
          Manage your account information and security settings
        </p>
      </div>

      {/* Account Status */}
      <Card>
        <CardHeader>
          <CardTitle>Account Status</CardTitle>
          <CardDescription>Your current account standing</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="flex items-center gap-3">
              <Shield className="h-5 w-5 text-muted-foreground" />
              <div>
                <p className="text-sm text-muted-foreground">Account Status</p>
                <Badge variant={user?.status === 'active' ? 'default' : 'secondary'}>
                  {user?.status}
                </Badge>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <Mail className="h-5 w-5 text-muted-foreground" />
              <div>
                <p className="text-sm text-muted-foreground">Email</p>
                <p className="font-medium">{user?.email}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <Shield className="h-5 w-5 text-muted-foreground" />
              <div>
                <p className="text-sm text-muted-foreground">Verification</p>
                <Badge variant={user?.is_verified ? 'default' : 'secondary'}>
                  {user?.is_verified ? 'Verified' : 'Not Verified'}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Profile Information */}
      <form onSubmit={handleProfileSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>Contact Person Information</CardTitle>
            <CardDescription>
              Update the primary contact details for your company account
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="contact_person_name">Full Name *</Label>
                <Input
                  id="contact_person_name"
                  value={profileForm.contact_person_name}
                  onChange={(e) =>
                    setProfileForm({ ...profileForm, contact_person_name: e.target.value })
                  }
                  required
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="contact_person_designation">Designation</Label>
                <Input
                  id="contact_person_designation"
                  placeholder="CEO, Founder, CFO"
                  value={profileForm.contact_person_designation}
                  onChange={(e) =>
                    setProfileForm({ ...profileForm, contact_person_designation: e.target.value })
                  }
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Phone Number</Label>
              <Input
                id="phone"
                type="tel"
                placeholder="+91 9876543210"
                value={profileForm.phone}
                onChange={(e) => setProfileForm({ ...profileForm, phone: e.target.value })}
              />
            </div>
            <div className="flex justify-end">
              <Button type="submit" disabled={updateProfileMutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {updateProfileMutation.isPending ? 'Saving...' : 'Save Changes'}
              </Button>
            </div>
          </CardContent>
        </Card>
      </form>

      {/* Change Password */}
      <form onSubmit={handlePasswordSubmit}>
        <Card>
          <CardHeader>
            <CardTitle>Change Password</CardTitle>
            <CardDescription>
              Update your password to keep your account secure
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="current_password">Current Password *</Label>
              <Input
                id="current_password"
                type="password"
                value={passwordForm.current_password}
                onChange={(e) =>
                  setPasswordForm({ ...passwordForm, current_password: e.target.value })
                }
                required
              />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="new_password">New Password *</Label>
                <Input
                  id="new_password"
                  type="password"
                  placeholder="Minimum 8 characters"
                  value={passwordForm.new_password}
                  onChange={(e) =>
                    setPasswordForm({ ...passwordForm, new_password: e.target.value })
                  }
                  required
                  minLength={8}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="new_password_confirmation">Confirm New Password *</Label>
                <Input
                  id="new_password_confirmation"
                  type="password"
                  placeholder="Re-enter new password"
                  value={passwordForm.new_password_confirmation}
                  onChange={(e) =>
                    setPasswordForm({ ...passwordForm, new_password_confirmation: e.target.value })
                  }
                  required
                  minLength={8}
                />
              </div>
            </div>
            <div className="flex justify-end">
              <Button type="submit" disabled={changePasswordMutation.isPending}>
                <Lock className="mr-2 h-4 w-4" />
                {changePasswordMutation.isPending ? 'Changing...' : 'Change Password'}
              </Button>
            </div>
          </CardContent>
        </Card>
      </form>

      {/* Company Information */}
      <Card>
        <CardHeader>
          <CardTitle>Company Information</CardTitle>
          <CardDescription>
            Your company details (Edit from Company Profile page)
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-muted-foreground">Company Name</p>
              <p className="font-medium">{company?.name || 'Not set'}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Sector</p>
              <p className="font-medium">{company?.sector || 'Not set'}</p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Profile Completion</p>
              <div className="flex items-center gap-2">
                <p className="font-medium">{company?.profile_completion_percentage || 0}%</p>
                <Badge variant={company?.profile_completed ? 'default' : 'secondary'}>
                  {company?.profile_completed ? 'Complete' : 'Incomplete'}
                </Badge>
              </div>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Company Status</p>
              <Badge variant={company?.status === 'active' ? 'default' : 'secondary'}>
                {company?.status}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Security Tips */}
      <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <Shield className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-1">
                Security Best Practices
              </h3>
              <ul className="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Use a strong, unique password with at least 8 characters</li>
                <li>• Change your password regularly (every 3-6 months)</li>
                <li>• Never share your login credentials with anyone</li>
                <li>• Log out from shared or public computers</li>
                <li>• Contact support immediately if you notice suspicious activity</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
