'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { useRouter } from "next/navigation";
import {
  Plus, Edit, Trash2, FileCheck, AlertTriangle, Clock,
  Shield, Eye, History, FileText, MoreVertical, Search,
  Filter, Download, Upload, Archive, Check, X
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

// Legal Agreement Types
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

// Agreement Status Types
const STATUS_TYPES = [
  { value: 'draft', label: 'Draft', color: 'bg-gray-500' },
  { value: 'review', label: 'Under Review', color: 'bg-yellow-500' },
  { value: 'active', label: 'Active', color: 'bg-green-500' },
  { value: 'archived', label: 'Archived', color: 'bg-blue-500' },
  { value: 'superseded', label: 'Superseded', color: 'bg-purple-500' },
];

export default function LegalAgreementsPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingAgreement, setEditingAgreement] = useState<any>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<any>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  // Form State
  const [agreementType, setAgreementType] = useState('terms_of_service');
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [content, setContent] = useState('');
  const [version, setVersion] = useState('1.0.0');
  const [status, setStatus] = useState('draft');
  const [effectiveDate, setEffectiveDate] = useState('');
  const [expiryDate, setExpiryDate] = useState('');
  const [requireSignature, setRequireSignature] = useState(false);
  const [isTemplate, setIsTemplate] = useState(false);

  // Fetch legal agreements
  const { data: agreements, isLoading } = useQuery({
    queryKey: ['legalAgreements', statusFilter],
    queryFn: async () => {
      const url = statusFilter === 'all'
        ? '/admin/compliance/legal-agreements'
        : `/admin/compliance/legal-agreements?status=${statusFilter}`;
      return (await api.get(url)).data;
    },
  });

  // Fetch statistics
  const { data: stats } = useQuery({
    queryKey: ['legalAgreementsStats'],
    queryFn: async () => (await api.get('/admin/compliance/legal-agreements/stats')).data,
  });

  const resetForm = () => {
    setAgreementType('terms_of_service');
    setTitle('');
    setDescription('');
    setContent('');
    setVersion('1.0.0');
    setStatus('draft');
    setEffectiveDate('');
    setExpiryDate('');
    setRequireSignature(false);
    setIsTemplate(false);
    setEditingAgreement(null);
  };

  const handleEdit = (agreement: any) => {
    setEditingAgreement(agreement);
    setAgreementType(agreement.type);
    setTitle(agreement.title);
    setDescription(agreement.description || '');
    setContent(agreement.content);
    setVersion(agreement.version);
    setStatus(agreement.status);
    setEffectiveDate(agreement.effective_date || '');
    setExpiryDate(agreement.expiry_date || '');
    setRequireSignature(agreement.require_signature || false);
    setIsTemplate(agreement.is_template || false);
    setIsDialogOpen(true);
  };

  // Create/Update mutation
  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingAgreement) {
        return api.put(`/admin/compliance/legal-agreements/${editingAgreement.id}`, data);
      }
      return api.post('/admin/compliance/legal-agreements', data);
    },
    onSuccess: () => {
      toast.success(editingAgreement ? "Agreement Updated" : "Agreement Created");
      queryClient.invalidateQueries({ queryKey: ['legalAgreements'] });
      queryClient.invalidateQueries({ queryKey: ['legalAgreementsStats'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/compliance/legal-agreements/${id}`),
    onSuccess: () => {
      toast.success("Agreement Deleted");
      queryClient.invalidateQueries({ queryKey: ['legalAgreements'] });
      queryClient.invalidateQueries({ queryKey: ['legalAgreementsStats'] });
      setDeleteConfirm(null);
    }
  });

  // Archive mutation
  const archiveMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/compliance/legal-agreements/${id}/archive`),
    onSuccess: () => {
      toast.success("Agreement Archived");
      queryClient.invalidateQueries({ queryKey: ['legalAgreements'] });
    }
  });

  // Publish mutation
  const publishMutation = useMutation({
    mutationFn: (id: number) => api.post(`/admin/compliance/legal-agreements/${id}/publish`),
    onSuccess: () => {
      toast.success("Agreement Published");
      queryClient.invalidateQueries({ queryKey: ['legalAgreements'] });
      queryClient.invalidateQueries({ queryKey: ['legalAgreementsStats'] });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({
      type: agreementType,
      title,
      description,
      content,
      version,
      status,
      effective_date: effectiveDate || null,
      expiry_date: expiryDate || null,
      require_signature: requireSignature,
      is_template: isTemplate,
    });
  };

  // Filter agreements based on search
  const filteredAgreements = agreements?.filter((agreement: any) =>
    agreement.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    agreement.type.toLowerCase().includes(searchQuery.toLowerCase()) ||
    agreement.version.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const getStatusBadge = (statusValue: string) => {
    const status = STATUS_TYPES.find(s => s.value === statusValue);
    return (
      <Badge variant="outline" className={`${status?.color} text-white border-0`}>
        {status?.label || statusValue}
      </Badge>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Versioned Legal Agreements</h1>
          <p className="text-muted-foreground">
            Manage legal agreements with full version control and audit trail.
          </p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <Button onClick={() => setIsDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" /> New Agreement
          </Button>
          <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>
                {editingAgreement ? 'Edit Legal Agreement' : 'Create New Legal Agreement'}
              </DialogTitle>
              <DialogDescription>
                {editingAgreement
                  ? 'Update the agreement. Changes will create a new version in the audit trail.'
                  : 'Create a new legal agreement with version control and audit tracking.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Agreement Type *</Label>
                  <Select value={agreementType} onValueChange={setAgreementType}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {AGREEMENT_TYPES.map(type => (
                        <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Version *</Label>
                  <Input
                    value={version}
                    onChange={e => setVersion(e.target.value)}
                    placeholder="e.g., 1.0.0, 2.1.0"
                    required
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Agreement Title *</Label>
                <Input
                  value={title}
                  onChange={e => setTitle(e.target.value)}
                  placeholder="e.g., Platform Terms of Service"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label>Description</Label>
                <Textarea
                  value={description}
                  onChange={e => setDescription(e.target.value)}
                  placeholder="Brief description of this agreement..."
                  rows={2}
                />
              </div>

              <div className="space-y-2">
                <Label>Agreement Content *</Label>
                <Textarea
                  value={content}
                  onChange={e => setContent(e.target.value)}
                  placeholder="Enter the full legal agreement text..."
                  rows={15}
                  required
                />
                <p className="text-xs text-muted-foreground">Supports markdown formatting.</p>
              </div>

              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>Status</Label>
                  <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {STATUS_TYPES.map(status => (
                        <SelectItem key={status.value} value={status.value}>
                          {status.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Effective Date</Label>
                  <Input
                    type="date"
                    value={effectiveDate}
                    onChange={e => setEffectiveDate(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Expiry Date</Label>
                  <Input
                    type="date"
                    value={expiryDate}
                    onChange={e => setExpiryDate(e.target.value)}
                  />
                </div>
              </div>

              <div className="space-y-4 p-4 bg-muted/50 rounded-lg">
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Require Digital Signature</Label>
                    <p className="text-xs text-muted-foreground">
                      Users must provide digital signature to accept
                    </p>
                  </div>
                  <Switch checked={requireSignature} onCheckedChange={setRequireSignature} />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Save as Template</Label>
                    <p className="text-xs text-muted-foreground">
                      Use this as a template for future agreements
                    </p>
                  </div>
                  <Switch checked={isTemplate} onCheckedChange={setIsTemplate} />
                </div>
              </div>

              {editingAgreement && (
                <div className="p-4 border border-blue-500/50 bg-blue-500/10 rounded-lg flex items-start gap-3">
                  <AlertTriangle className="h-5 w-5 text-blue-500 mt-0.5" />
                  <div>
                    <p className="font-medium text-blue-600">Version Control Notice</p>
                    <p className="text-sm text-muted-foreground">
                      Editing this agreement will create a new version entry in the audit trail
                      with all changes tracked and timestamped.
                    </p>
                  </div>
                </div>
              )}

              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingAgreement ? "Update Agreement" : "Create Agreement")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics Cards */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Agreements</CardTitle>
            <FileCheck className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_agreements || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.active_agreements || 0} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Under Review</CardTitle>
            <Eye className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">
              {stats?.review_agreements || 0}
            </div>
            <p className="text-xs text-muted-foreground">awaiting approval</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Total Versions</CardTitle>
            <History className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_versions || 0}</div>
            <p className="text-xs text-muted-foreground">across all agreements</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">This Month</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.this_month_changes || 0}</div>
            <p className="text-xs text-muted-foreground">changes made</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters and Search */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search agreements by title, type, or version..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-full md:w-[200px]">
                <Filter className="mr-2 h-4 w-4" />
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                {STATUS_TYPES.map(status => (
                  <SelectItem key={status.value} value={status.value}>
                    {status.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Button variant="outline">
              <Download className="mr-2 h-4 w-4" /> Export
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Agreements Table */}
      <Card>
        <CardHeader>
          <CardTitle>Legal Agreements</CardTitle>
          <CardDescription>
            All legal agreements with version control and audit trails.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Agreement</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Version</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Effective Date</TableHead>
                <TableHead>Last Modified</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {isLoading ? (
                <TableRow>
                  <TableCell colSpan={7} className="text-center">Loading...</TableCell>
                </TableRow>
              ) : filteredAgreements?.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} className="text-center text-muted-foreground">
                    No legal agreements found.
                  </TableCell>
                </TableRow>
              ) : (
                filteredAgreements?.map((agreement: any) => (
                  <TableRow key={agreement.id}>
                    <TableCell className="font-medium">
                      <div className="flex items-center gap-2">
                        <FileText className="h-4 w-4 text-muted-foreground" />
                        <div>
                          <div>{agreement.title}</div>
                          {agreement.is_template && (
                            <Badge variant="secondary" className="text-xs mt-1">
                              Template
                            </Badge>
                          )}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">
                        {AGREEMENT_TYPES.find(t => t.value === agreement.type)?.label || agreement.type}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <span className="font-mono text-sm">v{agreement.version}</span>
                        {agreement.version_count > 1 && (
                          <Badge variant="secondary" className="text-xs">
                            +{agreement.version_count - 1}
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>{getStatusBadge(agreement.status)}</TableCell>
                    <TableCell>
                      {agreement.effective_date
                        ? new Date(agreement.effective_date).toLocaleDateString()
                        : '-'}
                    </TableCell>
                    <TableCell>
                      <div className="text-sm">
                        {new Date(agreement.updated_at).toLocaleDateString()}
                        <div className="text-xs text-muted-foreground">
                          {new Date(agreement.updated_at).toLocaleTimeString()}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuLabel>Actions</DropdownMenuLabel>
                          <DropdownMenuItem
                            onClick={() => router.push(`/admin/compliance-manager/legal-agreements/${agreement.id}`)}
                          >
                            <Eye className="mr-2 h-4 w-4" /> View Details
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleEdit(agreement)}>
                            <Edit className="mr-2 h-4 w-4" /> Edit
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            onClick={() => router.push(`/admin/compliance-manager/legal-agreements/${agreement.id}/audit-trail`)}
                          >
                            <History className="mr-2 h-4 w-4" /> View Audit Trail
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          {agreement.status === 'draft' && (
                            <DropdownMenuItem
                              onClick={() => publishMutation.mutate(agreement.id)}
                            >
                              <Check className="mr-2 h-4 w-4" /> Publish
                            </DropdownMenuItem>
                          )}
                          {agreement.status === 'active' && (
                            <DropdownMenuItem
                              onClick={() => archiveMutation.mutate(agreement.id)}
                            >
                              <Archive className="mr-2 h-4 w-4" /> Archive
                            </DropdownMenuItem>
                          )}
                          <DropdownMenuItem
                            onClick={() => setDeleteConfirm(agreement)}
                            className="text-destructive"
                          >
                            <Trash2 className="mr-2 h-4 w-4" /> Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirm} onOpenChange={() => setDeleteConfirm(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Legal Agreement</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this agreement? This will remove all versions
              and audit trail entries. This action cannot be undone.
              <span className="block mt-2 font-medium text-foreground">
                &quot;{deleteConfirm?.title}&quot;
              </span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirm.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Agreement"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
