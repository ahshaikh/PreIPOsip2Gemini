// V-POLISH-1730-180
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";

export default function ProfilePage() {
  const queryClient = useQueryClient();
  
  // Profile Form State
  const [profileData, setProfileData] = useState({
    first_name: '', last_name: '', mobile: '', address: '', city: '', state: '', pincode: ''
  });

  // Password Form State
  const [passData, setPassData] = useState({
    current_password: '', password: '', password_confirmation: ''
  });

  const { data: user, isLoading } = useQuery({
    queryKey: ['userProfile'],
    queryFn: async () => (await api.get('/user/profile')).data,
  });

  useEffect(() => {
    if (user) {
      setProfileData({
        first_name: user.profile?.first_name || '',
        last_name: user.profile?.last_name || '',
        mobile: user.mobile || '',
        address: user.profile?.address || '',
        city: user.profile?.city || '',
        state: user.profile?.state || '',
        pincode: user.profile?.pincode || '',
      });
    }
  }, [user]);

  const profileMutation = useMutation({
    mutationFn: (data: any) => api.put('/user/profile', data),
    onSuccess: () => {
      toast.success("Profile Updated");
      queryClient.invalidateQueries({ queryKey: ['userProfile'] });
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const passwordMutation = useMutation({
    mutationFn: (data: any) => api.post('/user/security/password', data),
    onSuccess: () => {
      toast.success("Password Changed");
      setPassData({ current_password: '', password: '', password_confirmation: '' });
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    profileMutation.mutate(profileData);
  };

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    passwordMutation.mutate(passData);
  };

  if (isLoading) return <div>Loading profile...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Account Settings</h1>

      <Tabs defaultValue="profile">
        <TabsList>
          <TabsTrigger value="profile">Personal Info</TabsTrigger>
          <TabsTrigger value="security">Security</TabsTrigger>
        </TabsList>

        {/* Profile Tab */}
        <TabsContent value="profile">
          <Card>
            <CardHeader>
              <CardTitle>Personal Information</CardTitle>
              <CardDescription>Update your personal details.</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleProfileSubmit} className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>First Name</Label>
                    <Input value={profileData.first_name} onChange={e => setProfileData({...profileData, first_name: e.target.value})} />
                  </div>
                  <div className="space-y-2">
                    <Label>Last Name</Label>
                    <Input value={profileData.last_name} onChange={e => setProfileData({...profileData, last_name: e.target.value})} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Address</Label>
                  <Input value={profileData.address} onChange={e => setProfileData({...profileData, address: e.target.value})} />
                </div>
                <div className="grid grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label>City</Label>
                    <Input value={profileData.city} onChange={e => setProfileData({...profileData, city: e.target.value})} />
                  </div>
                  <div className="space-y-2">
                    <Label>State</Label>
                    <Input value={profileData.state} onChange={e => setProfileData({...profileData, state: e.target.value})} />
                  </div>
                  <div className="space-y-2">
                    <Label>Pincode</Label>
                    <Input value={profileData.pincode} onChange={e => setProfileData({...profileData, pincode: e.target.value})} />
                  </div>
                </div>
                <Button type="submit" disabled={profileMutation.isPending}>
                  {profileMutation.isPending ? "Saving..." : "Save Changes"}
                </Button>
              </form>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Security Tab */}
        <TabsContent value="security">
          <Card>
            <CardHeader>
              <CardTitle>Change Password</CardTitle>
              <CardDescription>Ensure your account is secure.</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handlePasswordSubmit} className="space-y-4 max-w-md">
                <div className="space-y-2">
                  <Label>Current Password</Label>
                  <Input type="password" value={passData.current_password} onChange={e => setPassData({...passData, current_password: e.target.value})} required />
                </div>
                <div className="space-y-2">
                  <Label>New Password</Label>
                  <Input type="password" value={passData.password} onChange={e => setPassData({...passData, password: e.target.value})} required />
                </div>
                <div className="space-y-2">
                  <Label>Confirm New Password</Label>
                  <Input type="password" value={passData.password_confirmation} onChange={e => setPassData({...passData, password_confirmation: e.target.value})} required />
                </div>
                <Button type="submit" disabled={passwordMutation.isPending}>
                  {passwordMutation.isPending ? "Updating..." : "Update Password"}
                </Button>
              </form>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}