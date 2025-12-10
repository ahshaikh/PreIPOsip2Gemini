// V-KYC-ENHANCEMENT-002 | Enhanced KYC Verification Modal
// Created: 2025-12-10 | Purpose: Advanced document verification with zoom, rotate, checklist

'use client';

import { useState } from 'react';
import {
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
  ZoomIn,
  ZoomOut,
  RotateCw,
  CheckCircle,
  XCircle,
  AlertCircle,
  FileText,
  MessageSquare,
  Send,
} from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';

interface VerificationChecklist {
  photo_quality: boolean;
  document_legible: boolean;
  details_match: boolean;
  not_expired: boolean;
  no_tampering: boolean;
}

interface KycVerificationModalProps {
  kycId: number;
  onClose: () => void;
}

export function EnhancedKycVerificationModal({ kycId, onClose }: KycVerificationModalProps) {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('documents');
  const [selectedDocument, setSelectedDocument] = useState<any>(null);
  const [documentZoom, setDocumentZoom] = useState(100);
  const [documentRotation, setDocumentRotation] = useState(0);
  const [verificationNotes, setVerificationNotes] = useState('');
  const [rejectionReason, setRejectionReason] = useState('');
  const [resubmissionInstructions, setResubmissionInstructions] = useState('');
  const [selectedTemplate, setSelectedTemplate] = useState<string>('');

  const [checklist, setChecklist] = useState<VerificationChecklist>({
    photo_quality: false,
    document_legible: false,
    details_match: false,
    not_expired: false,
    no_tampering: false,
  });

  // Fetch KYC details
  const { data: kyc, isLoading } = useQuery({
    queryKey: ['kycDetail', kycId],
    queryFn: async () => {
      const res = await api.get(`/admin/kyc-queue/${kycId}`);
      if (res.data.documents && res.data.documents.length > 0) {
        setSelectedDocument(res.data.documents[0]);
      }
      return res.data;
    },
  });

  // Fetch rejection templates
  const { data: templates = [] } = useQuery({
    queryKey: ['kycRejectionTemplates'],
    queryFn: async () => (await api.get('/admin/kyc/rejection-templates')).data.data,
  });

  // Add verification note
  const addNoteMutation = useMutation({
    mutationFn: (note: string) =>
      api.post(`/admin/kyc-queue/${kycId}/notes`, { note }),
    onSuccess: () => {
      toast.success('Note added');
      setVerificationNotes('');
      queryClient.invalidateQueries({ queryKey: ['kycDetail', kycId] });
    },
  });

  // Approve KYC
  const approveMutation = useMutation({
    mutationFn: () =>
      api.post(`/admin/kyc-queue/${kycId}/approve`, {
        verification_checklist: checklist,
        notes: verificationNotes,
      }),
    onSuccess: () => {
      toast.success('KYC Approved Successfully');
      onClose();
    },
    onError: (error: any) => {
      toast.error('Approval Failed', { description: error.response?.data?.message });
    },
  });

  // Reject KYC
  const rejectMutation = useMutation({
    mutationFn: (reason: string) =>
      api.post(`/admin/kyc-queue/${kycId}/reject`, {
        reason,
        verification_checklist: checklist,
      }),
    onSuccess: () => {
      toast.success('KYC Rejected');
      onClose();
    },
    onError: (error: any) => {
      toast.error('Rejection Failed', { description: error.response?.data?.message });
    },
  });

  // Request resubmission
  const resubmitRequestMutation = useMutation({
    mutationFn: (instructions: string) =>
      api.post(`/admin/kyc-queue/${kycId}/request-resubmission`, {
        instructions,
        verification_checklist: checklist,
      }),
    onSuccess: () => {
      toast.success('Resubmission request sent to user');
      onClose();
    },
  });

  const handleApprove = () => {
    const allChecksPassed = Object.values(checklist).every((v) => v);
    if (!allChecksPassed) {
      toast.error('Complete all checklist items before approving');
      return;
    }
    approveMutation.mutate();
  };

  const handleReject = () => {
    if (!rejectionReason.trim()) {
      toast.error('Please provide a rejection reason');
      return;
    }
    rejectMutation.mutate(rejectionReason);
  };

  const handleRequestResubmission = () => {
    if (!resubmissionInstructions.trim()) {
      toast.error('Please provide resubmission instructions');
      return;
    }
    resubmitRequestMutation.mutate(resubmissionInstructions);
  };

  const handleTemplateSelect = (templateId: string) => {
    const template = templates.find((t: any) => t.id.toString() === templateId);
    if (template) {
      setRejectionReason(template.reason);
      setSelectedTemplate(templateId);
    }
  };

  const isChecklistComplete = Object.values(checklist).every((v) => v);

  if (isLoading) {
    return (
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Loading KYC Details...</DialogTitle>
        </DialogHeader>
      </DialogContent>
    );
  }

  return (
    <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
      <DialogHeader>
        <DialogTitle className="flex items-center gap-2">
          Review KYC: {kyc.user.username}
          {kyc.status === 'submitted' && (
            <Badge variant="outline" className="ml-2">
              Pending Review
            </Badge>
          )}
        </DialogTitle>
        <DialogDescription>
          Submitted at: {new Date(kyc.submitted_at).toLocaleString()} | User ID: {kyc.user.id}
        </DialogDescription>
      </DialogHeader>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="flex-1 flex flex-col">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="documents">
            <FileText className="mr-2 h-4 w-4" />
            Documents
          </TabsTrigger>
          <TabsTrigger value="details">User Details</TabsTrigger>
          <TabsTrigger value="checklist">
            Verification Checklist
            {isChecklistComplete && <CheckCircle className="ml-2 h-4 w-4 text-green-500" />}
          </TabsTrigger>
          <TabsTrigger value="notes">
            <MessageSquare className="mr-2 h-4 w-4" />
            Notes ({kyc.verification_notes?.length || 0})
          </TabsTrigger>
        </TabsList>

        {/* Documents Tab */}
        <TabsContent value="documents" className="flex-1 overflow-hidden">
          <div className="grid grid-cols-3 gap-4 h-full">
            {/* Document List */}
            <Card className="overflow-y-auto">
              <CardContent className="p-4 space-y-2">
                <h3 className="font-semibold mb-3">Uploaded Documents</h3>
                {kyc.documents.map((doc: any) => (
                  <div
                    key={doc.id}
                    className={`p-3 border rounded cursor-pointer hover:bg-accent transition ${
                      selectedDocument?.id === doc.id ? 'bg-accent border-primary' : ''
                    }`}
                    onClick={() => {
                      setSelectedDocument(doc);
                      setDocumentZoom(100);
                      setDocumentRotation(0);
                    }}
                  >
                    <div className="font-medium capitalize">
                      {doc.doc_type.replace(/_/g, ' ')}
                    </div>
                    <div className="text-xs text-muted-foreground">{doc.file_name}</div>
                    <Badge
                      variant={
                        doc.status === 'approved'
                          ? 'default'
                          : doc.status === 'rejected'
                          ? 'destructive'
                          : 'secondary'
                      }
                      className="mt-1"
                    >
                      {doc.status || 'pending'}
                    </Badge>
                  </div>
                ))}
              </CardContent>
            </Card>

            {/* Document Viewer */}
            <Card className="col-span-2">
              <CardContent className="p-4">
                {selectedDocument ? (
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <h3 className="font-semibold capitalize">
                        {selectedDocument.doc_type.replace(/_/g, ' ')}
                      </h3>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => setDocumentZoom(Math.max(50, documentZoom - 25))}
                        >
                          <ZoomOut className="h-4 w-4" />
                        </Button>
                        <span className="text-sm px-3 py-2">{documentZoom}%</span>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => setDocumentZoom(Math.min(200, documentZoom + 25))}
                        >
                          <ZoomIn className="h-4 w-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => setDocumentRotation((documentRotation + 90) % 360)}
                        >
                          <RotateCw className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>

                    <div className="border rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900 h-[500px] flex items-center justify-center">
                      <div
                        style={{
                          transform: `scale(${documentZoom / 100}) rotate(${documentRotation}deg)`,
                          transition: 'transform 0.3s ease',
                        }}
                      >
                        {selectedDocument.file_path.endsWith('.pdf') ? (
                          <div className="text-center">
                            <FileText className="h-16 w-16 mx-auto mb-4 text-muted-foreground" />
                            <a
                              href={`/storage/${selectedDocument.file_path}`}
                              target="_blank"
                              rel="noreferrer"
                              className="text-blue-500 underline"
                            >
                              Open PDF Document
                            </a>
                          </div>
                        ) : (
                          <img
                            src={`/storage/${selectedDocument.file_path}`}
                            alt={selectedDocument.doc_type}
                            className="max-w-full max-h-full"
                          />
                        )}
                      </div>
                    </div>

                    {selectedDocument.processing_status && (
                      <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                          Auto-Verification Status:{' '}
                          <Badge
                            variant={
                              selectedDocument.processing_status === 'verified'
                                ? 'default'
                                : 'destructive'
                            }
                          >
                            {selectedDocument.processing_status}
                          </Badge>
                        </AlertDescription>
                      </Alert>
                    )}
                  </div>
                ) : (
                  <div className="h-full flex items-center justify-center text-muted-foreground">
                    Select a document to view
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Details Tab */}
        <TabsContent value="details" className="space-y-4">
          <Card>
            <CardContent className="pt-6">
              <div className="grid grid-cols-2 gap-6">
                <div>
                  <h3 className="font-semibold mb-4">Personal Information</h3>
                  <div className="space-y-3">
                    <div>
                      <Label className="text-muted-foreground">Full Name</Label>
                      <p className="font-medium">{kyc.user.username}</p>
                    </div>
                    <div>
                      <Label className="text-muted-foreground">Email</Label>
                      <p className="font-medium">{kyc.user.email}</p>
                    </div>
                    <div>
                      <Label className="text-muted-foreground">PAN Number</Label>
                      <p className="font-medium font-mono">{kyc.pan_number}</p>
                    </div>
                    <div>
                      <Label className="text-muted-foreground">Aadhaar Number</Label>
                      <p className="font-medium font-mono">
                        {kyc.aadhaar_number || 'Not provided'}
                      </p>
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="font-semibold mb-4">Financial Information</h3>
                  <div className="space-y-3">
                    <div>
                      <Label className="text-muted-foreground">Bank Account</Label>
                      <p className="font-medium font-mono">{kyc.bank_account}</p>
                    </div>
                    <div>
                      <Label className="text-muted-foreground">IFSC Code</Label>
                      <p className="font-medium font-mono">{kyc.bank_ifsc}</p>
                    </div>
                    <div>
                      <Label className="text-muted-foreground">Demat Account</Label>
                      <p className="font-medium font-mono">{kyc.demat_account || 'Not provided'}</p>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Checklist Tab */}
        <TabsContent value="checklist" className="space-y-4">
          <Alert>
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              All checklist items must be completed before approving KYC
            </AlertDescription>
          </Alert>

          <Card>
            <CardContent className="pt-6 space-y-4">
              <div className="flex items-center space-x-3">
                <Checkbox
                  id="photo_quality"
                  checked={checklist.photo_quality}
                  onCheckedChange={(checked) =>
                    setChecklist({ ...checklist, photo_quality: checked as boolean })
                  }
                />
                <Label htmlFor="photo_quality" className="cursor-pointer">
                  Photo/Scan quality is clear and readable
                </Label>
              </div>

              <div className="flex items-center space-x-3">
                <Checkbox
                  id="document_legible"
                  checked={checklist.document_legible}
                  onCheckedChange={(checked) =>
                    setChecklist({ ...checklist, document_legible: checked as boolean })
                  }
                />
                <Label htmlFor="document_legible" className="cursor-pointer">
                  All text and details are legible
                </Label>
              </div>

              <div className="flex items-center space-x-3">
                <Checkbox
                  id="details_match"
                  checked={checklist.details_match}
                  onCheckedChange={(checked) =>
                    setChecklist({ ...checklist, details_match: checked as boolean })
                  }
                />
                <Label htmlFor="details_match" className="cursor-pointer">
                  Details match across all documents (name, DOB, etc.)
                </Label>
              </div>

              <div className="flex items-center space-x-3">
                <Checkbox
                  id="not_expired"
                  checked={checklist.not_expired}
                  onCheckedChange={(checked) =>
                    setChecklist({ ...checklist, not_expired: checked as boolean })
                  }
                />
                <Label htmlFor="not_expired" className="cursor-pointer">
                  Documents are not expired
                </Label>
              </div>

              <div className="flex items-center space-x-3">
                <Checkbox
                  id="no_tampering"
                  checked={checklist.no_tampering}
                  onCheckedChange={(checked) =>
                    setChecklist({ ...checklist, no_tampering: checked as boolean })
                  }
                />
                <Label htmlFor="no_tampering" className="cursor-pointer">
                  No signs of tampering or forgery
                </Label>
              </div>

              {isChecklistComplete && (
                <Alert className="bg-green-50 border-green-200">
                  <CheckCircle className="h-4 w-4 text-green-600" />
                  <AlertDescription className="text-green-800">
                    All verification checks completed successfully
                  </AlertDescription>
                </Alert>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Notes Tab */}
        <TabsContent value="notes" className="space-y-4">
          <Card>
            <CardContent className="pt-6 space-y-4">
              <div className="space-y-3">
                {kyc.verification_notes?.map((note: any, index: number) => (
                  <div key={index} className="border-l-4 border-primary pl-4 py-2">
                    <div className="text-sm text-muted-foreground">
                      {new Date(note.created_at).toLocaleString()} - {note.admin_name}
                    </div>
                    <div className="mt-1">{note.note}</div>
                  </div>
                ))}

                {!kyc.verification_notes?.length && (
                  <div className="text-center text-muted-foreground py-8">No notes added yet</div>
                )}
              </div>

              <div className="space-y-2">
                <Label>Add Internal Note</Label>
                <Textarea
                  value={verificationNotes}
                  onChange={(e) => setVerificationNotes(e.target.value)}
                  placeholder="Add internal verification notes (not visible to user)"
                  rows={3}
                />
                <Button
                  onClick={() => addNoteMutation.mutate(verificationNotes)}
                  disabled={!verificationNotes.trim() || addNoteMutation.isPending}
                  size="sm"
                >
                  <Send className="mr-2 h-4 w-4" />
                  Add Note
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <DialogFooter className="flex-col sm:flex-row gap-2">
        <div className="flex-1 space-y-2">
          {/* Rejection Section */}
          <div className="flex gap-2">
            <Select value={selectedTemplate} onValueChange={handleTemplateSelect}>
              <SelectTrigger className="w-[200px]">
                <SelectValue placeholder="Rejection template" />
              </SelectTrigger>
              <SelectContent>
                {templates.map((template: any) => (
                  <SelectItem key={template.id} value={template.id.toString()}>
                    {template.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input
              placeholder="Rejection reason"
              value={rejectionReason}
              onChange={(e) => setRejectionReason(e.target.value)}
              className="flex-1"
            />
          </div>

          {/* Resubmission Instructions */}
          <Input
            placeholder="Request resubmission with instructions"
            value={resubmissionInstructions}
            onChange={(e) => setResubmissionInstructions(e.target.value)}
          />
        </div>

        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => handleRequestResubmission()}
            disabled={resubmitRequestMutation.isPending || !resubmissionInstructions.trim()}
          >
            <AlertCircle className="mr-2 h-4 w-4" />
            Request Resubmission
          </Button>
          <Button
            variant="destructive"
            onClick={handleReject}
            disabled={rejectMutation.isPending || !rejectionReason.trim()}
          >
            <XCircle className="mr-2 h-4 w-4" />
            {rejectMutation.isPending ? 'Rejecting...' : 'Reject'}
          </Button>
          <Button
            variant="default"
            onClick={handleApprove}
            disabled={approveMutation.isPending || !isChecklistComplete}
          >
            <CheckCircle className="mr-2 h-4 w-4" />
            {approveMutation.isPending ? 'Approving...' : 'Approve'}
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  );
}
