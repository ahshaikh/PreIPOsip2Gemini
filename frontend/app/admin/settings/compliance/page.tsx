// V-FINAL-1730-200 | V-ENHANCED-COMPLIANCE
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import {
  Plus, Edit, Trash2, FileCheck, AlertTriangle, Users, Clock,
  Shield, Eye, History, CheckCircle, XCircle, RefreshCw,
  FileText, Bell, Settings, Download, Upload
} from "lucide-react";

// Compliance document types
const DOCUMENT_TYPES = [
  { value: 'terms_of_service', label: 'Terms of Service' },
  { value: 'privacy_policy', label: 'Privacy Policy' },
  { value: 'cookie_policy', label: 'Cookie Policy' },
  { value: 'investment_disclaimer', label: 'Investment Disclaimer' },
  { value: 'risk_disclosure', label: 'Risk Disclosure' },
  { value: 'kyc_consent', label: 'KYC Consent' },
  { value: 'data_processing', label: 'Data Processing Agreement' },
  { value: 'refund_policy', label: 'Refund Policy' },
  { value: 'aml_policy', label: 'AML Policy' },
  { value: 'other', label: 'Other' },
];

export default function ComplianceSettingsPage() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('documents');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingDocument, setEditingDocument] = useState<any>(null);
  const [deleteConfirmDoc, setDeleteConfirmDoc] = useState<any>(null);
  const [viewHistoryDoc, setViewHistoryDoc] = useState<any>(null);
  const [notifyUsersDoc, setNotifyUsersDoc] = useState<any>(null);

  // Form State
  const [docType, setDocType] = useState('terms_of_service');
  const [docTitle, setDocTitle] = useState('');
  const [docContent, setDocContent] = useState('');
  const [docVersion, setDocVersion] = useState('1.0');
  const [requireReacceptance, setRequireReacceptance] = useState(true);
  const [isActive, setIsActive] = useState(true);
  const [effectiveDate, setEffectiveDate] = useState('');

  // Fetch compliance documents
  const { data: documents, isLoading } = useQuery({
    queryKey: ['adminCompliance'],
    queryFn: async () => (await api.get('/admin/compliance/documents')).data,
  });

  // Fetch compliance statistics
  const { data: stats } = useQuery({
    queryKey: ['adminComplianceStats'],
    queryFn: async () => (await api.get('/admin/compliance/stats')).data,
  });

  // Fetch user agreements
  const { data: userAgreements } = useQuery({
    queryKey: ['adminComplianceAgreements'],
    queryFn: async () => (await api.get('/admin/compliance/user-agreements')).data,
    enabled: activeTab === 'agreements',
  });

  // Fetch version history for a document
  const { data: versionHistory } = useQuery({
    queryKey: ['complianceHistory', viewHistoryDoc?.id],
    queryFn: async () => (await api.get(`/admin/compliance/documents/${viewHistoryDoc.id}/history`)).data,
    enabled: !!viewHistoryDoc,
  });

  const resetForm = () => {
    setDocType('terms_of_service');
    setDocTitle('');
    setDocContent('');
    setDocVersion('1.0');
    setRequireReacceptance(true);
    setIsActive(true);
    setEffectiveDate('');
    setEditingDocument(null);
  };

  const handleEdit = (doc: any) => {
    setEditingDocument(doc);
    setDocType(doc.type);
    setDocTitle(doc.title);
    setDocContent(doc.content);
    setDocVersion(doc.version);
    setRequireReacceptance(doc.require_reacceptance !== false);
    setIsActive(doc.is_active !== false);
    setEffectiveDate(doc.effective_date || '');
    setIsDialogOpen(true);
  };

  // Create/Update mutation
  const mutation = useMutation({
    mutationFn: (data: any) => {
      if (editingDocument) {
        return api.put(`/admin/compliance/documents/${editingDocument.id}`, data);
      }
      return api.post('/admin/compliance/documents', data);
    },
    onSuccess: () => {
      toast.success(editingDocument ? "Document Updated" : "Document Created");
      queryClient.invalidateQueries({ queryKey: ['adminCompliance'] });
      queryClient.invalidateQueries({ queryKey: ['adminComplianceStats'] });
      setIsDialogOpen(false);
      resetForm();
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/compliance/documents/${id}`),
    onSuccess: () => {
      toast.success("Document Deleted");
      queryClient.invalidateQueries({ queryKey: ['adminCompliance'] });
      setDeleteConfirmDoc(null);
    }
  });

  // Force re-acceptance mutation
  const forceReacceptMutation = useMutation({
    mutationFn: (docId: number) => api.post(`/admin/compliance/documents/${docId}/force-reaccept`),
    onSuccess: () => {
      toast.success("All users will be required to re-accept this document");
      queryClient.invalidateQueries({ queryKey: ['adminComplianceStats'] });
      queryClient.invalidateQueries({ queryKey: ['adminComplianceAgreements'] });
    }
  });

  // Notify users mutation
  const notifyUsersMutation = useMutation({
    mutationFn: (docId: number) => api.post(`/admin/compliance/documents/${docId}/notify-users`),
    onSuccess: () => {
      toast.success("Notification sent to all users");
      setNotifyUsersDoc(null);
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    mutation.mutate({
      type: docType,
      title: docTitle,
      content: docContent,
      version: docVersion,
      require_reacceptance: requireReacceptance,
      is_active: isActive,
      effective_date: effectiveDate || null,
    });
  };

  // Calculate acceptance percentage
  const acceptanceRate = stats?.total_users > 0
    ? Math.round((stats?.users_accepted / stats?.total_users) * 100)
    : 0;

  // Get pending users count
  const pendingUsers = (stats?.total_users || 0) - (stats?.users_accepted || 0);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Compliance Manager</h1>
          <p className="text-muted-foreground">Manage legal documents, user agreements, and compliance tracking.</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if(!open) resetForm(); }}>
          <Button onClick={() => setIsDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" /> Add Document
          </Button>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingDocument ? 'Edit Compliance Document' : 'Create New Document'}</DialogTitle>
              <DialogDescription>
                {editingDocument
                  ? 'Update the document. A new version will be created if content changes.'
                  : 'Create a new compliance document that users must accept.'}
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Document Type</Label>
                  <Select value={docType} onValueChange={setDocType}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {DOCUMENT_TYPES.map(type => (
                        <SelectItem key={type.value} value={type.value}>{type.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Version</Label>
                  <Input
                    value={docVersion}
                    onChange={e => setDocVersion(e.target.value)}
                    placeholder="e.g., 1.0, 2.1"
                    required
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label>Document Title</Label>
                <Input
                  value={docTitle}
                  onChange={e => setDocTitle(e.target.value)}
                  placeholder="e.g., Terms of Service"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label>Document Content</Label>
                <Textarea
                  value={docContent}
                  onChange={e => setDocContent(e.target.value)}
                  placeholder="Enter the full legal document content..."
                  rows={12}
                  required
                />
                <p className="text-xs text-muted-foreground">Supports markdown formatting.</p>
              </div>

              <div className="space-y-2">
                <Label>Effective Date (Optional)</Label>
                <Input
                  type="date"
                  value={effectiveDate}
                  onChange={e => setEffectiveDate(e.target.value)}
                />
                <p className="text-xs text-muted-foreground">Leave blank to use publish date.</p>
              </div>

              <div className="space-y-4 p-4 bg-muted/50 rounded-lg">
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Active Document</Label>
                    <p className="text-xs text-muted-foreground">Show this document to users</p>
                  </div>
                  <Switch checked={isActive} onCheckedChange={setIsActive} />
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Require Re-acceptance</Label>
                    <p className="text-xs text-muted-foreground">Users must re-accept when document changes</p>
                  </div>
                  <Switch checked={requireReacceptance} onCheckedChange={setRequireReacceptance} />
                </div>
              </div>

              {editingDocument && requireReacceptance && (
                <div className="p-4 border border-yellow-500/50 bg-yellow-500/10 rounded-lg flex items-start gap-3">
                  <AlertTriangle className="h-5 w-5 text-yellow-500 mt-0.5" />
                  <div>
                    <p className="font-medium text-yellow-600">Important Notice</p>
                    <p className="text-sm text-muted-foreground">
                      Saving changes will require all existing users to re-accept this document on their next login.
                    </p>
                  </div>
                </div>
              )}

              <Button type="submit" className="w-full" disabled={mutation.isPending}>
                {mutation.isPending ? "Saving..." : (editingDocument ? "Update Document" : "Create Document")}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistics Cards */}
      <div className="grid md:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Active Documents</CardTitle>
            <FileCheck className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.active_documents || 0}</div>
            <p className="text-xs text-muted-foreground">{stats?.total_documents || 0} total documents</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">User Acceptance</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{acceptanceRate}%</div>
            <Progress value={acceptanceRate} className="mt-2" />
            <p className="text-xs text-muted-foreground mt-1">{stats?.users_accepted || 0} of {stats?.total_users || 0} users</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Pending Acceptance</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">{pendingUsers}</div>
            <p className="text-xs text-muted-foreground">users need to accept</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium">Recent Updates</CardTitle>
            <RefreshCw className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.recent_updates || 0}</div>
            <p className="text-xs text-muted-foreground">in last 30 days</p>
          </CardContent>
        </Card>
      </div>

      {/* Main Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="documents">
            <FileText className="mr-2 h-4 w-4" /> Documents
          </TabsTrigger>
          <TabsTrigger value="agreements">
            <Shield className="mr-2 h-4 w-4" /> User Agreements
          </TabsTrigger>
          <TabsTrigger value="settings">
            <Settings className="mr-2 h-4 w-4" /> Settings
          </TabsTrigger>
        </TabsList>

        {/* Documents Tab */}
        <TabsContent value="documents">
          <Card>
            <CardHeader>
              <CardTitle>Compliance Documents</CardTitle>
              <CardDescription>All legal and compliance documents that users must accept.</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Document</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Version</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Acceptance</TableHead>
                    <TableHead>Last Updated</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {isLoading ? (
                    <TableRow><TableCell colSpan={7} className="text-center">Loading...</TableCell></TableRow>
                  ) : documents?.length === 0 ? (
                    <TableRow><TableCell colSpan={7} className="text-center text-muted-foreground">No compliance documents yet.</TableCell></TableRow>
                  ) : (
                    documents?.map((doc: any) => (
                      <TableRow key={doc.id}>
                        <TableCell className="font-medium">
                          <div className="flex items-center gap-2">
                            <FileText className="h-4 w-4 text-muted-foreground" />
                            {doc.title}
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline">
                            {DOCUMENT_TYPES.find(t => t.value === doc.type)?.label || doc.type}
                          </Badge>
                        </TableCell>
                        <TableCell>v{doc.version}</TableCell>
                        <TableCell>
                          <Badge variant={doc.is_active ? 'default' : 'secondary'}>
                            {doc.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Progress value={doc.acceptance_rate || 0} className="w-16 h-2" />
                            <span className="text-xs text-muted-foreground">{doc.acceptance_rate || 0}%</span>
                          </div>
                        </TableCell>
                        <TableCell>{new Date(doc.updated_at).toLocaleDateString()}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1">
                            <Button variant="ghost" size="sm" onClick={() => handleEdit(doc)} title="Edit">
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => setViewHistoryDoc(doc)} title="View History">
                              <History className="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="sm" onClick={() => setNotifyUsersDoc(doc)} title="Notify Users">
                              <Bell className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setDeleteConfirmDoc(doc)}
                              className="text-destructive hover:text-destructive"
                              title="Delete"
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* User Agreements Tab */}
        <TabsContent value="agreements">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>User Agreements</CardTitle>
                  <CardDescription>Track which users have accepted compliance documents.</CardDescription>
                </div>
                <Button variant="outline" size="sm">
                  <Download className="mr-2 h-4 w-4" /> Export Report
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>User</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Terms</TableHead>
                    <TableHead>Privacy</TableHead>
                    <TableHead>Investment Disclaimer</TableHead>
                    <TableHead>Last Accepted</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {userAgreements?.length === 0 ? (
                    <TableRow><TableCell colSpan={7} className="text-center text-muted-foreground">No user agreements found.</TableCell></TableRow>
                  ) : (
                    userAgreements?.map((user: any) => (
                      <TableRow key={user.id}>
                        <TableCell className="font-medium">{user.name}</TableCell>
                        <TableCell>{user.email}</TableCell>
                        <TableCell>
                          {user.terms_accepted ? (
                            <CheckCircle className="h-4 w-4 text-green-500" />
                          ) : (
                            <XCircle className="h-4 w-4 text-red-500" />
                          )}
                        </TableCell>
                        <TableCell>
                          {user.privacy_accepted ? (
                            <CheckCircle className="h-4 w-4 text-green-500" />
                          ) : (
                            <XCircle className="h-4 w-4 text-red-500" />
                          )}
                        </TableCell>
                        <TableCell>
                          {user.disclaimer_accepted ? (
                            <CheckCircle className="h-4 w-4 text-green-500" />
                          ) : (
                            <XCircle className="h-4 w-4 text-red-500" />
                          )}
                        </TableCell>
                        <TableCell>
                          {user.last_accepted_at
                            ? new Date(user.last_accepted_at).toLocaleDateString()
                            : 'Never'}
                        </TableCell>
                        <TableCell>
                          <Badge variant={user.all_accepted ? 'default' : 'destructive'}>
                            {user.all_accepted ? 'Compliant' : 'Pending'}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Settings Tab */}
        <TabsContent value="settings">
          <div className="grid gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Registration Settings</CardTitle>
                <CardDescription>Configure which documents users must accept during registration.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Require Terms of Service</Label>
                    <p className="text-xs text-muted-foreground">Users must accept terms during registration</p>
                  </div>
                  <Switch defaultChecked />
                </div>
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Require Privacy Policy</Label>
                    <p className="text-xs text-muted-foreground">Users must accept privacy policy during registration</p>
                  </div>
                  <Switch defaultChecked />
                </div>
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Require Investment Disclaimer</Label>
                    <p className="text-xs text-muted-foreground">Users must accept investment risks disclaimer</p>
                  </div>
                  <Switch defaultChecked />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Re-acceptance Settings</CardTitle>
                <CardDescription>Configure how users are prompted to re-accept updated documents.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Block Access Until Accepted</Label>
                    <p className="text-xs text-muted-foreground">Users cannot access the platform until they accept updated documents</p>
                  </div>
                  <Switch defaultChecked />
                </div>
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Send Email Notification</Label>
                    <p className="text-xs text-muted-foreground">Notify users via email when documents are updated</p>
                  </div>
                  <Switch defaultChecked />
                </div>
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Grace Period (Days)</Label>
                    <p className="text-xs text-muted-foreground">Days before blocking access after document update</p>
                  </div>
                  <Input type="number" defaultValue="7" className="w-20" min="0" max="30" />
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Bulk Actions</CardTitle>
                <CardDescription>Perform actions on all users.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-4 border border-yellow-500/50 rounded-lg bg-yellow-500/5">
                  <div>
                    <Label>Force Re-acceptance for All Users</Label>
                    <p className="text-xs text-muted-foreground">All users will be required to re-accept all active documents</p>
                  </div>
                  <Button variant="outline" className="border-yellow-500 text-yellow-600 hover:bg-yellow-500/10">
                    <RefreshCw className="mr-2 h-4 w-4" /> Force Re-accept
                  </Button>
                </div>
                <div className="flex items-center justify-between p-4 border rounded-lg">
                  <div>
                    <Label>Send Compliance Reminder</Label>
                    <p className="text-xs text-muted-foreground">Send email reminder to users with pending acceptances</p>
                  </div>
                  <Button variant="outline">
                    <Bell className="mr-2 h-4 w-4" /> Send Reminder
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!deleteConfirmDoc} onOpenChange={() => setDeleteConfirmDoc(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Compliance Document</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete this document? This action cannot be undone.
              <span className="block mt-2 font-medium">"{deleteConfirmDoc?.title}"</span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => deleteMutation.mutate(deleteConfirmDoc.id)}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {deleteMutation.isPending ? "Deleting..." : "Delete Document"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Version History Dialog */}
      <Dialog open={!!viewHistoryDoc} onOpenChange={() => setViewHistoryDoc(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Version History: {viewHistoryDoc?.title}</DialogTitle>
            <DialogDescription>View all versions of this compliance document.</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 max-h-96 overflow-y-auto">
            {versionHistory?.map((version: any, idx: number) => (
              <div key={version.id} className={`p-4 border rounded-lg ${idx === 0 ? 'border-primary bg-primary/5' : ''}`}>
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <Badge variant={idx === 0 ? 'default' : 'outline'}>v{version.version}</Badge>
                    {idx === 0 && <Badge variant="secondary">Current</Badge>}
                  </div>
                  <span className="text-xs text-muted-foreground">
                    {new Date(version.created_at).toLocaleString()}
                  </span>
                </div>
                <p className="text-sm text-muted-foreground">{version.change_summary || 'No change summary'}</p>
                <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                  <span>Created by: {version.created_by || 'System'}</span>
                  <span>Acceptances: {version.acceptance_count || 0}</span>
                </div>
              </div>
            ))}
            {(!versionHistory || versionHistory.length === 0) && (
              <p className="text-center text-muted-foreground py-4">No version history available.</p>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* Notify Users Dialog */}
      <AlertDialog open={!!notifyUsersDoc} onOpenChange={() => setNotifyUsersDoc(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Notify Users</AlertDialogTitle>
            <AlertDialogDescription>
              Send an email notification to all users about this compliance document update.
              <span className="block mt-2 font-medium">"{notifyUsersDoc?.title}"</span>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={() => notifyUsersMutation.mutate(notifyUsersDoc.id)}>
              {notifyUsersMutation.isPending ? "Sending..." : "Send Notification"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
