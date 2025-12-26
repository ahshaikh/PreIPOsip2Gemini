'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { ArrowLeft, CheckCircle, PlayCircle, PauseCircle, Users, IndianRupee, TrendingUp, Calendar, Percent, Star } from "lucide-react";
import Link from "next/link";
import { useParams } from "next/navigation";

// Helper to format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount);
};

// Helper to format date
const formatDate = (date: string | null) => {
    if (!date) return 'No limit';
    return new Date(date).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

export default function CampaignDetailPage() {
    const params = useParams();
    const campaignId = params.id;
    const queryClient = useQueryClient();

    // Fetch campaign details
    const { data: campaignData, isLoading } = useQuery({
        queryKey: ['admin-campaign', campaignId],
        queryFn: async () => {
            const response = await api.get(`/admin/campaigns/${campaignId}`);
            return response.data;
        },
        enabled: !!campaignId
    });

    // Fetch analytics
    const { data: analytics } = useQuery({
        queryKey: ['admin-campaign-analytics', campaignId],
        queryFn: async () => {
            const response = await api.get(`/admin/campaigns/${campaignId}/analytics`);
            return response.data;
        },
        enabled: !!campaignId
    });

    // Fetch usages
    const { data: usagesData } = useQuery({
        queryKey: ['admin-campaign-usages', campaignId],
        queryFn: async () => {
            const response = await api.get(`/admin/campaigns/${campaignId}/usages`);
            return response.data;
        },
        enabled: !!campaignId
    });

    const campaign = campaignData?.campaign;
    const stats = campaignData?.stats || analytics?.stats;
    const usageByDay = analytics?.usage_by_day || [];
    const topUsers = analytics?.top_users || [];
    const usages = usagesData?.data || [];

    // Approve mutation
    const approveMutation = useMutation({
        mutationFn: async () => {
            return api.post(`/admin/campaigns/${campaignId}/approve`);
        },
        onSuccess: () => {
            toast.success('Campaign approved successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaign', campaignId] });
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to approve campaign');
        }
    });

    // Activate mutation
    const activateMutation = useMutation({
        mutationFn: async () => {
            return api.post(`/admin/campaigns/${campaignId}/activate`);
        },
        onSuccess: () => {
            toast.success('Campaign activated successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaign', campaignId] });
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to activate campaign');
        }
    });

    // Pause mutation
    const pauseMutation = useMutation({
        mutationFn: async () => {
            return api.post(`/admin/campaigns/${campaignId}/pause`);
        },
        onSuccess: () => {
            toast.success('Campaign paused successfully');
            queryClient.invalidateQueries({ queryKey: ['admin-campaign', campaignId] });
        },
        onError: (error: any) => {
            toast.error(error.response?.data?.message || 'Failed to pause campaign');
        }
    });

    const getStateBadge = (state: string) => {
        const variants: any = {
            draft: 'secondary',
            scheduled: 'outline',
            live: 'default',
            paused: 'destructive',
            expired: 'secondary'
        };

        return <Badge variant={variants[state] || 'secondary'}>{state?.toUpperCase()}</Badge>;
    };

    if (isLoading) {
        return <div className="flex items-center justify-center h-screen">Loading campaign...</div>;
    }

    if (!campaign) {
        return <div className="flex items-center justify-center h-screen">Campaign not found</div>;
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Link href="/admin/campaigns">
                        <Button variant="outline" size="icon">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-3xl font-bold">{campaign.title}</h1>
                            {campaign.is_featured && <Star className="h-5 w-5 fill-yellow-400 text-yellow-400" />}
                        </div>
                        <p className="text-muted-foreground">
                            Code: <span className="font-mono font-semibold">{campaign.code}</span>
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    {campaign.can_be_approved && (
                        <Button onClick={() => approveMutation.mutate()}>
                            <CheckCircle className="mr-2 h-4 w-4" />
                            Approve
                        </Button>
                    )}
                    {campaign.can_be_activated && (
                        <Button onClick={() => activateMutation.mutate()}>
                            <PlayCircle className="mr-2 h-4 w-4" />
                            Activate
                        </Button>
                    )}
                    {campaign.can_be_paused && (
                        <Button variant="destructive" onClick={() => pauseMutation.mutate()}>
                            <PauseCircle className="mr-2 h-4 w-4" />
                            Pause
                        </Button>
                    )}
                </div>
            </div>

            {/* State Badge */}
            <div className="flex items-center gap-2">
                <span className="text-sm text-muted-foreground">Status:</span>
                {getStateBadge(campaign.state)}
            </div>

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Usage</CardTitle>
                        <Users className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {stats?.total_usage_count || 0}
                            {campaign.usage_limit && ` / ${campaign.usage_limit}`}
                        </div>
                        {stats?.usage_percentage !== undefined && (
                            <p className="text-xs text-muted-foreground">
                                {stats.usage_percentage}% utilized
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Unique Users</CardTitle>
                        <Users className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats?.unique_users_count || 0}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Total Discount Given</CardTitle>
                        <IndianRupee className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {formatCurrency(stats?.total_discount_given || 0)}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Average Discount</CardTitle>
                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">
                            {formatCurrency(stats?.average_discount || 0)}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Campaign Details */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Campaign Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h4 className="font-semibold mb-1">Description</h4>
                            <p className="text-sm text-muted-foreground">{campaign.description}</p>
                        </div>

                        {campaign.long_description && (
                            <div>
                                <h4 className="font-semibold mb-1">Long Description</h4>
                                <p className="text-sm text-muted-foreground">{campaign.long_description}</p>
                            </div>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <h4 className="font-semibold mb-1">Discount Type</h4>
                                <p className="text-sm">
                                    {campaign.discount_type === 'percentage' ? (
                                        <span className="flex items-center">
                                            <Percent className="h-3 w-3 mr-1" />
                                            {campaign.discount_percent}%
                                        </span>
                                    ) : (
                                        <span className="flex items-center">
                                            <IndianRupee className="h-3 w-3 mr-1" />
                                            {formatCurrency(campaign.discount_amount)}
                                        </span>
                                    )}
                                </p>
                            </div>

                            {campaign.min_investment && (
                                <div>
                                    <h4 className="font-semibold mb-1">Min Investment</h4>
                                    <p className="text-sm">{formatCurrency(campaign.min_investment)}</p>
                                </div>
                            )}

                            {campaign.max_discount && (
                                <div>
                                    <h4 className="font-semibold mb-1">Max Discount</h4>
                                    <p className="text-sm">{formatCurrency(campaign.max_discount)}</p>
                                </div>
                            )}

                            <div>
                                <h4 className="font-semibold mb-1">Start Date</h4>
                                <p className="text-sm flex items-center">
                                    <Calendar className="h-3 w-3 mr-1" />
                                    {formatDate(campaign.start_at)}
                                </p>
                            </div>

                            <div>
                                <h4 className="font-semibold mb-1">End Date</h4>
                                <p className="text-sm flex items-center">
                                    <Calendar className="h-3 w-3 mr-1" />
                                    {formatDate(campaign.end_at)}
                                </p>
                            </div>
                        </div>

                        {campaign.features && campaign.features.length > 0 && (
                            <div>
                                <h4 className="font-semibold mb-2">Features</h4>
                                <ul className="list-disc list-inside space-y-1 text-sm">
                                    {campaign.features.map((feature: string, index: number) => (
                                        <li key={index}>{feature}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {campaign.terms && campaign.terms.length > 0 && (
                            <div>
                                <h4 className="font-semibold mb-2">Terms & Conditions</h4>
                                <ul className="list-disc list-inside space-y-1 text-sm text-muted-foreground">
                                    {campaign.terms.map((term: string, index: number) => (
                                        <li key={index}>{term}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Workflow & Approval</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <h4 className="font-semibold mb-1">Created By</h4>
                            <p className="text-sm text-muted-foreground">
                                {campaign.creator?.username || campaign.creator?.email || 'N/A'}
                            </p>
                        </div>

                        {campaign.approver && (
                            <div>
                                <h4 className="font-semibold mb-1">Approved By</h4>
                                <p className="text-sm text-muted-foreground">
                                    {campaign.approver.username || campaign.approver.email}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {formatDate(campaign.approved_at)}
                                </p>
                            </div>
                        )}

                        <div>
                            <h4 className="font-semibold mb-1">Created At</h4>
                            <p className="text-sm text-muted-foreground">
                                {formatDate(campaign.created_at)}
                            </p>
                        </div>

                        <div>
                            <h4 className="font-semibold mb-1">Last Updated</h4>
                            <p className="text-sm text-muted-foreground">
                                {formatDate(campaign.updated_at)}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Top Users */}
            {topUsers && topUsers.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle>Top Users</CardTitle>
                        <CardDescription>Users with highest campaign usage</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Usage Count</TableHead>
                                    <TableHead>Total Discount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {topUsers.map((user: any, index: number) => (
                                    <TableRow key={index}>
                                        <TableCell>{user.username}</TableCell>
                                        <TableCell>{user.email}</TableCell>
                                        <TableCell>{user.usage_count}</TableCell>
                                        <TableCell>{formatCurrency(user.total_discount)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}

            {/* Recent Usages */}
            {usages && usages.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Applications</CardTitle>
                        <CardDescription>Latest campaign usages</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Applied To</TableHead>
                                    <TableHead>Original Amount</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead>Final Amount</TableHead>
                                    <TableHead>Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {usages.slice(0, 10).map((usage: any) => (
                                    <TableRow key={usage.id}>
                                        <TableCell>{usage.user?.email || 'N/A'}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {usage.applicable_type?.split('\\').pop()}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{formatCurrency(usage.original_amount)}</TableCell>
                                        <TableCell className="text-green-600">
                                            -{formatCurrency(usage.discount_applied)}
                                        </TableCell>
                                        <TableCell className="font-semibold">
                                            {formatCurrency(usage.final_amount)}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(usage.used_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
