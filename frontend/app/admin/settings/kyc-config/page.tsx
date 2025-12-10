// V-KYC-ENHANCEMENT-001 | KYC Document Type Configuration
// Created: 2025-12-10 | Purpose: Configure KYC document types and auto-verification settings

'use client';

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Plus, Pencil, Trash2, FileText, Settings, ShieldCheck, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import api from '@/lib/api';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface DocumentType {
  id: number;
  name: string;
  code: string;
  description?: string;
  is_required: boolean;
  is_active: boolean;
  max_file_size_mb: number;
  allowed_formats: string[];
  requires_ocr: boolean;
  requires_manual_verification: boolean;
  display_order: number;
}

interface AutoVerificationConfig {
  pan_api_enabled: boolean;
  pan_api_provider: string;
  pan_api_key?: string;
  aadhaar_api_enabled: boolean;
  aadhaar_api_provider: string;
  aadhaar_api_key?: string;
  bank_api_enabled: boolean;
  bank_api_provider: string;
  bank_api_key?: string;
  digilocker_enabled: boolean;
  digilocker_client_id?: string;
  digilocker_client_secret?: string;
  auto_approve_on_verification: boolean;
}

export default function KycConfigPage() {
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingDocType, setEditingDocType] = useState<DocumentType | null>(null);

  const [docTypeForm, setDocTypeForm] = useState({
    name: '',
    code: '',
    description: '',
    is_required: true,
    is_active: true,
    max_file_size_mb: 5,
    allowed_formats: ['jpg', 'jpeg', 'png', 'pdf'],
    requires_ocr: false,
    requires_manual_verification: true,
  });

  const [autoVerifyConfig, setAutoVerifyConfig] = useState<AutoVerificationConfig>({
    pan_api_enabled: false,
    pan_api_provider: 'nsdl',
    aadhaar_api_enabled: false,
    aadhaar_api_provider: 'uidai',
    bank_api_enabled: false,
    bank_api_provider: 'penny_drop',
    digilocker_enabled: false,
    auto_approve_on_verification: false,
  });

  // Fetch document types
  const { data: documentTypes = [] } = useQuery<DocumentType[]>({
    queryKey: ['kycDocumentTypes'],
    queryFn: async () => (await api.get('/admin/kyc/document-types')).data.data,
  });

  // Fetch auto-verification config
  const { data: autoVerifyData } = useQuery({
    queryKey: ['kycAutoVerifyConfig'],
    queryFn: async () => {
      const res = await api.get('/admin/kyc/auto-verify-config');
      setAutoVerifyConfig(res.data.config || autoVerifyConfig);
      return res.data;
    },
  });

  // Document type mutations
  const createDocTypeMutation = useMutation({
    mutationFn: (data: any) => api.post('/admin/kyc/document-types', data),
    onSuccess: () => {
      toast.success('Document type created successfully');
      queryClient.invalidateQueries({ queryKey: ['kycDocumentTypes'] });
      setDialogOpen(false);
      resetDocTypeForm();
    },
  });

  const updateDocTypeMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      api.put(`/admin/kyc/document-types/${id}`, data),
    onSuccess: () => {
      toast.success('Document type updated successfully');
      queryClient.invalidateQueries({ queryKey: ['kycDocumentTypes'] });
      setDialogOpen(false);
      resetDocTypeForm();
    },
  });

  const deleteDocTypeMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/kyc/document-types/${id}`),
    onSuccess: () => {
      toast.success('Document type deleted successfully');
      queryClient.invalidateQueries({ queryKey: ['kycDocumentTypes'] });
    },
  });

  // Auto-verification config mutation
  const saveAutoVerifyMutation = useMutation({
    mutationFn: (config: AutoVerificationConfig) =>
      api.post('/admin/kyc/auto-verify-config', { config }),
    onSuccess: () => {
      toast.success('Auto-verification settings saved');
      queryClient.invalidateQueries({ queryKey: ['kycAutoVerifyConfig'] });
    },
  });

  const resetDocTypeForm = () => {
    setDocTypeForm({
      name: '',
      code: '',
      description: '',
      is_required: true,
      is_active: true,
      max_file_size_mb: 5,
      allowed_formats: ['jpg', 'jpeg', 'png', 'pdf'],
      requires_ocr: false,
      requires_manual_verification: true,
    });
    setEditingDocType(null);
  };

  const handleDocTypeSubmit = () => {
    if (editingDocType) {
      updateDocTypeMutation.mutate({ id: editingDocType.id, data: docTypeForm });
    } else {
      createDocTypeMutation.mutate(docTypeForm);
    }
  };

  const openEditDialog = (docType: DocumentType) => {
    setEditingDocType(docType);
    setDocTypeForm({
      name: docType.name,
      code: docType.code,
      description: docType.description || '',
      is_required: docType.is_required,
      is_active: docType.is_active,
      max_file_size_mb: docType.max_file_size_mb,
      allowed_formats: docType.allowed_formats,
      requires_ocr: docType.requires_ocr,
      requires_manual_verification: docType.requires_manual_verification,
    });
    setDialogOpen(true);
  };

  const openNewDialog = () => {
    resetDocTypeForm();
    setDialogOpen(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">KYC Configuration</h1>
          <p className="text-muted-foreground mt-1">
            Configure document types and auto-verification settings
          </p>
        </div>
      </div>

      <Tabs defaultValue="document_types" className="space-y-4">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="document_types">
            <FileText className="mr-2 h-4 w-4" />
            Document Types
          </TabsTrigger>
          <TabsTrigger value="auto_verify">
            <ShieldCheck className="mr-2 h-4 w-4" />
            Auto-Verification
          </TabsTrigger>
        </TabsList>

        {/* Document Types Tab */}
        <TabsContent value="document_types">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Document Types</CardTitle>
                  <CardDescription>
                    Configure which documents are required for KYC verification
                  </CardDescription>
                </div>
                <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                  <DialogTrigger asChild>
                    <Button onClick={openNewDialog}>
                      <Plus className="mr-2 h-4 w-4" />
                      Add Document Type
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="max-w-2xl">
                    <DialogHeader>
                      <DialogTitle>
                        {editingDocType ? 'Edit Document Type' : 'New Document Type'}
                      </DialogTitle>
                      <DialogDescription>
                        Configure document type settings and requirements
                      </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 max-h-[60vh] overflow-y-auto">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Document Name</Label>
                          <Input
                            value={docTypeForm.name}
                            onChange={(e) =>
                              setDocTypeForm({ ...docTypeForm, name: e.target.value })
                            }
                            placeholder="PAN Card"
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Document Code</Label>
                          <Input
                            value={docTypeForm.code}
                            onChange={(e) =>
                              setDocTypeForm({ ...docTypeForm, code: e.target.value })
                            }
                            placeholder="pan_card"
                          />
                        </div>
                      </div>

                      <div className="space-y-2">
                        <Label>Description (Optional)</Label>
                        <Input
                          value={docTypeForm.description}
                          onChange={(e) =>
                            setDocTypeForm({ ...docTypeForm, description: e.target.value })
                          }
                          placeholder="Official PAN card issued by Income Tax Department"
                        />
                      </div>

                      <div className="space-y-2">
                        <Label>Max File Size (MB)</Label>
                        <Input
                          type="number"
                          value={docTypeForm.max_file_size_mb}
                          onChange={(e) =>
                            setDocTypeForm({
                              ...docTypeForm,
                              max_file_size_mb: parseInt(e.target.value),
                            })
                          }
                          min={1}
                          max={50}
                        />
                      </div>

                      <div className="space-y-2">
                        <Label>Allowed Formats (comma-separated)</Label>
                        <Input
                          value={docTypeForm.allowed_formats.join(', ')}
                          onChange={(e) =>
                            setDocTypeForm({
                              ...docTypeForm,
                              allowed_formats: e.target.value.split(',').map((s) => s.trim()),
                            })
                          }
                          placeholder="jpg, jpeg, png, pdf"
                        />
                      </div>

                      <div className="space-y-4 border-t pt-4">
                        <div className="flex items-center justify-between">
                          <div>
                            <Label>Required Document</Label>
                            <p className="text-xs text-muted-foreground">
                              Users must upload this document to complete KYC
                            </p>
                          </div>
                          <Switch
                            checked={docTypeForm.is_required}
                            onCheckedChange={(checked) =>
                              setDocTypeForm({ ...docTypeForm, is_required: checked })
                            }
                          />
                        </div>

                        <div className="flex items-center justify-between">
                          <div>
                            <Label>Active</Label>
                            <p className="text-xs text-muted-foreground">
                              Show this document type to users
                            </p>
                          </div>
                          <Switch
                            checked={docTypeForm.is_active}
                            onCheckedChange={(checked) =>
                              setDocTypeForm({ ...docTypeForm, is_active: checked })
                            }
                          />
                        </div>

                        <div className="flex items-center justify-between">
                          <div>
                            <Label>Requires OCR</Label>
                            <p className="text-xs text-muted-foreground">
                              Extract text from document automatically
                            </p>
                          </div>
                          <Switch
                            checked={docTypeForm.requires_ocr}
                            onCheckedChange={(checked) =>
                              setDocTypeForm({ ...docTypeForm, requires_ocr: checked })
                            }
                          />
                        </div>

                        <div className="flex items-center justify-between">
                          <div>
                            <Label>Requires Manual Verification</Label>
                            <p className="text-xs text-muted-foreground">
                              Admin must manually review this document
                            </p>
                          </div>
                          <Switch
                            checked={docTypeForm.requires_manual_verification}
                            onCheckedChange={(checked) =>
                              setDocTypeForm({
                                ...docTypeForm,
                                requires_manual_verification: checked,
                              })
                            }
                          />
                        </div>
                      </div>
                    </div>

                    <DialogFooter>
                      <Button variant="outline" onClick={() => setDialogOpen(false)}>
                        Cancel
                      </Button>
                      <Button
                        onClick={handleDocTypeSubmit}
                        disabled={
                          createDocTypeMutation.isPending || updateDocTypeMutation.isPending
                        }
                      >
                        {editingDocType ? 'Update' : 'Create'}
                      </Button>
                    </DialogFooter>
                  </DialogContent>
                </Dialog>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Name</TableHead>
                    <TableHead>Code</TableHead>
                    <TableHead>Required</TableHead>
                    <TableHead>Max Size</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {documentTypes.map((docType) => (
                    <TableRow key={docType.id}>
                      <TableCell>
                        <div>
                          <div className="font-medium">{docType.name}</div>
                          {docType.description && (
                            <div className="text-xs text-muted-foreground">
                              {docType.description}
                            </div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="font-mono text-xs">{docType.code}</TableCell>
                      <TableCell>
                        <Badge variant={docType.is_required ? 'destructive' : 'secondary'}>
                          {docType.is_required ? 'Required' : 'Optional'}
                        </Badge>
                      </TableCell>
                      <TableCell>{docType.max_file_size_mb} MB</TableCell>
                      <TableCell>
                        <Badge variant={docType.is_active ? 'default' : 'secondary'}>
                          {docType.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => openEditDialog(docType)}
                          >
                            <Pencil className="h-3 w-3" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => deleteDocTypeMutation.mutate(docType.id)}
                          >
                            <Trash2 className="h-3 w-3" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Auto-Verification Tab */}
        <TabsContent value="auto_verify">
          <div className="space-y-4">
            <Alert>
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                Configure API credentials for automatic document verification. Ensure you have
                valid API keys from the respective providers.
              </AlertDescription>
            </Alert>

            {/* PAN Verification */}
            <Card>
              <CardHeader>
                <CardTitle>PAN Card Verification</CardTitle>
                <CardDescription>Auto-verify PAN cards using NSDL or other APIs</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <Label>Enable PAN Auto-Verification</Label>
                  <Switch
                    checked={autoVerifyConfig.pan_api_enabled}
                    onCheckedChange={(checked) =>
                      setAutoVerifyConfig({ ...autoVerifyConfig, pan_api_enabled: checked })
                    }
                  />
                </div>

                {autoVerifyConfig.pan_api_enabled && (
                  <>
                    <div className="space-y-2">
                      <Label>API Provider</Label>
                      <Select
                        value={autoVerifyConfig.pan_api_provider}
                        onValueChange={(value) =>
                          setAutoVerifyConfig({ ...autoVerifyConfig, pan_api_provider: value })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="nsdl">NSDL</SelectItem>
                          <SelectItem value="karza">Karza</SelectItem>
                          <SelectItem value="signzy">Signzy</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label>API Key</Label>
                      <Input
                        type="password"
                        value={autoVerifyConfig.pan_api_key || ''}
                        onChange={(e) =>
                          setAutoVerifyConfig({ ...autoVerifyConfig, pan_api_key: e.target.value })
                        }
                        placeholder="Enter your API key"
                      />
                    </div>
                  </>
                )}
              </CardContent>
            </Card>

            {/* Aadhaar Verification */}
            <Card>
              <CardHeader>
                <CardTitle>Aadhaar Verification</CardTitle>
                <CardDescription>Auto-verify Aadhaar via UIDAI or DigiLocker</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <Label>Enable Aadhaar Auto-Verification</Label>
                  <Switch
                    checked={autoVerifyConfig.aadhaar_api_enabled}
                    onCheckedChange={(checked) =>
                      setAutoVerifyConfig({ ...autoVerifyConfig, aadhaar_api_enabled: checked })
                    }
                  />
                </div>

                {autoVerifyConfig.aadhaar_api_enabled && (
                  <>
                    <div className="space-y-2">
                      <Label>API Provider</Label>
                      <Select
                        value={autoVerifyConfig.aadhaar_api_provider}
                        onValueChange={(value) =>
                          setAutoVerifyConfig({ ...autoVerifyConfig, aadhaar_api_provider: value })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="uidai">UIDAI</SelectItem>
                          <SelectItem value="digilocker">DigiLocker</SelectItem>
                          <SelectItem value="karza">Karza</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label>API Key</Label>
                      <Input
                        type="password"
                        value={autoVerifyConfig.aadhaar_api_key || ''}
                        onChange={(e) =>
                          setAutoVerifyConfig({
                            ...autoVerifyConfig,
                            aadhaar_api_key: e.target.value,
                          })
                        }
                        placeholder="Enter your API key"
                      />
                    </div>
                  </>
                )}
              </CardContent>
            </Card>

            {/* Bank Account Verification */}
            <Card>
              <CardHeader>
                <CardTitle>Bank Account Verification</CardTitle>
                <CardDescription>
                  Verify bank accounts using penny drop or account verification APIs
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <Label>Enable Bank Auto-Verification</Label>
                  <Switch
                    checked={autoVerifyConfig.bank_api_enabled}
                    onCheckedChange={(checked) =>
                      setAutoVerifyConfig({ ...autoVerifyConfig, bank_api_enabled: checked })
                    }
                  />
                </div>

                {autoVerifyConfig.bank_api_enabled && (
                  <>
                    <div className="space-y-2">
                      <Label>API Provider</Label>
                      <Select
                        value={autoVerifyConfig.bank_api_provider}
                        onValueChange={(value) =>
                          setAutoVerifyConfig({ ...autoVerifyConfig, bank_api_provider: value })
                        }
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="penny_drop">Penny Drop</SelectItem>
                          <SelectItem value="razorpay">Razorpay Fund Account Validation</SelectItem>
                          <SelectItem value="karza">Karza</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label>API Key</Label>
                      <Input
                        type="password"
                        value={autoVerifyConfig.bank_api_key || ''}
                        onChange={(e) =>
                          setAutoVerifyConfig({ ...autoVerifyConfig, bank_api_key: e.target.value })
                        }
                        placeholder="Enter your API key"
                      />
                    </div>
                  </>
                )}
              </CardContent>
            </Card>

            {/* DigiLocker */}
            <Card>
              <CardHeader>
                <CardTitle>DigiLocker Integration</CardTitle>
                <CardDescription>Enable DigiLocker for instant Aadhaar verification</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <Label>Enable DigiLocker</Label>
                  <Switch
                    checked={autoVerifyConfig.digilocker_enabled}
                    onCheckedChange={(checked) =>
                      setAutoVerifyConfig({ ...autoVerifyConfig, digilocker_enabled: checked })
                    }
                  />
                </div>

                {autoVerifyConfig.digilocker_enabled && (
                  <>
                    <div className="space-y-2">
                      <Label>Client ID</Label>
                      <Input
                        value={autoVerifyConfig.digilocker_client_id || ''}
                        onChange={(e) =>
                          setAutoVerifyConfig({
                            ...autoVerifyConfig,
                            digilocker_client_id: e.target.value,
                          })
                        }
                        placeholder="DigiLocker Client ID"
                      />
                    </div>

                    <div className="space-y-2">
                      <Label>Client Secret</Label>
                      <Input
                        type="password"
                        value={autoVerifyConfig.digilocker_client_secret || ''}
                        onChange={(e) =>
                          setAutoVerifyConfig({
                            ...autoVerifyConfig,
                            digilocker_client_secret: e.target.value,
                          })
                        }
                        placeholder="DigiLocker Client Secret"
                      />
                    </div>
                  </>
                )}
              </CardContent>
            </Card>

            {/* General Settings */}
            <Card>
              <CardHeader>
                <CardTitle>General Verification Settings</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <div>
                    <Label>Auto-Approve on Verification</Label>
                    <p className="text-xs text-muted-foreground">
                      Automatically approve KYC if all auto-verifications pass
                    </p>
                  </div>
                  <Switch
                    checked={autoVerifyConfig.auto_approve_on_verification}
                    onCheckedChange={(checked) =>
                      setAutoVerifyConfig({
                        ...autoVerifyConfig,
                        auto_approve_on_verification: checked,
                      })
                    }
                  />
                </div>
              </CardContent>
            </Card>

            <div className="flex justify-end">
              <Button
                onClick={() => saveAutoVerifyMutation.mutate(autoVerifyConfig)}
                disabled={saveAutoVerifyMutation.isPending}
              >
                <Settings className="mr-2 h-4 w-4" />
                {saveAutoVerifyMutation.isPending ? 'Saving...' : 'Save Configuration'}
              </Button>
            </div>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}
