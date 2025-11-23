// V-FINAL-1730-201 | V-USER-COMPLIANCE-AGREEMENT
'use client';

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
  FileCheck, Shield, AlertTriangle, ChevronDown, ChevronUp,
  FileText, CheckCircle, Clock, ArrowRight
} from "lucide-react";

export default function ComplianceAgreementPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [acceptedDocs, setAcceptedDocs] = useState<Set<number>>(new Set());
  const [expandedDoc, setExpandedDoc] = useState<number | null>(null);

  // Fetch pending compliance documents that user needs to accept
  const { data: pendingDocuments, isLoading } = useQuery({
    queryKey: ['userPendingCompliance'],
    queryFn: async () => (await api.get('/user/compliance/pending')).data,
  });

  // Fetch all accepted documents for reference
  const { data: acceptedHistory } = useQuery({
    queryKey: ['userComplianceHistory'],
    queryFn: async () => (await api.get('/user/compliance/history')).data,
  });

  // Accept documents mutation
  const acceptMutation = useMutation({
    mutationFn: (documentIds: number[]) => api.post('/user/compliance/accept', { document_ids: documentIds }),
    onSuccess: () => {
      toast.success("Documents Accepted", {
        description: "Thank you for accepting the compliance documents."
      });
      queryClient.invalidateQueries({ queryKey: ['userPendingCompliance'] });
      queryClient.invalidateQueries({ queryKey: ['userComplianceHistory'] });
      // Redirect to dashboard after acceptance
      router.push('/dashboard');
    },
    onError: (e: any) => toast.error("Error", { description: e.response?.data?.message || "Failed to accept documents" })
  });

  const toggleAcceptance = (docId: number) => {
    const newAccepted = new Set(acceptedDocs);
    if (newAccepted.has(docId)) {
      newAccepted.delete(docId);
    } else {
      newAccepted.add(docId);
    }
    setAcceptedDocs(newAccepted);
  };

  const handleAcceptAll = () => {
    if (!pendingDocuments) return;
    const allIds = new Set(pendingDocuments.map((doc: any) => doc.id));
    setAcceptedDocs(allIds);
  };

  const handleSubmit = () => {
    if (!pendingDocuments || acceptedDocs.size !== pendingDocuments.length) {
      toast.error("Please accept all documents to continue");
      return;
    }
    acceptMutation.mutate(Array.from(acceptedDocs));
  };

  const allAccepted = pendingDocuments && acceptedDocs.size === pendingDocuments.length;

  // If no pending documents, redirect to dashboard
  useEffect(() => {
    if (!isLoading && (!pendingDocuments || pendingDocuments.length === 0)) {
      router.push('/dashboard');
    }
  }, [isLoading, pendingDocuments, router]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="text-center">
          <Shield className="h-12 w-12 mx-auto text-muted-foreground animate-pulse" />
          <p className="mt-4 text-muted-foreground">Loading compliance documents...</p>
        </div>
      </div>
    );
  }

  if (!pendingDocuments || pendingDocuments.length === 0) {
    return null; // Will redirect
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header Alert */}
      <Alert className="border-yellow-500/50 bg-yellow-500/10">
        <AlertTriangle className="h-4 w-4 text-yellow-500" />
        <AlertTitle className="text-yellow-600">Action Required</AlertTitle>
        <AlertDescription>
          Our compliance documents have been updated. Please review and accept the following documents to continue using the platform.
        </AlertDescription>
      </Alert>

      {/* Main Card */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="flex items-center gap-2">
                <Shield className="h-5 w-5" />
                Compliance Agreement
              </CardTitle>
              <CardDescription>
                Please review and accept the updated documents below.
              </CardDescription>
            </div>
            <Badge variant="secondary">
              {acceptedDocs.size} / {pendingDocuments.length} Accepted
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Document List */}
          {pendingDocuments.map((doc: any) => (
            <div
              key={doc.id}
              className={`border rounded-lg transition-all ${
                acceptedDocs.has(doc.id) ? 'border-green-500/50 bg-green-500/5' : ''
              }`}
            >
              {/* Document Header */}
              <div
                className="flex items-center justify-between p-4 cursor-pointer hover:bg-muted/50"
                onClick={() => setExpandedDoc(expandedDoc === doc.id ? null : doc.id)}
              >
                <div className="flex items-center gap-3">
                  <FileText className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <h3 className="font-medium">{doc.title}</h3>
                    <div className="flex items-center gap-2 mt-1">
                      <Badge variant="outline" className="text-xs">v{doc.version}</Badge>
                      {doc.is_updated && (
                        <Badge variant="secondary" className="text-xs bg-yellow-500/20 text-yellow-600">
                          Updated
                        </Badge>
                      )}
                      <span className="text-xs text-muted-foreground">
                        Effective: {new Date(doc.effective_date || doc.updated_at).toLocaleDateString()}
                      </span>
                    </div>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  {acceptedDocs.has(doc.id) && (
                    <CheckCircle className="h-5 w-5 text-green-500" />
                  )}
                  {expandedDoc === doc.id ? (
                    <ChevronUp className="h-4 w-4 text-muted-foreground" />
                  ) : (
                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                  )}
                </div>
              </div>

              {/* Document Content (Expandable) */}
              {expandedDoc === doc.id && (
                <div className="border-t">
                  <ScrollArea className="h-64 p-4">
                    <div className="prose prose-sm dark:prose-invert max-w-none">
                      <div className="whitespace-pre-wrap text-sm text-muted-foreground">
                        {doc.content}
                      </div>
                    </div>
                  </ScrollArea>
                </div>
              )}

              {/* Acceptance Checkbox */}
              <div className="flex items-center gap-3 p-4 bg-muted/30 border-t">
                <Checkbox
                  id={`accept-${doc.id}`}
                  checked={acceptedDocs.has(doc.id)}
                  onCheckedChange={() => toggleAcceptance(doc.id)}
                />
                <label
                  htmlFor={`accept-${doc.id}`}
                  className="text-sm cursor-pointer flex-1"
                >
                  I have read and agree to the <span className="font-medium">{doc.title}</span>
                </label>
              </div>
            </div>
          ))}

          {/* Accept All Button */}
          {pendingDocuments.length > 1 && (
            <Button
              variant="outline"
              className="w-full"
              onClick={handleAcceptAll}
              disabled={allAccepted}
            >
              <FileCheck className="mr-2 h-4 w-4" />
              {allAccepted ? 'All Documents Accepted' : 'Accept All Documents'}
            </Button>
          )}
        </CardContent>
        <Separator />
        <CardFooter className="flex items-center justify-between pt-6">
          <p className="text-sm text-muted-foreground">
            By accepting, you agree to be bound by these terms.
          </p>
          <Button
            onClick={handleSubmit}
            disabled={!allAccepted || acceptMutation.isPending}
            className="min-w-32"
          >
            {acceptMutation.isPending ? (
              "Processing..."
            ) : (
              <>
                Continue <ArrowRight className="ml-2 h-4 w-4" />
              </>
            )}
          </Button>
        </CardFooter>
      </Card>

      {/* Previously Accepted Documents */}
      {acceptedHistory && acceptedHistory.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
              <Clock className="h-4 w-4" />
              Previously Accepted Documents
            </CardTitle>
            <CardDescription>Your compliance history for reference.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              {acceptedHistory.map((item: any) => (
                <div key={item.id} className="flex items-center justify-between p-3 bg-muted/30 rounded-lg">
                  <div className="flex items-center gap-3">
                    <CheckCircle className="h-4 w-4 text-green-500" />
                    <div>
                      <span className="font-medium text-sm">{item.document_title}</span>
                      <span className="text-xs text-muted-foreground ml-2">v{item.version}</span>
                    </div>
                  </div>
                  <span className="text-xs text-muted-foreground">
                    Accepted: {new Date(item.accepted_at).toLocaleDateString()}
                  </span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
