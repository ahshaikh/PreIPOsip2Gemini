<?php
// V-REMEDIATE-1730-159
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
import { PlusCircle, Play } from "lucide-react";
import { useState } from "react";
import { useRouter } from "next/navigation";

export default function AdminLuckyDrawsPage() {
  const queryClient = useQueryClient();
  const router = useRouter();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  // State for new draw
  const [name, setName] = useState('');
  const [drawDate, setDrawDate] = useState('');
  const [prizeStructure, setPrizeStructure] = useState(
    JSON.stringify([
      { "rank": 1, "count": 1, "amount": 50000 },
      { "rank": 2, "count": 4, "amount": 10000 },
      { "rank": 3, "count": 10, "amount": 2000 }
    ], null, 2)
  );

  // Fetch all draws
  const { data, isLoading } = useQuery({
    queryKey: ['adminDraws'],
    queryFn: async () => (await api.get('/admin/lucky-draws')).data,
  });

  // Mutation to create a new draw
  const createMutation = useMutation({
    mutationFn: (newDraw: any) => api.post('/admin/lucky-draws', newDraw),
    onSuccess: () => {
      toast.success("Lucky Draw Created!");
      queryClient.invalidateQueries({ queryKey: ['adminDraws'] });
      setIsDialogOpen(false);
    },
    onError: (error: any) => {
      toast.error("Failed to Create Draw", { description: error.response?.data?.message });
    }
  });

  // Mutation to execute a draw
  const executeMutation = useMutation({
    mutationFn: (drawId: number) => api.post(`/admin/lucky-draws/${drawId}/execute`),
    onSuccess: (data: any) => {
      toast.success("Draw Executed!", { description: `${data.data.winners.length} winners have been paid.` });
      queryClient.invalidateQueries({ queryKey: ['adminDraws'] });
    },
    onError: (error: any) => {
      toast.error("Draw Execution Failed", { description: error.response?.data?.message });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const parsedStructure = JSON.parse(prizeStructure);
      createMutation.mutate({ name, draw_date: drawDate, prize_structure: parsedStructure });
    } catch (e) {
      toast.error("Invalid Prize Structure", { description: "Please enter valid JSON." });
    }
  };
  
  const handleExecute = (draw: any) => {
    if (confirm(`Are you sure you want to execute '${draw.name}'? This is irreversible and will pay out all prizes.`)) {
      executeMutation.mutate(draw.id);
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Manage Lucky Draws</h1>
        <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
          <DialogTrigger asChild>
            <Button><PlusCircle className="mr-2 h-4 w-4" /> Create New Draw</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create New Lucky Draw</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Draw Name</Label>
                <Input id="name" value={name} onChange={(e) => setName(e.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="drawDate">Draw Date</Label>
                <Input id="drawDate" type="date" value={drawDate} onChange={(e) => setDrawDate(e.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="prizeStructure">Prize Structure (JSON)</Label>
                <Textarea
                  id="prizeStructure"
                  value={prizeStructure}
                  onChange={(e) => setPrizeStructure(e.target.value)}
                  required
                  rows={8}
                />
              </div>
              <Button type="submit" disabled={createMutation.isPending} className="w-full">
                {createMutation.isPending ? "Creating..." : "Create Draw"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Draws</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? <p>Loading draws...</p> : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Draw Date</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.data.map((draw: any) => (
                  <TableRow key={draw.id}>
                    <TableCell className="font-medium">{draw.name}</TableCell>
                    <TableCell>{new Date(draw.draw_date).toLocaleDateString()}</TableCell>
                    <TableCell>
                      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${
                        draw.status === 'open' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-green-100 text-green-800'
                      }`}>
                        {draw.status}
                      </span>
                    </TableCell>
                    <TableCell>
                      {draw.status === 'open' && (
                        <Button 
                          variant="destructive" 
                          size="sm"
                          onClick={() => handleExecute(draw)}
                          disabled={executeMutation.isPending}
                        >
                          <Play className="mr-2 h-4 w-4" />
                          Execute Draw
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