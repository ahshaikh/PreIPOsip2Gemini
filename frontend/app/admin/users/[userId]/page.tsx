// V-REMEDIATE-1730-173 | V-FIX-HYDRATION-2025
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useParams, useRouter } from "next/navigation";
import { useState } from "react";
import { AlertTriangle, CreditCard, History, Shield, User as UserIcon, Wallet } from "lucide-react";

// [AUDIT FIX] Defined interface to match Backend Response exactly
interface UserDetail {
  id: number;
  username: string;
  email: string;
  mobile: string;
  status: string;
  created_at: string;
  profile: {
    first_name: string;
    last_name: string;
  };
  wallet: {
    balance: number;
    locked_balance: number;
    transactions: any[];
  } | null;
  kyc: {
    status: string;
    pan_number?: string;
    aadhaar_number?: string;
    bank_account?: string;
  } | null;
  subscription: {
    plan_name: string;
    status: string;
    starts_at: string; // Fixed from start_dat
    bonus_multiplier?: number;
  } | null;
  activity_logs: any[];
}

export default function UserDetailPage() {
  const params = useParams();
  const router = useRouter();
  const queryClient = useQueryClient();
  const userId = params.userId as string;

  // UI State
  const [adjustOpen, setAdjustOpen] = useState(false);
  const [suspendOpen, setSuspendOpen] = useState(false);
  
  // Form State
  const [adjustType, setAdjustType] = useState('credit');
  const [adjustAmount, setAdjustAmount] = useState('');
  const [adjustDesc, setAdjustDesc] = useState('');
  const [suspendReason, setSuspendReason] = useState('');

  // Fetch User Data
  const { data: user, isLoading } = useQuery<UserDetail>({
    queryKey: ['adminUserDetail', userId],
    queryFn: async () => (await api.get(`/admin/users/${userId}`)).data,
  });

  // Mutations
  const adjustMutation = useMutation({
    mutationFn: (data: any) => api.post(`/admin/users/${userId}/adjust-balance`, data),
    onSuccess: () => {
      toast.success("Balance Adjusted");
      queryClient.invalidateQueries({ queryKey: ['adminUserDetail', userId] });
      setAdjustOpen(false);
      setAdjustAmount(''); setAdjustDesc('');
    },
    onError: (e: any) => toast.error("Adjustment Failed", { description: e.response?.data?.message })
  });

  const statusMutation = useMutation({
    mutationFn: (status: string) => api.put(`/admin/users/${userId}`, { status }),
    onSuccess: () => {
      toast.success("Status Updated");
      queryClient.invalidateQueries({ queryKey: ['adminUserDetail', userId] });
    }
  });

  const suspendMutation = useMutation({
    mutationFn: () => api.post(`/admin/users/${userId}/suspend`, { reason: suspendReason }),
    onSuccess: () => {
      toast.success("User Suspended");
      queryClient.invalidateQueries({ queryKey: ['adminUserDetail', userId] });
      setSuspendOpen(false);
    }
  });

  const handleAdjustSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    adjustMutation.mutate({ type: adjustType, amount: parseFloat(adjustAmount), description: adjustDesc });
  };

  if (isLoading) return <div>Loading user details...</div>;
  if (!user) return <div>User not found</div>;

  return (
    <div className="space-y-6">
      <Button variant="outline" onClick={() => router.push('/admin/users')}>&larr; Back to Users</Button>

      {/* Header Card */}
      <div className="flex flex-col md:flex-row gap-6">
        <Card className="flex-1">
          <CardHeader className="flex flex-row items-start justify-between">
            <div className="flex items-center gap-4">
              <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center text-2xl font-bold text-primary">
                {user.username.charAt(0).toUpperCase()}
              </div>
              <div>
                <CardTitle>{user.profile.first_name} {user.profile.last_name}</CardTitle>
                <CardDescription>{user.email}</CardDescription>
                <div className="flex gap-2 mt-2">
                  <Badge variant={user.status === 'active' ? 'default' : 'destructive'}>{user.status}</Badge>
                  <Badge variant="outline">{user.kyc?.status || 'No KYC'}</Badge>
                </div>
              </div>
            </div>
            <div className="flex gap-2">
              {user.status !== 'active' ? (
                <Button variant="outline" onClick={() => statusMutation.mutate('active')}>Activate User</Button>
              ) : (
                <Dialog open={suspendOpen} onOpenChange={setSuspendOpen}>
                  <DialogTrigger asChild><Button variant="destructive">Suspend User</Button></DialogTrigger>
                  <DialogContent>
                    <DialogHeader><DialogTitle>Suspend User</DialogTitle></DialogHeader>
                    <div className="space-y-4">
                      <Label>Reason</Label>
                      <Textarea value={suspendReason} onChange={(e) => setSuspendReason(e.target.value)} />
                      <Button variant="destructive" onClick={() => suspendMutation.mutate()}>Confirm Suspension</Button>
                    </div>
                  </DialogContent>
                </Dialog>
              )}
            </div>
          </CardHeader>
        </Card>

        {/* Wallet Quick View */}
        <Card className="w-full md:w-80">
          <CardHeader>
            <CardTitle className="text-sm font-medium">Wallet Balance</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">₹{user.wallet?.balance?.toLocaleString() || '0.00'}</div>
            <p className="text-xs text-muted-foreground mb-4">Locked: ₹{user.wallet?.locked_balance?.toLocaleString() || '0.00'}</p>
            <Dialog open={adjustOpen} onOpenChange={setAdjustOpen}>
              <DialogTrigger asChild><Button className="w-full" variant="secondary">Adjust Balance</Button></DialogTrigger>
              <DialogContent>
                <DialogHeader><DialogTitle>Manual Balance Adjustment</DialogTitle></DialogHeader>
                <form onSubmit={handleAdjustSubmit} className="space-y-4">
                  <div className="space-y-2">
                    <Label>Type</Label>
                    <Select value={adjustType} onValueChange={setAdjustType}>
                      <SelectTrigger><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="credit">Credit (Add Money)</SelectItem>
                        <SelectItem value="debit">Debit (Remove Money)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Amount (₹)</Label>
                    <Input type="number" value={adjustAmount} onChange={(e) => setAdjustAmount(e.target.value)} required />
                  </div>
                  <div className="space-y-2">
                    <Label>Reason/Description</Label>
                    <Input value={adjustDesc} onChange={(e) => setAdjustDesc(e.target.value)} required />
                  </div>
                  <Button type="submit" className="w-full" disabled={adjustMutation.isPending}>Confirm Adjustment</Button>
                </form>
              </DialogContent>
            </Dialog>
          </CardContent>
        </Card>
      </div>

      {/* Tabs */}
      <Tabs defaultValue="activity">
        <TabsList>
          <TabsTrigger value="activity"><History className="mr-2 h-4 w-4"/> Activity Log</TabsTrigger>
          <TabsTrigger value="transactions"><Wallet className="mr-2 h-4 w-4"/> Transactions</TabsTrigger>
          <TabsTrigger value="kyc"><Shield className="mr-2 h-4 w-4"/> KYC Details</TabsTrigger>
          <TabsTrigger value="subscription"><CreditCard className="mr-2 h-4 w-4"/> Subscription</TabsTrigger>
        </TabsList>

        <TabsContent value="activity">
          <Card>
            <CardHeader><CardTitle>Recent Activity</CardTitle></CardHeader>
            <CardContent>
              <Table>
                <TableHeader><TableRow><TableHead>Action</TableHead><TableHead>Description</TableHead><TableHead>IP</TableHead><TableHead>Time</TableHead></TableRow></TableHeader>
                <TableBody>
                  {user.activity_logs?.map((log: any) => (
                    <TableRow key={log.id}>
                      <TableCell className="font-medium">{log.action}</TableCell>
                      <TableCell>{log.description}</TableCell>
                      <TableCell className="font-mono text-xs">{log.ip_address}</TableCell>
                      <TableCell>{new Date(log.created_at).toLocaleString()}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="transactions">
          <Card>
            <CardHeader><CardTitle>Transaction History</CardTitle></CardHeader>
            <CardContent>
              <Table>
                <TableHeader><TableRow><TableHead>Type</TableHead><TableHead>Amount</TableHead><TableHead>Balance</TableHead><TableHead>Date</TableHead></TableRow></TableHeader>
                <TableBody>
                  {user.wallet?.transactions?.map((tx: any) => (
                    <TableRow key={tx.id}>
                      <TableCell className="capitalize">{tx.type.replace('_', ' ')}</TableCell>
                      <TableCell className={tx.amount > 0 ? 'text-green-600' : 'text-red-600'}>₹{tx.amount}</TableCell>
                      <TableCell>₹{tx.balance_after}</TableCell>
                      <TableCell>{new Date(tx.created_at).toLocaleString()}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="kyc">
          <Card>
            <CardContent className="pt-6">
              <div className="grid grid-cols-2 gap-4">
                <div><Label>PAN</Label><p>{user.kyc?.pan_number || 'N/A'}</p></div>
                <div><Label>Aadhaar</Label><p>{user.kyc?.aadhaar_number || 'N/A'}</p></div>
                <div><Label>Bank Account</Label><p>{user.kyc?.bank_account || 'N/A'}</p></div>
                <div><Label>Status</Label><Badge>{user.kyc?.status}</Badge></div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="subscription">
          <Card>
            <CardContent className="pt-6">
              {user.subscription ? (
                <div className="space-y-4">
                  <h3 className="text-xl font-bold">{user.subscription.plan_name}</h3>
                  {/* [FIX]: Changed <p> to <div> to solve hydration error (Badge is a div) */}
                  <div className="flex items-center gap-2">
                    <span className="font-semibold">Status:</span> 
                    <Badge variant={user.subscription.status === 'active' ? 'default' : 'secondary'}>
                      {user.subscription.status}
                    </Badge>
                  </div>
                  {/* [FIX]: Corrected typo 'start_dat' to 'starts_at' matching Backend */}
                  <p>Start Date: {user.subscription.starts_at ? new Date(user.subscription.starts_at).toLocaleDateString() : 'N/A'}</p>
                  {/* [FIX]: Added optional chaining for bonus_multiplier */}
                  {user.subscription.bonus_multiplier && (
                    <p>Bonus Multiplier: {user.subscription.bonus_multiplier}x</p>
                  )}
                </div>
              ) : (
                <p className="text-muted-foreground">No active subscription.</p>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}