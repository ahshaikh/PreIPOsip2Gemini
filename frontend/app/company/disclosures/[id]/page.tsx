/**
 * Disclosure Thread Detail View
 *
 * DESIGN PRINCIPLES:
 * - Timeline-style audit trail (like GitHub PR review)
 * - Append-only history (immutable, no edits/deletes)
 * - Respectful, collaborative language
 * - Clear actor attribution (company vs platform)
 * - Document attachments visible in timeline
 * - Reply interface only when eligible
 *
 * TIMELINE ENTRIES:
 * - Initial submission by company
 * - Platform clarification requests
 * - Company responses with documents
 * - Platform approvals/actions
 * - Status changes with timestamps
 */

"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  CheckCircle2,
  MessageSquare,
  FileText,
  Clock,
  User,
  Building2,
  Shield,
  Upload,
  Download,
  AlertCircle,
  Info,
  Edit,
  Send,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { formatDistanceToNow } from "date-fns";

// Types
interface TimelineEvent {
  id: number;
  type: 'submission' | 'clarification' | 'response' | 'approval' | 'status_change';
  actor: 'company' | 'platform';
  actor_name: string;
  timestamp: string;
  message?: string;
  documents?: Document[];
  status_change?: {
    from: string;
    to: string;
  };
}

interface Document {
  id: number;
  filename: string;
  size: number;
  uploaded_at: string;
  url: string;
}

interface DisclosureThread {
  id: number;
  requirement_name: string;
  requirement_description?: string;
  current_status: string;
  can_respond: boolean;
  timeline: TimelineEvent[];
  created_at: string;
  updated_at: string;
}

export default function DisclosureThreadPage() {
  const params = useParams();
  const router = useRouter();
  const disclosureId = params.id as string;

  const [thread, setThread] = useState<DisclosureThread | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [replyMode, setReplyMode] = useState(false);
  const [replyText, setReplyText] = useState("");
  const [uploadedFiles, setUploadedFiles] = useState<File[]>([]);
  const [submitting, setSubmitting] = useState(false);

  // Load disclosure thread
  useEffect(() => {
    loadThread();
  }, [disclosureId]);

  async function loadThread() {
    setLoading(true);
    setError(null);

    try {
      const { fetchDisclosureThread } = await import('@/lib/issuerCompanyApi');
      const apiResponse = await fetchDisclosureThread(parseInt(disclosureId));

      // Backend returns: { status: 'success', data: { disclosure, module, timeline, clarifications, permissions } }
      const response = apiResponse.data;

      // Transform backend response to match frontend interface
      const threadData = {
        id: response.disclosure.id,
        requirement_name: response.module?.name || "Disclosure Requirement",
        requirement_description: response.module?.description,
        current_status: response.disclosure.status,
        can_respond: response.permissions?.can_respond || false,
        timeline: (response.timeline || []).map((event: any) => ({
          id: event.id,
          type: event.event_type,
          actor: event.actor_type === 'CompanyUser' ? 'company' : 'platform',
          actor_name: event.actor_name,
          timestamp: event.created_at, // Backend returns created_at, map to timestamp
          message: event.message,
          documents: (event.documents || []).map((doc: any) => ({
            id: doc.id,
            filename: doc.file_name,
            size: doc.file_size,
            uploaded_at: doc.uploaded_at,
            url: doc.url,
          })),
        })),
        created_at: response.disclosure.created_at || new Date().toISOString(),
        updated_at: response.disclosure.updated_at || new Date().toISOString(),
      };

      setThread(threadData);
    } catch (err: any) {
      console.error("[DISCLOSURE THREAD] Failed to load:", err);
      setError(err.response?.data?.message || "Unable to load disclosure thread.");
      toast.error("Failed to load thread");
    } finally {
      setLoading(false);
    }
  }

  // Get status badge with respectful language
  const getStatusBadge = (status: string) => {
    switch (status) {
      case "draft":
        return (
          <Badge variant="outline" className="text-gray-600 border-gray-300">
            <Clock className="w-3 h-3 mr-1" />
            Draft
          </Badge>
        );
      case "submitted":
      case "under_review":
        return (
          <Badge variant="outline" className="text-blue-600 border-blue-300">
            <Clock className="w-3 h-3 mr-1" />
            Pending Review
          </Badge>
        );
      case "clarification_required":
        return (
          <Badge variant="outline" className="text-amber-600 border-amber-300">
            <MessageSquare className="w-3 h-3 mr-1" />
            Action Requested
          </Badge>
        );
      case "approved":
        return (
          <Badge variant="outline" className="text-green-600 border-green-300">
            <CheckCircle2 className="w-3 h-3 mr-1" />
            Approved
          </Badge>
        );
      default:
        return <Badge variant="outline">{status}</Badge>;
    }
  };

  // Format file size
  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(1) + " MB";
  };

  // Handle file upload
  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      setUploadedFiles(Array.from(e.target.files));
    }
  };

  // Submit response (for clarification_required status)
  const handleSubmitResponse = async () => {
    if (!replyText.trim()) {
      toast.error("Please enter a response");
      return;
    }

    setSubmitting(true);
    try {
      const { submitDisclosureResponse } = await import('@/lib/issuerCompanyApi');
      await submitDisclosureResponse(parseInt(disclosureId), {
        message: replyText,
        documents: uploadedFiles,
      });

      toast.success("Response submitted successfully");
      setReplyMode(false);
      setReplyText("");
      setUploadedFiles([]);

      // Reload thread to show new entry
      await loadThread();
    } catch (err: any) {
      console.error("[DISCLOSURE THREAD] Failed to submit:", err);
      toast.error(err.response?.data?.message || "Failed to submit response");
    } finally {
      setSubmitting(false);
    }
  };

  // Submit disclosure for review (for draft status)
  const handleSubmitForReview = async () => {
    if (!replyText.trim()) {
      toast.error("Please provide disclosure details");
      return;
    }

    setSubmitting(true);
    try {
      // First, add the disclosure response/message
      const { submitDisclosureResponse, submitDisclosureForReview } = await import('@/lib/issuerCompanyApi');
      await submitDisclosureResponse(parseInt(disclosureId), {
        message: replyText,
        documents: uploadedFiles,
      });

      // Then submit for admin review
      await submitDisclosureForReview(parseInt(disclosureId));

      toast.success("Disclosure submitted for review");
      setReplyMode(false);
      setReplyText("");
      setUploadedFiles([]);

      // Reload thread to show new status
      await loadThread();
    } catch (err: any) {
      console.error("[DISCLOSURE THREAD] Failed to submit for review:", err);
      const errorMsg = err.response?.data?.message || err.response?.data?.error || "Failed to submit disclosure";
      toast.error(errorMsg);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="container py-20">
        <div className="flex items-center justify-center">
          <Clock className="w-8 h-8 animate-spin text-blue-600" />
        </div>
      </div>
    );
  }

  if (error || !thread) {
    return (
      <div className="container py-20">
        <Alert variant="destructive">
          <AlertCircle className="h-5 w-5" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error || "Thread not found"}</AlertDescription>
        </Alert>
      </div>
    );
  }

  return (
    <div className="container py-8 max-w-4xl">
      {/* Header */}
      <div className="mb-8">
        <Link href="/company/disclosures">
          <Button variant="ghost" size="sm" className="mb-4">
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back to Requirements
          </Button>
        </Link>

        <div className="flex items-start justify-between">
          <div className="flex-1">
            <div className="flex items-center gap-3 mb-2">
              <h1 className="text-3xl font-bold">{thread.requirement_name}</h1>
              {getStatusBadge(thread.current_status)}
            </div>
            {thread.requirement_description && (
              <p className="text-gray-600">{thread.requirement_description}</p>
            )}
          </div>
        </div>
      </div>

      {/* Action Requested Alert */}
      {thread.current_status === "clarification_required" && thread.can_respond && (
        <Alert className="mb-6 border-amber-300 bg-amber-50">
          <MessageSquare className="h-5 w-5 text-amber-600" />
          <AlertTitle className="text-amber-900">Action Requested</AlertTitle>
          <AlertDescription className="text-amber-800">
            The platform has requested additional information for this disclosure.
            Please review the request below and provide a response.
          </AlertDescription>
        </Alert>
      )}

      {/* Timeline */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Disclosure Thread</CardTitle>
          <CardDescription>
            Complete audit trail of all submissions, reviews, and communications
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-6">
            {thread.timeline.map((event, index) => (
              <div key={event.id}>
                {/* Timeline Entry */}
                <div className="flex gap-4">
                  {/* Actor Avatar */}
                  <div className="flex-shrink-0">
                    <Avatar className={event.actor === "company" ? "bg-blue-100" : "bg-purple-100"}>
                      <AvatarFallback>
                        {event.actor === "company" ? (
                          <Building2 className="w-4 h-4 text-blue-600" />
                        ) : (
                          <Shield className="w-4 h-4 text-purple-600" />
                        )}
                      </AvatarFallback>
                    </Avatar>
                  </div>

                  {/* Event Content */}
                  <div className="flex-1">
                    {/* Event Header */}
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-semibold">{event.actor_name}</span>
                      <Badge variant="outline" className="text-xs">
                        {event.actor === "company" ? "Company" : "Platform"}
                      </Badge>
                      {event.timestamp && (
                        <span className="text-sm text-gray-500">
                          {formatDistanceToNow(new Date(event.timestamp), { addSuffix: true })}
                        </span>
                      )}
                    </div>

                    {/* Event Type Badge */}
                    <div className="mb-2">
                      {event.type === "submission" && (
                        <Badge variant="outline" className="text-blue-600 border-blue-300 bg-blue-50">
                          <Upload className="w-3 h-3 mr-1" />
                          Submitted
                        </Badge>
                      )}
                      {event.type === "clarification" && (
                        <Badge variant="outline" className="text-amber-600 border-amber-300 bg-amber-50">
                          <MessageSquare className="w-3 h-3 mr-1" />
                          Requested Clarification
                        </Badge>
                      )}
                      {event.type === "response" && (
                        <Badge variant="outline" className="text-green-600 border-green-300 bg-green-50">
                          <MessageSquare className="w-3 h-3 mr-1" />
                          Responded
                        </Badge>
                      )}
                      {event.type === "approval" && (
                        <Badge variant="outline" className="text-green-600 border-green-300 bg-green-50">
                          <CheckCircle2 className="w-3 h-3 mr-1" />
                          Approved
                        </Badge>
                      )}
                      {event.type === "status_change" && (
                        <Badge variant="outline" className="text-gray-600 border-gray-300">
                          Status Changed
                        </Badge>
                      )}
                    </div>

                    {/* Event Message */}
                    {event.message && (
                      <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3">
                        <p className="text-sm text-gray-900 whitespace-pre-wrap">{event.message}</p>
                      </div>
                    )}

                    {/* Status Change */}
                    {event.status_change && (
                      <div className="bg-gray-50 border rounded-lg p-3 mb-3">
                        <p className="text-sm text-gray-700">
                          Status changed from{" "}
                          <span className="font-medium">{event.status_change.from}</span>
                          {" "}to{" "}
                          <span className="font-medium">{event.status_change.to}</span>
                        </p>
                      </div>
                    )}

                    {/* Attached Documents */}
                    {event.documents && event.documents.length > 0 && (
                      <div className="space-y-2">
                        <p className="text-sm font-medium text-gray-700">
                          Attached Documents:
                        </p>
                        {event.documents.map((doc) => (
                          <a
                            key={doc.id}
                            href={doc.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                          >
                            <FileText className="w-4 h-4 text-gray-600" />
                            <div className="flex-1">
                              <p className="text-sm font-medium">{doc.filename}</p>
                              <p className="text-xs text-gray-500">
                                {formatFileSize(doc.size)}
                              </p>
                            </div>
                            <Download className="w-4 h-4 text-gray-400" />
                          </a>
                        ))}
                      </div>
                    )}
                  </div>
                </div>

                {/* Separator (not after last item) */}
                {index < thread.timeline.length - 1 && (
                  <Separator className="my-6" />
                )}
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Reply Interface */}
      {thread.can_respond && (
        <Card>
          <CardHeader>
            <CardTitle>
              {replyMode
                ? (thread.current_status === 'draft' ? "Your Disclosure" : "Your Response")
                : (thread.current_status === 'draft' ? "Complete Your Disclosure" : "Respond to Request")
              }
            </CardTitle>
            <CardDescription>
              {replyMode
                ? (thread.current_status === 'draft'
                    ? "Provide your disclosure details and attach any supporting documents"
                    : "Provide your response and attach any supporting documents")
                : (thread.current_status === 'draft'
                    ? "Click below to provide your disclosure information for this requirement"
                    : "Click below to respond to the platform's request")
              }
            </CardDescription>
          </CardHeader>
          <CardContent>
            {!replyMode ? (
              <Button onClick={() => setReplyMode(true)} size="lg">
                <Edit className="w-4 h-4 mr-2" />
                {thread.current_status === 'draft' ? 'Start Writing' : 'Write Response'}
              </Button>
            ) : (
              <div className="space-y-4">
                {/* Response Text */}
                <div>
                  <Label htmlFor="response">
                    {thread.current_status === 'draft' ? 'Disclosure Details' : 'Response'}
                  </Label>
                  <Textarea
                    id="response"
                    placeholder={
                      thread.current_status === 'draft'
                        ? "Provide the required disclosure information here..."
                        : "Provide your response here..."
                    }
                    value={replyText}
                    onChange={(e) => setReplyText(e.target.value)}
                    rows={6}
                    className="mt-1"
                  />
                </div>

                {/* File Upload */}
                <div>
                  <Label htmlFor="documents">Supporting Documents (optional)</Label>
                  <input
                    id="documents"
                    type="file"
                    multiple
                    onChange={handleFileUpload}
                    className="mt-1 block w-full text-sm text-gray-500
                      file:mr-4 file:py-2 file:px-4
                      file:rounded-md file:border-0
                      file:text-sm file:font-semibold
                      file:bg-blue-50 file:text-blue-700
                      hover:file:bg-blue-100"
                  />
                  {uploadedFiles.length > 0 && (
                    <div className="mt-2 space-y-1">
                      {uploadedFiles.map((file, i) => (
                        <p key={i} className="text-sm text-gray-600">
                          {file.name} ({formatFileSize(file.size)})
                        </p>
                      ))}
                    </div>
                  )}
                </div>

                {/* Info Alert */}
                <Alert>
                  <Info className="h-4 w-4" />
                  <AlertDescription className="text-sm">
                    {thread.current_status === 'draft'
                      ? "Your disclosure will be submitted for admin review. Once submitted, the disclosure status will change and you'll be notified of the outcome."
                      : "Your response will be added to the thread and reviewed by the platform team. All entries are permanent and cannot be edited or deleted."}
                  </AlertDescription>
                </Alert>

                {/* Action Buttons */}
                <div className="flex gap-2">
                  <Button
                    onClick={thread.current_status === 'draft' ? handleSubmitForReview : handleSubmitResponse}
                    disabled={submitting || !replyText.trim()}
                    size="lg"
                  >
                    <Send className="w-4 h-4 mr-2" />
                    {submitting
                      ? "Submitting..."
                      : (thread.current_status === 'draft' ? "Submit for Review" : "Submit Response")
                    }
                  </Button>
                  <Button
                    variant="outline"
                    onClick={() => {
                      setReplyMode(false);
                      setReplyText("");
                      setUploadedFiles([]);
                    }}
                    disabled={submitting}
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Cannot Respond Notice */}
      {!thread.can_respond && (
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription>
            {thread.current_status === "approved"
              ? "This disclosure has been approved and is now locked. Contact platform support if you need to make changes."
              : "You cannot respond at this time. Platform restrictions may be in effect."}
          </AlertDescription>
        </Alert>
      )}
    </div>
  );
}
