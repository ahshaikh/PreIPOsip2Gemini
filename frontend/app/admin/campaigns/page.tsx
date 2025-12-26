'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Plus, Edit, CheckCircle, PlayCircle, PauseCircle, BarChart3, Calendar, Percent, IndianRupee, Users, Clock, Star, Tag } from "lucide-react";
import Link from "next/link";

// Helper to format date for input
const formatDateForInput = (date: string | null) => {
    if (!date) return '';
    return new Date(date).toISOString().split('T')[0];
};

// Helper to format date for display
const formatDateForDisplay = (date: string | null) => {
    if (!date) return 'No limit';
    return new Date(date).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

export default function CampaignManagerPage() {
    const queryClient = useQueryClient();
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingCampaign, setEditingCampaign] = useState<any>(null);
    const [activeTab, setActiveTab] = useState('all');

    // Form State
    const [title, setTitle] = useState('');
    const [subtitle, setSubtitle] = useState('');
    const [code, setCode] = useState('');
    const [description, setDescription] = useState('');
    const [longDescription, setLongDescription] = useState('');
    const [discountType, setDiscountType] = useState<'percentage' | 'fixed_amount'>('fixed_amount');
    const [discountPercent, setDiscountPercent] = useState('');
    const [discountAmount, setDiscountAmount] = useState('');
    const [minInvestment, setMinInvestment] = useState('');
    const [maxDiscount, setMaxDiscount] = useState('');
    const [usageLimit, setUsageLimit] = useState('');
    const [userUsageLimit, setUserUsageLimit] = useState('1');
    const [startAt, setStartAt] = useState('');
    const [endAt, setEndAt] = useState('');
    const [imageUrl, setImageUrl] = useState('');
    const [heroImage, setHeroImage] = useState('');
    const [videoUrl, setVideoUrl] = useState('');
    const [features, setFeatures] = useState<string[]>([]);
    const [newFeature, setNewFeature] = useState('');
    const [terms, setTerms] = useState<string[]>([]);
    const [newTerm, setNewTerm] = useState('');
    const [isFeatured, setIsFeatured] = useState(false);

    // Fetch campaigns
    const { data: campaigns = [], isLoading } = useQuery({
        queryKey: ['admin-campaigns', activeTab],
        queryFn: async () => {
            const params = activeTab !== 'all' ? { state: activeTab } : {};
            const response = await api.get('/admin/campaigns', { params });
            return response.data.data || [];
        }
    });

    // Create/Update mutation
    const campaignMutation = useMutation({
        mutationFn: async (data: any) => {
            if (editingCampaign) {
                return api.put(`/admin/campaigns/${editingCampaign.id}`, data);
            }
            return api.post('/admin/campaigns', data);
        },
        onSuccess: () => {
            toast.success(editingCampaign ? 'Campaign updated successfully' : 'Campaign created successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaigns'] });
            closeDialog();
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to save campaign');
        }
    });

    // Approve mutation
    const approveMutation = useMutation({
        mutationFn: async (id: number) => {
            return api.post(`/admin/campaigns/${id}/approve`);
        },
        onSuccess: () => {
            toast.success('Campaign approved successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaigns'] });
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to approve campaign');
        }
    });

    // Activate mutation
    const activateMutation = useMutation({
        mutationFn: async (id: number) => {
            return api.post(`/admin/campaigns/${id}/activate`);
        },
        onSuccess: () => {
            toast.success('Campaign activated successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaigns'] });
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to activate campaign');
        }
    });

    // Pause mutation
    const pauseMutation = useMutation({
        mutationFn: async (id: number) => {
            return api.post(`/admin/campaigns/${id}/pause`);
        },
        onSuccess: () => {
            toast.success('Campaign paused successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaigns'] });
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to pause campaign');
        }
    });

    const openDialog = (campaign?: any) => {
        if (campaign) {
            setEditingCampaign(campaign);
            setTitle(campaign.title || '');
            setSubtitle(campaign.subtitle || '');
            setCode(campaign.code || '');
            setDescription(campaign.description || '');
            setLongDescription(campaign.long_description || '');
            setDiscountType(campaign.discount_type || 'fixed_amount');
            setDiscountPercent(campaign.discount_percent?.toString() || '');
            setDiscountAmount(campaign.discount_amount?.toString() || '');
            setMinInvestment(campaign.min_investment?.toString() || '');
            setMaxDiscount(campaign.max_discount?.toString() || '');
            setUsageLimit(campaign.usage_limit?.toString() || '');
            setUserUsageLimit(campaign.user_usage_limit?.toString() || '1');
            setStartAt(formatDateForInput(campaign.start_at));
            setEndAt(formatDateForInput(campaign.end_at));
            setImageUrl(campaign.image_url || '');
            setHeroImage(campaign.hero_image || '');
            setVideoUrl(campaign.video_url || '');
            setFeatures(campaign.features || []);
            setTerms(campaign.terms || []);
            setIsFeatured(campaign.is_featured || false);
        } else {
            setEditingCampaign(null);
            resetForm();
        }
        setIsDialogOpen(true);
    };

    const closeDialog = () => {
        setIsDialogOpen(false);
        setEditingCampaign(null);
        resetForm();
    };

    const resetForm = () => {
        setTitle('');
        setSubtitle('');
        setCode('');
        setDescription('');
        setLongDescription('');
        setDiscountType('fixed_amount');
        setDiscountPercent('');
        setDiscountAmount('');
        setMinInvestment('');
        setMaxDiscount('');
        setUsageLimit('');
        setUserUsageLimit('1');
        setStartAt('');
        setEndAt('');
        setImageUrl('');
        setHeroImage('');
        setVideoUrl('');
        setFeatures([]);
        setNewFeature('');
        setTerms([]);
        setNewTerm('');
        setIsFeatured(false);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const data = {
            title,
            subtitle,
            code: code.toUpperCase(),
            description,
            long_description: longDescription,
            discount_type: discountType,
            discount_percent: discountType === 'percentage' ? parseFloat(discountPercent) : null,
            discount_amount: discountType === 'fixed_amount' ? parseFloat(discountAmount) : null,
            min_investment: minInvestment ? parseFloat(minInvestment) : null,
            max_discount: maxDiscount ? parseFloat(maxDiscount) : null,
            usage_limit: usageLimit ? parseInt(usageLimit) : null,
            user_usage_limit: userUsageLimit ? parseInt(userUsageLimit) : null,
            start_at: startAt || null,
            end_at: endAt || null,
            image_url: imageUrl || null,
            hero_image: heroImage || null,
            video_url: videoUrl || null,
            features,
            terms,
            is_featured: isFeatured,
        };

        campaignMutation.mutate(data);
    };

    const addFeature = () => {
        if (newFeature.trim()) {
            setFeatures([...features, newFeature.trim()]);
            setNewFeature('');
        }
    };

    const removeFeature = (index: number) => {
        setFeatures(features.filter((_, i) => i !== index));
    };

    const addTerm = () => {
        if (newTerm.trim()) {
            setTerms([...terms, newTerm.trim()]);
            setNewTerm('');
        }
    };

    const removeTerm = (index: number) => {
        setTerms(terms.filter((_, i) => i !== index));
    };

    const getStateBadge = (campaign: any) => {
        const state = campaign.state;
        const variants: any = {
            draft: 'secondary',
            scheduled: 'outline',
            live: 'default',
            paused: 'destructive',
            expired: 'secondary'
        };

        return <Badge variant={variants[state] || 'secondary'}>{state?.toUpperCase()}</Badge>;
    };

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-3xl font-bold">Campaign Management</h1>
                    <p className="text-muted-foreground">Create and manage promotional campaigns</p>
                </div>
                <Button onClick={() => openDialog()}>
                    <Plus className="mr-2 h-4 w-4" />
                    Create Campaign
                </Button>
            </div>

            <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList>
                    <TabsTrigger value="all">All Campaigns</TabsTrigger>
                    <TabsTrigger value="draft">Drafts</TabsTrigger>
                    <TabsTrigger value="scheduled">Scheduled</TabsTrigger>
                    <TabsTrigger value="live">Live</TabsTrigger>
                    <TabsTrigger value="paused">Paused</TabsTrigger>
                    <TabsTrigger value="expired">Expired</TabsTrigger>
                </TabsList>

                <TabsContent value={activeTab}>
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {activeTab === 'all' ? 'All Campaigns' : `${activeTab.charAt(0).toUpperCase() + activeTab.slice(1)} Campaigns`}
                            </CardTitle>
                            <CardDescription>
                                {campaigns.length} campaign{campaigns.length !== 1 ? 's' : ''}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {isLoading ? (
                                <div className="text-center py-8">Loading campaigns...</div>
                            ) : campaigns.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    No campaigns found. Create one to get started!
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Code</TableHead>
                                            <TableHead>Title</TableHead>
                                            <TableHead>Discount</TableHead>
                                            <TableHead>Usage</TableHead>
                                            <TableHead>Validity</TableHead>
                                            <TableHead>State</TableHead>
                                            <TableHead>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {campaigns.map((campaign: any) => (
                                            <TableRow key={campaign.id}>
                                                <TableCell className="font-mono font-semibold">
                                                    {campaign.code}
                                                    {campaign.is_featured && (
                                                        <Star className="inline ml-1 h-3 w-3 fill-yellow-400 text-yellow-400" />
                                                    )}
                                                </TableCell>
                                                <TableCell>{campaign.title}</TableCell>
                                                <TableCell>
                                                    {campaign.discount_type === 'percentage' ? (
                                                        <span className="flex items-center">
                                                            <Percent className="h-3 w-3 mr-1" />
                                                            {campaign.discount_percent}%
                                                        </span>
                                                    ) : (
                                                        <span className="flex items-center">
                                                            <IndianRupee className="h-3 w-3 mr-1" />
                                                            {campaign.discount_amount}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-1">
                                                        <Users className="h-3 w-3" />
                                                        {campaign.usage_count || 0}
                                                        {campaign.usage_limit && ` / ${campaign.usage_limit}`}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    <div className="flex items-center gap-1">
                                                        <Calendar className="h-3 w-3" />
                                                        {formatDateForDisplay(campaign.end_at)}
                                                    </div>
                                                </TableCell>
                                                <TableCell>{getStateBadge(campaign)}</TableCell>
                                                <TableCell>
                                                    <div className="flex gap-2">
                                                        {campaign.can_be_edited && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => openDialog(campaign)}
                                                            >
                                                                <Edit className="h-3 w-3" />
                                                            </Button>
                                                        )}
                                                        {campaign.can_be_approved && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => approveMutation.mutate(campaign.id)}
                                                            >
                                                                <CheckCircle className="h-3 w-3" />
                                                            </Button>
                                                        )}
                                                        {campaign.can_be_activated && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => activateMutation.mutate(campaign.id)}
                                                            >
                                                                <PlayCircle className="h-3 w-3" />
                                                            </Button>
                                                        )}
                                                        {campaign.can_be_paused && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => pauseMutation.mutate(campaign.id)}
                                                            >
                                                                <PauseCircle className="h-3 w-3" />
                                                            </Button>
                                                        )}
                                                        <Link href={`/admin/campaigns/${campaign.id}`}>
                                                            <Button size="sm" variant="outline">
                                                                <BarChart3 className="h-3 w-3" />
                                                            </Button>
                                                        </Link>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Create/Edit Dialog */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{editingCampaign ? 'Edit Campaign' : 'Create Campaign'}</DialogTitle>
                        <DialogDescription>
                            {editingCampaign ? 'Update campaign details' : 'Create a new promotional campaign'}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="title">Title *</Label>
                                <Input
                                    id="title"
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    required
                                />
                            </div>
                            <div>
                                <Label htmlFor="subtitle">Subtitle</Label>
                                <Input
                                    id="subtitle"
                                    value={subtitle}
                                    onChange={(e) => setSubtitle(e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="code">Campaign Code *</Label>
                            <Input
                                id="code"
                                value={code}
                                onChange={(e) => setCode(e.target.value.toUpperCase())}
                                placeholder="E.g., WELCOME500"
                                required
                                className="font-mono"
                            />
                        </div>

                        <div>
                            <Label htmlFor="description">Description *</Label>
                            <Textarea
                                id="description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                rows={3}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="longDescription">Long Description</Label>
                            <Textarea
                                id="longDescription"
                                value={longDescription}
                                onChange={(e) => setLongDescription(e.target.value)}
                                rows={4}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="discountType">Discount Type *</Label>
                                <Select value={discountType} onValueChange={(value: any) => setDiscountType(value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fixed_amount">Fixed Amount</SelectItem>
                                        <SelectItem value="percentage">Percentage</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            {discountType === 'percentage' ? (
                                <div>
                                    <Label htmlFor="discountPercent">Discount Percentage *</Label>
                                    <Input
                                        id="discountPercent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={discountPercent}
                                        onChange={(e) => setDiscountPercent(e.target.value)}
                                        required
                                    />
                                </div>
                            ) : (
                                <div>
                                    <Label htmlFor="discountAmount">Discount Amount (₹) *</Label>
                                    <Input
                                        id="discountAmount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={discountAmount}
                                        onChange={(e) => setDiscountAmount(e.target.value)}
                                        required
                                    />
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="minInvestment">Minimum Investment (₹)</Label>
                                <Input
                                    id="minInvestment"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={minInvestment}
                                    onChange={(e) => setMinInvestment(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="maxDiscount">Maximum Discount Cap (₹)</Label>
                                <Input
                                    id="maxDiscount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={maxDiscount}
                                    onChange={(e) => setMaxDiscount(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="usageLimit">Total Usage Limit</Label>
                                <Input
                                    id="usageLimit"
                                    type="number"
                                    min="1"
                                    value={usageLimit}
                                    onChange={(e) => setUsageLimit(e.target.value)}
                                    placeholder="Unlimited"
                                />
                            </div>
                            <div>
                                <Label htmlFor="userUsageLimit">Per-User Usage Limit</Label>
                                <Input
                                    id="userUsageLimit"
                                    type="number"
                                    min="1"
                                    value={userUsageLimit}
                                    onChange={(e) => setUserUsageLimit(e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="startAt">Start Date</Label>
                                <Input
                                    id="startAt"
                                    type="date"
                                    value={startAt}
                                    onChange={(e) => setStartAt(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="endAt">End Date</Label>
                                <Input
                                    id="endAt"
                                    type="date"
                                    value={endAt}
                                    onChange={(e) => setEndAt(e.target.value)}
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="imageUrl">Image URL</Label>
                            <Input
                                id="imageUrl"
                                type="url"
                                value={imageUrl}
                                onChange={(e) => setImageUrl(e.target.value)}
                                placeholder="https://..."
                            />
                        </div>

                        <div>
                            <Label htmlFor="heroImage">Hero Image URL</Label>
                            <Input
                                id="heroImage"
                                type="url"
                                value={heroImage}
                                onChange={(e) => setHeroImage(e.target.value)}
                                placeholder="https://..."
                            />
                        </div>

                        <div>
                            <Label htmlFor="videoUrl">Video URL</Label>
                            <Input
                                id="videoUrl"
                                type="url"
                                value={videoUrl}
                                onChange={(e) => setVideoUrl(e.target.value)}
                                placeholder="https://..."
                            />
                        </div>

                        <div>
                            <Label>Features</Label>
                            <div className="flex gap-2 mb-2">
                                <Input
                                    value={newFeature}
                                    onChange={(e) => setNewFeature(e.target.value)}
                                    placeholder="Add a feature..."
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addFeature())}
                                />
                                <Button type="button" onClick={addFeature}>Add</Button>
                            </div>
                            {features.length > 0 && (
                                <ul className="space-y-1">
                                    {features.map((feature, index) => (
                                        <li key={index} className="flex items-center justify-between bg-secondary p-2 rounded">
                                            <span>{feature}</span>
                                            <Button type="button" size="sm" variant="ghost" onClick={() => removeFeature(index)}>
                                                Remove
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        <div>
                            <Label>Terms & Conditions</Label>
                            <div className="flex gap-2 mb-2">
                                <Input
                                    value={newTerm}
                                    onChange={(e) => setNewTerm(e.target.value)}
                                    placeholder="Add a term..."
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addTerm())}
                                />
                                <Button type="button" onClick={addTerm}>Add</Button>
                            </div>
                            {terms.length > 0 && (
                                <ul className="space-y-1">
                                    {terms.map((term, index) => (
                                        <li key={index} className="flex items-center justify-between bg-secondary p-2 rounded">
                                            <span>{term}</span>
                                            <Button type="button" size="sm" variant="ghost" onClick={() => removeTerm(index)}>
                                                Remove
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>

                        <div className="flex items-center space-x-2">
                            <Switch
                                id="isFeatured"
                                checked={isFeatured}
                                onCheckedChange={setIsFeatured}
                            />
                            <Label htmlFor="isFeatured">Featured Campaign</Label>
                        </div>

                        <div className="flex justify-end gap-2 pt-4">
                            <Button type="button" variant="outline" onClick={closeDialog}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={campaignMutation.isPending}>
                                {campaignMutation.isPending ? 'Saving...' : editingCampaign ? 'Update Campaign' : 'Create Campaign'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
