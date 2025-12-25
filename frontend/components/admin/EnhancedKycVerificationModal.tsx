// V-KYC-ENHANCEMENT-002 | Enhanced KYC Verification Modal
// V-FIX-LAYOUT-SIZE | 2025-12-23 | Updated to max-w-[95vw] for full-screen immersive verification
// V-FIX-API-URL | Fixed rejection-templates endpoint

'use client';

import { useState } from 'react';
import {
  Dialog,
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
  const [documentBlobUrl, setDocumentBlobUrl] = useState<string>('');

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
        const firstDoc = res.data.documents[0];
        setSelectedDocument(firstDoc);
        // Fetch the document image with authentication
        await loadDocumentImage(firstDoc);
      }
      return res.data;
    },
  });

  // Function to load document image with authentication
  const loadDocumentImage = async (doc: any) => {
    try {
      // Revoke previous blob URL to prevent memory leaks
      if (documentBlobUrl) {
        URL.revokeObjectURL(documentBlobUrl);
      }

      const response = await api.get(`/user/kyc-documents/${doc.id}/view`, {
        responseType: 'blob',
      });

      const blobUrl = URL.createObjectURL(response.data);
      setDocumentBlobUrl(blobUrl);
    } catch (error) {
      console.error('Failed to load document:', error);
      toast.error('Failed to load document preview');
    }
  };

  // Fetch rejection templates
  const { data: templates = [] } = useQuery({
    queryKey: ['kycRejectionTemplates'],
    // [FIX]: Corrected URL to match backend controller route
    queryFn: async () => (await api.get('/admin/kyc-queue/rejection-templates')).data.data,
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
    onError: (error: any) => {
        // Show specific validation error if available
        const msg = error.response?.data?.message || 'Failed to add note';
        toast.error(msg);
    }
  });

  // Approve KYC
  const approveMutation = useMutation({
    mutationFn: () =>
      api.post(`/admin/kyc-queue/${kycId}/approve`, {
        verification_checklist: checklist,
        // Only include notes if there's actual content (not empty string)
        ...(verificationNotes?.trim() ? { notes: verificationNotes.trim() } : {}),
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
    onError: (error: any) => {
        toast.error('Failed to request resubmission', { description: error.response?.data?.message });
    }
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
    if (!rejectionReason.trim() || rejectionReason.length < 10) {
      toast.error('Rejection reason must be at least 10 characters');
      return;
    }
    rejectMutation.mutate(rejectionReason);
  };

  const handleRequestResubmission = () => {
    // [FIX]: Added validation check on frontend for 10 char limit
    const instruction = resubmissionInstructions || rejectionReason;
    
    if (!instruction.trim() || instruction.length < 10) {
      toast.error('Resubmission instructions must be at least 10 characters');
      return;
    }
    resubmitRequestMutation.mutate(instruction);
  };

  const handleTemplateSelect = (templateId: string) => {
    const template = templates.find((t: any) => t.id.toString() === templateId);
    if (template) {
      setRejectionReason(template.reason); // Pre-fill text box
      setResubmissionInstructions(template.reason); // Also pre-fill resubmit
      setSelectedTemplate(templateId);
    }
  };

  const isChecklistComplete = Object.values(checklist).every((v) => v);

  if (isLoading) {
    return (
      <Dialog open={true} onOpenChange={(open) => !open && onClose()}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Loading KYC Details...</DialogTitle>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    );
  }

  return (
    <Dialog open={true} onOpenChange={(open) => !open && onClose()}>
      {/* [FIX]: Updated classNames to use full viewport width/height (95vw/95vh) */}
      <DialogContent className="!max-w-[95vw] w-full h-[95vh] flex flex-col p-0 overflow-hidden">
      <DialogHeader className="px-6 py-4 border-b">
        <DialogTitle className="flex items-center gap-2">
          Review KYC: {kyc.user.username}
          {(kyc.status === 'submitted' || kyc.status === 'processing') && (
            <Badge variant="outline" className="ml-2">
              Pending Review
            </Badge>
          )}
        </DialogTitle>
        <DialogDescription>
          Submitted at: {new Date(kyc.submitted_at).toLocaleString()} | User ID: {kyc.user.id}
        </DialogDescription>
      </DialogHeader>

      <Tabs value={activeTab} onValueChange={setActiveTab} className="flex-1 flex flex-col overflow-hidden">
        <div className="px-6 pt-2 border-b bg-muted/20">
          <TabsList className="grid w-full grid-cols-4 max-w-2xl">
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
        </div>

        {/* Content Area */}
        <div className="flex-1 overflow-hidden p-6 bg-slate-50 dark:bg-slate-950/50">
          
          {/* Documents Tab */}
          <TabsContent value="documents" className="h-full mt-0">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
              {/* Document List */}
              <Card className="flex flex-col overflow-hidden h-full">
                <CardContent className="flex-1 overflow-y-auto p-4 space-y-2">
                  <h3 className="font-semibold mb-3">Uploaded Documents</h3>
                  {kyc.documents.map((doc: any) => (
                    <div
                      key={doc.id}
                      className={`p-3 border rounded-lg cursor-pointer hover:bg-accent transition ${
                        selectedDocument?.id === doc.id ? 'bg-accent border-primary ring-1 ring-primary' : ''
                      }`}
                      onClick={() => {
                        setSelectedDocument(doc);
                        setDocumentZoom(100);
                        setDocumentRotation(0);
                        loadDocumentImage(doc);
                      }}
                    >
                      <div className="flex justify-between items-start">
                        <div className="font-medium capitalize">{doc.doc_type.replace(/_/g, ' ')}</div>
                        <Badge
                          variant={
                            doc.status === 'approved'
                              ? 'default'
                              : doc.status === 'rejected'
                              ? 'destructive'
                              : 'secondary'
                          }
                        >
                          {doc.status || 'pending'}
                        </Badge>
                      </div>
                      <div className="text-xs text-muted-foreground mt-1 truncate" title={doc.file_name}>
                        {doc.file_name}
                      </div>
                    </div>
                  ))}
                </CardContent>
              </Card>

              {/* Document Viewer - Takes 2 Columns */}
              <Card className="col-span-1 lg:col-span-2 flex flex-col overflow-hidden h-full border-2 border-dashed">
                <div className="p-2 border-b bg-muted/40 flex justify-between items-center">
                   <h3 className="font-semibold capitalize px-2">
                      {selectedDocument ? selectedDocument.doc_type.replace(/_/g, ' ') : 'Preview'}
                   </h3>
                   <div className="flex gap-1">
                      <Button size="icon" variant="ghost" onClick={() => setDocumentZoom(Math.max(50, documentZoom - 25))}>
                        <ZoomOut className="h-4 w-4" />
                      </Button>
                      <span className="flex items-center text-xs font-mono w-12 justify-center">{documentZoom}%</span>
                      <Button size="icon" variant="ghost" onClick={() => setDocumentZoom(Math.min(200, documentZoom + 25))}>
                        <ZoomIn className="h-4 w-4" />
                      </Button>
                      <Button size="icon" variant="ghost" onClick={() => setDocumentRotation((documentRotation + 90) % 360)}>
                        <RotateCw className="h-4 w-4" />
                      </Button>
                   </div>
                </div>
                
                <CardContent className="flex-1 overflow-auto bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-8">
                  {selectedDocument ? (
                    <div
                      style={{
                        transform: `scale(${documentZoom / 100}) rotate(${documentRotation}deg)`,
                        transition: 'transform 0.3s ease',
                        transformOrigin: 'center center'
                      }}
                      className="shadow-2xl"
                    >
                      {selectedDocument.file_path.endsWith('.pdf') || selectedDocument.mime_type === 'application/pdf' ? (
                        <div className="text-center bg-white p-10 rounded-lg shadow">
                          <FileText className="h-16 w-16 mx-auto mb-4 text-primary" />
                          {documentBlobUrl ? (
                            <a
                              href={documentBlobUrl}
                              target="_blank"
                              rel="noreferrer"
                              className="text-blue-500 underline hover:text-blue-700 block"
                            >
                              Open PDF Document
                            </a>
                          ) : (
                            <p className="text-muted-foreground">Loading PDF...</p>
                          )}
                          <p className="text-xs text-muted-foreground mt-2">PDF previews open in new tab</p>
                        </div>
                      ) : documentBlobUrl ? (
                        <img
                          src={documentBlobUrl}
                          alt={selectedDocument.doc_type}
                          className="max-w-none object-contain rounded bg-white"
                          style={{ maxHeight: '70vh', maxWidth: '100%' }}
                        />
                      ) : (
                        <div className="text-center text-muted-foreground">
                          <p>Loading document...</p>
                        </div>
                      )}
                    </div>
                  ) : (
                    <div className="text-center text-muted-foreground">
                      <FileText className="h-16 w-16 mx-auto mb-4 opacity-20" />
                      <p>Select a document to view</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Details Tab (Same as before but flexible height) */}
          <TabsContent value="details" className="h-full overflow-y-auto mt-0">
             <Card>
              <CardContent className="pt-6">
                <div className="grid grid-cols-2 gap-8">
                  <div>
                    <h3 className="font-semibold mb-4 text-lg border-b pb-2">Personal Information</h3>
                    <div className="space-y-4">
                      <div><Label className="text-muted-foreground">Full Name</Label><p className="text-lg font-medium">{kyc.user.username}</p></div>
                      <div><Label className="text-muted-foreground">Email</Label><p className="font-medium">{kyc.user.email}</p></div>
                      <div><Label className="text-muted-foreground">PAN Number</Label><p className="font-mono bg-muted inline-block px-2 py-1 rounded">{kyc.pan_number}</p></div>
                      <div><Label className="text-muted-foreground">Aadhaar Number</Label><p className="font-mono bg-muted inline-block px-2 py-1 rounded">{kyc.aadhaar_number || 'Not provided'}</p></div>
                    </div>
                  </div>
                  <div>
                    <h3 className="font-semibold mb-4 text-lg border-b pb-2">Financial Information</h3>
                    <div className="space-y-4">
                      <div><Label className="text-muted-foreground">Bank Account</Label><p className="font-mono text-lg">{kyc.bank_account}</p></div>
                      <div><Label className="text-muted-foreground">IFSC Code</Label><p className="font-mono">{kyc.bank_ifsc}</p></div>
                      <div><Label className="text-muted-foreground">Bank Name</Label><p className="font-medium">{kyc.bank_name || 'Deriving from IFSC...'}</p></div>
                      <div><Label className="text-muted-foreground">Demat Account</Label><p className="font-mono">{kyc.demat_account || 'Not provided'}</p></div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Checklist Tab */}
          <TabsContent value="checklist" className="h-full overflow-y-auto mt-0 max-w-3xl mx-auto">
             {/* ... Checklist content (same as before) ... */}
              <div className="space-y-4 border p-4 rounded-lg bg-white dark:bg-card">
                  {[
                    { id: 'photo_quality', label: 'Photo is clear & visible' },
                    { id: 'document_legible', label: 'Text is legible & valid' },
                    { id: 'details_match', label: 'Details match user profile' },
                    { id: 'not_expired', label: 'Document is not expired' },
                    { id: 'no_tampering', label: 'No signs of tampering' },
                  ].map((item) => (
                    <div key={item.id} className="flex items-center space-x-3 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded">
                      <Checkbox
                        id={item.id}
                        checked={(checklist as any)[item.id]}
                        onCheckedChange={(checked) =>
                          setChecklist({ ...checklist, [item.id]: checked === true })
                        }
                      />
                      <Label htmlFor={item.id} className="cursor-pointer flex-1">{item.label}</Label>
                    </div>
                  ))}
                </div>
          </TabsContent>

          {/* Notes Tab */}
          <TabsContent value="notes" className="h-full overflow-y-auto mt-0 max-w-3xl mx-auto">
            <Card className="h-full flex flex-col">
              <CardContent className="flex-1 p-6 flex flex-col gap-4">
                <div className="flex-1 overflow-y-auto space-y-4 pr-2">
                  {kyc.verification_notes?.map((note: any, index: number) => (
                    <div key={index} className="flex gap-3">
                       <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0 text-xs font-bold">
                          {note.admin_name?.[0] || 'A'}
                       </div>
                       <div className="flex-1 bg-muted p-3 rounded-lg rounded-tl-none">
                          <div className="flex justify-between items-center mb-1">
                             <span className="font-semibold text-sm">{note.admin_name}</span>
                             <span className="text-xs text-muted-foreground">{new Date(note.created_at).toLocaleString()}</span>
                          </div>
                          <p className="text-sm">{note.note}</p>
                       </div>
                    </div>
                  ))}
                  {!kyc.verification_notes?.length && (
                    <div className="text-center text-muted-foreground py-10 italic">
                       No notes added to this application yet.
                    </div>
                  )}
                </div>

                <div className="pt-4 border-t mt-auto">
                  <Label className="mb-2 block">Add Internal Note</Label>
                  <div className="flex gap-3">
                    <Textarea
                      value={verificationNotes}
                      onChange={(e) => setVerificationNotes(e.target.value)}
                      placeholder="Add internal verification notes..."
                      className="resize-none"
                      rows={3}
                    />
                    <Button
                      onClick={() => addNoteMutation.mutate(verificationNotes)}
                      disabled={!verificationNotes.trim() || addNoteMutation.isPending}
                      className="h-auto"
                    >
                      <Send className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </div>
      </Tabs>

      <DialogFooter className="px-6 py-4 border-t bg-muted/10 gap-2 sm:gap-0">
        <div className="flex-1 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
          {/* Left Side: Rejection Controls */}
          <div className="flex-1 flex gap-2 w-full sm:w-auto">
            <Select value={selectedTemplate} onValueChange={handleTemplateSelect}>
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Quick Template..." />
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
              placeholder="Rejection reason / Instructions..."
              value={rejectionReason || resubmissionInstructions}
              onChange={(e) => {
                setRejectionReason(e.target.value);
                setResubmissionInstructions(e.target.value);
              }}
              className="flex-1 min-w-[200px]"
            />
          </div>

          {/* Right Side: Action Buttons */}
          <div className="flex gap-2 w-full sm:w-auto justify-end">
            <Button
              variant="outline"
              onClick={handleRequestResubmission}
              disabled={resubmitRequestMutation.isPending}
            >
              <AlertCircle className="mr-2 h-4 w-4" />
              Request Changes
            </Button>
            <Button
              variant="destructive"
              onClick={handleReject}
              disabled={rejectMutation.isPending}
            >
              <XCircle className="mr-2 h-4 w-4" />
              Reject
            </Button>
            <Button
              variant="default"
              className="bg-green-600 hover:bg-green-700"
              onClick={handleApprove}
              disabled={approveMutation.isPending || !isChecklistComplete}
            >
              <CheckCircle className="mr-2 h-4 w-4" />
              Approve
            </Button>
          </div>
        </div>
      </DialogFooter>
    </DialogContent>
    </Dialog>
  );
}