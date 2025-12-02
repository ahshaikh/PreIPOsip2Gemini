// V-PHASE5-1730-122 | V-ENHANCED-WALLET
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger, DialogFooter } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import {
  Wallet, ArrowUpRight, ArrowDownRight, Plus, Minus, Clock,
  Download, Filter, Search, Calendar, CreditCard, Building2,
  IndianRupee, TrendingUp, History, RefreshCw, AlertCircle, CheckCircle
} from "lucide-react";

// Transaction type filters
const TRANSACTION_TYPES = [
  { value: 'all', label: 'All Transactions' },
  { value: 'credit', label: 'Credits' },
  { value: 'debit', label: 'Debits' },
  { value: 'bonus', label: 'Bonuses' },
  { value: 'withdrawal', label: 'Withdrawals' },
  { value: 'refund', label: 'Refunds' },
];

export default function WalletPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('overview');
  const [filterType, setFilterType] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  // Withdraw dialog state
  const [withdrawAmount, setWithdrawAmount] = useState('');
  const [accountName, setAccountName] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [ifscCode, setIfscCode] = useState('');
  const [upiId, setUpiId] = useState('');
  const [withdrawMethod, setWithdrawMethod] = useState('bank');
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  // Add money dialog state
  const [addAmount, setAddAmount] = useState('');
  const [isAddMoneyOpen, setIsAddMoneyOpen] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['wallet'],
    queryFn: async () => (await api.get('/user/wallet')).data,
  });

  // Fetch withdrawal requests
  const { data: withdrawals } = useQuery({
    queryKey: ['withdrawalRequests'],
    queryFn: async () => (await api.get('/user/wallet/withdrawals')).data,
    enabled: activeTab === 'withdrawals',
  });

  const withdrawMutation = useMutation({
    mutationFn: (data: { amount: number, bank_details: any, method: string }) =>
      api.post('/user/wallet/withdraw', data),
    onSuccess: () => {
      toast.success("Withdrawal Request Submitted", { description: "Your request is pending approval." });
      queryClient.invalidateQueries({ queryKey: ['wallet'] });
      queryClient.invalidateQueries({ queryKey: ['withdrawalRequests'] });
      setIsDialogOpen(false);
      resetWithdrawForm();
    },
    onError: (error: any) => {
      toast.error("Withdrawal Failed", { description: error.response?.data?.message || "Unable to process request" });
    }
  });

  const addMoneyMutation = useMutation({
    mutationFn: (amount: number) => api.post('/user/wallet/add-money', { amount }),
    onSuccess: (response) => {
      // Handle payment gateway redirect if needed
      if (response.data.payment_url) {
        window.location.href = response.data.payment_url;
      } else {
        toast.success("Money Added", { description: "Your wallet has been credited." });
        queryClient.invalidateQueries({ queryKey: ['wallet'] });
      }
      setIsAddMoneyOpen(false);
      setAddAmount('');
    },
    onError: (error: any) => {
      toast.error("Failed to Add Money", { description: error.response?.data?.message });
    }
  });

  const resetWithdrawForm = () => {
    setWithdrawAmount('');
    setAccountName('');
    setAccountNumber('');
    setIfscCode('');
    setUpiId('');
    setWithdrawMethod('bank');
  };

  const handleWithdraw = () => {
    const amount = parseFloat(withdrawAmount);
    if (amount < 1000) {
      toast.error("Minimum Withdrawal", { description: "Minimum withdrawal amount is ₹1,000" });
      return;
    }
    if (amount > parseFloat(data?.wallet?.balance || '0')) {
      toast.error("Insufficient Balance", { description: "You don't have enough balance" });
      return;
    }

    const bankDetails = withdrawMethod === 'bank'
      ? { account_name: accountName, account_number: accountNumber, ifsc: ifscCode }
      : { upi_id: upiId };

    withdrawMutation.mutate({ amount, bank_details: bankDetails, method: withdrawMethod });
  };

  const handleAddMoney = () => {
    const amount = parseFloat(addAmount);
    if (amount < 100) {
      toast.error("Minimum Amount", { description: "Minimum amount is ₹100" });
      return;
    }
    addMoneyMutation.mutate(amount);
  };

  const handleDownloadStatement = async () => {
    try {
      const response = await api.get('/user/wallet/statement', {
        responseType: 'blob'
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `wallet-statement-${new Date().toISOString().split('T')[0]}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Statement Downloaded", { description: "Your wallet statement has been downloaded" });
    } catch (error: any) {
      toast.error("Download Failed", { description: error.response?.data?.message || "Unable to download statement" });
    }
  };

  if (isLoading) return <div className="flex items-center justify-center h-64">Loading wallet...</div>;

  const balance = parseFloat(data?.wallet?.balance || 0);
  const lockedBalance = parseFloat(data?.wallet?.locked_balance || 0);
  const transactions = data?.transactions?.data || [];

  // Filter transactions
  const filteredTransactions = transactions.filter((tx: any) => {
    const matchesType = filterType === 'all' ||
      (filterType === 'credit' && tx.amount > 0) ||
      (filterType === 'debit' && tx.amount < 0) ||
      tx.type === filterType;
    const matchesSearch = searchQuery === '' ||
      tx.description?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesType && matchesSearch;
  });

  // Calculate total credits and debits
  const totalCredits = transactions.filter((tx: any) => tx.amount > 0).reduce((acc: number, tx: any) => acc + parseFloat(tx.amount), 0);
  const totalDebits = transactions.filter((tx: any) => tx.amount < 0).reduce((acc: number, tx: any) => acc + Math.abs(parseFloat(tx.amount)), 0);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">My Wallet</h1>
          <p className="text-muted-foreground">Manage your wallet balance, add money, and request withdrawals.</p>
        </div>
      </div>

      {/* Balance Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="md:col-span-2 border-l-4 border-l-green-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Available Balance</CardTitle>
            <Wallet className="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">₹{balance.toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">Ready to withdraw or use</p>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-yellow-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Locked Balance</CardTitle>
            <Clock className="h-4 w-4 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">₹{lockedBalance.toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">Pending withdrawals</p>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Balance</CardTitle>
            <IndianRupee className="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{(balance + lockedBalance).toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">Available + Locked</p>
          </CardContent>
        </Card>
      </div>

      {/* Action Buttons */}
      <div className="flex gap-4">
        {/* Add Money Dialog */}
        <Dialog open={isAddMoneyOpen} onOpenChange={setIsAddMoneyOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" /> Add Money
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add Money to Wallet</DialogTitle>
              <DialogDescription>Add funds to your wallet using your preferred payment method.</DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Amount (INR)</Label>
                <Input
                  type="number"
                  value={addAmount}
                  onChange={(e) => setAddAmount(e.target.value)}
                  placeholder="Enter amount (min ₹100)"
                  min="100"
                />
              </div>
              <div className="grid grid-cols-4 gap-2">
                {[500, 1000, 2500, 5000].map(amount => (
                  <Button
                    key={amount}
                    variant="outline"
                    size="sm"
                    onClick={() => setAddAmount(amount.toString())}
                  >
                    ₹{amount}
                  </Button>
                ))}
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsAddMoneyOpen(false)}>Cancel</Button>
              <Button onClick={handleAddMoney} disabled={addMoneyMutation.isPending}>
                {addMoneyMutation.isPending ? "Processing..." : "Proceed to Pay"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Withdraw Dialog */}
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button variant="outline">
              <Minus className="mr-2 h-4 w-4" /> Withdraw
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-md">
            <DialogHeader>
              <DialogTitle>Request Withdrawal</DialogTitle>
              <DialogDescription>
                Min withdrawal: ₹1,000. Processing time: 2-3 business days.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Amount (INR)</Label>
                <Input
                  type="number"
                  value={withdrawAmount}
                  onChange={(e) => setWithdrawAmount(e.target.value)}
                  placeholder="Enter amount (min ₹1,000)"
                  min="1000"
                  max={balance}
                />
                <p className="text-xs text-muted-foreground">Available: ₹{balance.toLocaleString('en-IN')}</p>
              </div>

              <div className="space-y-2">
                <Label>Withdrawal Method</Label>
                <Select value={withdrawMethod} onValueChange={setWithdrawMethod}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="bank">Bank Transfer</SelectItem>
                    <SelectItem value="upi">UPI</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {withdrawMethod === 'bank' ? (
                <>
                  <div className="space-y-2">
                    <Label>Account Holder Name</Label>
                    <Input value={accountName} onChange={(e) => setAccountName(e.target.value)} placeholder="As per bank records" />
                  </div>
                  <div className="space-y-2">
                    <Label>Account Number</Label>
                    <Input value={accountNumber} onChange={(e) => setAccountNumber(e.target.value)} placeholder="Bank account number" />
                  </div>
                  <div className="space-y-2">
                    <Label>IFSC Code</Label>
                    <Input value={ifscCode} onChange={(e) => setIfscCode(e.target.value.toUpperCase())} placeholder="e.g., HDFC0001234" />
                  </div>
                </>
              ) : (
                <div className="space-y-2">
                  <Label>UPI ID</Label>
                  <Input value={upiId} onChange={(e) => setUpiId(e.target.value)} placeholder="yourname@upi" />
                </div>
              )}

              <div className="p-3 bg-muted/50 rounded-lg text-sm">
                <p className="text-muted-foreground">
                  <strong>Note:</strong> Withdrawals are processed within 2-3 business days.
                  A small processing fee may apply.
                </p>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
              <Button onClick={handleWithdraw} disabled={withdrawMutation.isPending}>
                {withdrawMutation.isPending ? "Processing..." : "Submit Request"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        <Button variant="outline" size="icon" onClick={handleDownloadStatement}>
          <Download className="h-4 w-4" />
        </Button>
      </div>

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="overview">
            <History className="mr-2 h-4 w-4" /> Transactions
          </TabsTrigger>
          <TabsTrigger value="withdrawals">
            <Clock className="mr-2 h-4 w-4" /> Withdrawal Requests
          </TabsTrigger>
          <TabsTrigger value="summary">
            <TrendingUp className="mr-2 h-4 w-4" /> Summary
          </TabsTrigger>
        </TabsList>

        {/* Transactions Tab */}
        <TabsContent value="overview">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Transaction History</CardTitle>
                  <CardDescription>All wallet credits and debits</CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Search..."
                      value={searchQuery}
                      onChange={e => setSearchQuery(e.target.value)}
                      className="pl-10 w-48"
                    />
                  </div>
                  <Select value={filterType} onValueChange={setFilterType}>
                    <SelectTrigger className="w-40">
                      <Filter className="h-4 w-4 mr-2" />
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {TRANSACTION_TYPES.map(type => (
                        <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                    <TableHead className="text-right">Balance After</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredTransactions.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                        No transactions found
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredTransactions.map((tx: any) => (
                      <TableRow key={tx.id}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            {new Date(tx.created_at).toLocaleDateString()}
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant={tx.amount > 0 ? 'default' : 'secondary'}>
                            {tx.type?.replace(/_/g, ' ')}
                          </Badge>
                        </TableCell>
                        <TableCell className="max-w-xs truncate">{tx.description}</TableCell>
                        <TableCell className="text-right">
                          <span className={`font-medium flex items-center justify-end gap-1 ${
                            tx.amount > 0 ? 'text-green-600' : 'text-red-600'
                          }`}>
                            {tx.amount > 0 ? (
                              <ArrowUpRight className="h-3 w-3" />
                            ) : (
                              <ArrowDownRight className="h-3 w-3" />
                            )}
                            {tx.amount > 0 ? '+' : ''}₹{Math.abs(tx.amount).toLocaleString('en-IN')}
                          </span>
                        </TableCell>
                        <TableCell className="text-right font-mono">
                          ₹{parseFloat(tx.balance_after).toLocaleString('en-IN')}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Withdrawal Requests Tab */}
        <TabsContent value="withdrawals">
          <Card>
            <CardHeader>
              <CardTitle>Withdrawal Requests</CardTitle>
              <CardDescription>Track your withdrawal request status</CardDescription>
            </CardHeader>
            <CardContent>
              {(!withdrawals?.data || withdrawals.data.length === 0) ? (
                <div className="text-center py-12 text-muted-foreground">
                  <Clock className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p className="text-lg font-medium">No Withdrawal Requests</p>
                  <p className="text-sm">You haven't made any withdrawal requests yet.</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Request ID</TableHead>
                      <TableHead>Date</TableHead>
                      <TableHead>Method</TableHead>
                      <TableHead className="text-right">Amount</TableHead>
                      <TableHead>Status</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {withdrawals.data.map((w: any) => (
                      <TableRow key={w.id}>
                        <TableCell className="font-mono">#{w.id}</TableCell>
                        <TableCell>{new Date(w.created_at).toLocaleDateString()}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            {w.method === 'bank' ? (
                              <Building2 className="h-4 w-4 text-muted-foreground" />
                            ) : (
                              <CreditCard className="h-4 w-4 text-muted-foreground" />
                            )}
                            {w.method === 'bank' ? 'Bank Transfer' : 'UPI'}
                          </div>
                        </TableCell>
                        <TableCell className="text-right font-medium">
                          ₹{parseFloat(w.amount).toLocaleString('en-IN')}
                        </TableCell>
                        <TableCell>
                          <Badge variant={
                            w.status === 'completed' ? 'success' :
                            w.status === 'pending' ? 'warning' :
                            w.status === 'rejected' ? 'destructive' : 'secondary'
                          }>
                            {w.status}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Summary Tab */}
        <TabsContent value="summary">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Wallet Summary</CardTitle>
                <CardDescription>Overview of your wallet activity</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-4 bg-green-500/10 rounded-lg">
                  <div className="flex items-center gap-2">
                    <ArrowUpRight className="h-5 w-5 text-green-500" />
                    <span>Total Credits</span>
                  </div>
                  <span className="text-xl font-bold text-green-600">
                    +₹{totalCredits.toLocaleString('en-IN')}
                  </span>
                </div>
                <div className="flex items-center justify-between p-4 bg-red-500/10 rounded-lg">
                  <div className="flex items-center gap-2">
                    <ArrowDownRight className="h-5 w-5 text-red-500" />
                    <span>Total Debits</span>
                  </div>
                  <span className="text-xl font-bold text-red-600">
                    -₹{totalDebits.toLocaleString('en-IN')}
                  </span>
                </div>
                <div className="flex items-center justify-between p-4 bg-muted/50 rounded-lg border">
                  <div className="flex items-center gap-2">
                    <Wallet className="h-5 w-5" />
                    <span>Net Balance</span>
                  </div>
                  <span className="text-xl font-bold">
                    ₹{(totalCredits - totalDebits).toLocaleString('en-IN')}
                  </span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Quick Stats</CardTitle>
                <CardDescription>Wallet statistics at a glance</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                  <span className="text-sm">Total Transactions</span>
                  <span className="font-medium">{transactions.length}</span>
                </div>
                <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                  <span className="text-sm">Credit Transactions</span>
                  <span className="font-medium text-green-600">
                    {transactions.filter((tx: any) => tx.amount > 0).length}
                  </span>
                </div>
                <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                  <span className="text-sm">Debit Transactions</span>
                  <span className="font-medium text-red-600">
                    {transactions.filter((tx: any) => tx.amount < 0).length}
                  </span>
                </div>
                <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                  <span className="text-sm">Pending Withdrawals</span>
                  <span className="font-medium text-yellow-600">
                    {withdrawals?.data?.filter((w: any) => w.status === 'pending').length || 0}
                  </span>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}
