// V-PHASE5-1730-120 | V-ENHANCED-BONUSES
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { toast } from "sonner";
import {
  Gift, Users, TrendingUp, Wallet, Calendar, Download,
  Search, Filter, Star, Award, Target, Sparkles, CreditCard,
  ArrowUpRight, Clock, Trophy
} from "lucide-react";
import { PaginationControls } from "@/components/shared/PaginationControls";

// Bonus types with labels and colors
const BONUS_TYPES = [
  { value: 'all', label: 'All Types' },
  { value: 'referral_bonus', label: 'Referral Bonus', icon: Users, color: 'text-blue-500', bgColor: 'bg-blue-500/10' },
  { value: 'welcome_bonus', label: 'Welcome Bonus', icon: Star, color: 'text-yellow-500', bgColor: 'bg-yellow-500/10' },
  { value: 'loyalty_bonus', label: 'Loyalty Bonus', icon: Award, color: 'text-purple-500', bgColor: 'bg-purple-500/10' },
  { value: 'milestone_bonus', label: 'Milestone Bonus', icon: Target, color: 'text-green-500', bgColor: 'bg-green-500/10' },
  { value: 'special_bonus', label: 'Special Bonus', icon: Sparkles, color: 'text-pink-500', bgColor: 'bg-pink-500/10' },
  { value: 'cashback', label: 'Cashback', icon: CreditCard, color: 'text-orange-500', bgColor: 'bg-orange-500/10' },
];

export default function BonusesPage() {
  const [activeTab, setActiveTab] = useState('overview');
  const [filterType, setFilterType] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [dateRange, setDateRange] = useState('all');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['bonuses'],
    queryFn: async () => (await api.get('/user/bonuses')).data,
  });

  // Paginated bonus transactions
  const { data: paginatedTransactions } = useQuery({
    queryKey: ['bonusTransactions', page, filterType],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        type: filterType,
      });
      const res = await api.get(`/user/bonuses/transactions?${params.toString()}`);
      return res.data;
    },
    enabled: activeTab === 'overview',
    placeholderData: (prev) => prev,
  });

  // Upcoming/Pending bonuses
  const { data: pendingBonuses } = useQuery({
    queryKey: ['pendingBonuses'],
    queryFn: async () => (await api.get('/user/bonuses/pending')).data,
  });

  if (isLoading) return <div className="flex items-center justify-center h-64">Loading bonuses...</div>;

  // Calculate totals
  const summary = data?.summary || {};
  const totalBonuses = Object.values(summary).reduce((acc: number, val: any) => acc + (Number(val) || 0), 0);

  // Use paginated transactions when in overview tab
  const transactions = paginatedTransactions?.data || [];

  // Filter transactions by search query (type filter is handled by backend)
  const filteredTransactions = transactions.filter((tx: any) => {
    const matchesSearch = searchQuery === '' ||
      tx.description?.toLowerCase().includes(searchQuery.toLowerCase()) ||
      tx.type?.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesSearch;
  });

  // Get bonus type info
  const getBonusTypeInfo = (type: string) => {
    return BONUS_TYPES.find(t => t.value === type) || BONUS_TYPES[0];
  };

  // Get highest bonus type
  const highestBonusType = Object.entries(summary).reduce((max, [type, value]) => {
    return (Number(value) || 0) > (Number(max.value) || 0) ? { type, value } : max;
  }, { type: '', value: 0 });

  const handleExportHistory = async () => {
    try {
      const response = await api.get('/user/bonuses/export', {
        responseType: 'blob'
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `bonuses-history-${new Date().toISOString().split('T')[0]}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Export Successful", { description: "Your bonus history has been downloaded" });
    } catch (error: any) {
      toast.error("Export Failed", { description: error.response?.data?.message || "Unable to export bonus history" });
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">My Bonuses</h1>
          <p className="text-muted-foreground">Track all your rewards and bonus earnings.</p>
        </div>
        <Button variant="outline" size="sm" onClick={handleExportHistory}>
          <Download className="mr-2 h-4 w-4" /> Export History
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total Bonuses */}
        <Card className="border-l-4 border-l-yellow-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Bonuses Earned</CardTitle>
            <Trophy className="h-4 w-4 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{totalBonuses.toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">Lifetime earnings</p>
          </CardContent>
        </Card>

        {/* Referral Bonuses */}
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Referral Bonuses</CardTitle>
            <Users className="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{Number(summary.referral_bonus || 0).toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">From referrals</p>
          </CardContent>
        </Card>

        {/* Welcome/Other Bonuses */}
        <Card className="border-l-4 border-l-green-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Special Bonuses</CardTitle>
            <Sparkles className="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              ₹{Number(summary.welcome_bonus || 0) + Number(summary.special_bonus || 0)}
            </div>
            <p className="text-xs text-muted-foreground">Welcome & special rewards</p>
          </CardContent>
        </Card>

        {/* Pending Bonuses */}
        <Card className="border-l-4 border-l-orange-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Pending Bonuses</CardTitle>
            <Clock className="h-4 w-4 text-orange-500" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-orange-600">
              ₹{Number(pendingBonuses?.total || 0).toLocaleString('en-IN')}
            </div>
            <p className="text-xs text-muted-foreground">Waiting to be credited</p>
          </CardContent>
        </Card>
      </div>

      {/* Bonus Breakdown by Type */}
      <Card>
        <CardHeader>
          <CardTitle>Bonus Breakdown</CardTitle>
          <CardDescription>Your earnings by bonus type</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {BONUS_TYPES.filter(t => t.value !== 'all').map(bonusType => {
              const amount = Number(summary[bonusType.value] || 0);
              const percentage = totalBonuses > 0 ? ((amount / totalBonuses) * 100).toFixed(1) : '0';
              const Icon = bonusType.icon;
              return (
                <div
                  key={bonusType.value}
                  className={`p-4 rounded-lg ${bonusType.bgColor} text-center`}
                >
                  <Icon className={`h-6 w-6 mx-auto mb-2 ${bonusType.color}`} />
                  <p className="text-sm font-medium">{bonusType.label}</p>
                  <p className="text-lg font-bold">₹{amount.toLocaleString('en-IN')}</p>
                  <p className="text-xs text-muted-foreground">{percentage}%</p>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Main Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="overview">
            <Gift className="mr-2 h-4 w-4" /> Transactions
          </TabsTrigger>
          <TabsTrigger value="pending">
            <Clock className="mr-2 h-4 w-4" /> Pending
          </TabsTrigger>
          <TabsTrigger value="milestones">
            <Target className="mr-2 h-4 w-4" /> Milestones
          </TabsTrigger>
        </TabsList>

        {/* Transactions Tab */}
        <TabsContent value="overview">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Bonus Transactions</CardTitle>
                  <CardDescription>All your bonus credits</CardDescription>
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
                      {BONUS_TYPES.map(type => (
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
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredTransactions.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                        No bonus transactions found
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredTransactions.map((tx: any) => {
                      const typeInfo = getBonusTypeInfo(tx.type);
                      const Icon = typeInfo.icon || Gift;
                      return (
                        <TableRow key={tx.id}>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Calendar className="h-4 w-4 text-muted-foreground" />
                              {new Date(tx.created_at).toLocaleDateString()}
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <div className={`p-1.5 rounded-full ${typeInfo.bgColor || 'bg-muted'}`}>
                                <Icon className={`h-3 w-3 ${typeInfo.color || ''}`} />
                              </div>
                              <span className="capitalize">{tx.type?.replace(/_/g, ' ')}</span>
                            </div>
                          </TableCell>
                          <TableCell className="max-w-xs truncate">{tx.description}</TableCell>
                          <TableCell className="text-right">
                            <span className="font-medium text-green-600 flex items-center justify-end gap-1">
                              <ArrowUpRight className="h-3 w-3" />
                              ₹{Number(tx.amount).toLocaleString('en-IN')}
                            </span>
                          </TableCell>
                          <TableCell>
                            <Badge variant={tx.status === 'credited' ? 'success' : tx.status === 'pending' ? 'warning' : 'secondary'}>
                              {tx.status || 'credited'}
                            </Badge>
                          </TableCell>
                        </TableRow>
                      );
                    })
                  )}
                </TableBody>
              </Table>

              {/* Pagination Controls */}
              {paginatedTransactions && (
                <PaginationControls
                  currentPage={paginatedTransactions.current_page}
                  totalPages={paginatedTransactions.last_page}
                  onPageChange={setPage}
                  totalItems={paginatedTransactions.total}
                  from={paginatedTransactions.from}
                  to={paginatedTransactions.to}
                />
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Pending Tab */}
        <TabsContent value="pending">
          <Card>
            <CardHeader>
              <CardTitle>Pending Bonuses</CardTitle>
              <CardDescription>Bonuses waiting to be credited to your account</CardDescription>
            </CardHeader>
            <CardContent>
              {(!pendingBonuses?.items || pendingBonuses.items.length === 0) ? (
                <div className="text-center py-12 text-muted-foreground">
                  <Clock className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p className="text-lg font-medium">No Pending Bonuses</p>
                  <p className="text-sm">All your bonuses have been credited!</p>
                </div>
              ) : (
                <div className="space-y-4">
                  {pendingBonuses.items.map((bonus: any) => {
                    const typeInfo = getBonusTypeInfo(bonus.type);
                    const Icon = typeInfo.icon || Gift;
                    return (
                      <div key={bonus.id} className="flex items-center justify-between p-4 border rounded-lg">
                        <div className="flex items-center gap-4">
                          <div className={`p-3 rounded-full ${typeInfo.bgColor || 'bg-muted'}`}>
                            <Icon className={`h-5 w-5 ${typeInfo.color || ''}`} />
                          </div>
                          <div>
                            <p className="font-medium">{bonus.description}</p>
                            <p className="text-sm text-muted-foreground capitalize">
                              {bonus.type?.replace(/_/g, ' ')}
                            </p>
                          </div>
                        </div>
                        <div className="text-right">
                          <p className="text-lg font-bold text-orange-600">
                            ₹{Number(bonus.amount).toLocaleString('en-IN')}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            Expected: {bonus.expected_date ? new Date(bonus.expected_date).toLocaleDateString() : 'Soon'}
                          </p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Milestones Tab */}
        <TabsContent value="milestones">
          <Card>
            <CardHeader>
              <CardTitle>Bonus Milestones</CardTitle>
              <CardDescription>Unlock rewards by reaching these milestones</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-6">
                {/* First Investment Milestone */}
                <div className="flex items-center gap-4 p-4 border rounded-lg">
                  <div className="p-3 bg-green-500/10 rounded-full">
                    <Star className="h-6 w-6 text-green-500" />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <p className="font-medium">First Investment</p>
                      <Badge variant="success">Completed</Badge>
                    </div>
                    <Progress value={100} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">₹500 welcome bonus earned</p>
                  </div>
                </div>

                {/* Referral Milestone */}
                <div className="flex items-center gap-4 p-4 border rounded-lg">
                  <div className="p-3 bg-blue-500/10 rounded-full">
                    <Users className="h-6 w-6 text-blue-500" />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <p className="font-medium">Refer 5 Friends</p>
                      <span className="text-sm text-muted-foreground">2/5</span>
                    </div>
                    <Progress value={40} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">₹1,000 bonus on completion</p>
                  </div>
                </div>

                {/* Investment Amount Milestone */}
                <div className="flex items-center gap-4 p-4 border rounded-lg">
                  <div className="p-3 bg-purple-500/10 rounded-full">
                    <Target className="h-6 w-6 text-purple-500" />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <p className="font-medium">Invest ₹50,000</p>
                      <span className="text-sm text-muted-foreground">₹25,000/₹50,000</span>
                    </div>
                    <Progress value={50} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">₹2,500 loyalty bonus on completion</p>
                  </div>
                </div>

                {/* Streak Milestone */}
                <div className="flex items-center gap-4 p-4 border rounded-lg">
                  <div className="p-3 bg-orange-500/10 rounded-full">
                    <Award className="h-6 w-6 text-orange-500" />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <p className="font-medium">6-Month SIP Streak</p>
                      <span className="text-sm text-muted-foreground">3/6 months</span>
                    </div>
                    <Progress value={50} className="h-2" />
                    <p className="text-xs text-muted-foreground mt-1">₹1,500 consistency bonus on completion</p>
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
