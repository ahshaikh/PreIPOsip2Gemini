// V-REMEDIATE-1730-171
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, ShoppingCart, TrendingUp } from "lucide-react";
import { useState } from "react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function ProductManagerPage() {
  const queryClient = useQueryClient();
  
  // UI State
  const [isProductOpen, setIsProductOpen] = useState(false);
  const [isInventoryOpen, setIsInventoryOpen] = useState(false);
  
  // Product Form
  const [prodName, setProdName] = useState('');
  const [prodSector, setProdSector] = useState('');
  const [prodPrice, setProdPrice] = useState('');
  const [prodStatus, setProdStatus] = useState('active');

  // Inventory Form
  const [selectedProdId, setSelectedProdId] = useState('');
  const [faceValue, setFaceValue] = useState('');
  const [cost, setCost] = useState('');
  const [extraAlloc, setExtraAlloc] = useState('');
  const [seller, setSeller] = useState('');

  // Queries
  const { data: products, isLoading: pLoading } = useQuery({
    queryKey: ['adminProducts'],
    queryFn: async () => (await api.get('/admin/products')).data,
  });

  const { data: inventory, isLoading: iLoading } = useQuery({
    queryKey: ['adminInventory'],
    queryFn: async () => (await api.get('/admin/bulk-purchases')).data,
  });

  // Mutations
  const productMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/products', data),
    onSuccess: () => {
      toast.success("Product Created");
      queryClient.invalidateQueries({ queryKey: ['adminProducts'] });
      setIsProductOpen(false);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const inventoryMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/bulk-purchases', data),
    onSuccess: () => {
      toast.success("Inventory Added");
      queryClient.invalidateQueries({ queryKey: ['adminInventory'] });
      setIsInventoryOpen(false);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const handleProductSubmit = () => {
    productMutation.mutate({
      name: prodName,
      sector: prodSector,
      face_value_per_unit: parseFloat(prodPrice),
      min_investment: 1000,
      status: prodStatus,
      is_featured: true
    });
  };

  const handleInventorySubmit = () => {
    inventoryMutation.mutate({
      product_id: selectedProdId,
      face_value_purchased: parseFloat(faceValue),
      actual_cost_paid: parseFloat(cost),
      extra_allocation_percentage: parseFloat(extraAlloc),
      seller_name: seller,
      purchase_date: new Date().toISOString().split('T')[0]
    });
  };

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Financial Products & Inventory</h1>
      </div>

      {/* Products Section */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Pre-IPO Products</CardTitle>
            <CardDescription>Manage companies available for investment.</CardDescription>
          </div>
          <Dialog open={isProductOpen} onOpenChange={setIsProductOpen}>
            <DialogTrigger asChild><Button><Plus className="mr-2 h-4 w-4" /> Add Product</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Add New Company</DialogTitle></DialogHeader>
              <div className="space-y-4">
                <div className="space-y-2"><Label>Company Name</Label><Input value={prodName} onChange={e => setProdName(e.target.value)} /></div>
                <div className="space-y-2"><Label>Sector</Label><Input value={prodSector} onChange={e => setProdSector(e.target.value)} /></div>
                <div className="space-y-2"><Label>Face Value (₹)</Label><Input type="number" value={prodPrice} onChange={e => setProdPrice(e.target.value)} /></div>
                <div className="space-y-2">
                  <Label>Status</Label>
                  <Select value={prodStatus} onValueChange={setProdStatus}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="upcoming">Upcoming</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Button onClick={handleProductSubmit} className="w-full" disabled={productMutation.isPending}>Create Product</Button>
              </div>
            </DialogContent>
          </Dialog>
        </CardHeader>
        <CardContent>
          {pLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader><TableRow><TableHead>Name</TableHead><TableHead>Sector</TableHead><TableHead>Price</TableHead><TableHead>Status</TableHead></TableRow></TableHeader>
              <TableBody>
                {products?.map((p: any) => (
                  <TableRow key={p.id}>
                    <TableCell className="font-medium">{p.name}</TableCell>
                    <TableCell>{p.sector}</TableCell>
                    <TableCell>₹{p.face_value_per_unit}</TableCell>
                    <TableCell><span className="capitalize bg-muted px-2 py-1 rounded text-xs">{p.status}</span></TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Inventory Section */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <div>
            <CardTitle>Bulk Inventory</CardTitle>
            <CardDescription>Track bulk purchases and margins.</CardDescription>
          </div>
          <Dialog open={isInventoryOpen} onOpenChange={setIsInventoryOpen}>
            <DialogTrigger asChild><Button variant="secondary"><ShoppingCart className="mr-2 h-4 w-4" /> Add Inventory</Button></DialogTrigger>
            <DialogContent>
              <DialogHeader><DialogTitle>Log Bulk Purchase</DialogTitle></DialogHeader>
              <div className="space-y-4">
                <div className="space-y-2">
                  <Label>Product</Label>
                  <Select onValueChange={setSelectedProdId}>
                    <SelectTrigger><SelectValue placeholder="Select Product" /></SelectTrigger>
                    <SelectContent>
                      {products?.map((p: any) => <SelectItem key={p.id} value={p.id.toString()}>{p.name}</SelectItem>)}
                    </SelectContent>
                  </Select>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2"><Label>Face Value (Total ₹)</Label><Input type="number" value={faceValue} onChange={e => setFaceValue(e.target.value)} /></div>
                  <div className="space-y-2"><Label>Cost Paid (Total ₹)</Label><Input type="number" value={cost} onChange={e => setCost(e.target.value)} /></div>
                </div>
                <div className="space-y-2"><Label>Extra Allocation (%)</Label><Input type="number" value={extraAlloc} onChange={e => setExtraAlloc(e.target.value)} /></div>
                <div className="space-y-2"><Label>Seller Name</Label><Input value={seller} onChange={e => setSeller(e.target.value)} /></div>
                <Button onClick={handleInventorySubmit} className="w-full" disabled={inventoryMutation.isPending}>Log Purchase</Button>
              </div>
            </DialogContent>
          </Dialog>
        </CardHeader>
        <CardContent>
          {iLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader><TableRow><TableHead>Product</TableHead><TableHead>Total Value</TableHead><TableHead>Remaining</TableHead><TableHead>Margin</TableHead></TableRow></TableHeader>
              <TableBody>
                {inventory?.data.map((i: any) => (
                  <TableRow key={i.id}>
                    <TableCell className="font-medium">{i.product.name}</TableCell>
                    <TableCell>₹{parseFloat(i.total_value_received).toLocaleString()}</TableCell>
                    <TableCell>₹{parseFloat(i.value_remaining).toLocaleString()}</TableCell>
                    <TableCell className="text-green-600 font-bold">
                      {((1 - (i.actual_cost_paid / i.total_value_received)) * 100).toFixed(1)}%
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