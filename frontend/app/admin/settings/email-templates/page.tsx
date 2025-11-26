// Email Template Management Page
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import DOMPurify from 'dompurify';
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { Edit, Mail, Eye, Save, Info, Code } from "lucide-react";

interface EmailTemplate {
  id: number;
  name: string;
  slug: string;
  subject: string;
  body: string;
  variables: string[] | null;
  created_at: string;
  updated_at: string;
}

export default function EmailTemplatesPage() {
  const queryClient = useQueryClient();
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState<EmailTemplate | null>(null);
  const [previewMode, setPreviewMode] = useState(false);

  // Form state
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');

  const { data: templates, isLoading } = useQuery({
    queryKey: ['emailTemplates'],
    queryFn: async () => (await api.get('/admin/email-templates')).data as EmailTemplate[],
  });

  const mutation = useMutation({
    mutationFn: (data: { subject: string; body: string }) =>
      api.put(`/admin/email-templates/${editingTemplate?.id}`, data),
    onSuccess: () => {
      toast.success("Email Template Updated");
      queryClient.invalidateQueries({ queryKey: ['emailTemplates'] });
      setIsDialogOpen(false);
      setEditingTemplate(null);
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message })
  });

  const handleEdit = (template: EmailTemplate) => {
    setEditingTemplate(template);
    setSubject(template.subject);
    setBody(template.body);
    setPreviewMode(false);
    setIsDialogOpen(true);
  };

  const handleSave = () => {
    mutation.mutate({ subject, body });
  };

  // Parse variables from template body
  const getVariables = (template: EmailTemplate): string[] => {
    if (template.variables) return template.variables;
    const matches = template.body.match(/\{\{\s*\$?[\w.]+\s*\}\}/g) || [];
    return [...new Set(matches)];
  };

  // Preview with sample data
  const getPreviewHtml = () => {
    let preview = body;
    const sampleData: Record<string, string> = {
      '{{ $user->name }}': 'John Doe',
      '{{ $user->email }}': 'john@example.com',
      '{{ $user->username }}': 'johndoe',
      '{{ $amount }}': '₹5,000',
      '{{ $payment->amount }}': '₹5,000',
      '{{ $plan->name }}': 'Premium Plan',
      '{{ $subscription->plan->name }}': 'Premium Plan',
      '{{ $status }}': 'Approved',
      '{{ $reason }}': 'All documents verified successfully',
      '{{ $date }}': new Date().toLocaleDateString(),
      '{{ $link }}': 'https://preiposip.com/verify',
      '{{ $otp }}': '123456',
      '{{ $token }}': 'abc123xyz',
    };

    Object.entries(sampleData).forEach(([key, value]) => {
      preview = preview.replace(new RegExp(key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), value);
    });

    return preview;
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">Email Templates</h1>
          <p className="text-muted-foreground">Manage email templates for system notifications</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Available Templates</CardTitle>
          <CardDescription>
            Edit email subjects and body content. Use variables like {'{{ $user->name }}'} for dynamic content.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-center py-4">Loading templates...</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Template Name</TableHead>
                  <TableHead>Subject</TableHead>
                  <TableHead>Variables</TableHead>
                  <TableHead>Last Updated</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {templates?.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                      No email templates found. Templates are created during system setup.
                    </TableCell>
                  </TableRow>
                )}
                {templates?.map((template) => (
                  <TableRow key={template.id}>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <Mail className="h-4 w-4 text-muted-foreground" />
                        <div>
                          <div className="font-medium">{template.name}</div>
                          <div className="text-xs text-muted-foreground">{template.slug}</div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell className="max-w-[200px] truncate">{template.subject}</TableCell>
                    <TableCell>
                      <div className="flex flex-wrap gap-1">
                        {getVariables(template).slice(0, 3).map((v, i) => (
                          <Badge key={i} variant="secondary" className="text-xs">
                            {v}
                          </Badge>
                        ))}
                        {getVariables(template).length > 3 && (
                          <Badge variant="outline" className="text-xs">
                            +{getVariables(template).length - 3}
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">
                      {new Date(template.updated_at).toLocaleDateString()}
                    </TableCell>
                    <TableCell>
                      <Button variant="outline" size="sm" onClick={() => handleEdit(template)}>
                        <Edit className="h-4 w-4 mr-1" /> Edit
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Variable Reference Card */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Code className="h-5 w-5" />
            Available Variables
          </CardTitle>
          <CardDescription>Use these variables in your email templates</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <p className="font-medium mb-2">User Data</p>
              <ul className="space-y-1 text-muted-foreground">
                <li><code>{'{{ $user->name }}'}</code></li>
                <li><code>{'{{ $user->email }}'}</code></li>
                <li><code>{'{{ $user->username }}'}</code></li>
              </ul>
            </div>
            <div>
              <p className="font-medium mb-2">Payment Data</p>
              <ul className="space-y-1 text-muted-foreground">
                <li><code>{'{{ $payment->amount }}'}</code></li>
                <li><code>{'{{ $plan->name }}'}</code></li>
                <li><code>{'{{ $status }}'}</code></li>
              </ul>
            </div>
            <div>
              <p className="font-medium mb-2">Verification</p>
              <ul className="space-y-1 text-muted-foreground">
                <li><code>{'{{ $otp }}'}</code></li>
                <li><code>{'{{ $link }}'}</code></li>
                <li><code>{'{{ $token }}'}</code></li>
              </ul>
            </div>
            <div>
              <p className="font-medium mb-2">General</p>
              <ul className="space-y-1 text-muted-foreground">
                <li><code>{'{{ $date }}'}</code></li>
                <li><code>{'{{ $reason }}'}</code></li>
                <li><code>{'{{ $amount }}'}</code></li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Edit Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) setEditingTemplate(null); }}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Edit Template: {editingTemplate?.name}</DialogTitle>
            <DialogDescription>
              Modify the email subject and body content. Variables will be replaced with actual data when sent.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div className="flex gap-2">
              <Button
                variant={previewMode ? "outline" : "default"}
                size="sm"
                onClick={() => setPreviewMode(false)}
              >
                <Edit className="h-4 w-4 mr-1" /> Edit
              </Button>
              <Button
                variant={previewMode ? "default" : "outline"}
                size="sm"
                onClick={() => setPreviewMode(true)}
              >
                <Eye className="h-4 w-4 mr-1" /> Preview
              </Button>
            </div>

            {!previewMode ? (
              <>
                <div className="space-y-2">
                  <Label>Subject Line</Label>
                  <Input
                    value={subject}
                    onChange={(e) => setSubject(e.target.value)}
                    placeholder="Email subject..."
                  />
                </div>

                <div className="space-y-2">
                  <Label>Email Body (HTML)</Label>
                  <Textarea
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    placeholder="Email body content..."
                    rows={15}
                    className="font-mono text-sm"
                  />
                </div>

                {editingTemplate && (
                  <div className="p-3 bg-muted rounded-lg">
                    <p className="text-sm font-medium flex items-center gap-2">
                      <Info className="h-4 w-4" />
                      Available Variables for this template:
                    </p>
                    <div className="flex flex-wrap gap-1 mt-2">
                      {getVariables(editingTemplate).map((v, i) => (
                        <Badge key={i} variant="secondary" className="text-xs font-mono">
                          {v}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
              </>
            ) : (
              <div className="space-y-4">
                <div className="p-3 bg-muted rounded-lg">
                  <p className="text-sm font-medium">Subject Preview:</p>
                  <p className="mt-1">{subject}</p>
                </div>
                <div className="border rounded-lg p-4 bg-white">
                  <p className="text-sm font-medium mb-2 text-muted-foreground">Body Preview (with sample data):</p>
                  <div
                    className="prose prose-sm max-w-none"
                    dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(getPreviewHtml()) }}
                  />
                </div>
              </div>
            )}

            <div className="flex justify-end gap-2 pt-4 border-t">
              <Button variant="ghost" onClick={() => setIsDialogOpen(false)}>Cancel</Button>
              <Button onClick={handleSave} disabled={mutation.isPending}>
                <Save className="mr-2 h-4 w-4" />
                {mutation.isPending ? 'Saving...' : 'Save Changes'}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
