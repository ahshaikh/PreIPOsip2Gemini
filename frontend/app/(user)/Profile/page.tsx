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
import { Download, Trash2, AlertTriangle, Upload, User, Building2 } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

export default function ProfilePage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const [isDownloading, setIsDownloading] = useState(false);
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [avatarPreview, setAvatarPreview] = useState<string>("");

  // Profile Form State
  const [profileData, setProfileData] = useState({
    first_name: '', last_name: '', address: '', city: '', state: '', pincode: ''
  });

  // Bank Details State
  const [bankData, setBankData] = useState({
    account_number: '', ifsc_code: '', bank_name: '', branch_name: '', account_holder_name: ''
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
      setBankData({
        account_number: user.bank_details?.account_number || '',
        ifsc_code: user.bank_details?.ifsc_code || '',
        bank_name: user.bank_details?.bank_name || '',
        branch_name: user.bank_details?.branch_name || '',
        account_holder_name: user.bank_details?.account_holder_name || '',
      });
      setAvatarPreview(user.profile?.avatar_url || '');
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

  const avatarMutation = useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData();
      formData.append('avatar', file);
      return api.post('/user/profile/avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    },
    onSuccess: () => {
      toast.success("Avatar Updated");
      queryClient.invalidateQueries({ queryKey: ['userProfile'] });
      setAvatarFile(null);
    },
    onError: (e: any) => toast.error("Upload Failed", { description: e.response?.data?.message })
  });

  const bankMutation = useMutation({
    mutationFn: (data: any) => api.put('/user/bank-details', data),
    onSuccess: () => {
      toast.success("Bank Details Updated");
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

  const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        toast.error("File too large", { description: "Avatar must be less than 5MB" });
        return;
      }
      if (!file.type.startsWith('image/')) {
        toast.error("Invalid file type", { description: "Please upload an image file" });
        return;
      }
      setAvatarFile(file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setAvatarPreview(reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleAvatarUpload = () => {
    if (avatarFile) {
      avatarMutation.mutate(avatarFile);
    }
  };

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    profileMutation.mutate(profileData);
  };

  const handleBankSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    bankMutation.mutate(bankData);
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
          <TabsTrigger value="bank">Bank Details</TabsTrigger>
          <TabsTrigger value="security">Security</TabsTrigger>
          <TabsTrigger value="privacy">Data & Privacy</TabsTrigger>
        </TabsList>

        {/* Profile Tab */}
        <TabsContent value="profile">
          <div className="space-y-6">
            {/* Avatar Upload Section */}
            <Card>
              <CardHeader>
                <CardTitle>Profile Picture</CardTitle>
                <CardDescription>Upload your avatar (max 5MB, JPG/PNG)</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-6">
                  <Avatar className="h-24 w-24">
                    <AvatarImage src={avatarPreview} />
                    <AvatarFallback>
                      <User className="h-12 w-12 text-muted-foreground" />
                    </AvatarFallback>
                  </Avatar>
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <Input
                        id="avatar-upload"
                        type="file"
                        accept="image/*"
                        onChange={handleAvatarChange}
                        className="hidden"
                      />
                      <Label htmlFor="avatar-upload">
                        <Button type="button" variant="outline" asChild>
                          <span>
                            <Upload className="h-4 w-4 mr-2" />
                            Choose Photo
                          </span>
                        </Button>
                      </Label>
                      {avatarFile && (
                        <Button onClick={handleAvatarUpload} disabled={avatarMutation.isPending}>
                          {avatarMutation.isPending ? "Uploading..." : "Upload"}
                        </Button>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      Recommended: Square image, at least 200x200 pixels
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Personal Info Section */}
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
          </div>
        </TabsContent>

        {/* Bank Details Tab */}
        <TabsContent value="bank">
          <Card>
            <CardHeader>
              <CardTitle>Bank Account Details</CardTitle>
              <CardDescription>Update your bank account information for withdrawals and refunds</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleBankSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label>Account Holder Name</Label>
                  <Input
                    value={bankData.account_holder_name}
                    onChange={e => setBankData({...bankData, account_holder_name: e.target.value})}
                    placeholder="As per bank records"
                    disabled={user?.kyc?.status === 'verified'}
                  />
                  {user?.kyc?.status === 'verified' && (
                    <p className="text-xs text-muted-foreground">
                      Name cannot be changed after KYC verification
                    </p>
                  )}
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Account Number</Label>
                    <Input
                      type="text"
                      value={bankData.account_number}
                      onChange={e => setBankData({...bankData, account_number: e.target.value})}
                      placeholder="Enter account number"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>IFSC Code</Label>
                    <Input
                      value={bankData.ifsc_code}
                      onChange={e => setBankData({...bankData, ifsc_code: e.target.value.toUpperCase()})}
                      placeholder="e.g., HDFC0001234"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Bank Name</Label>
                    <Input
                      value={bankData.bank_name}
                      onChange={e => setBankData({...bankData, bank_name: e.target.value})}
                      placeholder="e.g., HDFC Bank"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Branch Name</Label>
                    <Input
                      value={bankData.branch_name}
                      onChange={e => setBankData({...bankData, branch_name: e.target.value})}
                      placeholder="e.g., Mumbai Main Branch"
                    />
                  </div>
                </div>
                <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 space-y-2">
                  <div className="flex items-start gap-2">
                    <Building2 className="h-5 w-5 text-blue-600 mt-0.5" />
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-blue-900 dark:text-blue-100">
                        Important Information
                      </p>
                      <ul className="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                        <li>• All fields except Account Holder Name can be edited anytime</li>
                        <li>• Ensure bank details are accurate for smooth withdrawals</li>
                        <li>• Withdrawals will be processed to this account only</li>
                        <li>• Changes take effect immediately</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <Button type="submit" disabled={bankMutation.isPending}>
                  {bankMutation.isPending ? "Saving..." : "Save Bank Details"}
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