// V-REMEDIATE-1730-186 (REVISED)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Download, Trash2, AlertTriangle } from "lucide-react";

export default function ProfilePage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const [isDownloading, setIsDownloading] = useState(false);
  
  // Profile Form State
  const [profileData, setProfileData] = useState({
    first_name: '', last_name: '', address: '', city: '', state: '', pincode: ''
  });

  // Password Form State
  const [passData, setPassData] = useState({
    current_password: '', password: '', password_confirmation: ''
  });

  // Deletion State
  const [deletePassword, setDeletePassword] = useState('');
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);

  const { data: user, isLoading } = useQuery({
    queryKey: ['userProfile'],
    queryFn: async () => (await api.get('/user/profile')).data,
  });

  useEffect(() => {
    if (user) {
      setProfileData({
        first_name: user.profile?.first_name || '',
        last_name: user.profile?.last_name || '',
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
    onError: (e: any) => {
      const errorMsg = e.response?.data?.errors?.current_password?.[0] || e.response?.data?.message || "An error occurred";
      toast.error("Error", { description: errorMsg });
    }
  });

  const deleteMutation = useMutation({
    mutationFn: (password: string) => api.post('/user/security/delete-account', { password }),
    onSuccess: () => {
      toast.success("Account Deleted");
      localStorage.removeItem('auth_token');
      router.push('/');
    },
    onError: (e: any) => toast.error("Deletion Failed", { description: e.response?.data?.message })
  });

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    profileMutation.mutate(profileData);
  };

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    passwordMutation.mutate(passData);
  };

  const handleExport = async () => {
    setIsDownloading(true);
    try {
      const response = await api.get('/user/security/export-data', { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `my_data_export.zip`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Export Downloaded");
    } catch (e) {
      toast.error("Export Failed");
    } finally {
      setIsDownloading(false);
    }
  };

  if (isLoading) return <div>Loading profile...</div>;

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Account Settings</h1>

      <Tabs defaultValue="profile">
        <TabsList>
          <TabsTrigger value="profile">Personal Info</TabsTrigger>
          <TabsTrigger value="security">Security</TabsTrigger>
          <TabsTrigger value="privacy">Data & Privacy</TabsTrigger>
        </TabsList>

        {/* Profile Tab */}
        <TabsContent value="profile">
          <Card>
            <CardHeader>
              <CardTitle>Personal Information</CardTitle>
              <CardDescription>Update your personal details. This information is locked once KYC is verified.</CardDescription>
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
                <Button type="submit" disabled={profileMutation.isPending || user?.kyc?.status === 'verified'}>
                  {user?.kyc?.status === 'verified' ? 'Profile Locked (KYC Verified)' : profileMutation.isPending ? "Saving..." : "Save Changes"}
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

        {/* Privacy Tab */}
        <TabsContent value="privacy">
          <div className="grid gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Export Your Data</CardTitle>
                <CardDescription>Download a copy of your personal data, transactions, and documents (GDPR Compliance).</CardDescription>
              </CardHeader>
              <CardContent>
                <Button variant="outline" onClick={handleExport} disabled={isDownloading}>
                  <Download className="mr-2 h-4 w-4" />
                  {isDownloading ? "Preparing Download..." : "Download My Data"}
                </Button>
              </CardContent>
            </Card>

            <Card className="border-destructive/50">
              <CardHeader>
                <CardTitle className="text-destructive">Delete Account</CardTitle>
                <CardDescription>Permanently delete your account and data. This action is irreversible.</CardDescription>
              </CardHeader>
              <CardContent>
                <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
                  <DialogTrigger asChild>
                    <Button variant="destructive">
                      <Trash2 className="mr-2 h-4 w-4" /> Delete Account
                    </Button>
                  </DialogTrigger>
                  <DialogContent>
                    <DialogHeader>
                      <DialogTitle>Are you absolutely sure?</DialogTitle>
                      <DialogDescription>
                        This will permanently delete your account and anonymize your data. You cannot undo this.
                        <br/><br/>
                        <strong>Note:</strong> You cannot delete your account if you have active investments or a wallet balance.
                      </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                      <div className="flex items-center p-4 bg-destructive/10 text-destructive rounded-md">
                        <AlertTriangle className="h-6 w-6 mr-2" />
                        <p className="text-sm font-semibold">This action cannot be undone.</p>
                      </div>
                      <div className="space-y-2">
                        <Label>Confirm Password</Label>
                        <Input 
                          type="password" 
                          value={deletePassword} 
                          onChange={e => setDeletePassword(e.target.value)} 
                          placeholder="Enter your password to confirm"
                        />
                      </div>
                    </div>
                    <DialogFooter>
                      <Button variant="outline" onClick={() => setIsDeleteOpen(false)}>Cancel</Button>
                      <Button 
                        variant="destructive" 
                        disabled={!deletePassword || deleteMutation.isPending}
                        onClick={() => deleteMutation.mutate(deletePassword)}
                      >
                        {deleteMutation.isPending ? "Deleting..." : "Confirm Deletion"}
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}