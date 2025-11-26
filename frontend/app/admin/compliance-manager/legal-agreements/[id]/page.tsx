'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useParams, useRouter } from "next/navigation";
import {
  ArrowLeft, Edit, History, Shield, FileText, Clock, User,
  Calendar, CheckCircle, XCircle, Download, Eye, AlertTriangle,
  Archive, Check, Share2
} from "lucide-react";

const AGREEMENT_TYPES = [
  { value: 'terms_of_service', label: 'Terms of Service' },
  { value: 'privacy_policy', label: 'Privacy Policy' },
  { value: 'user_agreement', label: 'User Agreement' },
  { value: 'investment_agreement', label: 'Investment Agreement' },
  { value: 'subscription_agreement', label: 'Subscription Agreement' },
  { value: 'partnership_agreement', label: 'Partnership Agreement' },
  { value: 'non_disclosure', label: 'Non-Disclosure Agreement (NDA)' },
  { value: 'service_level', label: 'Service Level Agreement (SLA)' },
  { value: 'data_processing', label: 'Data Processing Agreement (DPA)' },
  { value: 'master_service', label: 'Master Service Agreement (MSA)' },
  { value: 'license_agreement', label: 'License Agreement' },
  { value: 'other', label: 'Other' },
];

const STATUS_TYPES = [
  { value: 'draft', label: 'Draft', color: 'bg-gray-500' },
  { value: 'review', label: 'Under Review', color: 'bg-yellow-500' },
  { value: 'active', label: 'Active', color: 'bg-green-500' },
  { value: 'archived', label: 'Archived', color: 'bg-blue-500' },
  { value: 'superseded', label: 'Superseded', color: 'bg-purple-500' },
];

export default function LegalAgreementDetailPage() {
  const params = useParams();
  const router = useRouter();
  const queryClient = useQueryClient();
  const agreementId = params.id as string;

  // Fetch agreement details
  const { data: agreement, isLoading } = useQuery({
    queryKey: ['legalAgreement', agreementId],
    queryFn: async () => (await api.get(`/admin/compliance/legal-agreements/${agreementId}`)).data,
  });

  // Fetch version history
  const { data: versions } = useQuery({
    queryKey: ['legalAgreementVersions', agreementId],
    queryFn: async () => (await api.get(`/admin/compliance/legal-agreements/${agreementId}/versions`)).data,
  });

  // Fetch acceptance stats
  const { data: acceptanceStats } = useQuery({
    queryKey: ['legalAgreementAcceptance', agreementId],
    queryFn: async () => (await api.get(`/admin/compliance/legal-agreements/${agreementId}/acceptance-stats`)).data,
  });

  // Fetch related documents
  const { data: relatedDocs } = useQuery({
    queryKey: ['relatedDocuments', agreementId],
    queryFn: async () => (await api.get(`/admin/compliance/legal-agreements/${agreementId}/related`)).data,
  });

  // Publish mutation
  const publishMutation = useMutation({
    mutationFn: () => api.post(`/admin/compliance/legal-agreements/${agreementId}/publish`),
    onSuccess: () => {
      toast.success("Agreement Published");
      queryClient.invalidateQueries({ queryKey: ['legalAgreement', agreementId] });
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Archive mutation
  const archiveMutation = useMutation({
    mutationFn: () => api.post(`/admin/compliance/legal-agreements/${agreementId}/archive`),
    onSuccess: () => {
      toast.success("Agreement Archived");
      queryClient.invalidateQueries({ queryKey: ['legalAgreement', agreementId] });
    }
  });

  const getStatusBadge = (statusValue: string) => {
    const status = STATUS_TYPES.find(s => s.value === statusValue);
    return (
      <Badge variant="outline" className={`${status?.color} text-white border-0`}>
        {status?.label || statusValue}
      </Badge>
    );
  };

  const getAgreementTypeLabel = (type: string) => {
    return AGREEMENT_TYPES.find(t => t.value === type)?.label || type;
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
          <p className="mt-4 text-muted-foreground">Loading agreement details...</p>
        </div>
      </div>
    );
  }

  if (!agreement) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <AlertTriangle className="h-12 w-12 text-destructive mx-auto mb-4" />
          <h2 className="text-xl font-semibold">Agreement Not Found</h2>
          <p className="text-muted-foreground mt-2">
            The legal agreement you're looking for doesn't exist.
          </p>
          <Button onClick={() => router.back()} className="mt-4">
            <ArrowLeft className="mr-2 h-4 w-4" /> Go Back
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="flex items-start gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.back()}
          >
            <ArrowLeft className="mr-2 h-4 w-4" /> Back
          </Button>
          <div>
            <div className="flex items-center gap-3 mb-2">
              <h1 className="text-3xl font-bold">{agreement.title}</h1>
              {getStatusBadge(agreement.status)}
              {agreement.is_template && (
                <Badge variant="secondary">Template</Badge>
              )}
            </div>
            <p className="text-muted-foreground">
              {agreement.description || 'No description provided'}
            </p>
          </div>
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => router.push(`/admin/compliance-manager/legal-agreements/${agreementId}/audit-trail`)}
          >
            <History className="mr-2 h-4 w-4" /> Audit Trail
          </Button>
          {agreement.status === 'draft' && (
            <Button onClick={() => publishMutation.mutate()}>
              <Check className="mr-2 h-4 w-4" /> Publish
            </Button>
          )}
          {agreement.status === 'active' && (
            <Button variant="outline" onClick={() => archiveMutation.mutate()}>
              <Archive className="mr-2 h-4 w-4" /> Archive
            </Button>
          )}
          <Button
            onClick={() => router.push(`/admin/compliance-manager/legal-agreements?edit=${agreementId}`)}
          >
            <Edit className="mr-2 h-4 w-4" /> Edit
          </Button>
        </div>
      </div>

      {/* Overview Cards */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Agreement Type</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-lg font-semibold">
              {getAgreementTypeLabel(agreement.type)}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Current Version</CardTitle>
            <History className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-lg font-semibold font-mono">v{agreement.version}</div>
            <p className="text-xs text-muted-foreground">
              {versions?.length || 0} total versions
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Acceptance Rate</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-lg font-semibold">
              {acceptanceStats?.acceptance_rate || 0}%
            </div>
            <p className="text-xs text-muted-foreground">
              {acceptanceStats?.accepted_count || 0} / {acceptanceStats?.total_users || 0} users
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Last Modified</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-sm font-semibold">
              {new Date(agreement.updated_at).toLocaleDateString()}
            </div>
            <p className="text-xs text-muted-foreground">
              {new Date(agreement.updated_at).toLocaleTimeString()}
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Main Content Tabs */}
      <Tabs defaultValue="content" className="space-y-4">
        <TabsList>
          <TabsTrigger value="content">
            <FileText className="mr-2 h-4 w-4" /> Content
          </TabsTrigger>
          <TabsTrigger value="metadata">
            <Shield className="mr-2 h-4 w-4" /> Metadata
          </TabsTrigger>
          <TabsTrigger value="versions">
            <History className="mr-2 h-4 w-4" /> Versions ({versions?.length || 0})
          </TabsTrigger>
          <TabsTrigger value="acceptance">
            <CheckCircle className="mr-2 h-4 w-4" /> Acceptance
          </TabsTrigger>
        </TabsList>

        {/* Content Tab */}
        <TabsContent value="content">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Agreement Content</CardTitle>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm">
                    <Download className="mr-2 h-4 w-4" /> Download PDF
                  </Button>
                  <Button variant="outline" size="sm">
                    <Share2 className="mr-2 h-4 w-4" /> Share
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="prose prose-sm max-w-none dark:prose-invert">
                <div className="p-6 bg-muted/30 rounded-lg border">
                  <pre className="whitespace-pre-wrap font-sans text-sm leading-relaxed">
                    {agreement.content}
                  </pre>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Metadata Tab */}
        <TabsContent value="metadata">
          <Card>
            <CardHeader>
              <CardTitle>Agreement Metadata</CardTitle>
              <CardDescription>Detailed information about this legal agreement</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid md:grid-cols-2 gap-6">
                <div className="space-y-4">
                  <div>
                    <Label className="text-xs text-muted-foreground">Agreement ID</Label>
                    <p className="font-mono text-sm">{agreement.id}</p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Type</Label>
                    <p className="font-medium">{getAgreementTypeLabel(agreement.type)}</p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Version</Label>
                    <p className="font-mono font-medium">v{agreement.version}</p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Status</Label>
                    <div className="mt-1">{getStatusBadge(agreement.status)}</div>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Require Signature</Label>
                    <div className="flex items-center gap-2 mt-1">
                      {agreement.require_signature ? (
                        <>
                          <CheckCircle className="h-4 w-4 text-green-500" />
                          <span className="text-sm">Yes</span>
                        </>
                      ) : (
                        <>
                          <XCircle className="h-4 w-4 text-red-500" />
                          <span className="text-sm">No</span>
                        </>
                      )}
                    </div>
                  </div>
                </div>

                <div className="space-y-4">
                  <div>
                    <Label className="text-xs text-muted-foreground">Effective Date</Label>
                    <p className="font-medium">
                      {agreement.effective_date
                        ? new Date(agreement.effective_date).toLocaleDateString()
                        : 'Not set'}
                    </p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Expiry Date</Label>
                    <p className="font-medium">
                      {agreement.expiry_date
                        ? new Date(agreement.expiry_date).toLocaleDateString()
                        : 'No expiry'}
                    </p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Created</Label>
                    <p className="text-sm">
                      {new Date(agreement.created_at).toLocaleString()}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      by {agreement.created_by || 'System'}
                    </p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Last Updated</Label>
                    <p className="text-sm">
                      {new Date(agreement.updated_at).toLocaleString()}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      by {agreement.updated_by || 'System'}
                    </p>
                  </div>
                  <Separator />
                  <div>
                    <Label className="text-xs text-muted-foreground">Is Template</Label>
                    <div className="flex items-center gap-2 mt-1">
                      {agreement.is_template ? (
                        <>
                          <CheckCircle className="h-4 w-4 text-green-500" />
                          <span className="text-sm">Yes</span>
                        </>
                      ) : (
                        <>
                          <XCircle className="h-4 w-4 text-red-500" />
                          <span className="text-sm">No</span>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Versions Tab */}
        <TabsContent value="versions">
          <Card>
            <CardHeader>
              <CardTitle>Version History</CardTitle>
              <CardDescription>All versions of this legal agreement</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Version</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Changes</TableHead>
                    <TableHead>Modified By</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {versions?.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center text-muted-foreground">
                        No version history available
                      </TableCell>
                    </TableRow>
                  ) : (
                    versions?.map((version: any, idx: number) => (
                      <TableRow key={version.id} className={idx === 0 ? 'bg-muted/30' : ''}>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <span className="font-mono font-medium">v{version.version}</span>
                            {idx === 0 && <Badge variant="default">Current</Badge>}
                          </div>
                        </TableCell>
                        <TableCell>{getStatusBadge(version.status)}</TableCell>
                        <TableCell className="max-w-md">
                          <p className="text-sm text-muted-foreground truncate">
                            {version.change_summary || 'No change summary'}
                          </p>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-muted-foreground" />
                            <span className="text-sm">{version.modified_by || 'System'}</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="text-sm">
                            {new Date(version.created_at).toLocaleDateString()}
                            <div className="text-xs text-muted-foreground">
                              {new Date(version.created_at).toLocaleTimeString()}
                            </div>
                          </div>
                        </TableCell>
                        <TableCell>
                          <Button variant="ghost" size="sm">
                            <Eye className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Acceptance Tab */}
        <TabsContent value="acceptance">
          <div className="grid gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Acceptance Statistics</CardTitle>
                <CardDescription>User acceptance data for this agreement</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="grid md:grid-cols-3 gap-4">
                  <div className="p-4 border rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <CheckCircle className="h-5 w-5 text-green-500" />
                      <Label>Accepted</Label>
                    </div>
                    <div className="text-2xl font-bold">
                      {acceptanceStats?.accepted_count || 0}
                    </div>
                  </div>
                  <div className="p-4 border rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <Clock className="h-5 w-5 text-yellow-500" />
                      <Label>Pending</Label>
                    </div>
                    <div className="text-2xl font-bold">
                      {acceptanceStats?.pending_count || 0}
                    </div>
                  </div>
                  <div className="p-4 border rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <XCircle className="h-5 w-5 text-red-500" />
                      <Label>Declined</Label>
                    </div>
                    <div className="text-2xl font-bold">
                      {acceptanceStats?.declined_count || 0}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Recent Acceptances</CardTitle>
              </CardHeader>
              <CardContent>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>User</TableHead>
                      <TableHead>Email</TableHead>
                      <TableHead>Version</TableHead>
                      <TableHead>Accepted Date</TableHead>
                      <TableHead>IP Address</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {acceptanceStats?.recent_acceptances?.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={5} className="text-center text-muted-foreground">
                          No acceptances yet
                        </TableCell>
                      </TableRow>
                    ) : (
                      acceptanceStats?.recent_acceptances?.map((acceptance: any) => (
                        <TableRow key={acceptance.id}>
                          <TableCell className="font-medium">{acceptance.user_name}</TableCell>
                          <TableCell>{acceptance.user_email}</TableCell>
                          <TableCell className="font-mono">v{acceptance.version}</TableCell>
                          <TableCell>
                            {new Date(acceptance.accepted_at).toLocaleString()}
                          </TableCell>
                          <TableCell className="font-mono text-sm">
                            {acceptance.ip_address || 'N/A'}
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function Label({ children, className = '' }: { children: React.ReactNode; className?: string }) {
  return <div className={`text-sm font-medium ${className}`}>{children}</div>;
}
