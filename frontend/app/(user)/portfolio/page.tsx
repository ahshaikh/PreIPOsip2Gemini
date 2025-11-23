// V-PHASE5-1730-119 | V-ENHANCED-PORTFOLIO
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import {
  TrendingUp, TrendingDown, PieChart, BarChart3, ArrowUpRight,
  ArrowDownRight, Wallet, Target, Info, Download, Calendar,
  Building2, Clock, Percent, IndianRupee
} from "lucide-react";

export default function PortfolioPage() {
  const [activeTab, setActiveTab] = useState('overview');
  const [selectedHolding, setSelectedHolding] = useState<any>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['portfolio'],
    queryFn: async () => (await api.get('/user/portfolio')).data,
  });

  // Transaction history
  const { data: transactions } = useQuery({
    queryKey: ['portfolioTransactions'],
    queryFn: async () => (await api.get('/user/portfolio/transactions')).data,
    enabled: activeTab === 'transactions',
  });

  if (isLoading) return <div className="flex items-center justify-center h-64">Loading portfolio...</div>;

  // Calculate portfolio metrics
  const totalInvested = parseFloat(data?.summary?.total_invested || 0);
  const currentValue = parseFloat(data?.summary?.current_value || 0);
  const unrealizedGain = parseFloat(data?.summary?.unrealized_gain || 0);
  const gainPercentage = totalInvested > 0 ? ((unrealizedGain / totalInvested) * 100).toFixed(2) : '0.00';
  const isPositive = unrealizedGain >= 0;

  // Calculate allocation percentages
  const holdings = data?.holdings || [];
  const totalHoldingValue = holdings.reduce((acc: number, h: any) => acc + parseFloat(h.total_value || 0), 0);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">My Portfolio</h1>
          <p className="text-muted-foreground">Track your investment performance and holdings.</p>
        </div>
        <Button variant="outline" size="sm">
          <Download className="mr-2 h-4 w-4" /> Download Statement
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="border-l-4 border-l-blue-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Invested</CardTitle>
            <Wallet className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{totalInvested.toLocaleString('en-IN')}</div>
            <p className="text-xs text-muted-foreground">Total capital deployed</p>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-green-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Current Value</CardTitle>
            <PieChart className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">₹{currentValue.toLocaleString('en-IN')}</div>
            <div className="flex items-center mt-1">
              {isPositive ? (
                <ArrowUpRight className="h-4 w-4 text-green-500 mr-1" />
              ) : (
                <ArrowDownRight className="h-4 w-4 text-red-500 mr-1" />
              )}
              <span className={`text-xs ${isPositive ? 'text-green-500' : 'text-red-500'}`}>
                {isPositive ? '+' : ''}{gainPercentage}%
              </span>
            </div>
          </CardContent>
        </Card>

        <Card className={`border-l-4 ${isPositive ? 'border-l-green-500' : 'border-l-red-500'}`}>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Unrealized {isPositive ? 'Gain' : 'Loss'}</CardTitle>
            {isPositive ? (
              <TrendingUp className="h-4 w-4 text-green-500" />
            ) : (
              <TrendingDown className="h-4 w-4 text-red-500" />
            )}
          </CardHeader>
          <CardContent>
            <div className={`text-2xl font-bold ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
              {isPositive ? '+' : ''}₹{Math.abs(unrealizedGain).toLocaleString('en-IN')}
            </div>
            <Progress
              value={Math.min(Math.abs(parseFloat(gainPercentage)), 100)}
              className={`mt-2 ${isPositive ? '[&>div]:bg-green-500' : '[&>div]:bg-red-500'}`}
            />
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-purple-500">
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Holdings</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{holdings.length}</div>
            <p className="text-xs text-muted-foreground">Different products</p>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="overview">
            <PieChart className="mr-2 h-4 w-4" /> Holdings
          </TabsTrigger>
          <TabsTrigger value="transactions">
            <Clock className="mr-2 h-4 w-4" /> Transactions
          </TabsTrigger>
          <TabsTrigger value="analysis">
            <BarChart3 className="mr-2 h-4 w-4" /> Analysis
          </TabsTrigger>
        </TabsList>

        {/* Holdings Tab */}
        <TabsContent value="overview">
          <Card>
            <CardHeader>
              <CardTitle>My Holdings</CardTitle>
              <CardDescription>Your current investment positions</CardDescription>
            </CardHeader>
            <CardContent>
              {holdings.length === 0 ? (
                <div className="text-center py-12 text-muted-foreground">
                  <Target className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p className="text-lg font-medium">No Holdings Yet</p>
                  <p className="text-sm">Start investing to build your portfolio</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Product</TableHead>
                      <TableHead className="text-right">Units</TableHead>
                      <TableHead className="text-right">Avg. Price</TableHead>
                      <TableHead className="text-right">Current Price</TableHead>
                      <TableHead className="text-right">Total Value</TableHead>
                      <TableHead className="text-right">Gain/Loss</TableHead>
                      <TableHead className="text-center">Allocation</TableHead>
                      <TableHead></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {holdings.map((holding: any) => {
                      const units = parseFloat(holding.total_units || 0);
                      const totalValue = parseFloat(holding.total_value || 0);
                      const avgPrice = units > 0 ? totalValue / units : 0;
                      const currentPrice = parseFloat(holding.product?.current_price || avgPrice);
                      const currentTotalValue = units * currentPrice;
                      const gain = currentTotalValue - totalValue;
                      const gainPct = totalValue > 0 ? ((gain / totalValue) * 100).toFixed(2) : '0.00';
                      const allocation = totalHoldingValue > 0 ? ((totalValue / totalHoldingValue) * 100).toFixed(1) : '0';
                      const holdingPositive = gain >= 0;

                      return (
                        <TableRow key={holding.product?.id || holding.id}>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Building2 className="h-4 w-4 text-muted-foreground" />
                              <div>
                                <p className="font-medium">{holding.product?.name}</p>
                                <p className="text-xs text-muted-foreground">{holding.product?.symbol || 'PRE-IPO'}</p>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell className="text-right font-mono">{units.toFixed(4)}</TableCell>
                          <TableCell className="text-right font-mono">₹{avgPrice.toFixed(2)}</TableCell>
                          <TableCell className="text-right font-mono">₹{currentPrice.toFixed(2)}</TableCell>
                          <TableCell className="text-right font-medium">₹{currentTotalValue.toLocaleString('en-IN')}</TableCell>
                          <TableCell className="text-right">
                            <div className={`flex items-center justify-end gap-1 ${holdingPositive ? 'text-green-600' : 'text-red-600'}`}>
                              {holdingPositive ? (
                                <ArrowUpRight className="h-3 w-3" />
                              ) : (
                                <ArrowDownRight className="h-3 w-3" />
                              )}
                              <span className="font-medium">{holdingPositive ? '+' : ''}{gainPct}%</span>
                            </div>
                          </TableCell>
                          <TableCell className="text-center">
                            <div className="flex items-center gap-2">
                              <Progress value={parseFloat(allocation)} className="w-16 h-2" />
                              <span className="text-xs text-muted-foreground">{allocation}%</span>
                            </div>
                          </TableCell>
                          <TableCell>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setSelectedHolding(holding)}
                            >
                              <Info className="h-4 w-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Transactions Tab */}
        <TabsContent value="transactions">
          <Card>
            <CardHeader>
              <CardTitle>Transaction History</CardTitle>
              <CardDescription>All your investment transactions</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Product</TableHead>
                    <TableHead className="text-right">Units</TableHead>
                    <TableHead className="text-right">Price</TableHead>
                    <TableHead className="text-right">Amount</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {transactions?.data?.map((tx: any) => (
                    <TableRow key={tx.id}>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                          {new Date(tx.created_at).toLocaleDateString()}
                        </div>
                      </TableCell>
                      <TableCell>
                        <Badge variant={tx.type === 'buy' ? 'default' : 'secondary'}>
                          {tx.type?.toUpperCase()}
                        </Badge>
                      </TableCell>
                      <TableCell className="font-medium">{tx.product?.name || 'N/A'}</TableCell>
                      <TableCell className="text-right font-mono">{parseFloat(tx.units || 0).toFixed(4)}</TableCell>
                      <TableCell className="text-right font-mono">₹{parseFloat(tx.price || 0).toFixed(2)}</TableCell>
                      <TableCell className="text-right font-medium">₹{parseFloat(tx.amount || 0).toLocaleString('en-IN')}</TableCell>
                      <TableCell>
                        <Badge variant={tx.status === 'completed' ? 'success' : tx.status === 'pending' ? 'warning' : 'destructive'}>
                          {tx.status}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                  {(!transactions?.data || transactions.data.length === 0) && (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                        No transactions yet
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Analysis Tab */}
        <TabsContent value="analysis">
          <div className="grid gap-6 md:grid-cols-2">
            {/* Allocation Chart */}
            <Card>
              <CardHeader>
                <CardTitle>Portfolio Allocation</CardTitle>
                <CardDescription>Distribution of your investments</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {holdings.map((holding: any) => {
                    const value = parseFloat(holding.total_value || 0);
                    const allocation = totalHoldingValue > 0 ? ((value / totalHoldingValue) * 100) : 0;
                    return (
                      <div key={holding.product?.id} className="space-y-2">
                        <div className="flex items-center justify-between">
                          <span className="text-sm font-medium">{holding.product?.name}</span>
                          <span className="text-sm text-muted-foreground">{allocation.toFixed(1)}%</span>
                        </div>
                        <Progress value={allocation} />
                      </div>
                    );
                  })}
                  {holdings.length === 0 && (
                    <p className="text-center text-muted-foreground py-8">No holdings to analyze</p>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Performance Metrics */}
            <Card>
              <CardHeader>
                <CardTitle>Performance Metrics</CardTitle>
                <CardDescription>Key portfolio statistics</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                    <div className="flex items-center gap-2">
                      <Percent className="h-4 w-4 text-muted-foreground" />
                      <span className="text-sm">Overall Return</span>
                    </div>
                    <span className={`font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                      {isPositive ? '+' : ''}{gainPercentage}%
                    </span>
                  </div>
                  <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                    <div className="flex items-center gap-2">
                      <IndianRupee className="h-4 w-4 text-muted-foreground" />
                      <span className="text-sm">Absolute Gain</span>
                    </div>
                    <span className={`font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                      {isPositive ? '+' : ''}₹{Math.abs(unrealizedGain).toLocaleString('en-IN')}
                    </span>
                  </div>
                  <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                    <div className="flex items-center gap-2">
                      <Target className="h-4 w-4 text-muted-foreground" />
                      <span className="text-sm">Number of Holdings</span>
                    </div>
                    <span className="font-medium">{holdings.length}</span>
                  </div>
                  <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                    <div className="flex items-center gap-2">
                      <BarChart3 className="h-4 w-4 text-muted-foreground" />
                      <span className="text-sm">Average Investment</span>
                    </div>
                    <span className="font-medium">
                      ₹{holdings.length > 0 ? (totalInvested / holdings.length).toLocaleString('en-IN') : '0'}
                    </span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      {/* Holding Detail Dialog */}
      <Dialog open={!!selectedHolding} onOpenChange={() => setSelectedHolding(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{selectedHolding?.product?.name}</DialogTitle>
            <DialogDescription>Detailed holding information</DialogDescription>
          </DialogHeader>
          {selectedHolding && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="p-4 bg-muted/50 rounded-lg">
                  <p className="text-sm text-muted-foreground">Total Units</p>
                  <p className="text-lg font-bold">{parseFloat(selectedHolding.total_units || 0).toFixed(4)}</p>
                </div>
                <div className="p-4 bg-muted/50 rounded-lg">
                  <p className="text-sm text-muted-foreground">Face Value</p>
                  <p className="text-lg font-bold">₹{parseFloat(selectedHolding.total_value || 0).toLocaleString('en-IN')}</p>
                </div>
              </div>
              <div className="p-4 border rounded-lg">
                <h4 className="font-medium mb-2">Product Details</h4>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Symbol</span>
                    <span>{selectedHolding.product?.symbol || 'N/A'}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Current Price</span>
                    <span>₹{parseFloat(selectedHolding.product?.current_price || 0).toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Sector</span>
                    <span>{selectedHolding.product?.sector || 'N/A'}</span>
                  </div>
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
