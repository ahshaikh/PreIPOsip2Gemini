<?php
// V-REMEDIATE-1730-163
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { PlusCircle, Calculator,CheckCircle } from "lucide-react";
import { useState } from "react";

export default function AdminProfitSharingPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  // State for new period
  const [periodName, setPeriodName] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [netProfit, setNetProfit] = useState('');
  const [totalPool, setTotalPool] = useState('');

  // Fetch all periods
  const { data, isLoading } = useQuery({
    queryKey: ['adminProfitShares'],
    queryFn: async () => (await api.get('/admin/profit-sharing')).data,
  });

  // Mutation to create a new period
  const createMutation = useMutation({
    mutationFn: (newPeriod: any) => api.post('/admin/profit-sharing', newPeriod),
    onSuccess: () => {
      toast.success("Period Created!");
      queryClient.invalidateQueries({ queryKey: ['adminProfitShares'] });
      setIsDialogOpen(false);
    },
    onError: (error: any) => {
      toast.error("Failed to Create", { description: error.response?.data?.message });
    }
  });
  
  // Mutation to calculate
  const calculateMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/profit-sharing/${id}/calculate`),
    onSuccess: (data) => {
      toast.success("Calculation Complete", { description: `Calculated for ${data.data.eligible_users} users.` });
      queryClient.invalidateQueries({ queryKey: ['adminProfitShares'] });
    },
    onError: (error: any) => {
      toast.error("Calculation Failed", { description: error.response?.data?.message });
    }
  });

  // Mutation to distribute
  const distributeMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/profit-sharing/${id}/distribute`),
    onSuccess: (data) => {
      toast.success("Distribution Complete!", { description: data.data.message });
      queryClient.invalidateQueries({ queryKey: ['adminProfitShares'] });
    },
    onError: (error: any) => {
      toast.error("Distribution Failed", { description: error.response?.data?.message });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate({
      period_name: periodName,
      start_date: startDate,
      end_date: endDate,
      net_profit: parseFloat(netProfit),
      total_pool: parseFloat(totalPool),
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Manage Profit Sharing</h1>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> Create New Period</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create New Distribution Period</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label>Period Name (e.g., "Q4 2025")</Label>
                <Input value={periodName} onChange={(e) => setPeriodName(e.target.value)} required />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Start Date</Label>
                  <Input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} required />
                </div>
                <div className="space-y-2">
                  <Label>End Date</Label>
                  <Input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} required />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Total Platform Net Profit (₹)</Label>
                <Input type="number" value={netProfit} onChange={(e) => setNetProfit(e.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label>Total Pool to Distribute (₹)</Label>
                <Input type="number" value={totalPool} onChange={(e) => setTotalPool(e.target.value)} required />
              </div>
              <Button type="submit" disabled={createMutation.isPending} className="w-full">
                {createMutation.isPending ? "Creating..." : "Create Period"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Periods</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading periods...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Period</TableHead>
                  <TableHead>Net Profit</TableHead>
                  <TableHead>Distribution Pool</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data.map((period: any) => (
                  <TableRow key={period.id}>
                    <TableCell className="font-medium">{period.period_name}</TableCell>
                    <TableCell>₹{period.net_profit}</TableCell>
                    <TableCell>₹{period.total_pool}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        period.status === 'pending' ? 'bg-gray-100 text-gray-800' :
                        period.status === 'calculated' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-green-100 text-green-800'
                      }`}>
                        {period.status}
                      </span>
                    </TableCell>
                    <TableCell className="space-x-2">
                      {period.status === 'pending' && (
                        <Button variant="outline" size="sm" onClick={() => calculateMutation.mutate(period.id)} disabled={calculateMutation.isPending}>
                          <Calculator className="mr-2 h-4 w-4" /> Calculate
                        </Button>
                      )}
                      {period.status === 'calculated' && (
                        <Button variant="destructive" size="sm" onClick={() => distributeMutation.mutate(period.id)} disabled={distributeMutation.isPending}>
                          <CheckCircle className="mr-2 h-4 w-4" /> Distribute
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}