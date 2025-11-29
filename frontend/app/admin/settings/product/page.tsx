// V-REMEDIATE-1730-171 (Created) | V-FINAL-1730-515 (Full Product Editor)
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ScrollArea } from "@/components/ui/scroll-area";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { Plus, Edit, Trash2, GripVertical, Save, AlertTriangle, Shield } from "lucide-react";

/**
 * Generate a unique ID for array items
 */
const generateUniqueId = (): string => {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
};

/**
 * Helper to format date for input[type="date"]
 * (Handles null or undefined values)
 */
const formatDateForInput = (date: string | null | undefined): string => {
    if (!date) return '';
    try {
        return new Date(date).toISOString().split('T')[0];
    } catch (e) {
        return '';
    }
};

// --- Edit Form (Handles all product data) ---
function EditProductForm({ product, onSave, onCancel }: { product: any, onSave: (data: any) => void, onCancel: () => void }) {
    const [formData, setFormData] = useState(product);

    useEffect(() => {
        // Format dates for input fields
        const formattedData = {
            ...product,
            expected_ipo_date: formatDateForInput(product.expected_ipo_date),
            sebi_approval_date: formatDateForInput(product.sebi_approval_date),
            funding_rounds: (product.funding_rounds || []).map((r: any) => ({
                ...r,
                date: formatDateForInput(r.date)
            }))
        };
        setFormData(formattedData);
    }, [product]);

    const handleChange = (field: string, value: any) => {
        setFormData((prev: any) => ({ ...prev, [field]: value }));
    };

    // --- Array Handlers (for Highlights, Founders, etc.) ---
    const handleArrayChange = (key: string, index: number, field: string, value: any) => {
        setFormData((prev: any) => {
            const newArray = [...(prev[key] || [])];
            newArray[index] = { ...newArray[index], [field]: value };
            return { ...prev, [key]: newArray };
        });
    };

    const addArrayItem = (key: string, template: any) => {
        setFormData((prev: any) => ({
            ...prev,
            [key]: [...(prev[key] || []), { ...template, _uid: generateUniqueId() }]
        }));
    };

    const removeArrayItem = (key: string, index: number) => {
        setFormData((prev: any) => ({
            ...prev,
            [key]: prev[key].filter((_: any, i: number) => i !== index)
        }));
    };
    // ----------------------------------------------------

    const handleSave = () => {
        onSave(formData);
    };

    return (
        <div className="space-y-4">
            <Tabs defaultValue="basic" className="h-[70vh]">
                <TabsList className="grid w-full grid-cols-6">
                    <TabsTrigger value="basic">Basic Info</TabsTrigger>
                    <TabsTrigger value="pricing">Pricing</TabsTrigger>
                    <TabsTrigger value="company">Company</TabsTrigger>
                    <TabsTrigger value="financials">Financials</TabsTrigger>
                    <TabsTrigger value="risks" className="text-destructive"><AlertTriangle className="mr-2 h-4 w-4" /> Risks</TabsTrigger>
                    <TabsTrigger value="compliance"><Shield className="mr-2 h-4 w-4" /> Compliance</TabsTrigger>
                </TabsList>

                <ScrollArea className="h-full w-full p-4">
                    {/* --- Basic Info --- */}
                    <TabsContent value="basic" className="space-y-4">
                        <div className="space-y-2">
                            <Label>Product Name</Label>
                            <Input value={formData.name || ''} onChange={(e) => handleChange('name', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2"><Label>Slug</Label><Input value={formData.slug || ''} onChange={(e) => handleChange('slug', e.target.value)} /></div>
                            <div className="space-y-2"><Label>Sector</Label><Input value={formData.sector || ''} onChange={(e) => handleChange('sector', e.target.value)} /></div>
                        </div>
                        <div className="space-y-2">
                            <Label>About (JSON Description)</Label>
                            <Textarea value={formData.description || ''} onChange={(e) => handleChange('description', e.target.value)} rows={5} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="flex items-center space-x-2"><Switch id="is_active" checked={formData.is_active} onCheckedChange={(c) => handleChange('is_active', c)} /><Label htmlFor="is_active">Active</Label></div>
                            <div className="flex items-center space-x-2"><Switch id="is_featured" checked={formData.is_featured} onCheckedChange={(c) => handleChange('is_featured', c)} /><Label htmlFor="is_featured">Featured</Label></div>
                        </div>
                    </TabsContent>

                    {/* --- Pricing --- */}
                    <TabsContent value="pricing" className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2"><Label>Face Value (Cost Basis)</Label><Input type="number" value={formData.face_value_per_unit || 0} onChange={(e) => handleChange('face_value_per_unit', parseFloat(e.target.value))} /></div>
                            <div className="space-y-2"><Label>Current Market Price</Label><Input type="number" value={formData.current_market_price || 0} onChange={(e) => handleChange('current_market_price', parseFloat(e.target.value))} /></div>
                        </div>
                        <div className="space-y-2"><Label>Min. Investment</Label><Input type="number" value={formData.min_investment || 0} onChange={(e) => handleChange('min_investment', parseFloat(e.target.value))} /></div>
                        <hr />
                        <div className="space-y-2">
                            <div className="flex items-center space-x-2">
                                <Switch id="auto_update" checked={formData.auto_update_price} onCheckedChange={(c) => handleChange('auto_update_price', c)} />
                                <Label htmlFor="auto_update">Auto-Update Price via API</Label>
                            </div>
                            <Label>Price API Endpoint</Label>
                            <Input value={formData.price_api_endpoint || ''} onChange={(e) => handleChange('price_api_endpoint', e.target.value)} />
                        </div>
                    </TabsContent>
                    
                    {/* --- Company Info (Highlights, Founders) --- */}
                    <TabsContent value="company" className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Key Highlights</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                {formData.highlights?.map((item: any, index: number) => (
                                    <div key={item.id || item._uid || `highlight-${index}`} className="flex gap-2"><Input value={item.content} onChange={(e) => handleArrayChange('highlights', index, 'content', e.target.value)} /><Button variant="destructive" size="icon" onClick={() => removeArrayItem('highlights', index)}><Trash2 className="h-4 w-4" /></Button></div>
                                ))}
                                <Button variant="outline" size="sm" onClick={() => addArrayItem('highlights', { content: '' })}>+ Add Highlight</Button>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle>Founders</CardTitle></CardHeader>
                            <CardContent className="space-y-4">
                                {formData.founders?.map((item: any, index: number) => (
                                    <div key={item.id || item._uid || `founder-${index}`} className="border p-3 rounded-md space-y-2">
                                        <div className="flex justify-between"><Label>Founder #{index + 1}</Label><Button variant="destructive" size="xs" onClick={() => removeArrayItem('founders', index)}>Remove</Button></div>
                                        <div className="grid grid-cols-2 gap-2"><Input placeholder="Name" value={item.name} onChange={(e) => handleArrayChange('founders', index, 'name', e.target.value)} /><Input placeholder="Title" value={item.title} onChange={(e) => handleArrayChange('founders', index, 'title', e.target.value)} /></div>
                                        <Input placeholder="LinkedIn URL" value={item.linkedin_url} onChange={(e) => handleArrayChange('founders', index, 'linkedin_url', e.target.value)} />
                                    </div>
                                ))}
                                <Button variant="outline" size="sm" onClick={() => addArrayItem('founders', { name: '', title: '', linkedin_url: '' })}>+ Add Founder</Button>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* --- Financials (Funding, Metrics) --- */}
                    <TabsContent value="financials" className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Key Metrics</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                {formData.key_metrics?.map((item: any, index: number) => (
                                    <div key={item.id || item._uid || `metric-${index}`} className="flex gap-2">
                                        <Input placeholder="Metric (e.g. Revenue)" value={item.metric_name} onChange={(e) => handleArrayChange('key_metrics', index, 'metric_name', e.target.value)} />
                                        <Input placeholder="Value (e.g. 500)" value={item.value} onChange={(e) => handleArrayChange('key_metrics', index, 'value', e.target.value)} />
                                        <Input placeholder="Unit (e.g. Crores)" value={item.unit} onChange={(e) => handleArrayChange('key_metrics', index, 'unit', e.target.value)} />
                                        <Button variant="destructive" size="icon" onClick={() => removeArrayItem('key_metrics', index)}><Trash2 className="h-4 w-4" /></Button>
                                    </div>
                                ))}
                                <Button variant="outline" size="sm" onClick={() => addArrayItem('key_metrics', { metric_name: '', value: '', unit: '' })}>+ Add Metric</Button>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle>Funding Rounds</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                {formData.funding_rounds?.map((item: any, index: number) => (
                                    <div key={item.id || item._uid || `funding-${index}`} className="border p-3 rounded-md space-y-2">
                                        <div className="flex justify-between"><Label>Round #{index + 1}</Label><Button variant="destructive" size="xs" onClick={() => removeArrayItem('funding_rounds', index)}>Remove</Button></div>
                                        <div className="grid grid-cols-2 gap-2"><Input placeholder="Round Name" value={item.round_name} onChange={(e) => handleArrayChange('funding_rounds', index, 'round_name', e.target.value)} /><Input type="date" value={item.date} onChange={(e) => handleArrayChange('funding_rounds', index, 'date', e.target.value)} /></div>
                                        <Input placeholder="Amount" value={item.amount} onChange={(e) => handleArrayChange('funding_rounds', index, 'amount', e.target.value)} />
                                        <Input placeholder="Valuation" value={item.valuation} onChange={(e) => handleArrayChange('funding_rounds', index, 'valuation', e.target.value)} />
                                    </div>
                                ))}
                                <Button variant="outline" size="sm" onClick={() => addArrayItem('funding_rounds', { round_name: '', date: '', amount: 0, valuation: 0 })}>+ Add Round</Button>
                            </CardContent>
                        </Card>
                    </TabsContent>
                    
                    {/* --- Risks (FSD-PROD-009) --- */}
                    <TabsContent value="risks" className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Risk Disclosures</CardTitle></CardHeader>
                            <CardContent className="space-y-4">
                                {formData.risk_disclosures?.map((item: any, index: number) => (
                                    <div key={item.id || item._uid || `risk-${index}`} className="border p-3 rounded-md space-y-2">
                                        <div className="flex justify-between"><Label>Risk #{index + 1}</Label><Button variant="destructive" size="xs" onClick={() => removeArrayItem('risk_disclosures', index)}>Remove</Button></div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <Input placeholder="Title" value={item.risk_title} onChange={(e) => handleArrayChange('risk_disclosures', index, 'risk_title', e.target.value)} />
                                            <Select value={item.severity} onValueChange={(v) => handleArrayChange('risk_disclosures', index, 'severity', v)}>
                                                <SelectTrigger><SelectValue placeholder="Select Severity" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Low">Low</SelectItem>
                                                    <SelectItem value="Medium">Medium</SelectItem>
                                                    <SelectItem value="High">High</SelectItem>
                                                    <SelectItem value="Critical">Critical</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <Select value={item.risk_category} onValueChange={(v) => handleArrayChange('risk_disclosures', index, 'risk_category', v)}>
                                            <SelectTrigger><SelectValue placeholder="Select Category" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Market">Market Risk</SelectItem>
                                                <SelectItem value="Business">Business Risk</SelectItem>
                                                <SelectItem value="Financial">Financial Risk</SelectItem>
                                                <SelectItem value="Regulatory">Regulatory Risk</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Textarea placeholder="Description" value={item.risk_description} onChange={(e) => handleArrayChange('risk_disclosures', index, 'risk_description', e.target.value)} />
                                    </div>
                                ))}
                                <Button variant="outline" size="sm" onClick={() => addArrayItem('risk_disclosures', { risk_title: '', severity: 'Medium', risk_category: 'Market', risk_description: '' })}>+ Add Risk</Button>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* --- Compliance (FSD-PROD-012) --- */}
                    <TabsContent value="compliance" className="space-y-4">
                        <Card>
                            <CardHeader><CardTitle>Regulatory Information</CardTitle></CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>SEBI Approval Number</Label>
                                        <Input value={formData.sebi_approval_number || ''} onChange={(e) => handleChange('sebi_approval_number', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>SEBI Approval Date</Label>
                                        <Input type="date" value={formData.sebi_approval_date || ''} onChange={(e) => handleChange('sebi_approval_date', e.target.value)} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Regulatory Warnings (Shown to user)</Label>
                                    <Textarea value={formData.regulatory_warnings || ''} onChange={(e) => handleChange('regulatory_warnings', e.target.value)} rows={4} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Internal Compliance Notes (Admin only)</Label>
                                    <Textarea value={formData.compliance_notes || ''} onChange={(e) => handleChange('compliance_notes', e.target.value)} rows={4} />
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                </ScrollArea>
            </Tabs>
            <div className="flex justify-end gap-2 border-t pt-4">
                <Button variant="ghost" onClick={onCancel}>Cancel</Button>
                <Button onClick={handleSave}><Save className="mr-2 h-4 w-4" /> Save Changes</Button>
            </div>
        </div>
    );
}

// --- MAIN PAGE ---
export default function ProductManagerPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<any>(null);
  const [isCreateMode, setIsCreateMode] = useState(false);

  // We fetch the simple list
  const { data: products, isLoading } = useQuery({
    queryKey: ['adminProducts'],
    queryFn: async () => (await api.get('/admin/products')).data,
  });

  // Update mutation
  const updateMutation = useMutation({
    mutationFn: (data: any) => api.put(`/admin/products/${editingProduct.id}`, data),
    onSuccess: () => {
      toast.success("Product Updated");
      queryClient.invalidateQueries({ queryKey: ['adminProducts'] });
      setIsDialogOpen(false);
      setEditingProduct(null);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Create mutation
  const createMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/products', data),
    onSuccess: () => {
      toast.success("Product Created");
      queryClient.invalidateQueries({ queryKey: ['adminProducts'] });
      setIsDialogOpen(false);
      setEditingProduct(null);
      setIsCreateMode(false);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const handleSave = (data: any) => {
    if (isCreateMode) {
      createMutation.mutate(data);
    } else {
      updateMutation.mutate(data);
    }
  };

  const handleOpenDialog = (product: any) => {
    // Fetch the *full* product data on click, as the list is partial
    api.get(`/admin/products/${product.id}`).then(res => {
        setEditingProduct(res.data); // This now contains all relations
        setIsCreateMode(false);
        setIsDialogOpen(true);
    });
  };

  const handleCreateNew = () => {
    // Initialize with empty product template
    setEditingProduct({
      name: '',
      slug: '',
      sector: '',
      description: '',
      is_active: true,
      is_featured: false,
      face_value_per_unit: 0,
      current_market_price: 0,
      min_investment: 0,
      auto_update_price: false,
      price_api_endpoint: '',
      highlights: [],
      founders: [],
      key_metrics: [],
      funding_rounds: [],
      risk_disclosures: [],
      sebi_approval_number: '',
      sebi_approval_date: '',
      regulatory_warnings: '',
      compliance_notes: '',
    });
    setIsCreateMode(true);
    setIsDialogOpen(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Product Management</h1>
        <Button onClick={handleCreateNew}>
          <Plus className="mr-2 h-4 w-4" /> Create Product
        </Button>
      </div>

      <Card>
        <CardContent className="pt-6">
          {isLoading ? <p>Loading...</p> : (
            <Table>
              <TableHeader><TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Face Value</TableHead>
                <TableHead>Market Price</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow></TableHeader>
              <TableBody>
                {products?.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                      No products found. Click "Create Product" to add your first product.
                    </TableCell>
                  </TableRow>
                )}
                {products?.map((p: any) => (
                  <TableRow key={p.id}>
                    <TableCell className="font-medium">{p.name}</TableCell>
                    <TableCell>₹{p.face_value_per_unit}</TableCell>
                    <TableCell className="font-bold">₹{p.current_market_price || p.face_value_per_unit}</TableCell>
                    <TableCell><span className="capitalize bg-muted px-2 py-1 rounded text-xs">{p.status}</span></TableCell>
                    <TableCell>
                      <Button variant="outline" size="sm" onClick={() => handleOpenDialog(p)}>
                        <Edit className="h-4 w-4" /> Manage
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Create/Edit Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) { setEditingProduct(null); setIsCreateMode(false); } }}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>{isCreateMode ? 'Create New Product' : `Manage Product: ${editingProduct?.name}`}</DialogTitle>
          </DialogHeader>
          {editingProduct && (
            <EditProductForm
              product={editingProduct}
              onSave={handleSave}
              onCancel={() => setIsDialogOpen(false)}
            />
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}