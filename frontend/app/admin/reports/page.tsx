// V-FINAL-1730-223 (Created) | V-FINAL-1730-488 (Full Report Suite)
'use client';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import { Textarea } from "@/components/ui/textarea";
import api from "@/lib/api";
import { useQuery } from "@tanstack/react-query";
import { DollarSign, Users, Package, CreditCard, Download, TrendingUp, AlertCircle, FileText } from "lucide-react";
import { DynamicLineChart, DynamicBarChart, Line, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from '@/components/shared/DynamicChart';
import { useState } from "react";

export default function AdvancedReportsPage() {
  const [isDownloading, setIsDownloading] = useState(false);
  const [exportFormat, setExportFormat] = useState('csv');

  // 1. Fetch Financial Summary
  const { data: financials } = useQuery({
    queryKey: ['adminFinancialSummary'],
    queryFn: async () => (await api.get('/admin/reports/financial-summary')).data,
  });

  // 2. Fetch User Analytics
  const { data: userStats } = useQuery({
    queryKey: ['adminUserAnalytics'],
    queryFn: async () => (await api.get('/admin/reports/analytics/users')).data,
  });

  // 3. Fetch Product Performance
  const { data: productStats } = useQuery({
    queryKey: ['adminProductStats'],
    queryFn: async () => (await api.get('/admin/reports/analytics/products')).data,
  });

  const handleExport = async (type: string) => {
    setIsDownloading(true);
    try {
      const response = await api.get(`/admin/reports/download?report_type=${type}&format=${exportFormat}`, { 
        responseType: 'blob' 
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${type}_report.${exportFormat}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Report Downloaded");
    } catch (e) {
      toast.error("Export Failed");
    } finally {
      setIsDownloading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 className="text-3xl font-bold">Reporting & Analytics</h1>
          <p className="text-muted-foreground">Deep dive into platform performance and compliance.</p>
        </div>
        <div className="flex items-center gap-2 bg-muted p-2 rounded-lg">
          <span className="text-sm font-medium ml-2">Export Format:</span>
          <Select value={exportFormat} onValueChange={setExportFormat}>
            <SelectTrigger className="w-[100px] h-8"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="csv">CSV</SelectItem>
              <SelectItem value="pdf">PDF</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <Tabs defaultValue="financial" className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="financial">Financials</TabsTrigger>
          <TabsTrigger value="users">User Growth</TabsTrigger>
          <TabsTrigger value="products">Products</TabsTrigger>
          <TabsTrigger value="compliance">Compliance</TabsTrigger>
        </TabsList>

        {/* --- TAB 1: FINANCIALS --- */}
        <TabsContent value="financial" className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
                <DollarSign className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent><div className="text-2xl font-bold">₹{financials?.kpis.total_revenue?.toLocaleString() || 0}</div></CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                <Users className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent><div className="text-2xl font-bold">{financials?.kpis.total_users || 0}</div></CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Pending KYC</CardTitle>
                <Users className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent><div className="text-2xl font-bold">{financials?.kpis.pending_kyc || 0}</div></CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Pending Withdrawals</CardTitle>
                <DollarSign className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent><div className="text-2xl font-bold">{financials?.kpis.pending_withdrawals || 0}</div></CardContent>
            </Card>
          </div>

          <div className="grid md:grid-cols-2 gap-6">
            <Card>
              <CardHeader><CardTitle>Revenue Trend (30 Days)</CardTitle></CardHeader>
              <CardContent className="h-[300px]">
                <ResponsiveContainer width="100%" height="100%">
                  <DynamicLineChart data={financials?.charts.daily_revenue || []}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="date" />
                    <YAxis />
                    <Tooltip formatter={(value: any) => `₹${value.toLocaleString()}`} />
                    <Line type="monotone" dataKey="total" stroke="#8884d8" strokeWidth={2} />
                  </DynamicLineChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle>Profit & Loss (This Year)</CardTitle></CardHeader>
              <CardContent>
                <Button className="w-full" onClick={() => handleExport('p-and-l')} disabled={isDownloading}>
                  <Download className="mr-2 h-4 w-4" /> Download P&L Statement
                </Button>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* --- TAB 2: USER ANALYTICS --- */}
        <TabsContent value="users" className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium">KYC Verified</CardTitle></CardHeader>
              <CardContent><div className="text-2xl font-bold">{userStats?.kyc_percentage || 0}%</div></CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium">Churn Rate (This Year)</CardTitle></CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-destructive">{userStats?.retention_metrics.churn_rate || 0}%</div>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="pb-2"><CardTitle className="text-sm font-medium">Users Lost (This Year)</CardTitle></CardHeader>
              <CardContent><div className="text-2xl font-bold">{userStats?.retention_metrics.users_lost || 0}</div></CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader><CardTitle>User Acquisition (Last 12 Months)</CardTitle></CardHeader>
            <CardContent className="h-[300px]">
              <ResponsiveContainer width="100%" height="100%">
                <DynamicBarChart data={userStats?.acquisition_chart || []}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="month" />
                  <YAxis />
                  <Tooltip />
                  <Bar dataKey="count" fill="#82ca9d" name="New Users" />
                </DynamicBarChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>
        </TabsContent>

        {/* --- TAB 3: PRODUCTS --- */}
        <TabsContent value="products" className="space-y-6">
          <div className="flex justify-end">
            <Button variant="outline" size="sm" onClick={() => handleExport('products')} disabled={isDownloading}>
              <Download className="mr-2 h-4 w-4" /> Export Performance Report
            </Button>
          </div>
          <Card>
            <CardHeader><CardTitle>Product Performance</CardTitle></CardHeader>
            <CardContent>
              <Table>
                <TableHeader><TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>Total Inventory</TableHead>
                  <TableHead>Sold Value</TableHead>
                  <TableHead>Sold %</TableHead>
                  <TableHead>Investors</TableHead>
                </TableRow></TableHeader>
                <TableBody>
                  {productStats?.map((p: any) => (
                    <TableRow key={p.id}>
                      <TableCell className="font-medium">{p.name}</TableCell>
                      <TableCell>₹{p.total_inventory.toLocaleString()}</TableCell>
                      <TableCell>₹{p.sold_value.toLocaleString()}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <div className="w-full bg-gray-200 rounded-full h-2.5 w-[60px]">
                            <div className="bg-blue-600 h-2.5 rounded-full" style={{ width: `${p.sold_percentage}%` }}></div>
                          </div>
                          <span className="text-xs">{p.sold_percentage}%</span>
                        </div>
                      </TableCell>
                      <TableCell>{p.investor_count}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* --- TAB 4: COMPLIANCE --- */}
        <TabsContent value="compliance" className="space-y-6">
          <Card className="bg-amber-50 border-amber-200">
            <CardContent className="flex items-start gap-4 pt-6">
              <AlertCircle className="h-6 w-6 text-amber-600 mt-1" />
              <div>
                <h3 className="font-semibold text-amber-800">Compliance Center</h3>
                <p className="text-sm text-amber-700">Download reports for tax filing and internal audits. Ensure all data is verified before submitting to authorities.</p>
              </div>
            </CardContent>
          </Card>

          <div className="grid md:grid-cols-3 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>GST Report (GSTR-1)</CardTitle>
                <CardDescription>Export all sales (payments) for GST filing.</CardDescription>
              </CardHeader>
              <CardContent>
                <Button className="w-full" onClick={() => handleExport('gst')} disabled={isDownloading}>
                  <Download className="mr-2 h-4 w-4" /> Download GST Report
                </Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>TDS Report (Form 26Q)</CardTitle>
                <CardDescription>Export all payouts (withdrawals) for TDS filing.</CardDescription>
              </CardHeader>
              <CardContent>
                <Button className="w-full" onClick={() => handleExport('tds')} disabled={isDownloading}>
                  <Download className="mr-2 h-4 w-4" /> Download TDS Report
                </Button>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <CardTitle>Global Audit Trail</CardTitle>
                <CardDescription>Export a full log of all system and admin actions.</CardDescription>
              </CardHeader>
              <CardContent>
                <Button className="w-full" onClick={() => handleExport('audit-trail')} disabled={isDownloading}>
                  <Download className="mr-2 h-4 w-4" /> Download Audit Log
                </Button>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}