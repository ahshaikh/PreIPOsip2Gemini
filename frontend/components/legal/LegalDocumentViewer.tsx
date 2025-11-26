'use client';

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { toast } from "sonner";
import api from "@/lib/api";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import {
  FileText, Calendar, Clock, AlertTriangle, CheckCircle,
  Download, History, Shield, Info
} from "lucide-react";

interface LegalDocumentViewerProps {
  documentType: string;
  title: string;
  showAcceptance?: boolean;
  requireAuth?: boolean;
}

export default function LegalDocumentViewer({
  documentType,
  title,
  showAcceptance = false,
  requireAuth = false,
}: LegalDocumentViewerProps) {
  const queryClient = useQueryClient();
  const [hasAccepted, setHasAccepted] = useState(false);

  // Fetch the active legal document
  const { data: document, isLoading, error } = useQuery({
    queryKey: ['legalDocument', documentType],
    queryFn: async () => {
      const response = await api.get(`/legal/documents/${documentType}`);
      return response.data;
    },
  });

  // Fetch user's acceptance status if authenticated
  const { data: acceptanceStatus } = useQuery({
    queryKey: ['legalAcceptance', documentType],
    queryFn: async () => {
      const response = await api.get(`/legal/documents/${documentType}/acceptance-status`);
      return response.data;
    },
    enabled: showAcceptance && requireAuth,
  });

  // Accept document mutation
  const acceptMutation = useMutation({
    mutationFn: () => api.post(`/legal/documents/${documentType}/accept`),
    onSuccess: () => {
      toast.success("Document Accepted", {
        description: "Your acceptance has been recorded."
      });
      setHasAccepted(true);
      queryClient.invalidateQueries({ queryKey: ['legalAcceptance', documentType] });
    },
    onError: (e: any) => {
      toast.error("Error", {
        description: e.response?.data?.message || "Failed to record acceptance"
      });
    }
  });

  const handleAccept = () => {
    acceptMutation.mutate();
  };

  const downloadPDF = async () => {
    try {
      const response = await api.get(`/legal/documents/${documentType}/download`, {
        responseType: 'blob'
      });
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `${documentType}-v${document.version}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      toast.success("Download started");
    } catch (error) {
      toast.error("Download failed");
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p className="text-muted-foreground">Loading {title}...</p>
        </div>
      </div>
    );
  }

  if (error || !document) {
    return (
      <div className="min-h-screen flex items-center justify-center p-4">
        <Card className="max-w-2xl w-full">
          <CardContent className="pt-6">
            <div className="text-center">
              <AlertTriangle className="h-12 w-12 text-destructive mx-auto mb-4" />
              <h2 className="text-xl font-semibold mb-2">Document Not Found</h2>
              <p className="text-muted-foreground mb-4">
                The {title} is currently unavailable. Please try again later.
              </p>
              <Button onClick={() => window.history.back()}>Go Back</Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  const isAccepted = acceptanceStatus?.accepted || hasAccepted;

  return (
    <div className="min-h-screen bg-gradient-to-b from-background to-muted/20 py-12">
      <div className="max-w-5xl mx-auto px-4">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h1 className="text-4xl font-bold mb-2">{document.title || title}</h1>
              <p className="text-muted-foreground">
                {document.description || `Read our ${title} carefully`}
              </p>
            </div>
            <Button variant="outline" onClick={downloadPDF}>
              <Download className="mr-2 h-4 w-4" /> Download PDF
            </Button>
          </div>

          {/* Metadata Bar */}
          <div className="flex flex-wrap gap-4 items-center">
            <Badge variant="outline" className="flex items-center gap-1">
              <History className="h-3 w-3" />
              Version {document.version}
            </Badge>
            {document.effective_date && (
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Calendar className="h-4 w-4" />
                <span>Effective: {new Date(document.effective_date).toLocaleDateString()}</span>
              </div>
            )}
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <Clock className="h-4 w-4" />
              <span>Last Updated: {new Date(document.updated_at).toLocaleDateString()}</span>
            </div>
            {document.status === 'active' && (
              <Badge variant="default" className="bg-green-500">
                <CheckCircle className="mr-1 h-3 w-3" /> Active
              </Badge>
            )}
          </div>
        </div>

        {/* Acceptance Status Alert */}
        {showAcceptance && (
          <Alert className={isAccepted ? "border-green-500 bg-green-50 dark:bg-green-950" : "border-yellow-500 bg-yellow-50 dark:bg-yellow-950"}>
            <Shield className={`h-4 w-4 ${isAccepted ? "text-green-600" : "text-yellow-600"}`} />
            <AlertDescription className={isAccepted ? "text-green-800 dark:text-green-200" : "text-yellow-800 dark:text-yellow-200"}>
              {isAccepted ? (
                <div>
                  <strong>Accepted</strong> - You have accepted this document on{' '}
                  {acceptanceStatus?.accepted_at && new Date(acceptanceStatus.accepted_at).toLocaleDateString()}.
                </div>
              ) : (
                <div>
                  <strong>Action Required</strong> - Please review and accept this document to continue using our services.
                </div>
              )}
            </AlertDescription>
          </Alert>
        )}

        <Separator className="my-8" />

        {/* Document Content */}
        <Card className="mb-8">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5" />
              Document Content
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="prose prose-slate dark:prose-invert max-w-none">
              <div className="p-6 bg-muted/30 rounded-lg border">
                <pre className="whitespace-pre-wrap font-sans text-sm leading-relaxed">
                  {document.content}
                </pre>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Version Information */}
        <Card className="mb-8">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Info className="h-5 w-5" />
              Version Information
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-1">Version</div>
                <div className="font-mono font-semibold">v{document.version}</div>
              </div>
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-1">Status</div>
                <div>
                  <Badge variant={document.status === 'active' ? 'default' : 'secondary'}>
                    {document.status}
                  </Badge>
                </div>
              </div>
              {document.effective_date && (
                <div>
                  <div className="text-sm font-medium text-muted-foreground mb-1">Effective Date</div>
                  <div>{new Date(document.effective_date).toLocaleDateString()}</div>
                </div>
              )}
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-1">Last Modified</div>
                <div>{new Date(document.updated_at).toLocaleString()}</div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Acceptance Section */}
        {showAcceptance && !isAccepted && (
          <Card className="border-primary">
            <CardContent className="pt-6">
              <div className="text-center">
                <Shield className="h-12 w-12 text-primary mx-auto mb-4" />
                <h3 className="text-xl font-semibold mb-2">Accept This Document</h3>
                <p className="text-muted-foreground mb-6">
                  By clicking accept, you acknowledge that you have read and agree to the terms
                  outlined in this document.
                </p>
                <Button
                  size="lg"
                  onClick={handleAccept}
                  disabled={acceptMutation.isPending}
                  className="min-w-[200px]"
                >
                  {acceptMutation.isPending ? (
                    <>
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2" />
                      Processing...
                    </>
                  ) : (
                    <>
                      <CheckCircle className="mr-2 h-4 w-4" />
                      I Accept
                    </>
                  )}
                </Button>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Footer Note */}
        <div className="mt-8 text-center text-sm text-muted-foreground">
          <p>
            If you have any questions about this document, please{' '}
            <a href="/contact" className="text-primary hover:underline">
              contact us
            </a>
            .
          </p>
        </div>
      </div>
    </div>
  );
}
