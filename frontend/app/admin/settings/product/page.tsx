// PHASE 1 AUDIT: Admin Product Review Queue
// Product creation is NOT permitted via admin panel - must go through Company Portal.
// This page provides read-only review and approval/rejection functionality.
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { AlertTriangle, CheckCircle, XCircle, Eye, Info, Building2 } from "lucide-react";

// --- MAIN PAGE ---
// PHASE 1 AUDIT: This is now a read-only review and approval queue.
// Product creation is NOT permitted via admin panel - must go through Company Portal.
export default function ProductManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [viewingProduct, setViewingProduct] = useState<any>(null);
  const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');
  const [productToReject, setProductToReject] = useState<any>(null);
  const [statusFilter, setStatusFilter] = useState<string>('all');

  // Fetch products with status filter
  const { data: products, isLoading } = useQuery({
    queryKey: ['adminProducts', statusFilter],
    queryFn: async () => {
      const params = statusFilter !== 'all' ? `?status=${statusFilter}` : '';
      const response = await api.get(`/admin/products${params}`);
      return response.data?.data || response.data;
    },
  });

  // Approve mutation
  const approveMutation = useMutation({
    mutationFn: (productId: number) => api.post(`/admin/products/${productId}/approve`),
    onSuccess: () => {
      toast.success("Product Approved", { description: "The product is now visible to investors." });
      queryClient.invalidateQueries({ queryKey: ['adminProducts'] });
    },
    onError: (e: any) => toast.error("Approval Failed", { description: e.response?.data?.message })
  });

  // Reject mutation
  const rejectMutation = useMutation({
    mutationFn: ({ productId, reason }: { productId: number; reason: string }) =>
      api.post(`/admin/products/${productId}/reject`, { reason }),
    onSuccess: () => {
      toast.success("Product Rejected", { description: "The company has been notified." });
      queryClient.invalidateQueries({ queryKey: ['adminProducts'] });
      setRejectDialogOpen(false);
      setRejectReason('');
      setProductToReject(null);
    },
    onError: (e: any) => toast.error("Rejection Failed", { description: e.response?.data?.message })
  });

  const handleViewProduct = (product: any) => {
    api.get(`/admin/products/${product.id}`).then(res => {
      setViewingProduct(res.data);
      setIsDialogOpen(true);
    });
  };

  const handleApprove = (productId: number) => {
    approveMutation.mutate(productId);
  };

  const handleOpenRejectDialog = (product: any) => {
    setProductToReject(product);
    setRejectDialogOpen(true);
  };

  const handleReject = () => {
    if (productToReject && rejectReason.length >= 10) {
      rejectMutation.mutate({ productId: productToReject.id, reason: rejectReason });
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: "default" | "secondary" | "destructive" | "outline"; label: string }> = {
      draft: { variant: "secondary", label: "Draft" },
      submitted: { variant: "default", label: "Pending Review" },
      approved: { variant: "outline", label: "Approved" },
      rejected: { variant: "destructive", label: "Rejected" },
      locked: { variant: "outline", label: "Locked" },
    };
    const config = variants[status] || { variant: "secondary", label: status };
    return <Badge variant={config.variant}>{config.label}</Badge>;
  };

  return (
    <div className="space-y-6">
      {/* PHASE 1 AUDIT: Header with policy notice */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Product Review Queue</h1>
          <p className="text-muted-foreground mt-1">Review and approve products submitted by companies</p>
        </div>
      </div>

      {/* PHASE 1 AUDIT: Policy notice */}
      <Alert>
        <Info className="h-4 w-4" />
        <AlertTitle>Admin Product Policy</AlertTitle>
        <AlertDescription>
          Products are authored by companies through their Company Portal. As an administrator, you can
          review, approve, or reject submitted products. You cannot create products directly.
        </AlertDescription>
      </Alert>

      {/* Status Filter Tabs */}
      <div className="flex gap-2">
        <Button
          variant={statusFilter === 'all' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setStatusFilter('all')}
        >
          All Products
        </Button>
        <Button
          variant={statusFilter === 'submitted' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setStatusFilter('submitted')}
        >
          Pending Review
        </Button>
        <Button
          variant={statusFilter === 'approved' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setStatusFilter('approved')}
        >
          Approved
        </Button>
        <Button
          variant={statusFilter === 'rejected' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setStatusFilter('rejected')}
        >
          Rejected
        </Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader><TableRow>
                <TableHead>Product</TableHead>
                <TableHead>Company</TableHead>
                <TableHead>Face Value</TableHead>
                <TableHead>Market Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {products?.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                      No products found matching the selected filter.
                    </TableCell>
                  </TableRow>
                )}
                {products?.map((p: any) => (
                  <TableRow key={p.id}>
                    <TableCell className="font-medium">{p.name}</TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Building2 className="h-4 w-4 text-muted-foreground" />
                        {p.company?.name || <span className="text-destructive">No Company</span>}
                      </div>
                    </TableCell>
                    <TableCell>₹{p.face_value_per_unit}</TableCell>
                    <TableCell className="font-bold">₹{p.current_market_price || p.face_value_per_unit}</TableCell>
                    <TableCell>{getStatusBadge(p.status)}</TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="outline" size="sm" onClick={() => handleViewProduct(p)}>
                          <Eye className="h-4 w-4 mr-1" /> View
                        </Button>
                        {p.status === 'submitted' && (
                          <>
                            <Button
                              variant="default"
                              size="sm"
                              onClick={() => handleApprove(p.id)}
                              disabled={approveMutation.isPending}
                            >
                              <CheckCircle className="h-4 w-4 mr-1" /> Approve
                            </Button>
                            <Button
                              variant="destructive"
                              size="sm"
                              onClick={() => handleOpenRejectDialog(p)}
                            >
                              <XCircle className="h-4 w-4 mr-1" /> Reject
                            </Button>
                          </>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* View Product Dialog (Read-Only) */}
      <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) setViewingProduct(null); }}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Product Details: {viewingProduct?.name}</DialogTitle>
            <DialogDescription>
              Submitted by {viewingProduct?.company?.name || 'Unknown Company'}
            </DialogDescription>
          </DialogHeader>
          {viewingProduct && (
            <ProductViewForm
              product={viewingProduct}
              onClose={() => setIsDialogOpen(false)}
              onApprove={() => { handleApprove(viewingProduct.id); setIsDialogOpen(false); }}
              onReject={() => { setIsDialogOpen(false); handleOpenRejectDialog(viewingProduct); }}
            />
          )}
        </DialogContent>
      </Dialog>

      {/* Reject Dialog with Reason */}
      <Dialog open={rejectDialogOpen} onOpenChange={(open) => { setRejectDialogOpen(open); if (!open) { setRejectReason(''); setProductToReject(null); } }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Reject Product</DialogTitle>
            <DialogDescription>
              Please provide a reason for rejecting "{productToReject?.name}". This will be sent to the company.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="rejectReason">Rejection Reason (min 10 characters)</Label>
              <Textarea
                id="rejectReason"
                value={rejectReason}
                onChange={(e) => setRejectReason(e.target.value)}
                placeholder="Explain why this product is being rejected and what needs to be corrected..."
                rows={4}
              />
              <p className="text-xs text-muted-foreground">
                {rejectReason.length}/10 characters minimum
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setRejectDialogOpen(false)}>Cancel</Button>
            <Button
              variant="destructive"
              onClick={handleReject}
              disabled={rejectReason.length < 10 || rejectMutation.isPending}
            >
              Reject Product
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

// --- READ-ONLY PRODUCT VIEW FORM ---
// PHASE 1 AUDIT: Admins can view product details but not edit content directly.
// Only approve/reject actions are available for submitted products.
function ProductViewForm({ product, onClose, onApprove, onReject }: {
  product: any;
  onClose: () => void;
  onApprove: () => void;
  onReject: () => void;
}) {
  return (
    <div className="space-y-4">
      <Tabs defaultValue="basic" className="h-[60vh]">
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="basic">Basic Info</TabsTrigger>
          <TabsTrigger value="pricing">Pricing</TabsTrigger>
          <TabsTrigger value="company">Company</TabsTrigger>
          <TabsTrigger value="financials">Financials</TabsTrigger>
          <TabsTrigger value="risks" className="text-destructive"><AlertTriangle className="mr-2 h-4 w-4" /> Risks</TabsTrigger>
        </TabsList>

        <ScrollArea className="h-full w-full p-4">
          {/* Basic Info (Read-Only) */}
          <TabsContent value="basic" className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label className="text-muted-foreground">Product Name</Label>
                <p className="font-medium">{product.name}</p>
              </div>
              <div>
                <Label className="text-muted-foreground">Slug</Label>
                <p className="font-medium">{product.slug}</p>
              </div>
              <div>
                <Label className="text-muted-foreground">Sector</Label>
                <p className="font-medium">{product.sector || '-'}</p>
              </div>
              <div>
                <Label className="text-muted-foreground">Status</Label>
                <p className="font-medium capitalize">{product.status}</p>
              </div>
            </div>
            <div>
              <Label className="text-muted-foreground">Description</Label>
              <p className="text-sm mt-1">{typeof product.description === 'string' ? product.description : JSON.stringify(product.description)}</p>
            </div>
          </TabsContent>

          {/* Pricing (Read-Only) */}
          <TabsContent value="pricing" className="space-y-4">
            <div className="grid grid-cols-3 gap-4">
              <div>
                <Label className="text-muted-foreground">Face Value</Label>
                <p className="font-medium text-lg">₹{product.face_value_per_unit}</p>
              </div>
              <div>
                <Label className="text-muted-foreground">Market Price</Label>
                <p className="font-medium text-lg">₹{product.current_market_price || product.face_value_per_unit}</p>
              </div>
              <div>
                <Label className="text-muted-foreground">Min Investment</Label>
                <p className="font-medium">₹{product.min_investment || '0'}</p>
              </div>
            </div>
          </TabsContent>

          {/* Company Info (Read-Only) */}
          <TabsContent value="company" className="space-y-4">
            <Card>
              <CardHeader><CardTitle>Key Highlights</CardTitle></CardHeader>
              <CardContent>
                {product.highlights?.length > 0 ? (
                  <ul className="list-disc pl-4 space-y-1">
                    {product.highlights.map((h: any, i: number) => (
                      <li key={i}>{h.content}</li>
                    ))}
                  </ul>
                ) : <p className="text-muted-foreground">No highlights provided</p>}
              </CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle>Founders</CardTitle></CardHeader>
              <CardContent>
                {product.founders?.length > 0 ? (
                  <div className="space-y-2">
                    {product.founders.map((f: any, i: number) => (
                      <div key={i} className="flex items-center gap-2">
                        <span className="font-medium">{f.name}</span>
                        <span className="text-muted-foreground">- {f.title}</span>
                      </div>
                    ))}
                  </div>
                ) : <p className="text-muted-foreground">No founders listed</p>}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Financials (Read-Only) */}
          <TabsContent value="financials" className="space-y-4">
            <Card>
              <CardHeader><CardTitle>Key Metrics</CardTitle></CardHeader>
              <CardContent>
                {product.key_metrics?.length > 0 ? (
                  <div className="grid grid-cols-2 gap-4">
                    {product.key_metrics.map((m: any, i: number) => (
                      <div key={i}>
                        <Label className="text-muted-foreground">{m.metric_name}</Label>
                        <p className="font-medium">{m.value} {m.unit}</p>
                      </div>
                    ))}
                  </div>
                ) : <p className="text-muted-foreground">No metrics provided</p>}
              </CardContent>
            </Card>
            <Card>
              <CardHeader><CardTitle>Funding Rounds</CardTitle></CardHeader>
              <CardContent>
                {product.funding_rounds?.length > 0 ? (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Round</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Valuation</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {product.funding_rounds.map((r: any, i: number) => (
                        <TableRow key={i}>
                          <TableCell>{r.round_name}</TableCell>
                          <TableCell>{r.date || '-'}</TableCell>
                          <TableCell>{r.amount || '-'}</TableCell>
                          <TableCell>{r.valuation || '-'}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : <p className="text-muted-foreground">No funding rounds listed</p>}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Risks (Read-Only) */}
          <TabsContent value="risks" className="space-y-4">
            <Card>
              <CardHeader><CardTitle>Risk Disclosures</CardTitle></CardHeader>
              <CardContent>
                {product.risk_disclosures?.length > 0 ? (
                  <div className="space-y-4">
                    {product.risk_disclosures.map((r: any, i: number) => (
                      <Alert key={i} variant={r.severity === 'critical' ? 'destructive' : 'default'}>
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>{r.risk_title}</AlertTitle>
                        <AlertDescription>{r.risk_description}</AlertDescription>
                      </Alert>
                    ))}
                  </div>
                ) : <p className="text-muted-foreground">No risk disclosures provided</p>}
              </CardContent>
            </Card>
          </TabsContent>
        </ScrollArea>
      </Tabs>

      {/* Action Buttons */}
      <div className="flex justify-between border-t pt-4">
        <Button variant="outline" onClick={onClose}>Close</Button>
        {product.status === 'submitted' && (
          <div className="flex gap-2">
            <Button variant="destructive" onClick={onReject}>
              <XCircle className="mr-2 h-4 w-4" /> Reject
            </Button>
            <Button onClick={onApprove}>
              <CheckCircle className="mr-2 h-4 w-4" /> Approve
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}